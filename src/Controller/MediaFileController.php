<?php

namespace App\Controller;

use App\Exception\ValidationException;
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
        // Baca ResourceNotFoundException ako ne postoji ili nije korisnikov
        $contentRequest = $this->contentRequestService
            ->findByIdAndUser($contentRequestId, $this->getUser());

        $file = $request->files->get('file');

        if (!$file) {
            throw new ValidationException('No file uploaded');
        }

        return $this->json(
            $this->mediaFileService->upload($file, $contentRequest),
            201
        );
    }
}