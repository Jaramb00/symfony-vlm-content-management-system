<?php

namespace App\Controller;

use App\Service\MediaFileService;
use App\Service\ContentRequestService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/media')]
final class MediaFileController extends AbstractController
{
    public function __construct(
        private MediaFileService $mediaFileService,
        private ContentRequestService $contentRequestService
    ) {}

    #[Route('/upload/{contentRequestId}', name: 'media_upload', methods: ['POST'])]
    public function upload(int $contentRequestId, Request $request): JsonResponse
    {
        $contentRequest = $this->contentRequestService
            ->findByIdAndUser($contentRequestId, $this->getUser());

        if (!$contentRequest) {
            return $this->json(['error' => 'Content request not found'], 404);
        }

        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        try {
            $result = $this->mediaFileService->upload($file, $contentRequest);
            return $this->json($result, 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}