<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.anthropic.com/v1/messages';
    
    public function __construct(
        private HttpClientInterface $httpClient,
        string $anthropicApiKey
    ) {
        $this->apiKey = $anthropicApiKey;
    }

    public function analyzeImage(string $imagePath, string $prompt = 'Describe this image in detail.'): array
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);
        
        $startTime = microtime(true);

        $response = $this->httpClient->request('POST', $this->apiUrl, [
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
                                    'data' => $imageData
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]);

        $latencyMs = (int)((microtime(true) - $startTime) * 1000);
        $data = $response->toArray();

        return [
            'rawResponse' => $data,
            'processedContent' => $data['content'][0]['text'] ?? 'No response',
            'modelUsed' => $data['model'] ?? 'claude-haiku-4-5-20251001',
            'latencyMs' => $latencyMs,
        ];
    }
}