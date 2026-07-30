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
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsMessageHandler]
final class ProcessContentRequestHandler
{
    public function __construct(
        private ContentRequestRepository $contentRequestRepository,
        private ApiService $apiService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $aiApiLogger,
        private string $uploadDir
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
            // Trajno stanje — retry ne može stvoriti sliku koje nema
            $this->aiApiLogger->error('No media files attached, marking as failed', [
                'contentRequestId' => $contentRequest->getId(),
            ]);

            $contentRequest->setStatus(RequestStatus::FAILED);
            $this->contentRequestRepository->save($contentRequest, true);

            return;
        }

        $mediaFile = $mediaFiles->first();

        try {
            $result = $this->apiService->analyzeImage(
                $this->uploadDir . DIRECTORY_SEPARATOR . $mediaFile->getPath()
            );        
            } catch (\Throwable $e) {
            if ($this->isRetryable($e)) {
                // Bacamo dalje: Messengerova retry_strategy vraća poruku u queue
                // s odgodom, worker je slobodan za druge poruke. Status ostaje
                // "processing" dok se ne iscrpe svi pokušaji (vidi subscriber).
                $this->aiApiLogger->warning('Transient API failure, Messenger will retry', [
                    'contentRequestId' => $contentRequest->getId(),
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);

                throw $e;
            }

            // Trajna greška (4xx, nečitljiva datoteka...) — retry je besmislen,
            // preskačemo ga i šaljemo poruku ravno u failure transport
            $this->aiApiLogger->error('Permanent API failure, skipping retries', [
                'contentRequestId' => $contentRequest->getId(),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw new UnrecoverableMessageHandlingException($e->getMessage(), 0, $e);
        }

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
    }

    /**
     * Prolazne greške (imaju smisla za retry): mrežni problemi, timeout,
     * rate limit (429) i serverske greške (5xx).
     * Trajne (nemaju): 4xx osim 429, nečitljiva datoteka, sve ostalo.
     */
    private function isRetryable(\Throwable $e): bool
    {
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getResponse()->getStatusCode();

            return 429 === $status || $status >= 500;
        }

        // TransportException: DNS, connection reset, timeout — mreža, ne API
        return $e instanceof TransportExceptionInterface;
    }
}