<?php

namespace App\Service\Import;

use App\Entity\GameMatch;
use App\Entity\ImportLog;
use App\Entity\MatchEvent;
use App\Entity\MatchLineup;
use App\Entity\Organization;
use App\Entity\Player;
use App\Entity\Round;
use App\Entity\Season;
use App\Entity\Stadium;
use App\Entity\Team;
use App\Entity\TeamPlayer;
use App\Entity\User;
use App\Enum\MatchEventType;
use App\Enum\MatchStatus;
use App\Repository\GameMatchRepository;
use App\Repository\MatchLineupRepository;
use App\Repository\PlayerRepository;
use App\Repository\RoundRepository;
use App\Repository\SeasonRepository;
use App\Repository\StadiumRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use Doctrine\ORM\EntityManagerInterface;

class ImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TeamRepository $teamRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly SeasonRepository $seasonRepository,
        private readonly RoundRepository $roundRepository,
        private readonly StadiumRepository $stadiumRepository,
        private readonly GameMatchRepository $matchRepository,
        private readonly TeamPlayerRepository $teamPlayerRepository,
        private readonly MatchLineupRepository $matchLineupRepository,
    ) {
    }

    /** @param list<array<string, mixed>> $rows */
    public function importTeams(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();
        $seenExternalIds = [];

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report, &$seenExternalIds) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $externalId = trim((string) ($row['external_id'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));

                if ('' === $externalId) {
                    $report->addError("Linha {$line}: external_id é obrigatório.");
                    continue;
                }
                if ('' === $name) {
                    $report->addError("Linha {$line}: name é obrigatório.");
                    continue;
                }
                if (isset($seenExternalIds[$externalId])) {
                    $report->addError("Linha {$line}: external_id '{$externalId}' duplicado no próprio lote (já usado na linha {$seenExternalIds[$externalId]}).");
                    continue;
                }
                $seenExternalIds[$externalId] = $line;

                $team = $this->teamRepository->findOneBy(['organization' => $organization, 'externalId' => $externalId]);

                if ($team) {
                    $team->setName($name)
                        ->setShortName($this->nullableString($row['short_name'] ?? null))
                        ->setCity($this->nullableString($row['city'] ?? null))
                        ->setStatus((string) ($row['status'] ?? $team->getStatus()));
                    if ($logo = $this->nullableString($row['logo'] ?? null)) {
                        $team->setLogo($logo);
                    }
                    $report->recordsUpdated++;
                } else {
                    $team = new Team($organization, $name, $this->uniqueSlug($name, fn ($slug) => null !== $this->teamRepository->findOneBy(['organization' => $organization, 'slug' => $slug])));
                    $team->setExternalId($externalId)
                        ->setShortName($this->nullableString($row['short_name'] ?? null))
                        ->setCity($this->nullableString($row['city'] ?? null))
                        ->setLogo($this->nullableString($row['logo'] ?? null))
                        ->setStatus((string) ($row['status'] ?? 'ATIVO'));
                    $this->entityManager->persist($team);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'TEAMS', $report);

        return $report;
    }

    /** @param list<array<string, mixed>> $rows */
    public function importPlayers(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();
        $seenExternalIds = [];

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report, &$seenExternalIds) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $externalId = trim((string) ($row['external_id'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));

                if ('' === $externalId) {
                    $report->addError("Linha {$line}: external_id é obrigatório.");
                    continue;
                }
                if ('' === $name) {
                    $report->addError("Linha {$line}: name é obrigatório.");
                    continue;
                }
                if (isset($seenExternalIds[$externalId])) {
                    $report->addError("Linha {$line}: external_id '{$externalId}' duplicado no próprio lote (já usado na linha {$seenExternalIds[$externalId]}).");
                    continue;
                }
                $seenExternalIds[$externalId] = $line;

                $birthDate = null;
                if (!empty($row['birth_date'])) {
                    try {
                        $birthDate = new \DateTimeImmutable((string) $row['birth_date']);
                    } catch (\Exception) {
                        $report->addError("Linha {$line}: birth_date '{$row['birth_date']}' inválida.");
                        continue;
                    }
                }

                $player = $this->playerRepository->findOneBy(['organization' => $organization, 'externalId' => $externalId]);

                if ($player) {
                    $player->setName($name)
                        ->setBirthDate($birthDate)
                        ->setPosition($this->nullableString($row['position'] ?? null))
                        ->setStatus((string) ($row['status'] ?? $player->getStatus()));
                    $report->recordsUpdated++;
                } else {
                    $player = new Player($organization, $name, $this->uniqueSlug($name, fn ($slug) => null !== $this->playerRepository->findOneBy(['organization' => $organization, 'slug' => $slug])));
                    $player->setExternalId($externalId)
                        ->setBirthDate($birthDate)
                        ->setPosition($this->nullableString($row['position'] ?? null))
                        ->setStatus((string) ($row['status'] ?? 'ATIVO'));
                    $this->entityManager->persist($player);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'PLAYERS', $report);

        return $report;
    }

    /** @param list<array<string, mixed>> $rows */
    public function importRosters(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $teamName = trim((string) ($row['team_name'] ?? ''));
                $playerName = trim((string) ($row['player_name'] ?? ''));
                $seasonId = (int) ($row['season_id'] ?? 0);
                $seasonYear = (int) ($row['season_year'] ?? 0);
                $shirtNumber = isset($row['shirt_number']) && '' !== $row['shirt_number'] ? (int) $row['shirt_number'] : null;
                $status = (string) ($row['status'] ?? 'ATIVO');

                if ('' === $teamName || '' === $playerName) {
                    $report->addError("Linha {$line}: Time e Jogador são obrigatórios.");
                    continue;
                }

                $team = $this->teamRepository->findOneBy(['organization' => $organization, 'name' => $teamName]);
                if (!$team) {
                    $report->addError("Linha {$line}: Time '{$teamName}' não encontrado.");
                    continue;
                }

                $player = $this->playerRepository->findOneBy(['organization' => $organization, 'name' => $playerName]);
                if (!$player) {
                    $report->addError("Linha {$line}: Jogador '{$playerName}' não encontrado.");
                    continue;
                }

                $season = null;
                if ($seasonId > 0) {
                    $season = $this->seasonRepository->find($seasonId);
                } elseif ($seasonYear > 0) {
                    $season = $this->seasonRepository->findOneBy(['year' => $seasonYear]);
                }
                if (!$season) {
                    $season = $this->seasonRepository->findOneBy([], ['year' => 'DESC']);
                }

                if (!$season) {
                    $report->addError("Linha {$line}: Nenhuma temporada disponível.");
                    continue;
                }

                $teamPlayer = $this->teamPlayerRepository->findOneBy([
                    'team' => $team,
                    'player' => $player,
                    'season' => $season,
                ]);

                if ($teamPlayer) {
                    $teamPlayer->setShirtNumber($shirtNumber);
                    $teamPlayer->setStatus($status);
                    $report->recordsUpdated++;
                } else {
                    $teamPlayer = new TeamPlayer($team, $player, $season);
                    $teamPlayer->setShirtNumber($shirtNumber);
                    $teamPlayer->setStatus($status);
                    $this->entityManager->persist($teamPlayer);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'ROSTERS', $report);

        return $report;
    }

    /** @param list<array<string, mixed>> $rows */
    public function importRounds(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $number = (int) ($row['number'] ?? 0);
                $seasonId = (int) ($row['season_id'] ?? 0);

                if ($number <= 0) {
                    $report->addError("Linha {$line}: Número da rodada é obrigatório.");
                    continue;
                }

                $season = $seasonId > 0 ? $this->seasonRepository->find($seasonId) : $this->seasonRepository->findOneBy([], ['year' => 'DESC']);
                if (!$season) {
                    $report->addError("Linha {$line}: Temporada não encontrada.");
                    continue;
                }

                $round = $this->roundRepository->findOneBy(['season' => $season, 'number' => $number]);
                if ($round) {
                    $report->recordsUpdated++;
                } else {
                    $round = new Round($season, $number);
                    $this->entityManager->persist($round);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'ROUNDS', $report);

        return $report;
    }

    /** @param list<array<string, mixed>> $rows */
    public function importMatches(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();
        $seenExternalIds = [];

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report, &$seenExternalIds) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $externalId = trim((string) ($row['external_id'] ?? ''));
                $seasonId = (int) ($row['season_id'] ?? 0);
                $roundNumber = (int) ($row['round_number'] ?? 0);
                $homeTeamName = trim((string) ($row['home_team_name'] ?? ''));
                $awayTeamName = trim((string) ($row['away_team_name'] ?? ''));

                if ('' === $externalId) {
                    $report->addError("Linha {$line}: external_id é obrigatório.");
                    continue;
                }
                if (isset($seenExternalIds[$externalId])) {
                    $report->addError("Linha {$line}: external_id '{$externalId}' duplicado no próprio lote.");
                    continue;
                }
                if ($roundNumber <= 0 || '' === $homeTeamName || '' === $awayTeamName) {
                    $report->addError("Linha {$line}: round_number, home_team_name e away_team_name são obrigatórios.");
                    continue;
                }

                $season = $seasonId > 0 ? $this->seasonRepository->find($seasonId) : $this->seasonRepository->findOneBy([], ['year' => 'DESC']);
                if (!$season || $season->getChampionship()->getOrganization()->getId() !== $organization->getId()) {
                    $report->addError("Linha {$line}: temporada '{$seasonId}' não encontrada.");
                    continue;
                }

                $homeTeam = $this->teamRepository->findOneBy(['organization' => $organization, 'name' => $homeTeamName]);
                $awayTeam = $this->teamRepository->findOneBy(['organization' => $organization, 'name' => $awayTeamName]);
                if (!$homeTeam) {
                    $report->addError("Linha {$line}: time mandante '{$homeTeamName}' não encontrado.");
                    continue;
                }
                if (!$awayTeam) {
                    $report->addError("Linha {$line}: time visitante '{$awayTeamName}' não encontrado.");
                    continue;
                }
                if ($homeTeam === $awayTeam) {
                    $report->addError("Linha {$line}: mandante e visitante não podem ser o mesmo time.");
                    continue;
                }

                $seenExternalIds[$externalId] = $line;

                $round = $this->roundRepository->findOneBy(['season' => $season, 'number' => $roundNumber]);
                if (!$round) {
                    $round = new Round($season, $roundNumber);
                    $this->entityManager->persist($round);
                }

                $stadium = null;
                $stadiumName = $this->nullableString($row['stadium_name'] ?? null);
                if ($stadiumName) {
                    $stadium = $this->stadiumRepository->findOneBy(['organization' => $organization, 'name' => $stadiumName]);
                    if (!$stadium) {
                        $stadium = new Stadium($organization, $stadiumName);
                        $this->entityManager->persist($stadium);
                    }
                }

                $scheduledAt = null;
                if (!empty($row['scheduled_at'])) {
                    try {
                        $scheduledAt = new \DateTimeImmutable((string) $row['scheduled_at']);
                    } catch (\Exception) {
                        $report->addError("Linha {$line}: scheduled_at '{$row['scheduled_at']}' inválida.");
                        continue;
                    }
                }

                $status = MatchStatus::tryFrom((string) ($row['status'] ?? 'SCHEDULED')) ?? MatchStatus::SCHEDULED;

                $match = $this->matchRepository->createQueryBuilder('m')
                    ->join('m.homeTeam', 'ht')
                    ->where('ht.organization = :organization')
                    ->andWhere('m.externalId = :externalId')
                    ->setParameter('organization', $organization)
                    ->setParameter('externalId', $externalId)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($match) {
                    $match->setStadium($stadium);
                    $match->setScheduledAt($scheduledAt);
                    $match->setHomeScore(isset($row['home_score']) && '' !== $row['home_score'] ? (int) $row['home_score'] : null);
                    $match->setAwayScore(isset($row['away_score']) && '' !== $row['away_score'] ? (int) $row['away_score'] : null);
                    $match->setStatus($status);
                    $report->recordsUpdated++;
                } else {
                    $match = new GameMatch($season, $round, $homeTeam, $awayTeam);
                    $match->setExternalId($externalId);
                    $match->setStadium($stadium);
                    $match->setScheduledAt($scheduledAt);
                    if (isset($row['home_score']) && '' !== $row['home_score']) {
                        $match->setHomeScore((int) $row['home_score']);
                    }
                    if (isset($row['away_score']) && '' !== $row['away_score']) {
                        $match->setAwayScore((int) $row['away_score']);
                    }
                    $match->setStatus($status);
                    $this->entityManager->persist($match);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'MATCHES', $report);

        return $report;
    }

    /** @param list<array<string, mixed>> $rows */
    public function importEvents(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();
        $seenExternalIds = [];

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report, &$seenExternalIds) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $externalId = trim((string) ($row['external_id'] ?? ''));
                $matchExternalId = trim((string) ($row['match_external_id'] ?? ''));
                $teamName = trim((string) ($row['team_name'] ?? ''));
                $playerName = trim((string) ($row['player_name'] ?? ''));
                $typeValue = trim((string) ($row['type'] ?? ''));

                if ('' === $externalId) {
                    $report->addError("Linha {$line}: external_id é obrigatório.");
                    continue;
                }
                if (isset($seenExternalIds[$externalId])) {
                    $report->addError("Linha {$line}: external_id '{$externalId}' duplicado no próprio lote.");
                    continue;
                }

                $type = MatchEventType::tryFrom($typeValue);
                if (!$type) {
                    $report->addError("Linha {$line}: tipo de evento '{$typeValue}' inválido.");
                    continue;
                }

                $match = $this->matchRepository->createQueryBuilder('m')
                    ->join('m.homeTeam', 'ht')
                    ->where('ht.organization = :organization')
                    ->andWhere('m.externalId = :externalId')
                    ->setParameter('organization', $organization)
                    ->setParameter('externalId', $matchExternalId)
                    ->getQuery()
                    ->getOneOrNullResult();
                if (!$match) {
                    $report->addError("Linha {$line}: jogo com external_id '{$matchExternalId}' não encontrado.");
                    continue;
                }

                $team = $this->teamRepository->findOneBy(['organization' => $organization, 'name' => $teamName]);
                if (!$team) {
                    $report->addError("Linha {$line}: time '{$teamName}' não encontrado.");
                    continue;
                }

                $player = $this->playerRepository->findOneBy(['organization' => $organization, 'name' => $playerName]);
                if (!$player) {
                    $report->addError("Linha {$line}: jogador '{$playerName}' não encontrado.");
                    continue;
                }

                $seenExternalIds[$externalId] = $line;

                $event = $this->entityManager->getRepository(MatchEvent::class)->findOneBy(['externalId' => $externalId]);
                $minute = isset($row['minute']) && '' !== $row['minute'] ? (int) $row['minute'] : null;

                if ($event) {
                    $event->setType($type);
                    $event->setMinute($minute);
                    $report->recordsUpdated++;
                } else {
                    $event = new MatchEvent($match, $team, $player, $type);
                    $event->setExternalId($externalId);
                    $event->setMinute($minute);
                    $this->entityManager->persist($event);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'EVENTS', $report);

        return $report;
    }

    /** @param list<array<string, mixed>> $rows */
    public function importLineups(Organization $organization, User $user, array $rows): ImportReport
    {
        $report = new ImportReport();

        $this->entityManager->wrapInTransaction(function () use ($organization, $rows, $report) {
            foreach ($rows as $i => $row) {
                $report->recordsProcessed++;
                $line = $i + 1;

                $matchExternalId = trim((string) ($row['match_external_id'] ?? ''));
                $teamName = trim((string) ($row['team_name'] ?? ''));
                $playerName = trim((string) ($row['player_name'] ?? ''));
                $starterStr = strtoupper(trim((string) ($row['starter'] ?? 'SIM')));
                $starter = ('SIM' === $starterStr || '1' === $starterStr || 'TRUE' === $starterStr);
                $position = $this->nullableString($row['position'] ?? null);
                $shirtNumber = isset($row['shirt_number']) && '' !== $row['shirt_number'] ? (int) $row['shirt_number'] : null;

                if ('' === $matchExternalId || '' === $teamName || '' === $playerName) {
                    $report->addError("Linha {$line}: Jogo, Time e Jogador são obrigatórios.");
                    continue;
                }

                $match = $this->matchRepository->createQueryBuilder('m')
                    ->join('m.homeTeam', 'ht')
                    ->where('ht.organization = :organization')
                    ->andWhere('m.externalId = :externalId')
                    ->setParameter('organization', $organization)
                    ->setParameter('externalId', $matchExternalId)
                    ->getQuery()
                    ->getOneOrNullResult();

                if (!$match) {
                    $report->addError("Linha {$line}: Jogo '{$matchExternalId}' não encontrado.");
                    continue;
                }

                $team = $this->teamRepository->findOneBy(['organization' => $organization, 'name' => $teamName]);
                if (!$team) {
                    $report->addError("Linha {$line}: Time '{$teamName}' não encontrado.");
                    continue;
                }

                $player = $this->playerRepository->findOneBy(['organization' => $organization, 'name' => $playerName]);
                if (!$player) {
                    $report->addError("Linha {$line}: Jogador '{$playerName}' não encontrado.");
                    continue;
                }

                $lineup = $this->matchLineupRepository->findOneBy([
                    'match' => $match,
                    'team' => $team,
                    'player' => $player,
                ]);

                if ($lineup) {
                    $lineup->setStarter($starter)
                        ->setPosition($position)
                        ->setShirtNumber($shirtNumber);
                    $report->recordsUpdated++;
                } else {
                    $lineup = new MatchLineup($match, $team, $player);
                    $lineup->setStarter($starter)
                        ->setPosition($position)
                        ->setShirtNumber($shirtNumber);
                    $this->entityManager->persist($lineup);
                    $report->recordsCreated++;
                }
            }
        });

        $this->finalizeReport($report);
        $this->log($organization, $user, 'LINEUPS', $report);

        return $report;
    }

    /** Export all database records for this organization */
    public function exportAll(Organization $organization, ?int $seasonId = null): array
    {
        $teams = $this->teamRepository->findBy(['organization' => $organization], ['name' => 'ASC']);
        $players = $this->playerRepository->findBy(['organization' => $organization], ['name' => 'ASC']);

        $season = $seasonId ? $this->seasonRepository->find($seasonId) : $this->seasonRepository->findOneBy([], ['year' => 'DESC']);

        $teamsData = [];
        $idMap = [];
        foreach ($teams as $idx => $t) {
            $id = $idx + 1;
            $idMap['team'][$t->getId()] = $id;
            $teamsData[] = [
                'id' => $id,
                'name' => $t->getName(),
                'short_name' => $t->getShortName() ?? '',
                'city' => $t->getCity() ?? 'Januária',
                'status' => $t->getStatus(),
                'logo' => $t->getLogo() ?? '',
            ];
        }

        $playersData = [];
        foreach ($players as $idx => $p) {
            $id = $idx + 1;
            $idMap['player'][$p->getId()] = $id;
            $playersData[] = [
                'id' => $id,
                'name' => $p->getName(),
                'birth_date' => $p->getBirthDate() ? $p->getBirthDate()->format('Y-m-d') : '',
                'position' => $p->getPosition() ?? '',
                'number' => '',
                'status' => $p->getStatus(),
            ];
        }

        $rostersData = [];
        $rosters = $this->teamPlayerRepository->createQueryBuilder('tp')
            ->join('tp.team', 't')
            ->where('t.organization = :org')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getResult();

        foreach ($rosters as $idx => $tp) {
            $rostersData[] = [
                'id' => $idx + 1,
                'team' => $tp->getTeam()->getName(),
                'player' => $tp->getPlayer()->getName(),
                'season' => (string) $tp->getSeason()->getYear(),
                'number' => $tp->getShirtNumber() ?? '',
                'status' => $tp->getStatus(),
            ];
        }

        $roundsData = [];
        $rounds = $season ? $this->roundRepository->findBy(['season' => $season], ['number' => 'ASC']) : [];
        foreach ($rounds as $idx => $r) {
            $roundsData[] = [
                'id' => $idx + 1,
                'number' => $r->getNumber(),
                'name' => 'Rodada ' . $r->getNumber(),
                'date' => '',
                'status' => 'ATIVO',
            ];
        }

        $matchesData = [];
        $matchMap = [];
        $matches = $season ? $this->matchRepository->findBy(['season' => $season], ['scheduledAt' => 'ASC']) : [];
        foreach ($matches as $idx => $m) {
            $id = $idx + 1;
            $matchMap[$m->getId()] = $id;
            $matchesData[] = [
                'id' => $id,
                'round' => $m->getRound() ? $m->getRound()->getNumber() : 1,
                'date' => $m->getScheduledAt() ? $m->getScheduledAt()->format('Y-m-d') : '',
                'time' => $m->getScheduledAt() ? $m->getScheduledAt()->format('H:i') : '',
                'home_team' => $m->getHomeTeam()->getName(),
                'away_team' => $m->getAwayTeam()->getName(),
                'stadium' => $m->getStadium() ? $m->getStadium()->getName() : 'Estádio Municipal',
                'home_score' => $m->getHomeScore() !== null ? $m->getHomeScore() : '',
                'away_score' => $m->getAwayScore() !== null ? $m->getAwayScore() : '',
                'status' => $m->getStatus()->value,
            ];
        }

        $eventsData = [];
        $events = $this->entityManager->getRepository(MatchEvent::class)->createQueryBuilder('e')
            ->join('e.match', 'm')
            ->join('m.homeTeam', 'ht')
            ->where('ht.organization = :org')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getResult();

        foreach ($events as $idx => $ev) {
            $eventsData[] = [
                'id' => $idx + 1,
                'match_id' => $matchMap[$ev->getMatch()->getId()] ?? 1,
                'minute' => $ev->getMinute() ?? '',
                'team' => $ev->getTeam()->getName(),
                'player' => $ev->getPlayer()->getName(),
                'type' => $ev->getType()->value,
            ];
        }

        $lineupsData = [];
        $lineups = $this->matchLineupRepository->createQueryBuilder('l')
            ->join('l.team', 't')
            ->where('t.organization = :org')
            ->setParameter('org', $organization)
            ->getQuery()
            ->getResult();

        foreach ($lineups as $idx => $ln) {
            $lineupsData[] = [
                'id' => $idx + 1,
                'match_id' => $matchMap[$ln->getMatch()->getId()] ?? 1,
                'team' => $ln->getTeam()->getName(),
                'player' => $ln->getPlayer()->getName(),
                'starter' => $ln->isStarter() ? 'SIM' : 'NAO',
                'position' => $ln->getPosition() ?? '',
                'shirt_number' => $ln->getShirtNumber() ?? '',
            ];
        }

        return [
            'teams' => $teamsData,
            'players' => $playersData,
            'rosters' => $rostersData,
            'rounds' => $roundsData,
            'matches' => $matchesData,
            'events' => $eventsData,
            'lineups' => $lineupsData,
        ];
    }

    private function finalizeReport(ImportReport $report): void
    {
        $report->status = $report->recordsFailed > 0
            ? ($report->recordsCreated + $report->recordsUpdated > 0 ? 'CONCLUIDA_COM_ERROS' : 'FALHOU')
            : 'CONCLUIDA';
    }

    private function log(Organization $organization, User $user, string $type, ImportReport $report): void
    {
        $log = new ImportLog($organization, $user, $type, $report->status);
        $log->setRecordsProcessed($report->recordsProcessed)
            ->setRecordsCreated($report->recordsCreated)
            ->setRecordsUpdated($report->recordsUpdated)
            ->setRecordsFailed($report->recordsFailed)
            ->setErrors($report->errors ?: null);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    private function nullableString(mixed $value): ?string
    {
        if (null === $value) {
            return null;
        }
        $value = trim((string) $value);

        return '' === $value ? null : $value;
    }

    /** @param callable(string): bool $exists */
    private function uniqueSlug(string $name, callable $exists): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', $this->transliterate($name)), '-'));
        $base = $base ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($exists($slug)) {
            $slug = $base.'-'.$suffix;
            ++$suffix;
        }

        return $slug;
    }

    private function transliterate(string $text): string
    {
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);

        return false !== $transliterated ? $transliterated : $text;
    }
}
