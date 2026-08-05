<?php

namespace App\Controller\Public;

use App\Entity\GameMatch;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MatchController extends AbstractController
{
    #[Route('/jogos/{id}', name: 'public_match_show', requirements: ['id' => '\d+'])]
    public function show(GameMatch $match): Response
    {
        $events = $match->getEvents()->toArray();
        usort($events, fn ($a, $b) => ($a->getMinute() ?? 0) <=> ($b->getMinute() ?? 0));

        return $this->render('public/match/show.html.twig', [
            'match' => $match,
            'events' => $events,
            'homeLineup' => $match->getLineups()->filter(fn ($l) => $l->getTeam() === $match->getHomeTeam()),
            'awayLineup' => $match->getLineups()->filter(fn ($l) => $l->getTeam() === $match->getAwayTeam()),
        ]);
    }
}
