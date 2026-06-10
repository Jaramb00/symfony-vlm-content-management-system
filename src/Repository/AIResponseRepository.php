<?php

namespace App\Repository;

use App\Entity\AIResponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AIResponse>
 */
class AIResponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AIResponse::class);
    }

    public function getLatencyStatistics(): array
    {
        return $this->createQueryBuilder('a')
            ->select(
                'COUNT(a.id) as total_requests',
                'AVG(a.latencyMs) as avg_latency',
                'MIN(a.latencyMs) as min_latency',
                'MAX(a.latencyMs) as max_latency'
            )
            ->getQuery()
            ->getSingleResult();
    }

    public function getLatencyByModel(): array
    {
        return $this->createQueryBuilder('a')
            ->select(
                'a.modelUsed as model',
                'COUNT(a.id) as total',
                'AVG(a.latencyMs) as avg_latency',
                'MIN(a.latencyMs) as min_latency',
                'MAX(a.latencyMs) as max_latency'
            )
            ->groupBy('a.modelUsed')
            ->getQuery()
            ->getResult();
    }

    public function getLatencyOverTime(): array
    {
        return $this->createQueryBuilder('a')
            ->select(
                'a.createdAt as date',
                'a.latencyMs as latency',
                'a.modelUsed as model'
            )
            ->orderBy('a.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function getLatencyByImageSize(): array
{
    return $this->createQueryBuilder('a')
        ->select(
            'a.imageSizeBytes as size',
            'a.latencyMs as latency',
            'a.modelUsed as model',
            'a.imageFilename as filename'
        )
        ->orderBy('a.imageSizeBytes', 'ASC')
        ->getQuery()
        ->getResult();
}

}