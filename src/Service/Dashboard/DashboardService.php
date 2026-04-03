<?php

namespace App\Service\Dashboard;

use App\Entity\Organization;
use App\Enum\MatchStatus;
use App\Repository\ChampionshipRepository;
use App\Repository\GameMatchRepository;
use App\Repository\ImportLogRepository;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamRepository;
use App\Service\Championship\ChampionshipService;
use App\Service\Standings\StandingsService;
use App\Service\Statistics\StatisticsService;

class DashboardService
{
    public function __construct(
        private readonly ChampionshipRepository $championshipRepository,
        private readonly SeasonRepository $seasonRepository,
        private readonly TeamRepository $teamRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly GameMatchRepository $matchRepository,
        private readonly ImportLogRepository $importLogRepository,
        private readonly ChampionshipService $championshipService,
        private readonly StandingsService $standingsService,
        private readonly StatisticsService $statisticsService,
    ) {
    }

    public function buildOverview(Organization $organization): DashboardOverview
    {
        $overview = new DashboardOverview();

        $overview->championshipsCount = $this->championshipRepository->count(['organization' => $organization]);
        $overview->seasonsCount = $this->seasonRepository->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->join('s.championship', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getSingleScalarResult();
        $overview->teamsCount = $this->teamRepository->count(['organization' => $organization]);
        $overview->playersCount = $this->playerRepository->count(['organization' => $organization]);
        $overview->matchesCount = $this->matchRepository->countByOrganization($organization);
        $overview->goalsCount = $this->matchRepository->sumGoalsByOrganization($organization);

        $overview->recentResults = $this->matchRepository->findRecentResults($organization, 5);
        $overview->upcomingMatches = $this->matchRepository->findUpcoming($organization, 5);
        $overview->recentImports = $this->importLogRepository->findBy(['organization' => $organization], ['createdAt' => 'DESC'], 5);

        $championship = $this->championshipRepository->findOneBy(['organization' => $organization, 'status' => 'ATIVO'], ['id' => 'ASC']);
        $overview->championship = $championship;

        if (!$championship) {
            return $overview;
        }

        $season = $this->championshipService->getCurrentSeason($championship);
        $overview->season = $season;

        if (!$season) {
            return $overview;
        }

        $overview->standings = $this->standingsService->calculate($season);
        $overview->topScorers = $this->statisticsService->topScorers($season, 5);

        $overview->pointsByTeam = array_map(
            fn ($row) => ['team' => $row->team->getName(), 'points' => $row->points],
            $overview->standings,
        );

        $rounds = $season->getRounds()->toArray();
        usort($rounds, fn ($a, $b) => $a->getNumber() <=> $b->getNumber());

        foreach ($rounds as $round) {
            $goals = 0;
            foreach ($round->getMatches() as $match) {
                if (MatchStatus::FINISHED === $match->getStatus()) {
                    $goals += ($match->getHomeScore() ?? 0) + ($match->getAwayScore() ?? 0);
                }
            }

            $overview->goalsByRound[] = [
                'label' => $round->getName() ?? ('Rodada '.$round->getNumber()),
                'goals' => $goals,
            ];
        }

        return $overview;
    }
}
