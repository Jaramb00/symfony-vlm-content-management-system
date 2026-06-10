<?php

namespace App\MessageHandler;

use App\Constants\RequestStatus;
use App\Entity\AIResponse;
use App\Message\ProcessContentRequest;
use App\Repository\ContentRequestRepository;
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessContentRequestHandler
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 2000;

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

        $contentRequest->setStatus(RequestStatus::PROCESSING);
        $this->contentRequestRepository->save($contentRequest, true);

        $mediaFiles = $contentRequest->getMediaFiles();

        if ($mediaFiles->isEmpty()) {
            $contentRequest->setStatus(RequestStatus::FAILED);
            $this->contentRequestRepository->save($contentRequest, true);
            return;
        }

        $mediaFile = $mediaFiles->first();

        try {
            $result = $this->analyzeImageWithRetry($mediaFile->getPath());

            $aiResponse = new AIResponse();
            $aiResponse->setRawResponse($result['rawResponse']);
            $aiResponse->setProcessedContent($result['processedContent']);
            $aiResponse->setModelUsed($result['modelUsed']);
            $aiResponse->setLatencyMs($result['latencyMs']);
            $aiResponse->setCreatedAt(new \DateTimeImmutable());
            $aiResponse->setContentRequest($contentRequest);
            $aiResponse->setImageSizeBytes($mediaFile->getSize());
            $aiResponse->setImageFilename($mediaFile->getFilename());

            $this->entityManager->persist($aiResponse);

            $contentRequest->setStatus(RequestStatus::DONE);
            $this->contentRequestRepository->save($contentRequest, true);

            $this->entityManager->flush();

        } catch (\Exception $e) {
                error_log('Handler error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $contentRequest->setStatus(RequestStatus::FAILED);
            $this->contentRequestRepository->save($contentRequest, true);
        }
    }

    private function analyzeImageWithRetry(string $imagePath, int $attempt = 1): array
    {
        try {
            return $this->apiService->analyzeImage($imagePath);
        } catch (\Exception $e) {
            if ($attempt >= self::MAX_RETRIES) {
                throw $e;
            }
            usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
            return $this->analyzeImageWithRetry($imagePath, $attempt + 1);
        }
    }
}