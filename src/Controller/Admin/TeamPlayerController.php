<?php

namespace App\Controller\Admin;

use App\Entity\TeamPlayer;
use App\Form\TeamPlayerType;
use App\Repository\PlayerRepository;
use App\Repository\SeasonRepository;
use App\Repository\TeamPlayerRepository;
use App\Repository\TeamRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/elencos')]
#[IsGranted('ROLE_VIEWER')]
class TeamPlayerController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_team_player_index', methods: ['GET'])]
    public function index(TeamPlayerRepository $repository): Response
    {
        $teamPlayers = $repository->createQueryBuilder('tp')
            ->join('tp.team', 't')
            ->addSelect('t')
            ->join('tp.player', 'p')
            ->addSelect('p')
            ->join('tp.season', 's')
            ->addSelect('s')
            ->where('t.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->orderBy('t.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/team_player/index.html.twig', ['teamPlayers' => $teamPlayers]);
    }

    #[Route('/novo', name: 'admin_team_player_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EDITOR')]
    public function new(
        Request $request,
        TeamRepository $teamRepository,
        PlayerRepository $playerRepository,
        SeasonRepository $seasonRepository,
    ): Response {
        $organization = $this->organizationContext->requireCurrentOrganization();

        $team = $teamRepository->findOneBy(['organization' => $organization]);
        $player = $playerRepository->findOneBy(['organization' => $organization]);
        $season = $seasonRepository->createQueryBuilder('s')
            ->join('s.championship', 'c')
            ->where('c.organization = :organization')
            ->setParameter('organization', $organization)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$team || !$player || !$season) {
            $this->addFlash('error', 'Cadastre ao menos um time, um jogador e uma temporada antes de montar um elenco.');

            return $this->redirectToRoute('admin_team_index');
        }

        $teamPlayer = new TeamPlayer($team, $player, $season);

        $form = $this->createForm(TeamPlayerType::class, $teamPlayer, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($teamPlayer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Jogador adicionado ao elenco.');

            return $this->redirectToRoute('admin_team_player_index');
        }

        return $this->render('admin/team_player/form.html.twig', ['form' => $form, 'teamPlayer' => $teamPlayer]);
    }

    #[Route('/{id}/editar', name: 'admin_team_player_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, TeamPlayer $teamPlayer): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $this->denyAccessUnlessOrganizationMatches($teamPlayer, $organization->getId());

        $form = $this->createForm(TeamPlayerType::class, $teamPlayer, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Elenco atualizado com sucesso.');

            return $this->redirectToRoute('admin_team_player_index');
        }

        return $this->render('admin/team_player/form.html.twig', ['form' => $form, 'teamPlayer' => $teamPlayer]);
    }

    #[Route('/{id}/excluir', name: 'admin_team_player_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, TeamPlayer $teamPlayer): Response
    {
        $this->denyAccessUnlessOrganizationMatches($teamPlayer, $this->organizationContext->requireCurrentOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-team-player-'.$teamPlayer->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($teamPlayer);
            $this->entityManager->flush();
            $this->addFlash('success', 'Registro removido do elenco.');
        }

        return $this->redirectToRoute('admin_team_player_index');
    }

    private function denyAccessUnlessOrganizationMatches(TeamPlayer $teamPlayer, ?int $organizationId): void
    {
        if ($teamPlayer->getTeam()->getOrganization()->getId() !== $organizationId) {
            throw $this->createAccessDeniedException();
        }
    }
}
