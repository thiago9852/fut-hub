<?php

namespace App\Controller\Public;

use App\Enum\MatchStatus;
use App\Repository\ChampionshipRepository;
use App\Service\Championship\ChampionshipService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CalendarController extends AbstractController
{
    public function __construct(
        private readonly ChampionshipService $championshipService,
    ) {
    }

    #[Route('/calendario', name: 'public_calendar')]
    public function index(ChampionshipRepository $championshipRepository, Request $request): Response
    {
        $championships = $championshipRepository->findBy(['status' => 'ATIVO'], ['name' => 'ASC']);

        $matches = [];
        foreach ($championships as $championship) {
            $season = $this->championshipService->getCurrentSeason($championship);
            if ($season) {
                $matches = [...$matches, ...$season->getMatches()->toArray()];
            }
        }
        usort($matches, fn ($a, $b) => ($a->getScheduledAt() ?? new \DateTimeImmutable('@0')) <=> ($b->getScheduledAt() ?? new \DateTimeImmutable('@0')));

        $matchesByDate = [];
        foreach ($matches as $match) {
            $scheduledAt = $match->getScheduledAt();
            if (!$scheduledAt) {
                continue;
            }
            $matchesByDate[$scheduledAt->format('Y-m-d')][] = $match;
        }

        $availableDates = array_keys($matchesByDate);
        sort($availableDates);

        $today = new \DateTimeImmutable('today');
        $todayKey = $today->format('Y-m-d');
        $requestedDate = $request->query->get('data');

        if ($requestedDate && isset($matchesByDate[$requestedDate])) {
            $selectedDate = $requestedDate;
        } elseif (isset($matchesByDate[$todayKey])) {
            $selectedDate = $todayKey;
        } else {
            $upcoming = array_values(array_filter($availableDates, fn ($d) => $d >= $todayKey));
            $selectedDate = $upcoming[0] ?? ($availableDates[count($availableDates) - 1] ?? null);
        }

        $weekdays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        $months = ['', 'Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];

        $dateOptions = [];
        $selectedDateOption = null;
        foreach ($availableDates as $dateKey) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateKey);
            $option = [
                'key' => $dateKey,
                'weekday' => $weekdays[(int) $dt->format('w')],
                'day' => $dt->format('d'),
                'month' => $months[(int) $dt->format('n')],
                'matchCount' => count($matchesByDate[$dateKey]),
                'isToday' => $dateKey === $todayKey,
            ];
            $dateOptions[] = $option;
            if ($dateKey === $selectedDate) {
                $selectedDateOption = $option;
            }
        }

        $dayMatches = $selectedDate ? ($matchesByDate[$selectedDate] ?? []) : [];

        $statusFilter = $request->query->get('status', 'all');
        if ('live' === $statusFilter) {
            $filteredMatches = array_values(array_filter($dayMatches, fn ($m) => MatchStatus::LIVE === $m->getStatus()));
        } elseif ('finished' === $statusFilter) {
            $filteredMatches = array_values(array_filter($dayMatches, fn ($m) => MatchStatus::FINISHED === $m->getStatus()));
        } else {
            $statusFilter = 'all';
            $filteredMatches = $dayMatches;
        }

        return $this->render('public/calendar.html.twig', [
            'dateOptions' => $dateOptions,
            'selectedDate' => $selectedDate,
            'selectedDateOption' => $selectedDateOption,
            'statusFilter' => $statusFilter,
            'matchesForSelectedDate' => $filteredMatches,
            'totalMatchesForDate' => count($dayMatches),
        ]);
    }
}
