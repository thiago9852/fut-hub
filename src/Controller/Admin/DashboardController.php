<?php

namespace App\Controller\Admin;

use App\Service\Dashboard\DashboardService;
use App\Service\Security\OrganizationContext;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DashboardController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    #[IsGranted('ROLE_VIEWER')]
    public function index(DashboardService $dashboardService, OrganizationContext $organizationContext): Response
    {
        $overview = $dashboardService->buildOverview($organizationContext->requireCurrentOrganization());

        return $this->render('admin/dashboard.html.twig', ['overview' => $overview]);
    }
}
