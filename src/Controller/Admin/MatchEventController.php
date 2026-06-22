<?php

namespace App\Controller\Admin;

use App\Entity\MatchEvent;
use App\Enum\MatchEventType;
use App\Form\MatchEventType as MatchEventFormType;
use App\Repository\GameMatchRepository;
use App\Repository\MatchEventRepository;
use App\Repository\PlayerRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/eventos')]
#[IsGranted('ROLE_VIEWER')]
class MatchEventController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_match_event_index', methods: ['GET'])]
    public function index(MatchEventRepository $repository): Response
    {
        $events = $repository->createQueryBuilder('e')
            ->join('e.team', 't')
            ->addSelect('t')
            ->join('e.player', 'p')
            ->addSelect('p')
            ->join('e.match', 'm')
            ->addSelect('m')
            ->where('t.organization = :organization')
            ->setParameter('organization', $this->organizationContext->requireCurrentOrganization())
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/match_event/index.html.twig', ['events' => $events]);
    }

    #[Route('/novo', name: 'admin_match_event_new', methods: ['GET', 'POST'])]
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
            $this->addFlash('error', 'Cadastre um jogo e um jogador antes de registrar um evento.');

            return $this->redirectToRoute('admin_match_index');
        }

        $event = new MatchEvent($match, $match->getHomeTeam(), $player, MatchEventType::GOAL);

        $form = $this->createForm(MatchEventFormType::class, $event, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($event);
            $this->entityManager->flush();

            $this->addFlash('success', 'Evento registrado com sucesso.');

            return $this->redirectToRoute('admin_match_event_index');
        }

        return $this->render('admin/match_event/form.html.twig', ['form' => $form, 'event' => $event]);
    }

    #[Route('/{id}/editar', name: 'admin_match_event_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_EDITOR')]
    public function edit(Request $request, MatchEvent $event): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $this->denyAccessUnlessOrganizationMatches($event, $organization->getId());

        $form = $this->createForm(MatchEventFormType::class, $event, ['organization' => $organization]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Evento atualizado com sucesso.');

            return $this->redirectToRoute('admin_match_event_index');
        }

        return $this->render('admin/match_event/form.html.twig', ['form' => $form, 'event' => $event]);
    }

    #[Route('/{id}/excluir', name: 'admin_match_event_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, MatchEvent $event): Response
    {
        $this->denyAccessUnlessOrganizationMatches($event, $this->organizationContext->requireCurrentOrganization()->getId());

        if ($this->isCsrfTokenValid('delete-match-event-'.$event->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($event);
            $this->entityManager->flush();
            $this->addFlash('success', 'Evento removido.');
        }

        return $this->redirectToRoute('admin_match_event_index');
    }

    private function denyAccessUnlessOrganizationMatches(MatchEvent $event, ?int $organizationId): void
    {
        if ($event->getTeam()->getOrganization()->getId() !== $organizationId) {
            throw $this->createAccessDeniedException();
        }
    }
}
