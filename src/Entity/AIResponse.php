<?php

namespace App\Entity;

use App\Repository\AIResponseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AIResponseRepository::class)]
class AIResponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private array $RawResponse = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $processedContent = null;

    #[ORM\Column(length: 100)]
    private ?string $modelUsed = null;

    #[ORM\Column]
    private ?int $latencyMs = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToOne(inversedBy: 'aIResponse', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?ContentRequest $contentRequest = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSizeBytes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRawResponse(): array
    {
        return $this->RawResponse;
    }

    public function setRawResponse(array $RawResponse): static
    {
        $this->RawResponse = $RawResponse;

        return $this;
    }

    public function getProcessedContent(): ?string
    {
        return $this->processedContent;
    }

    public function setProcessedContent(?string $processedContent): static
    {
        $this->processedContent = $processedContent;

        return $this;
    }

    public function getModelUsed(): ?string
    {
        return $this->modelUsed;
    }

    public function setModelUsed(string $modelUsed): static
    {
        $this->modelUsed = $modelUsed;

        return $this;
    }

    public function getLatencyMs(): ?int
    {
        return $this->latencyMs;
    }

    public function setLatencyMs(int $latencyMs): static
    {
        $this->latencyMs = $latencyMs;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getContentRequest(): ?ContentRequest
    {
        return $this->contentRequest;
    }

    public function setContentRequest(ContentRequest $contentRequest): static
    {
        $this->contentRequest = $contentRequest;

        return $this;
    }

    public function getImageSizeBytes(): ?int
    {
        return $this->imageSizeBytes;
    }

    public function setImageSizeBytes(?int $imageSizeBytes): static
    {
        $this->imageSizeBytes = $imageSizeBytes;

        return $this;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): static
    {
        $this->imageFilename = $imageFilename;

        return $this;
    }
}
