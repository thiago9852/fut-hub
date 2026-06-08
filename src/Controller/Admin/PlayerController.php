<?php

namespace App\Controller\Admin;

use App\Entity\Player;
use App\Form\PlayerType;
use App\Repository\PlayerRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/jogadores')]
#[IsGranted('ROLE_VIEWER')]
class PlayerController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_player_index', methods: ['GET'])]
    public function index(PlayerRepository $repository): Response
    {
        $players = $repository->findBy(
            ['organization' => $this->organizationContext->requireCurrentOrganization()],
            ['name' => 'ASC'],
        );

        return $this->render('admin/player/index.html.twig', ['players' => $players]);
    }

    #[Route('/novo', name: 'admin_player_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request): Response
    {
        $player = new Player($this->organizationContext->requireCurrentOrganization(), '', '');

        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($player);
            $this->entityManager->flush();

            $this->addFlash('success', 'Jogador criado com sucesso.');

            return $this->redirectToRoute('admin_player_index');
        }

        return $this->render('admin/player/form.html.twig', ['form' => $form, 'player' => $player]);
    }

    #[Route('/{id}/editar', name: 'admin_player_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Player $player): Response
    {
        $this->denyAccessUnlessOrganizationMatches($player->getOrganization()->getId());

        $form = $this->createForm(PlayerType::class, $player);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Jogador atualizado com sucesso.');

            return $this->redirectToRoute('admin_player_index');
        }

        return $this->render('admin/player/form.html.twig', ['form' => $form, 'player' => $player]);
    }

    #[Route('/{id}/excluir', name: 'admin_player_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Player $player): Response
    {
        $this->denyAccessUnlessOrganizationMatches($player->getOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-player-'.$player->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($player);
            $this->entityManager->flush();
            $this->addFlash('success', 'Jogador removido.');
        }

        return $this->redirectToRoute('admin_player_index');
    }

    private function denyAccessUnlessOrganizationMatches(?int $organizationId): void
    {
        if ($organizationId !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
