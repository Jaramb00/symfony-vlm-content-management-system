<?php

namespace App\Service;

use App\Entity\MediaFile;
use App\Entity\ContentRequest;
use App\Message\ProcessContentRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class MediaFileService
{
    private string $uploadDir;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        string $uploadDir
    ) {
        $this->uploadDir = $uploadDir;
    }

    public function upload(UploadedFile $file, ContentRequest $contentRequest): array
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            throw new \InvalidArgumentException('Invalid file type. Only images are allowed.');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSize) {
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