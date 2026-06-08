<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\Security\OrganizationContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/usuarios')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly OrganizationContext $organizationContext,
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    #[Route('', name: 'admin_user_index', methods: ['GET'])]
    public function index(UserRepository $repository): Response
    {
        $users = $repository->findBy(
            ['organization' => $this->organizationContext->requireCurrentOrganization()],
            ['name' => 'ASC'],
        );

        return $this->render('admin/user/index.html.twig', ['users' => $users]);
    }

    #[Route('/novo', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $user = new User('', '', $this->organizationContext->requireCurrentOrganization());

        $form = $this->createForm(UserType::class, $user, ['is_new' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles([$form->get('role')->getData()]);
            $user->setPassword($this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Usuário criado com sucesso.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/form.html.twig', ['form' => $form, 'userEntity' => $user]);
    }

    #[Route('/{id}/editar', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function edit(Request $request, User $user): Response
    {
        $this->denyAccessUnlessOrganizationMatches($user);

        $currentRole = $user->getRoles()[0] ?? 'ROLE_VIEWER';
        $form = $this->createForm(UserType::class, $user, ['is_new' => false, 'current_role' => $currentRole]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setRoles([$form->get('role')->getData()]);

            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $plainPassword));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Usuário atualizado com sucesso.');

            return $this->redirectToRoute('admin_user_index');
        }

        return $this->render('admin/user/form.html.twig', ['form' => $form, 'userEntity' => $user]);
    }

    #[Route('/{id}/excluir', name: 'admin_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(Request $request, User $user): Response
    {
        $this->denyAccessUnlessOrganizationMatches($user);

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Você não pode remover o próprio usuário.');

            return $this->redirectToRoute('admin_user_index');
        }

        if ($this->isCsrfTokenValid('delete-user-'.$user->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Usuário removido.');
        }

        return $this->redirectToRoute('admin_user_index');
    }

    private function denyAccessUnlessOrganizationMatches(User $user): void
    {
        if ($user->getOrganization()?->getId() !== $this->organizationContext->requireCurrentOrganization()->getId()) {
            throw $this->createAccessDeniedException();
        }
    }
}
