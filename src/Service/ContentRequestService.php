<?php

namespace App\Service;

use App\Constants\RequestStatus;
use App\Entity\ContentRequest;
use App\Entity\User;
use App\Message\ProcessContentRequest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class ContentRequestService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $messageBus
    ) {}

    public function create(array $data, User $user): array
    {
        if (empty($data['title'])) {
            throw new \InvalidArgumentException('Title is required');
        }

        $contentRequest = new ContentRequest();
        $contentRequest->setTitle($data['title']);
        $contentRequest->setDescription($data['description'] ?? null);
        $contentRequest->setStatus(RequestStatus::PENDING);
        $contentRequest->setCreatedAt(new \DateTimeImmutable());
        $contentRequest->setUser($user);

        $this->entityManager->persist($contentRequest);
        $this->entityManager->flush();

        // Stavi poruku u queue
        $this->messageBus->dispatch(new ProcessContentRequest($contentRequest->getId()));

        return [
            'id' => $contentRequest->getId(),
            'title' => $contentRequest->getTitle(),
            'status' => $contentRequest->getStatus(),
        ];
    }

    public function list(User $user): array
    {
        $requests = $this->entityManager->getRepository(ContentRequest::class)
            ->findBy(['user' => $user]);

        return array_map(fn($r) => [
            'id' => $r->getId(),
            'title' => $r->getTitle(),
            'status' => $r->getStatus(),
            'createdAt' => $r->getCreatedAt()->format('Y-m-d H:i:s'),
        ], $requests);
    }

    public function show(int $id, User $user): ?array
    {
        $contentRequest = $this->entityManager->getRepository(ContentRequest::class)->find($id);

        if (!$contentRequest || $contentRequest->getUser() !== $user) {
            return null;
        }

        return [
            'id' => $contentRequest->getId(),
            'title' => $contentRequest->getTitle(),
            'description' => $contentRequest->getDescription(),
            'status' => $contentRequest->getStatus(),
            'createdAt' => $contentRequest->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public function findByIdAndUser(int $id, User $user): ?ContentRequest
    {
        $contentRequest = $this->entityManager
            ->getRepository(ContentRequest::class)
            ->find($id);

        if (!$contentRequest || $contentRequest->getUser() !== $user) {
            return null;
        }

        return $contentRequest;
    }
}