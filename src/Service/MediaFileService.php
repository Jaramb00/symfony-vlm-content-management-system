<?php

namespace App\Service;

use App\Entity\MediaFile;
use App\Entity\ContentRequest;
use App\Message\ProcessContentRequest;
use App\Exception\ValidationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Messenger\MessageBusInterface;

class MediaFileService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus,
        private LoggerInterface $logger,
        private string $uploadDir
    ) {}

    public function upload(UploadedFile $file, ContentRequest $contentRequest): array
    {
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $this->logger->notice('Upload rejected: invalid mime type', [
                'contentRequestId' => $contentRequest->getId(),
                'mimeType' => $file->getMimeType(),
                'originalName' => $file->getClientOriginalName(),
            ]);

            throw new ValidationException('Invalid file type. Only images are allowed.');
        }

        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file->getSize() > $maxSize) {
            $this->logger->notice('Upload rejected: file too large', [
                'contentRequestId' => $contentRequest->getId(),
                'size' => $file->getSize(),
                'maxSize' => $maxSize,
            ]);

            throw new ValidationException('File too large. Maximum size is 5MB.');
        }

        $size = $file->getSize();
        $mimeType = $file->getMimeType();

        
        $filename = bin2hex(random_bytes(16)) . '.' . $file->guessExtension();

        // move() je atomski i briše temp datoteku (copy() ju je ostavljao)
        $file->move($this->uploadDir, $filename);

        $mediaFile = new MediaFile();
        $mediaFile->setFilename($filename);
        $mediaFile->setMimeType($mimeType);
        // U bazu ide RELATIVNA putanja — apsolutnu gradi tko je treba,
        // iz konfiguracije. Baza više ne ovisi o layoutu diska.
        $mediaFile->setPath($filename);
        $mediaFile->setSize($size);
        $mediaFile->setCreatedAt(new \DateTimeImmutable());
        $mediaFile->setContentRequest($contentRequest);

        $this->entityManager->persist($mediaFile);
        $this->entityManager->flush();

        $this->logger->info('Media file uploaded, dispatching for AI processing', [
            'contentRequestId' => $contentRequest->getId(),
            'mediaFileId' => $mediaFile->getId(),
            'filename' => $filename,
            'size' => $size,
        ]);

        $this->messageBus->dispatch(new ProcessContentRequest($contentRequest->getId()));

        return [
            'id' => $mediaFile->getId(),
            'filename' => $mediaFile->getFilename(),
            'mimeType' => $mediaFile->getMimeType(),
            'size' => $mediaFile->getSize(),
        ];
    }
}