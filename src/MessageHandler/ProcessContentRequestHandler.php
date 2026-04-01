<?php

namespace App\MessageHandler;

use App\Message\ProcessContentRequest;
use App\Repository\ContentRequestRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class ProcessContentRequestHandler
{
    public function __construct(
        private ContentRequestRepository $contentRequestRepository
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

        // Ovdje će doći VLM API poziv
    }
}