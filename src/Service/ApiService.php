<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ApiService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct(
        private HttpClientInterface $httpClient,
        string $geminiApiKey
    ) {
        $this->apiKey = $geminiApiKey;
    }

    public function analyzeImage(string $imagePath, string $prompt = 'Describe this image in detail.'): array
    {
        $imageData = base64_encode(file_get_contents($imagePath));
        $mimeType = mime_content_type($imagePath);

        $startTime = microtime(true);

        $response = $this->httpClient->request('POST', $this->apiUrl . '?key=' . $this->apiKey, [
            'json' => [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
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
            'processedContent' => $data['candidates'][0]['content']['parts'][0]['text'] ?? 'No response',
            'modelUsed' => 'gemini-1.5-flash',
            'latencyMs' => $latencyMs,
        ];
    }
}