<?php

namespace App\Controller\Public;

use App\Entity\Player;
use App\Enum\MatchEventType;
use App\Repository\TeamPlayerRepository;
use App\Service\Statistics\StatisticsService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PlayerController extends AbstractController
{
    #[Route('/jogadores/{slug}', name: 'public_player_show')]
    public function show(
        #[MapEntity(mapping: ['slug' => 'slug'])] Player $player,
        TeamPlayerRepository $teamPlayerRepository,
        StatisticsService $statisticsService,
    ): Response {
        $history = $teamPlayerRepository->createQueryBuilder('tp')
            ->join('tp.team', 't')
            ->addSelect('t')
            ->join('tp.season', 's')
            ->addSelect('s')
            ->where('tp.player = :player')
            ->setParameter('player', $player)
            ->orderBy('s.year', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('public/player/show.html.twig', [
            'player' => $player,
            'history' => $history,
            'goals' => $statisticsService->countPlayerEvents($player, MatchEventType::GOAL),
            'assists' => $statisticsService->countPlayerEvents($player, MatchEventType::ASSIST),
            'yellowCards' => $statisticsService->countPlayerEvents($player, MatchEventType::YELLOW_CARD),
            'redCards' => $statisticsService->countPlayerEvents($player, MatchEventType::RED_CARD),
        ]);
    }
}
