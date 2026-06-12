<?php

namespace App\Controller;

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
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->contentRequestService->create($data, $this->getUser());
            return $this->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('', name: 'content_request_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $result = $this->contentRequestService->list($this->getUser());
        return $this->json($result);
    }

    #[Route('/{id}', name: 'content_request_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $result = $this->contentRequestService->show($id, $this->getUser());

        if (!$result) {
            return $this->json(['error' => 'Not found'], 404);
        }

        return $this->json($result);
    }
    
    #[Route('/{id}/response', name: 'content_request_response', methods: ['GET'])]
    public function getResponse(int $id): JsonResponse
    {
        $contentRequest = $this->contentRequestService->findByIdAndUser($id, $this->getUser());

        if (!$contentRequest) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $aiResponse = $contentRequest->getAIResponse();

        if (!$aiResponse) {
            return $this->json(['error' => 'AI response not available yet'], 404);
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