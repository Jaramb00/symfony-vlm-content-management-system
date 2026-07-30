<?php

namespace App\Controller;

use App\Exception\ResourceNotFoundException;
use App\Service\ContentRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/content-request')]
final class ContentRequestController extends AbstractController
{
    public function __construct(
        private ContentRequestService $contentRequestService
    ) {}

    #[Route('', name: 'content_request_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        // "?? []" — neispravan JSON više ne ruši app s TypeErrorom,
        // nego uredno padne na validaciji ("Title is required")
        $data = json_decode($request->getContent(), true) ?? [];

        return $this->json(
            $this->contentRequestService->create($data, $this->getUser()),
            201
        );
    }

    #[Route('', name: 'content_request_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        return $this->json($this->contentRequestService->list($this->getUser()));
    }

    #[Route('/{id}', name: 'content_request_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        return $this->json($this->contentRequestService->show($id, $this->getUser()));
    }

    #[Route('/{id}/response', name: 'content_request_response', methods: ['GET'])]
    public function getResponse(int $id): JsonResponse
    {
        $contentRequest = $this->contentRequestService->findByIdAndUser($id, $this->getUser());

        $aiResponse = $contentRequest->getAIResponse();

        if (!$aiResponse) {
            throw new ResourceNotFoundException('AI response not available yet');
        }

        return $this->json([
            'id' => $aiResponse->getId(),
            'processedContent' => $aiResponse->getProcessedContent(),
            'modelUsed' => $aiResponse->getModelUsed(),
            'latencyMs' => $aiResponse->getLatencyMs(),
            'imageFilename' => $aiResponse->getImageFilename(),
            'createdAt' => $aiResponse->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }
}