<?php

namespace App\Controller\Api;

use App\DTO\PlayerInput;
use App\Entity\Player;
use App\Repository\PlayerRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1/players')]
class PlayerApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('', name: 'api_player_index', methods: ['GET'])]
    public function index(PlayerRepository $repository): JsonResponse
    {
        $players = $repository->findBy(['organization' => $this->organizationContext->requireCurrentOrganization()]);

        return $this->json(array_map($this->toArray(...), $players));
    }

    #[Route('/{id}', name: 'api_player_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Player $player): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($player)) {
            return $response;
        }

        return $this->json($this->toArray($player));
    }

    #[Route('', name: 'api_player_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $dto = $this->decodeAndValidate($request, PlayerInput::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $player = new Player($this->organizationContext->requireCurrentOrganization(), $dto->name, $dto->slug);
        $player->setPosition($dto->position)->setStatus($dto->status);
        if ($dto->birthDate) {
            $player->setBirthDate(new \DateTimeImmutable($dto->birthDate));
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();

        return $this->json($this->toArray($player), 201);
    }

    #[Route('/{id}', name: 'api_player_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    public function update(Request $request, Player $player): JsonResponse
    {
        if ($response = $this->denyAccessUnlessOwnedByOrganization($player)) {
            return $response;
        }

        $dto = $this->decodeAndValidate($request, PlayerInput::class);
        if ($dto instanceof JsonResponse) {
            return $dto;
        }

        $player->setName($dto->name)
            ->setSlug($dto->slug)
            ->setPosition($dto->position)
            ->setStatus($dto->status);
        $player->setBirthDate($dto->birthDate ? new \DateTimeImmutable($dto->birthDate) : null);

        $this->entityManager->flush();

        return $this->json($this->toArray($player));
    }

    private function denyAccessUnlessOwnedByOrganization(Player $player): ?JsonResponse
    {
        if ($player->getOrganization()->getId() !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            return $this->json(['error' => 'Jogador não encontrado.'], 404);
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function toArray(Player $player): array
    {
        return [
            'id' => $player->getId(),
            'name' => $player->getName(),
            'slug' => $player->getSlug(),
            'birth_date' => $player->getBirthDate()?->format('Y-m-d'),
            'position' => $player->getPosition(),
            'status' => $player->getStatus(),
        ];
    }
}
