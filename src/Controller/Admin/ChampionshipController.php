<?php

namespace App\Controller\Admin;

use App\Entity\Championship;
use App\Form\ChampionshipType;
use App\Repository\ChampionshipRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/campeonatos')]
#[IsGranted('ROLE_VIEWER')]
class ChampionshipController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_championship_index', methods: ['GET'])]
    public function index(ChampionshipRepository $repository): Response
    {
        $championships = $repository->findBy(
            ['organization' => $this->organizationContext->requireCurrentOrganization()],
            ['id' => 'DESC'],
        );

        return $this->render('admin/championship/index.html.twig', [
            'championships' => $championships,
        ]);
    }

    #[Route('/novo', name: 'admin_championship_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request): Response
    {
        $championship = new Championship($this->organizationContext->requireCurrentOrganization(), '', '');

        $form = $this->createForm(ChampionshipType::class, $championship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($championship);
            $this->entityManager->flush();

            $this->addFlash('success', 'Campeonato criado com sucesso.');

            return $this->redirectToRoute('admin_championship_index');
        }

        return $this->render('admin/championship/form.html.twig', [
            'form' => $form,
            'championship' => $championship,
        ]);
    }

    #[Route('/{id}/editar', name: 'admin_championship_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Championship $championship): Response
    {
        $this->denyAccessUnlessOrganizationMatches($championship->getOrganization()->getId());

        $form = $this->createForm(ChampionshipType::class, $championship);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Campeonato atualizado com sucesso.');

            return $this->redirectToRoute('admin_championship_index');
        }

        return $this->render('admin/championship/form.html.twig', [
            'form' => $form,
            'championship' => $championship,
        ]);
    }

    #[Route('/{id}/excluir', name: 'admin_championship_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Championship $championship): Response
    {
        $this->denyAccessUnlessOrganizationMatches($championship->getOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-championship-'.$championship->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($championship);
            $this->entityManager->flush();
            $this->addFlash('success', 'Campeonato removido.');
        }

        return $this->redirectToRoute('admin_championship_index');
    }

    private function denyAccessUnlessOrganizationMatches(?int $organizationId): void
    {
        if ($organizationId !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
