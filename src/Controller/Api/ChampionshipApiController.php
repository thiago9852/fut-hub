<?php

namespace App\Controller\Api;

use App\Entity\Championship;
use App\Repository\ChampionshipRepository;
use App\Service\Security\OrganizationContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/championships')]
class ChampionshipApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly OrganizationContext $organizationContext,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('', name: 'api_championship_index', methods: ['GET'])]
    public function index(ChampionshipRepository $repository): JsonResponse
    {
        $championships = $repository->findBy(['organization' => $this->organizationContext->requireCurrentOrganization()]);

        return $this->json(array_map($this->toArray(...), $championships));
    }

    #[Route('/{id}', name: 'api_championship_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Championship $championship): JsonResponse
    {
        if ($championship->getOrganization()->getId() !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            return $this->json(['error' => 'Campeonato não encontrado.'], 404);
        }

        return $this->json($this->toArray($championship));
    }

    /** @return array<string, mixed> */
    private function toArray(Championship $championship): array
    {
        return [
            'id' => $championship->getId(),
            'name' => $championship->getName(),
            'slug' => $championship->getSlug(),
            'description' => $championship->getDescription(),
            'format' => $championship->getFormat(),
            'status' => $championship->getStatus(),
            'start_date' => $championship->getStartDate()?->format('Y-m-d'),
            'end_date' => $championship->getEndDate()?->format('Y-m-d'),
        ];
    }
}
