<?php

namespace App\Controller\Admin;

use App\Entity\MatchLineup;
use App\Form\MatchLineupType;
use App\Repository\GameMatchRepository;
use App\Repository\MatchLineupRepository;
use App\Repository\PlayerRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/escalacoes')]
#[IsGranted('ROLE_VIEWER')]
class MatchLineupController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_match_lineup_index', methods: ['GET'])]
    public function index(MatchLineupRepository $repository): Response
    {
        $lineups = $repository->createQueryBuilder('l')
            ->join('l.team', 't')
            ->addSelect('t')
            ->join('l.player', 'p')
            ->addSelect('p')
            ->join('l.match', 'm')
            ->addSelect('m')
            ->where('t.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->orderBy('m.scheduledAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/match_lineup/index.html.twig', ['lineups' => $lineups]);
    }

    #[Route('/novo', name: 'admin_match_lineup_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(Request $request, GameMatchRepository $matchRepository, PlayerRepository $playerRepository): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();

        $match = $matchRepository->createQueryBuilder('m')
            ->join('m.homeTeam', 'ht')
            ->where('ht.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();

        $player = $playerRepository->findOneBy(['organization' => $organization]);

        if (!$match || !$player) {
            $this->addFlash('error', 'Cadastre um jogo e um jogador antes de montar a escalação.');

            return $this->redirectToRoute('admin_match_index');
        }

        $lineup = new MatchLineup($match, $match->getHomeTeam(), $player);

        $form = $this->createForm(MatchLineupType::class, $lineup, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($lineup);
            $this->entityManager->flush();

            $this->addFlash('success', 'Escalação registrada com sucesso.');

            return $this->redirectToRoute('admin_match_lineup_index');
        }

        return $this->render('admin/match_lineup/form.html.twig', ['form' => $form, 'lineup' => $lineup]);
    }

    #[Route('/{id}/editar', name: 'admin_match_lineup_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, MatchLineup $lineup): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $this->denyAccessUnlessOrganizationMatches($lineup, $organization->getId());

        $form = $this->createForm(MatchLineupType::class, $lineup, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Escalação atualizada com sucesso.');

            return $this->redirectToRoute('admin_match_lineup_index');
        }

        return $this->render('admin/match_lineup/form.html.twig', ['form' => $form, 'lineup' => $lineup]);
    }

    #[Route('/{id}/excluir', name: 'admin_match_lineup_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, MatchLineup $lineup): Response
    {
        $this->denyAccessUnlessOrganizationMatches($lineup, $this->organizationContext->requireCurrentOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-match-lineup-'.$lineup->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($lineup);
            $this->entityManager->flush();
            $this->addFlash('success', 'Escalação removida.');
        }

        return $this->redirectToRoute('admin_match_lineup_index');
    }

    private function denyAccessUnlessOrganizationMatches(MatchLineup $lineup, ?int $organizationId): void
    {
        if ($lineup->getTeam()->getOrganization()->getId() !== $organizationId) {
            throw $this->createAccessDeniedException();
        }
    }
}
