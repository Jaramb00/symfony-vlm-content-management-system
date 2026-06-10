<?php

namespace App\Controller;

use App\Service\AnalyticsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/analytics')]
class AnalyticsController extends AbstractController
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    #[Route('/latency', name: 'analytics_latency', methods: ['GET'])]
    public function latency(): JsonResponse
    {
        $stats = $this->analyticsService->getLatencyStatistics();
        return $this->json($stats);
    }
}