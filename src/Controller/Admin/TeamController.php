<?php

namespace App\Controller\Admin;

use App\Entity\Team;
use App\Form\TeamType;
use App\Repository\TeamRepository;
use App\Service\Media\FileUploader;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/times')]
#[IsGranted('ROLE_VIEWER')]
class TeamController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly FileUploader $fileUploader,
    ) {
    }

    #[Route('', name: 'admin_team_index', methods: ['GET'])]
    public function index(TeamRepository $repository): Response
    {
        $teams = $repository->findBy(
            ['organization' => $this->organizationContext->requireCurrentOrganization()],
            ['name' => 'ASC'],
        );

        return $this->render('admin/team/index.html.twig', ['teams' => $teams]);
    }

    #[Route('/novo', name: 'admin_team_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request): Response
    {
        $team = new Team($this->organizationContext->requireCurrentOrganization(), '', '');

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyLogoUpload($form, $team);

            $this->entityManager->persist($team);
            $this->entityManager->flush();

            $this->addFlash('success', 'Time criado com sucesso.');

            return $this->redirectToRoute('admin_team_index');
        }

        return $this->render('admin/team/form.html.twig', ['form' => $form, 'team' => $team]);
    }

    #[Route('/{id}/editar', name: 'admin_team_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Team $team): Response
    {
        $this->denyAccessUnlessOrganizationMatches($team->getOrganization()->getId());

        $form = $this->createForm(TeamType::class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->applyLogoUpload($form, $team);

            $this->entityManager->flush();

            $this->addFlash('success', 'Time atualizado com sucesso.');

            return $this->redirectToRoute('admin_team_index');
        }

        return $this->render('admin/team/form.html.twig', ['form' => $form, 'team' => $team]);
    }

    #[Route('/{id}/excluir', name: 'admin_team_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Team $team): Response
    {
        $this->denyAccessUnlessOrganizationMatches($team->getOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-team-'.$team->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($team);
            $this->entityManager->flush();
            $this->addFlash('success', 'Time removido.');
        }

        return $this->redirectToRoute('admin_team_index');
    }

    private function applyLogoUpload(FormInterface $form, Team $team): void
    {
        /** @var UploadedFile|null $logoFile */
        $logoFile = $form->get('logoFile')->getData();

        if ($logoFile instanceof UploadedFile) {
            $team->setLogo($this->fileUploader->upload($logoFile, 'teams'));
        }
    }

    private function denyAccessUnlessOrganizationMatches(?int $organizationId): void
    {
        if ($organizationId !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
