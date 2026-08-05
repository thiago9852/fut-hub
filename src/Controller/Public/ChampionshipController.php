<?php

namespace App\Controller\Public;

use App\Entity\Championship;
use App\Repository\ChampionshipRepository;
use App\Repository\TeamRepository;
use App\Service\Championship\ChampionshipService;
use App\Service\Standings\StandingsService;
use App\Service\Statistics\StatisticsService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ChampionshipController extends AbstractController
{
    public function __construct(
        private readonly ChampionshipService $championshipService,
    ) {
    }

    #[Route('/campeonatos', name: 'public_championship_index')]
    public function index(ChampionshipRepository $repository): Response
    {
        $championships = $repository->findBy(['status' => 'ATIVO'], ['name' => 'ASC']);

        return $this->render('public/championship/index.html.twig', ['championships' => $championships]);
    }

    #[Route('/campeonatos/{slug}', name: 'public_championship_show')]
    public function show(#[MapEntity(mapping: ['slug' => 'slug'])] Championship $championship): Response
    {
        return $this->redirectToRoute('public_championship_standings', ['slug' => $championship->getSlug()]);
    }

    #[Route('/campeonatos/{slug}/classificacao', name: 'public_championship_standings')]
    public function standings(#[MapEntity(mapping: ['slug' => 'slug'])] Championship $championship, StandingsService $standingsService): Response
    {
        $season = $this->championshipService->getCurrentSeason($championship);

        return $this->render('public/championship/standings.html.twig', [
            'championship' => $championship,
            'season' => $season,
            'standings' => $season ? $standingsService->calculate($season) : [],
        ]);
    }

    #[Route('/campeonatos/{slug}/jogos', name: 'public_championship_matches')]
    public function matches(#[MapEntity(mapping: ['slug' => 'slug'])] Championship $championship): Response
    {
        $season = $this->championshipService->getCurrentSeason($championship);

        $matches = $season ? $season->getMatches()->toArray() : [];
        usort($matches, fn ($a, $b) => ($b->getScheduledAt() ?? new \DateTimeImmutable('@0')) <=> ($a->getScheduledAt() ?? new \DateTimeImmutable('@0')));

        return $this->render('public/championship/matches.html.twig', [
            'championship' => $championship,
            'season' => $season,
            'matches' => $matches,
        ]);
    }

    #[Route('/campeonatos/{slug}/times', name: 'public_championship_teams')]
    public function teams(
        #[MapEntity(mapping: ['slug' => 'slug'])] Championship $championship,
        TeamRepository $teamRepository,
    ): Response {
        $season = $this->championshipService->getCurrentSeason($championship);

        $teams = [];

        // 1. All active teams of this championship's organization
        $orgTeams = $teamRepository->findBy(
            ['organization' => $championship->getOrganization(), 'status' => 'ATIVO'],
            ['name' => 'ASC']
        );
        foreach ($orgTeams as $team) {
            $teams[$team->getId()] = $team;
        }

        // 2. Teams participating via squad roster or matches
        if ($season) {
            foreach ($season->getTeamPlayers() as $teamPlayer) {
                $teams[$teamPlayer->getTeam()->getId()] = $teamPlayer->getTeam();
            }
            foreach ($season->getMatches() as $match) {
                $teams[$match->getHomeTeam()->getId()] = $match->getHomeTeam();
                $teams[$match->getAwayTeam()->getId()] = $match->getAwayTeam();
            }
        }

        $teams = array_values($teams);
        usort($teams, fn ($a, $b) => $a->getName() <=> $b->getName());

        return $this->render('public/championship/teams.html.twig', [
            'championship' => $championship,
            'season' => $season,
            'teams' => $teams,
        ]);
    }

    #[Route('/campeonatos/{slug}/artilharia', name: 'public_championship_top_scorers')]
    public function topScorers(#[MapEntity(mapping: ['slug' => 'slug'])] Championship $championship, StatisticsService $statisticsService): Response
    {
        $season = $this->championshipService->getCurrentSeason($championship);

        return $this->render('public/championship/top_scorers.html.twig', [
            'championship' => $championship,
            'season' => $season,
            'topScorers' => $season ? $statisticsService->topScorers($season, 20) : [],
        ]);
    }
}
