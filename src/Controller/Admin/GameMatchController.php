<?php

namespace App\Controller\Admin;

use App\Entity\GameMatch;
use App\Form\GameMatchType;
use App\Repository\GameMatchRepository;
use App\Repository\RoundRepository;
use App\Repository\TeamRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/jogos')]
#[IsGranted('ROLE_VIEWER')]
class GameMatchController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_match_index', methods: ['GET'])]
    public function index(GameMatchRepository $repository): Response
    {
        $matches = $repository->createQueryBuilder('m')
            ->join('m.homeTeam', 'ht')
            ->addSelect('ht')
            ->join('m.awayTeam', 'at')
            ->addSelect('at')
            ->join('m.round', 'r')
            ->addSelect('r')
            ->where('ht.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->orderBy('m.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/game_match/index.html.twig', ['matches' => $matches]);
    }

    #[Route('/novo', name: 'admin_match_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request, RoundRepository $roundRepository, TeamRepository $teamRepository): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();

        $round = $roundRepository->createQueryBuilder('r')
            ->join('r.season', 's')
            ->join('s.championship', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();

        $teams = $teamRepository->findBy(['organization' => $organization], null, 2);

        if (!$round || count($teams) < 2) {
            $this->addFlash('error', 'Cadastre uma rodada e ao menos dois times antes de criar um jogo.');

            return $this->redirectToRoute('admin_round_index');
        }

        $match = new GameMatch($round->getSeason(), $round, $teams[0], $teams[1]);

        $form = $this->createForm(GameMatchType::class, $match, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($match);
            $this->entityManager->flush();

            $this->addFlash('success', 'Jogo criado com sucesso.');

            return $this->redirectToRoute('admin_match_index');
        }

        return $this->render('admin/game_match/form.html.twig', ['form' => $form, 'match' => $match]);
    }

    #[Route('/{id}/editar', name: 'admin_match_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, GameMatch $match): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $this->denyAccessUnlessOrganizationMatches($match, $organization->getId());

        $form = $this->createForm(GameMatchType::class, $match, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Jogo atualizado com sucesso.');

            return $this->redirectToRoute('admin_match_index');
        }

        return $this->render('admin/game_match/form.html.twig', ['form' => $form, 'match' => $match]);
    }

    #[Route('/{id}/excluir', name: 'admin_match_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, GameMatch $match): Response
    {
        $this->denyAccessUnlessOrganizationMatches($match, $this->organizationContext->requireCurrentOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-match-'.$match->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($match);
            $this->entityManager->flush();
            $this->addFlash('success', 'Jogo removido.');
        }

        return $this->redirectToRoute('admin_match_index');
    }

    private function denyAccessUnlessOrganizationMatches(GameMatch $match, ?int $organizationId): void
    {
        if ($match->getHomeTeam()->getOrganization()->getId() !== $organizationId) {
            throw $this->createAccessDeniedException();
        }
    }
}
