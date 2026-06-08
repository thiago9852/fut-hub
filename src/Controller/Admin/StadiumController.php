<?php

namespace App\Controller\Admin;

use App\Entity\Stadium;
use App\Form\StadiumType;
use App\Repository\StadiumRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/estadios')]
#[IsGranted('ROLE_VIEWER')]
class StadiumController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_stadium_index', methods: ['GET'])]
    public function index(StadiumRepository $repository): Response
    {
        $stadiums = $repository->findBy(
            ['organization' => $this->organizationContext->requireCurrentOrganization()],
            ['name' => 'ASC'],
        );

        return $this->render('admin/stadium/index.html.twig', ['stadiums' => $stadiums]);
    }

    #[Route('/novo', name: 'admin_stadium_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request): Response
    {
        $stadium = new Stadium($this->organizationContext->requireCurrentOrganization(), '');

        $form = $this->createForm(StadiumType::class, $stadium);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($stadium);
            $this->entityManager->flush();

            $this->addFlash('success', 'Estádio criado com sucesso.');

            return $this->redirectToRoute('admin_stadium_index');
        }

        return $this->render('admin/stadium/form.html.twig', ['form' => $form, 'stadium' => $stadium]);
    }

    #[Route('/{id}/editar', name: 'admin_stadium_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Stadium $stadium): Response
    {
        $this->denyAccessUnlessOrganizationMatches($stadium->getOrganization()->getId());

        $form = $this->createForm(StadiumType::class, $stadium);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Estádio atualizado com sucesso.');

            return $this->redirectToRoute('admin_stadium_index');
        }

        return $this->render('admin/stadium/form.html.twig', ['form' => $form, 'stadium' => $stadium]);
    }

    #[Route('/{id}/excluir', name: 'admin_stadium_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Stadium $stadium): Response
    {
        $this->denyAccessUnlessOrganizationMatches($stadium->getOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-stadium-'.$stadium->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($stadium);
            $this->entityManager->flush();
            $this->addFlash('success', 'Estádio removido.');
        }

        return $this->redirectToRoute('admin_stadium_index');
    }

    private function denyAccessUnlessOrganizationMatches(?int $organizationId): void
    {
        if ($organizationId !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
