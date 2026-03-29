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
}