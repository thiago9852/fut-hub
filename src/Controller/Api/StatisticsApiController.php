<?php

namespace App\Controller\Api;

use App\Entity\Season;
use App\Repository\ChampionshipRepository;
use App\Repository\GameMatchRepository;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamRepository;
use App\Service\Championship\ChampionshipService;
use App\Service\Security\OrganizationContext;
use App\Service\Standings\StandingsRow;
use App\Service\Standings\StandingsService;
use App\Service\Statistics\PlayerStat;
use App\Service\Statistics\StatisticsService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class StatisticsApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly OrganizationContext $organizationContext,
        private readonly ChampionshipRepository $championshipRepository,
        private readonly SeasonRepository $seasonRepository,
        private readonly ChampionshipService $championshipService,
        private readonly StandingsService $standingsService,
        private readonly StatisticsService $statisticsService,
        private readonly TeamRepository $teamRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly GameMatchRepository $matchRepository,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('/standings', name: 'api_standings', methods: ['GET'])]
    public function standings(Request $request): JsonResponse
    {
        $season = $this->resolveSeason($request);
        if ($season instanceof JsonResponse) {
            return $season;
        }

        $rows = $season ? $this->standingsService->calculate($season) : [];

        return $this->json(array_map(fn (StandingsRow $row) => [
            'team_id' => $row->team->getId(),
            'team' => $row->team->getName(),
            'played' => $row->played,
            'wins' => $row->wins,
            'draws' => $row->draws,
            'losses' => $row->losses,
            'goals_for' => $row->goalsFor,
            'goals_against' => $row->goalsAgainst,
            'goal_difference' => $row->getGoalDifference(),
            'points' => $row->points,
            'win_percentage' => $row->getWinPercentage(),
        ], $rows));
    }

    #[Route('/top-scorers', name: 'api_top_scorers', methods: ['GET'])]
    public function topScorers(Request $request): JsonResponse
    {
        $season = $this->resolveSeason($request);
        if ($season instanceof JsonResponse) {
            return $season;
        }

        $limit = min(50, max(1, $request->query->getInt('limit', 10)));
        $stats = $season ? $this->statisticsService->topScorers($season, $limit) : [];

        return $this->json(array_map($this->statToArray(...), $stats));
    }

    #[Route('/statistics', name: 'api_statistics', methods: ['GET'])]
    public function statistics(): JsonResponse
    {
        $organization = $this->organizationContext->requireCurrentOrganization();

        return $this->json([
            'championships' => $this->championshipRepository->count(['organization' => $organization]),
            'teams' => $this->teamRepository->count(['organization' => $organization]),
            'players' => $this->playerRepository->count(['organization' => $organization]),
            'matches' => $this->matchRepository->countByOrganization($organization),
            'goals' => $this->matchRepository->sumGoalsByOrganization($organization),
        ]);
    }

    private function resolveSeason(Request $request): Season|JsonResponse|null
    {
        $organization = $this->organizationContext->requireCurrentOrganization();

        if ($seasonId = $request->query->getInt('season_id')) {
            $season = $this->seasonRepository->find($seasonId);
            if (!$season || $season->getChampionship()->getOrganization()->getId() !== $organization->getId()) {
                return $this->json(['error' => 'Temporada não encontrada.'], 404);
            }

            return $season;
        }

        $championship = $this->championshipRepository->findOneBy(['organization' => $organization, 'status' => 'ATIVO'], ['id' => 'ASC']);

        return $championship ? $this->championshipService->getCurrentSeason($championship) : null;
    }

    /** @return array<string, mixed> */
    private function statToArray(PlayerStat $stat): array
    {
        return [
            'player_id' => $stat->player->getId(),
            'player' => $stat->player->getName(),
            'team_id' => $stat->team->getId(),
            'team' => $stat->team->getName(),
            'count' => $stat->count,
        ];
    }
}
