<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\OrganizationType;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/configuracoes')]
#[IsGranted('ROLE_ADMIN')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $organization = $this->organizationContext->requireCurrentOrganization();

        $form = $this->createForm(OrganizationType::class, $organization);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'Configurações atualizadas com sucesso.');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings/index.html.twig', [
            'form' => $form,
            'organization' => $organization,
        ]);
    }

    #[Route('/token/gerar', name: 'admin_settings_regenerate_token', methods: ['POST'])]
    public function regenerateToken(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('regenerate-api-token', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        /** @var User $user */
        $user = $this->getUser();
        $user->setApiToken(bin2hex(random_bytes(24)));
        $this->entityManager->flush();

        $this->addFlash('success', 'Novo token de API gerado. O token anterior deixou de funcionar.');

        return $this->redirectToRoute('admin_settings');
    }
}
