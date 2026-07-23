<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $aiApiLogger,
        string $anthropicApiKey
    ) {
        $this->apiKey = $anthropicApiKey;
    }

    public function analyzeImage(string $imagePath, string $prompt = 'Describe this image in detail.'): array
    {
        $imageContent = @file_get_contents($imagePath);

        if ($imageContent === false) {
            $this->aiApiLogger->error('Image file could not be read', [
                'path' => $imagePath,
            ]);

            throw new \RuntimeException(sprintf('Cannot read image file: %s', $imagePath));
        }

        $imageData = base64_encode($imageContent);
        $mimeType = mime_content_type($imagePath);

        $this->aiApiLogger->info('Anthropic API call started', [
            'path' => $imagePath,
            'mimeType' => $mimeType,
            'imageSizeBytes' => strlen($imageContent),
        ]);

        $startTime = microtime(true);

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'timeout' => 15,
                'max_duration' => 60,
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => 'claude-haiku-4-5-20251001',
                    'max_tokens' => 1024,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                ['type' => 'text', 'text' => $prompt],
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $mimeType,
                                        'data' => $imageData,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray();
        } catch (\Throwable $e) {
            $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->aiApiLogger->error('Anthropic API call failed', [
                'path' => $imagePath,
                'latencyMs' => $latencyMs,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $latencyMs = (int) ((microtime(true) - $startTime) * 1000);

        $this->aiApiLogger->info('Anthropic API call succeeded', [
            'model' => $data['model'] ?? 'unknown',
            'latencyMs' => $latencyMs,
            'inputTokens' => $data['usage']['input_tokens'] ?? null,
            'outputTokens' => $data['usage']['output_tokens'] ?? null,
        ]);

        return [
            'rawResponse' => $data,
            'processedContent' => $data['content'][0]['text'] ?? 'No response',
            'modelUsed' => $data['model'] ?? 'claude-haiku-4-5-20251001',
            'latencyMs' => $latencyMs,
        ];
    }
}