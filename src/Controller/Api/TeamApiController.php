<?php

namespace App\Controller\Api;

use App\DTO\TeamInput;
use App\Entity\Team;
use App\Repository\TeamRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/teams')]
class TeamApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('', name: 'api_team_index', methods: ['GET'])]
    public function index(TeamRepository $repository): JsonResponse
    {
        $teams = $repository->findBy(['organization' => $this->organizationContext->requireCurrentOrganization()]);

        return $this->json(array_map($this->toArray(...), $teams));
    }

    #[Route('/{id}', name: 'api_team_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Team $team): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($team)) {
            return $response;
        }

        return $this->json($this->toArray($team));
    }

    #[Route('', name: 'api_team_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->decodeAndValidate($request, TeamInput::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $team = new Team($this->organizationContext->requireCurrentOrganization(), $dto->name, $dto->slug);
        $team->setShortName($dto->shortName)->setCity($dto->city)->setStatus($dto->status)->setLogo($dto->logo);

        $this->entityManager->persist($team);
        $this->entityManager->flush();

        return $this->json($this->toArray($team), 201);
    }

    #[Route('/{id}', name: 'api_team_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Team $team): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($team)) {
            return $response;
        }

        $dto = $this->decodeAndValidate($request, TeamInput::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $team->setName($dto->name)
            ->setSlug($dto->slug)
            ->setShortName($dto->shortName)
            ->setCity($dto->city)
            ->setStatus($dto->status)
            ->setLogo($dto->logo);

        $this->entityManager->flush();

        return $this->json($this->toArray($team));
    }

    #[Route('/{id}', name: 'api_team_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function delete(Team $team): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($team)) {
            return $response;
        }

        $this->entityManager->remove($team);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    private function denyAccessUnlessOwnedByOrganization(Team $team): ?JsonResponse
    {
        if ($team->getOrganization()->getId() !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            return $this->json(['error' => 'Time não encontrado.'], 404);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function toArray(Team $team): array
    {
        return [
            'id' => $team->getId(),
            'name' => $team->getName(),
            'short_name' => $team->getShortName(),
            'slug' => $team->getSlug(),
            'city' => $team->getCity(),
            'logo' => $team->getLogo(),
            'status' => $team->getStatus(),
        ];
    }
}
