<?php

namespace App\MessageHandler;

use App\Entity\AIResponse;
use App\Message\ProcessContentRequest;
use App\Repository\ContentRequestRepository;
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessContentRequestHandler
{
    public function __construct(
        private ContentRequestRepository $contentRequestRepository,
        private ApiService $apiService,
        private EntityManagerInterface $entityManager
    ) {}

    public function __invoke(ProcessContentRequest $message): void
    {
        $contentRequest = $this->contentRequestRepository->find($message->contentRequestId);

        if (!$contentRequest) {
            return;
        }

        // Postavi status na "processing"
        $contentRequest->setStatus('processing');
        $this->contentRequestRepository->save($contentRequest, true);

        // Dohvati prvu sliku vezanu uz zahtjev
        $mediaFiles = $contentRequest->getMediaFiles();

        if ($mediaFiles->isEmpty()) {
            $contentRequest->setStatus('failed');
            $this->contentRequestRepository->save($contentRequest, true);
            return;
        }

        $mediaFile = $mediaFiles->first();

        try {
            // Pošalji sliku Gemini API-ju
            $result = $this->apiService->analyzeImage($mediaFile->getPath());

            // Spremi odgovor
            $aiResponse = new AIResponse();
            $aiResponse->setRawResponse($result['rawResponse']);
            $aiResponse->setProcessedContent($result['processedContent']);
            $aiResponse->setModelUsed($result['modelUsed']);
            $aiResponse->setLatencyMs($result['latencyMs']);
            $aiResponse->setCreatedAt(new \DateTimeImmutable());
            $aiResponse->setContentRequest($contentRequest);

            $this->entityManager->persist($aiResponse);

            // Postavi status na "done"
            $contentRequest->setStatus('done');
            $this->contentRequestRepository->save($contentRequest, true);

            $this->entityManager->flush();

        } catch (\Exception $e) {
            error_log($e->getMessage());
            $contentRequest->setStatus('failed');
            $this->contentRequestRepository->save($contentRequest, true);
        }
    }
}