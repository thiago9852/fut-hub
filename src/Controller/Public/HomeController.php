<?php

namespace App\Controller\Public;

use App\Enum\MatchStatus;
use App\Repository\ChampionshipRepository;
use App\Repository\PlayerRepository;
use App\Repository\TeamRepository;
use App\Service\Championship\ChampionshipService;
use App\Service\Standings\StandingsService;
use App\Service\Statistics\StatisticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'public_home')]
    public function index(
        ChampionshipRepository $championshipRepository,
        ChampionshipService $championshipService,
        StandingsService $standingsService,
        StatisticsService $statisticsService,
        TeamRepository $teamRepository,
        PlayerRepository $playerRepository,
    ): Response {
        $championship = $championshipRepository->findOneBy(['status' => 'ATIVO'], ['id' => 'ASC']);
        $season = $championship ? $championshipService->getCurrentSeason($championship) : null;

        if (!$season) {
            return $this->render('public/home.html.twig', [
                'championship' => $championship,
                'season' => null,
            ]);
        }

        $matches = $season->getMatches()->toArray();

        $recentResults = array_values(array_filter($matches, fn ($m) => MatchStatus::FINISHED === $m->getStatus()));
        usort($recentResults, fn ($a, $b) => ($b->getScheduledAt() ?? new \DateTimeImmutable('@0')) <=> ($a->getScheduledAt() ?? new \DateTimeImmutable('@0')));

        $upcomingMatches = array_values(array_filter($matches, fn ($m) => MatchStatus::SCHEDULED === $m->getStatus()));
        usort($upcomingMatches, fn ($a, $b) => ($a->getScheduledAt() ?? new \DateTimeImmutable('9999-01-01')) <=> ($b->getScheduledAt() ?? new \DateTimeImmutable('9999-01-01')));

        $teamsCount = $teamRepository->count(['organization' => $championship->getOrganization(), 'status' => 'ATIVO']);
        $playersCount = $playerRepository->count(['organization' => $championship->getOrganization(), 'status' => 'ATIVO']);

        $goals = 0;
        foreach ($recentResults as $match) {
            $goals += ($match->getHomeScore() ?? 0) + ($match->getAwayScore() ?? 0);
        }

        return $this->render('public/home.html.twig', [
            'championship' => $championship,
            'season' => $season,
            'standings' => array_slice($standingsService->calculate($season), 0, 5),
            'topScorers' => $statisticsService->topScorers($season, 3),
            'recentResults' => array_slice($recentResults, 0, 4),
            'upcomingMatches' => array_slice($upcomingMatches, 0, 4),
            'teamsCount' => $teamsCount,
            'playersCount' => $playersCount,
            'matchesCount' => count($matches),
            'goalsCount' => $goals,
        ]);
    }
}
