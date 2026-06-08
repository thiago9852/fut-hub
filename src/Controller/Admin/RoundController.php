<?php

namespace App\Controller\Admin;

use App\Entity\Round;
use App\Form\RoundType;
use App\Repository\RoundRepository;
use App\Repository\SeasonRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/rodadas')]
#[IsGranted('ROLE_VIEWER')]
class RoundController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_round_index', methods: ['GET'])]
    public function index(RoundRepository $repository): Response
    {
        $rounds = $repository->createQueryBuilder('r')
            ->join('r.season', 's')
            ->addSelect('s')
            ->join('s.championship', 'c')
            ->addSelect('c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->orderBy('r.number', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/round/index.html.twig', ['rounds' => $rounds]);
    }

    #[Route('/novo', name: 'admin_round_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request, SeasonRepository $seasonRepository): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $season = $seasonRepository->createQueryBuilder('s')
            ->join('s.championship', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$season) {
            $this->addFlash('error', 'Cadastre uma temporada antes de criar uma rodada.');

            return $this->redirectToRoute('admin_season_new');
        }

        $round = new Round($season, 1);

        $form = $this->createForm(RoundType::class, $round, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($round);
            $this->entityManager->flush();

            $this->addFlash('success', 'Rodada criada com sucesso.');

            return $this->redirectToRoute('admin_round_index');
        }

        return $this->render('admin/round/form.html.twig', ['form' => $form, 'round' => $round]);
    }

    #[Route('/{id}/editar', name: 'admin_round_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Round $round): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $this->denyAccessUnlessOrganizationMatches($round, $organization->getId());

        $form = $this->createForm(RoundType::class, $round, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Rodada atualizada com sucesso.');

            return $this->redirectToRoute('admin_round_index');
        }

        return $this->render('admin/round/form.html.twig', ['form' => $form, 'round' => $round]);
    }

    #[Route('/{id}/excluir', name: 'admin_round_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Round $round): Response
    {
        $this->denyAccessUnlessOrganizationMatches($round, $this->organizationContext->requireCurrentOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-round-'.$round->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($round);
            $this->entityManager->flush();
            $this->addFlash('success', 'Rodada removida.');
        }

        return $this->redirectToRoute('admin_round_index');
    }

    private function denyAccessUnlessOrganizationMatches(Round $round, ?int $organizationId): void
    {
        if ($round->getSeason()->getChampionship()->getOrganization()->getId() !== $organizationId) {
            throw $this->createAccessDeniedException();
        }
    }
}
