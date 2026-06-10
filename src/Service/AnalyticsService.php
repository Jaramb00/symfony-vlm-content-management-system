<?php

namespace App\Service;

use App\Repository\AIResponseRepository;

class AnalyticsService
{
    public function __construct(
        private AIResponseRepository $aiResponseRepository
    ) {}

    public function getLatencyStatistics(): array
    {
        $stats = $this->aiResponseRepository->getLatencyStatistics();
        $byModel = $this->aiResponseRepository->getLatencyByModel();
        $overTime = $this->aiResponseRepository->getLatencyOverTime();

        return [
            'total_requests' => (int) $stats['total_requests'],
            'latency' => [
                'average_ms' => round((float) $stats['avg_latency'], 2),
                'min_ms' => (int) $stats['min_latency'],
                'max_ms' => (int) $stats['max_latency'],
            ],
            'by_model' => array_map(fn($m) => [
                'model' => $m['model'],
                'total' => (int) $m['total'],
                'avg_latency_ms' => round((float) $m['avg_latency'], 2),
                'min_latency_ms' => (int) $m['min_latency'],
                'max_latency_ms' => (int) $m['max_latency'],
            ], $byModel),
            'over_time' => array_map(fn($r) => [
                'date' => $r['date']->format('Y-m-d H:i:s'),
                'latency_ms' => (int) $r['latency'],
                'model' => $r['model'],
            ], $overTime),
        ];
    }
}