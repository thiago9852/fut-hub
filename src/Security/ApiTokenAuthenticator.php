<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

/**
 * Autentica requisições à API via "Authorization: Bearer TOKEN".
 *
 * O token é o de um usuário (User::apiToken), que já está vinculado a uma
 * organização — assim toda chamada à API automaticamente sabe "a qual
 * organização este dado pertence" (ver princípio de multi-tenancy no README).
 */
class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {
    }

    public function supports(Request $request): ?bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $token = substr($request->headers->get('Authorization'), 7);

        if ('' === $token) {
            throw new AuthenticationException('Token não informado.');
        }

        $user = $this->userRepository->findOneBy(['apiToken' => $token]);

        if (!$user) {
            throw new AuthenticationException('Token inválido.');
        }

        return new SelfValidatingPassport(new UserBadge($token, fn () => $user));
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return new JsonResponse(['error' => 'Não autenticado.'], Response::HTTP_UNAUTHORIZED);
    }
}
