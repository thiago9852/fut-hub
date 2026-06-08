<?php

namespace App\Controller\Admin;

use App\Entity\Season;
use App\Form\SeasonType;
use App\Repository\ChampionshipRepository;
use App\Repository\SeasonRepository;
use App\Service\Security\OrganizationContext;
use App\Service\Standings\StandingsService;
use App\Service\Statistics\StatisticsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/temporadas')]
#[IsGranted('ROLE_VIEWER')]
class SeasonController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_season_index', methods: ['GET'])]
    public function index(SeasonRepository $repository): Response
    {
        $seasons = $repository->createQueryBuilder('s')
            ->join('s.championship', 'c')
            ->addSelect('c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->orderBy('s.year', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/season/index.html.twig', ['seasons' => $seasons]);
    }

    #[Route('/{id}', name: 'admin_season_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Season $season, StandingsService $standingsService, StatisticsService $statisticsService): Response
    {
        $this->denyAccessUnlessOrganizationMatches($season, $this->organizationContext->requireCurrentOrganization()->getId());

        return $this->render('admin/season/show.html.twig', [
            'season' => $season,
            'standings' => $standingsService->calculate($season),
            'topScorers' => $statisticsService->topScorers($season, 5),
            'topAssists' => $statisticsService->topAssists($season, 5),
            'yellowCards' => $statisticsService->yellowCards($season, 5),
            'redCards' => $statisticsService->redCards($season, 5),
        ]);
    }

    #[Route('/novo', name: 'admin_season_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request, ChampionshipRepository $championshipRepository): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $championship = $championshipRepository->findOneBy(['organization' => $organization]);

        if (!$championship) {
            $this->addFlash('error', 'Cadastre um campeonato antes de criar uma temporada.');

            return $this->redirectToRoute('admin_championship_new');
        }

        $season = new Season($championship, '', (int) date('Y'));

        $form = $this->createForm(SeasonType::class, $season, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($season);
            $this->entityManager->flush();

            $this->addFlash('success', 'Temporada criada com sucesso.');

            return $this->redirectToRoute('admin_season_index');
        }

        return $this->render('admin/season/form.html.twig', ['form' => $form, 'season' => $season]);
    }

    #[Route('/{id}/editar', name: 'admin_season_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, Season $season): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $this->denyAccessUnlessOrganizationMatches($season, $organization->getId());

        $form = $this->createForm(SeasonType::class, $season, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Temporada atualizada com sucesso.');

            return $this->redirectToRoute('admin_season_index');
        }

        return $this->render('admin/season/form.html.twig', ['form' => $form, 'season' => $season]);
    }

    #[Route('/{id}/excluir', name: 'admin_season_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Season $season): Response
    {
        $this->denyAccessUnlessOrganizationMatches($season, $this->organizationContext->requireCurrentOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-season-'.$season->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($season);
            $this->entityManager->flush();
            $this->addFlash('success', 'Temporada removida.');
        }

        return $this->redirectToRoute('admin_season_index');
    }

    private function denyAccessUnlessOrganizationMatches(Season $season, ?int $organizationId): void
    {
        if ($season->getChampionship()->getOrganization()->getId() !== $organizationId) {
            throw $this->createAccessDeniedException();
        }
    }
}
