<?php

namespace App\MessageHandler;

use App\Constants\RequestStatus;
use App\Entity\AIResponse;
use App\Message\ProcessContentRequest;
use App\Repository\ContentRequestRepository;
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessContentRequestHandler
{
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 2000;

    public function __construct(
        private ContentRequestRepository $contentRequestRepository,
        private ApiService $apiService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $aiApiLogger
    ) {}

    public function __invoke(ProcessContentRequest $message): void
    {
        $contentRequest = $this->contentRequestRepository->find($message->contentRequestId);

        if (!$contentRequest) {
            $this->aiApiLogger->warning('ContentRequest not found, skipping message', [
                'contentRequestId' => $message->contentRequestId,
            ]);

            return;
        }

        $this->aiApiLogger->info('Processing started', [
            'contentRequestId' => $contentRequest->getId(),
        ]);

        $contentRequest->setStatus(RequestStatus::PROCESSING);
        $this->contentRequestRepository->save($contentRequest, true);

        $mediaFiles = $contentRequest->getMediaFiles();

        if ($mediaFiles->isEmpty()) {
            $this->aiApiLogger->error('No media files attached, marking as failed', [
                'contentRequestId' => $contentRequest->getId(),
            ]);

            $contentRequest->setStatus(RequestStatus::FAILED);
            $this->contentRequestRepository->save($contentRequest, true);

            return;
        }

        $mediaFile = $mediaFiles->first();

        try {
            $result = $this->analyzeImageWithRetry(
                $mediaFile->getPath(),
                $contentRequest->getId()
            );

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

            $this->aiApiLogger->info('Processing finished', [
                'contentRequestId' => $contentRequest->getId(),
                'status' => RequestStatus::DONE,
                'latencyMs' => $result['latencyMs'],
            ]);
        } catch (\Exception $e) {
            $this->aiApiLogger->error('Processing failed after all retries', [
                'contentRequestId' => $contentRequest->getId(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $contentRequest->setStatus(RequestStatus::FAILED);
            $this->contentRequestRepository->save($contentRequest, true);
        }
    }

    private function analyzeImageWithRetry(string $imagePath, int $contentRequestId, int $attempt = 1): array
    {
        try {
            return $this->apiService->analyzeImage($imagePath);
        } catch (\Exception $e) {
            if ($attempt >= self::MAX_RETRIES) {
                throw $e;
            }

            $this->aiApiLogger->warning('API call failed, retrying', [
                'contentRequestId' => $contentRequestId,
                'attempt' => $attempt,
                'maxRetries' => self::MAX_RETRIES,
                'nextDelayMs' => self::RETRY_DELAY_MS * $attempt,
                'message' => $e->getMessage(),
            ]);

            usleep(self::RETRY_DELAY_MS * 1000 * $attempt);

            return $this->analyzeImageWithRetry($imagePath, $contentRequestId, $attempt + 1);
        }
    }
}