<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        protected readonly SerializerInterface $serializer,
        protected readonly ValidatorInterface $validator,
    ) {
    }

    /**
     * Decodifica o corpo JSON da requisição num DTO e valida.
     * Retorna o DTO se válido, ou uma JsonResponse 422/400 pronta para devolver.
     *
     * @template T of object
     * @param class-string<T> $dtoClass
     * @return T|JsonResponse
     */
    protected function decodeAndValidate(Request $request, string $dtoClass): object
    {
        try {
            $dto = $this->serializer->deserialize($request->getContent(), $dtoClass, 'json');
        } catch (\Throwable) {
            return $this->json(['error' => 'JSON inválido.'], 400);
        }

        $violations = $this->validator->validate($dto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->json(['error' => 'Dados inválidos.', 'fields' => $errors], 422);
        }

        return $dto;
    }
}
