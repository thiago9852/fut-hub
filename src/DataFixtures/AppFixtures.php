<?php

namespace App\DataFixtures;

use App\Entity\Championship;
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
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $organization = new Organization('Liga Municipal de Januária', 'liga-municipal-januaria');
        $organization->setCity('Januária')->setState('MG');
        $manager->persist($organization);

        $admin = new User('Administrador', 'admin@futebollocal.com', $organization);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $admin->setApiToken('dev_'.bin2hex(random_bytes(16)));
        $manager->persist($admin);

        $championship = new Championship($organization, 'Campeonato Municipal 2026', 'campeonato-municipal-2026');
        $championship->setFormat('Pontos corridos');
        $manager->persist($championship);

        $season = new Season($championship, '2026', 2026);
        $manager->persist($season);

        $stadium = new Stadium($organization, 'Estádio Municipal');
        $stadium->setCity('Januária')->setCapacity(5000);
        $manager->persist($stadium);

        $teamsData = [
            ['União FC', 'UNI', 'uniao-fc'],
            ['Real América', 'REA', 'real-america'],
            ['Atlético', 'ATL', 'atletico'],
            ['Nacional', 'NAC', 'nacional'],
        ];

        $teams = [];
        foreach ($teamsData as [$name, $shortName, $slug]) {
            $team = new Team($organization, $name, $slug);
            $team->setShortName($shortName)->setCity('Januária');
            $manager->persist($team);
            $teams[$slug] = $team;
        }

        $positions = ['Goleiro', 'Zagueiro', 'Meio-campo', 'Atacante'];
        $playersByTeam = [];
        $playerNumber = 1;
        foreach ($teams as $slug => $team) {
            $playersByTeam[$slug] = [];
            for ($i = 1; $i <= 3; ++$i) {
                $player = new Player($organization, sprintf('%s Jogador %d', $team->getName(), $i), sprintf('%s-jogador-%d', $slug, $i));
                $player->setPosition($positions[$i % count($positions)]);
                $manager->persist($player);

                $teamPlayer = new TeamPlayer($team, $player, $season);
                $teamPlayer->setShirtNumber($playerNumber++);
                $manager->persist($teamPlayer);

                $playersByTeam[$slug][] = $player;
            }
        }

        $round = new Round($season, 1);
        $round->setName('Rodada 1')->setScheduledDate(new \DateTimeImmutable('2026-09-20'));
        $manager->persist($round);

        $match = new GameMatch($season, $round, $teams['uniao-fc'], $teams['atletico']);
        $match->setStadium($stadium);
        $match->setScheduledAt(new \DateTimeImmutable('2026-09-20 16:00'));
        $match->setStatus(MatchStatus::FINISHED);
        $match->setHomeScore(2)->setAwayScore(1);
        $manager->persist($match);

        $scorerHome = $playersByTeam['uniao-fc'][0];
        $scorerAway = $playersByTeam['atletico'][0];

        $goal1 = new MatchEvent($match, $teams['uniao-fc'], $scorerHome, MatchEventType::GOAL);
        $goal1->setMinute(23);
        $manager->persist($goal1);

        $goal2 = new MatchEvent($match, $teams['uniao-fc'], $scorerHome, MatchEventType::GOAL);
        $goal2->setMinute(55);
        $manager->persist($goal2);

        $goal3 = new MatchEvent($match, $teams['atletico'], $scorerAway, MatchEventType::GOAL);
        $goal3->setMinute(67);
        $manager->persist($goal3);

        foreach ($playersByTeam['uniao-fc'] as $player) {
            $lineup = new MatchLineup($match, $teams['uniao-fc'], $player);
            $lineup->setStarter(true);
            $manager->persist($lineup);
        }

        foreach ($playersByTeam['atletico'] as $player) {
            $lineup = new MatchLineup($match, $teams['atletico'], $player);
            $lineup->setStarter(true);
            $manager->persist($lineup);
        }

        $secondMatch = new GameMatch($season, $round, $teams['real-america'], $teams['nacional']);
        $secondMatch->setStadium($stadium);
        $secondMatch->setScheduledAt(new \DateTimeImmutable('2026-09-21 16:00'));
        $secondMatch->setStatus(MatchStatus::SCHEDULED);
        $manager->persist($secondMatch);

        $importLog = new ImportLog($organization, $admin, 'EXCEL_SYNC', 'CONCLUIDA');
        $importLog->setRecordsProcessed(4)->setRecordsCreated(4);
        $manager->persist($importLog);

        $manager->flush();
    }
}
