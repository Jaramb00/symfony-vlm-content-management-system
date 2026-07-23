<?php

namespace App\Service;

use App\Entity\MediaFile;
use App\Entity\ContentRequest;
use App\Message\ProcessContentRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class MediaFileService
{
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        // Default logger (main kanal) — uploadi nisu AI pozivi
        private LoggerInterface $logger,
        string $uploadDir
    ) {
        $this->uploadDir = $uploadDir;
    }

    public function upload(UploadedFile $file, ContentRequest $contentRequest): array
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $this->logger->notice('Upload rejected: invalid mime type', [
                'contentRequestId' => $contentRequest->getId(),
                'mimeType' => $file->getMimeType(),
                'originalName' => $file->getClientOriginalName(),
            ]);

            throw new \InvalidArgumentException('Invalid file type. Only images are allowed.');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSize) {
            $this->logger->notice('Upload rejected: file too large', [
                'contentRequestId' => $contentRequest->getId(),
                'size' => $file->getSize(),
                'maxSize' => $maxSize,
            ]);

            throw new \InvalidArgumentException('File too large. Maximum size is 5MB.');
        }

        $filename = uniqid() . '.' . $file->guessExtension();
        $targetPath = $this->uploadDir . DIRECTORY_SEPARATOR . $filename;
        copy($file->getRealPath(), $targetPath);

        $mediaFile = new MediaFile();
        $mediaFile->setFilename($filename);
        $mediaFile->setMimeType($file->getMimeType());
        $mediaFile->setPath($targetPath);
        $mediaFile->setSize($file->getSize());
        $mediaFile->setCreatedAt(new \DateTimeImmutable());
        $mediaFile->setContentRequest($contentRequest);

        $this->entityManager->persist($mediaFile);
        $this->entityManager->flush();

        $this->logger->info('Media file uploaded, dispatching for AI processing', [
            'contentRequestId' => $contentRequest->getId(),
            'mediaFileId' => $mediaFile->getId(),
            'filename' => $filename,
            'size' => $mediaFile->getSize(),
        ]);

        // Slika postoji — sada je sigurno staviti zahtjev u queue za AI obradu
        $this->messageBus->dispatch(new ProcessContentRequest($contentRequest->getId()));

        return [
            'id' => $mediaFile->getId(),
            'filename' => $mediaFile->getFilename(),
            'mimeType' => $mediaFile->getMimeType(),
            'size' => $mediaFile->getSize(),
        ];
    }
}