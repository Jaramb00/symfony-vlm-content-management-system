<?php

namespace App\EventSubscriber;

use App\Constants\RequestStatus;
use App\Message\ProcessContentRequest;
use App\Repository\ContentRequestRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

/**
 * Označava ContentRequest kao FAILED tek kad je poruka KONAČNO pala:
 * ili su iscrpljeni svi retryji, ili je greška bila trajna (unrecoverable).
 * Dok Messenger još retry-a, status ostaje "processing".
 */
final class ContentRequestFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ContentRequestRepository $contentRequestRepository,
        private LoggerInterface $aiApiLogger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            WorkerMessageFailedEvent::class => 'onMessageFailed',
        ];
    }

    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $message = $event->getEnvelope()->getMessage();

        if (!$message instanceof ProcessContentRequest) {
            return;
        }

        if ($event->willRetry()) {
            // Nije još kraj — Messenger će ponoviti
            return;
        }

        $contentRequest = $this->contentRequestRepository->find($message->contentRequestId);

        if (!$contentRequest) {
            return;
        }

        $contentRequest->setStatus(RequestStatus::FAILED);
        $this->contentRequestRepository->save($contentRequest, true);

        $this->aiApiLogger->error('All retries exhausted, ContentRequest marked as failed', [
            'contentRequestId' => $message->contentRequestId,
            'error' => $event->getThrowable()->getMessage(),
        ]);
    }
}