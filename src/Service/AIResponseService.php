<?php

namespace App\Service;

use App\Entity\AIResponse;
use App\Entity\ContentRequest;
use Doctrine\ORM\EntityManagerInterface;

class AIResponseService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function createFromApiResult(ContentRequest $contentRequest, array $result): AIResponse
    {
        $aiResponse = new AIResponse();
        $aiResponse->setRawResponse($result['rawResponse']);
        $aiResponse->setProcessedContent($result['processedContent']);
        $aiResponse->setModelUsed($result['modelUsed']);
        $aiResponse->setLatencyMs($result['latencyMs']);
        $aiResponse->setCreatedAt(new \DateTimeImmutable());
        $aiResponse->setContentRequest($contentRequest);

        $this->entityManager->persist($aiResponse);

        return $aiResponse;
    }
}