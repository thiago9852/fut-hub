<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\Import\ImportReport;
use App\Service\Import\ImportService;
use App\Service\Security\OrganizationContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/v1')]
class ImportApiController extends AbstractApiController
{
    public function __construct(
        SerializerInterface $serializer,
        ValidatorInterface $validator,
        private readonly OrganizationContext $organizationContext,
        private readonly ImportService $importService,
    ) {
        parent::__construct($serializer, $validator);
    }

    #[Route('/import/teams', name: 'api_import_teams', methods: ['POST'])]
    public function teams(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importTeams($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/import/players', name: 'api_import_players', methods: ['POST'])]
    public function players(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importPlayers($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/import/rosters', name: 'api_import_rosters', methods: ['POST'])]
    public function rosters(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importRosters($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/import/rounds', name: 'api_import_rounds', methods: ['POST'])]
    public function rounds(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importRounds($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/import/matches', name: 'api_import_matches', methods: ['POST'])]
    public function matches(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importMatches($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/import/events', name: 'api_import_events', methods: ['POST'])]
    public function events(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importEvents($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/import/lineups', name: 'api_import_lineups', methods: ['POST'])]
    public function lineups(Request $request): JsonResponse
    {
        $rows = $this->decodeRows($request);
        if ($rows instanceof JsonResponse) {
            return $rows;
        }

        $report = $this->importService->importLineups($this->organizationContext->requireCurrentOrganization(), $this->currentUser(), $rows);

        return $this->reportToResponse($report);
    }

    #[Route('/export', name: 'api_export_all', methods: ['GET'])]
    public function export(Request $request): JsonResponse
    {
        $organization = $this->organizationContext->requireCurrentOrganization();
        $seasonId = $request->query->getInt('season_id', 0) ?: null;

        $data = $this->importService->exportAll($organization, $seasonId);

        return $this->json($data);
    }

    private function currentUser(): User
    {
        /** @var User $user */
        $user = $this->getUser();

        return $user;
    }

    /** @return list<array<string, mixed>>|JsonResponse */
    private function decodeRows(Request $request): array|JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['error' => 'JSON inválido.'], 400);
        }

        $rows = $data['rows'] ?? $data;

        if (!is_array($rows)) {
            return $this->json(['error' => '"rows" deve ser uma lista de objetos.'], 400);
        }

        return array_values($rows);
    }

    private function reportToResponse(ImportReport $report): JsonResponse
    {
        return $this->json($this->reportToArray($report), 'FALHOU' === $report->status ? 422 : 200);
    }

    /** @return array<string, mixed> */
    private function reportToArray(ImportReport $report): array
    {
        return [
            'status' => $report->status,
            'records_processed' => $report->recordsProcessed,
            'records_created' => $report->recordsCreated,
            'records_updated' => $report->recordsUpdated,
            'records_failed' => $report->recordsFailed,
            'errors' => $report->errors,
        ];
    }
}
