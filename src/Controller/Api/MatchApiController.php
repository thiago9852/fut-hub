<?php

namespace App\Controller\Api;

use App\DTO\MatchInput;
use App\Entity\GameMatch;
use App\Entity\Organization;
use App\Enum\MatchStatus;
use App\Repository\GameMatchRepository;
use App\Repository\RoundRepository;
use App\Repository\SeasonRepository;
use App\Repository\StadiumRepository;
use App\Repository\TeamRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/matches')]
class MatchApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly SeasonRepository $seasonRepository,
        private readonly RoundRepository $roundRepository,
        private readonly TeamRepository $teamRepository,
        private readonly StadiumRepository $stadiumRepository,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('', name: 'api_match_index', methods: ['GET'])]
    public function index(GameMatchRepository $repository): JsonResponse
    {
        $matches = $repository->createQueryBuilder('m')
            ->join('m.homeTeam', 'ht')
            ->where('ht.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->getQuery()
            ->getResult();

        return $this->json(array_map($this->toArray(...), $matches));
    }

    #[Route('/{id}', name: 'api_match_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(GameMatch $match): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($match)) {
            return $response;
        }

        return $this->json($this->toArray($match));
    }

    #[Route('', name: 'api_match_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->decodeAndValidate($request, MatchInput::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $organization = $this->organizationContext->requireCurrentOrganization();

        [$season, $round, $homeTeam, $awayTeam, $stadium, $error] = $this->resolveRelations($dto, $organization);
        if ($error) {
            return $error;
        }

        $match = new GameMatch($season, $round, $homeTeam, $awayTeam);
        $this->applyDto($match, $dto, $stadium);

        $this->entityManager->persist($match);
        $this->entityManager->flush();

        return $this->json($this->toArray($match), 201);
    }

    #[Route('/{id}', name: 'api_match_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, GameMatch $match): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($match)) {
            return $response;
        }

        $dto = $this->decodeAndValidate($request, MatchInput::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $organization = $this->organizationContext->requireCurrentOrganization();
        [, , , , $stadium, $error] = $this->resolveRelations($dto, $organization);
        if ($error) {
            return $error;
        }

        $this->applyDto($match, $dto, $stadium);
        $this->entityManager->flush();

        return $this->json($this->toArray($match));
    }

    /** @return array{0: ?\App\Entity\Season, 1: ?\App\Entity\Round, 2: ?\App\Entity\Team, 3: ?\App\Entity\Team, 4: ?\App\Entity\Stadium, 5: ?JsonResponse} */
    private function resolveRelations(MatchInput $dto, Organization $organization): array
    {
        $season = $this->seasonRepository->find($dto->seasonId);
        if (!$season || $season->getChampionship()->getOrganization()->getId() !== $organization->getId()) {
            return [null, null, null, null, null, $this->json(['error' => 'Temporada não encontrada.'], 422)];
        }

        $round = $this->roundRepository->find($dto->roundId);
        if (!$round || $round->getSeason()->getChampionship()->getOrganization()->getId() !== $organization->getId()) {
            return [null, null, null, null, null, $this->json(['error' => 'Rodada não encontrada.'], 422)];
        }

        $homeTeam = $this->teamRepository->find($dto->homeTeamId);
        if (!$homeTeam || $homeTeam->getOrganization()->getId() !== $organization->getId()) {
            return [null, null, null, null, null, $this->json(['error' => 'Time mandante não encontrado.'], 422)];
        }

        $awayTeam = $this->teamRepository->find($dto->awayTeamId);
        if (!$awayTeam || $awayTeam->getOrganization()->getId() !== $organization->getId()) {
            return [null, null, null, null, null, $this->json(['error' => 'Time visitante não encontrado.'], 422)];
        }

        $stadium = null;
        if ($dto->stadiumId) {
            $stadium = $this->stadiumRepository->find($dto->stadiumId);
            if (!$stadium || $stadium->getOrganization()->getId() !== $organization->getId()) {
                return [null, null, null, null, null, $this->json(['error' => 'Estádio não encontrado.'], 422)];
            }
        }

        return [$season, $round, $homeTeam, $awayTeam, $stadium, null];
    }

    private function applyDto(GameMatch $match, MatchInput $dto, ?\App\Entity\Stadium $stadium): void
    {
        $match->setStadium($stadium);
        $match->setScheduledAt($dto->scheduledAt ? new \DateTimeImmutable($dto->scheduledAt) : null);
        $match->setHomeScore($dto->homeScore);
        $match->setAwayScore($dto->awayScore);
        $match->setStatus(MatchStatus::from($dto->status));
    }

    private function denyAccessUnlessOwnedByOrganization(GameMatch $match): ?JsonResponse
    {
        if ($match->getHomeTeam()->getOrganization()->getId() !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            return $this->json(['error' => 'Jogo não encontrado.'], 404);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function toArray(GameMatch $match): array
    {
        return [
            'id' => $match->getId(),
            'season_id' => $match->getSeason()->getId(),
            'round_id' => $match->getRound()->getId(),
            'stadium_id' => $match->getStadium()?->getId(),
            'home_team_id' => $match->getHomeTeam()->getId(),
            'away_team_id' => $match->getAwayTeam()->getId(),
            'scheduled_at' => $match->getScheduledAt()?->format(DATE_ATOM),
            'home_score' => $match->getHomeScore(),
            'away_score' => $match->getAwayScore(),
            'status' => $match->getStatus()->value,
        ];
    }
}
