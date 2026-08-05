<?php

namespace App\Controller\Public;

use App\Entity\Team;
use App\Repository\GameMatchRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TeamController extends AbstractController
{
    #[Route('/times', name: 'public_team_index')]
    public function index(
        TeamRepository $teamRepository,
        TeamPlayerRepository $teamPlayerRepository,
        GameMatchRepository $gameMatchRepository,
        SeasonRepository $seasonRepository,
    ): Response {
        $teams = $teamRepository->findBy(['status' => 'ATIVO'], ['name' => 'ASC']);

        $teamsData = [];
        foreach ($teams as $team) {
            // Find active seasons/championships for this team
            $rosterCount = $teamPlayerRepository->count(['team' => $team, 'status' => 'ATIVO']);

            // Find championships through team players
            $tps = $teamPlayerRepository->createQueryBuilder('tp')
                ->join('tp.season', 's')
                ->join('s.championship', 'c')
                ->addSelect('s', 'c')
                ->where('tp.team = :team')
                ->andWhere('tp.status = :status')
                ->setParameter('team', $team)
                ->setParameter('status', 'ATIVO')
                ->getQuery()
                ->getResult();

            $championships = [];
            foreach ($tps as $tp) {
                $c = $tp->getSeason()->getChampionship();
                $championships[$c->getId()] = $c;
            }

            // Also check matches
            $matches = $gameMatchRepository->createQueryBuilder('m')
                ->join('m.season', 's')
                ->join('s.championship', 'c')
                ->addSelect('s', 'c')
                ->where('m.homeTeam = :team OR m.awayTeam = :team')
                ->setParameter('team', $team)
                ->getQuery()
                ->getResult();

            foreach ($matches as $match) {
                $c = $match->getSeason()->getChampionship();
                $championships[$c->getId()] = $c;
            }

            $teamsData[] = [
                'team' => $team,
                'playersCount' => $rosterCount,
                'championships' => array_values($championships),
            ];
        }

        return $this->render('public/team/index.html.twig', [
            'teamsData' => $teamsData,
        ]);
    }

    #[Route('/times/{slug}', name: 'public_team_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Team $team,
        TeamPlayerRepository $teamPlayerRepository,
        GameMatchRepository $gameMatchRepository,
    ): Response {
        $roster = $teamPlayerRepository->createQueryBuilder('tp')
            ->join('tp.player', 'p')
            ->addSelect('p')
            ->join('tp.season', 's')
            ->where('tp.team = :team')
            ->andWhere('tp.status = :status')
            ->setParameter('team', $team)
            ->setParameter('status', 'ATIVO')
            ->orderBy('s.year', 'DESC')
            ->addOrderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();

        $matches = $gameMatchRepository->createQueryBuilder('m')
            ->join('m.homeTeam', 'ht')
            ->addSelect('ht')
            ->join('m.awayTeam', 'at')
            ->addSelect('at')
            ->where('m.homeTeam = :team OR m.awayTeam = :team')
            ->setParameter('team', $team)
            ->orderBy('m.scheduledAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Calculate statistics
        $wins = 0;
        $draws = 0;
        $losses = 0;
        $goalsFor = 0;
        $goalsAgainst = 0;

        foreach ($matches as $match) {
            if (null !== $match->getHomeScore() && null !== $match->getAwayScore()) {
                $isHome = $match->getHomeTeam()->getId() === $team->getId();
                $myScore = $isHome ? $match->getHomeScore() : $match->getAwayScore();
                $oppScore = $isHome ? $match->getAwayScore() : $match->getHomeScore();

                $goalsFor += $myScore;
                $goalsAgainst += $oppScore;

                if ($myScore > $oppScore) {
                    ++$wins;
                } elseif ($myScore < $oppScore) {
                    ++$losses;
                } else {
                    ++$draws;
                }
            }
        }

        $played = $wins + $draws + $losses;
        $points = ($wins * 3) + ($draws * 1);
        $winRate = $played > 0 ? round(($points / ($played * 3)) * 100) : 0;

        $posGk = 0;
        $posDef = 0;
        $posMid = 0;
        $posFwd = 0;

        foreach ($roster as $tp) {
            $pos = mb_strtolower($tp->getPlayer()->getPosition() ?? '');
            if (str_contains($pos, 'goleiro')) {
                ++$posGk;
            } elseif (str_contains($pos, 'zagueiro') || str_contains($pos, 'lateral') || str_contains($pos, 'defensor')) {
                ++$posDef;
            } elseif (str_contains($pos, 'meio') || str_contains($pos, 'volante') || str_contains($pos, 'meia')) {
                ++$posMid;
            } else {
                ++$posFwd;
            }
        }

        $stats = [
            'wins' => $wins,
            'draws' => $draws,
            'losses' => $losses,
            'played' => $played,
            'points' => $points,
            'winRate' => $winRate,
            'goalsFor' => $goalsFor,
            'goalsAgainst' => $goalsAgainst,
            'goalDiff' => $goalsFor - $goalsAgainst,
            'posGk' => $posGk,
            'posDef' => $posDef,
            'posMid' => $posMid,
            'posFwd' => $posFwd,
        ];

        return $this->render('public/team/show.html.twig', [
            'team' => $team,
            'roster' => $roster,
            'matches' => $matches,
            'stats' => $stats,
        ]);
    }
}
