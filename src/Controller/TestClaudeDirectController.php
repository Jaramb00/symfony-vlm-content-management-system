<?php

namespace App\Controller;

use App\Repository\ContentRequestRepository;
use App\Service\ApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestClaudeDirectController extends AbstractController
{
    #[Route('/test-claude-direct/{id}', name: 'test_claude_direct')]
    public function test(int $id, ContentRequestRepository $repo, ApiService $apiService): Response
    {
        // Pronađi ContentRequest
        $contentRequest = $repo->find($id);
        
        if (!$contentRequest) {
            return $this->json(['error' => "ContentRequest $id not found"]);
        }
        
        // Dohvati sliku
        $mediaFiles = $contentRequest->getMediaFiles();
        if ($mediaFiles->isEmpty()) {
            return $this->json(['error' => 'No media files for this request']);
        }
        
        $mediaFile = $mediaFiles->first();
        $imagePath = $mediaFile->getPath();
        
        // Provjeri putanju
        $debug = [
            'content_request_id' => $id,
            'raw_path_from_db' => $imagePath,
            'file_exists_raw' => file_exists($imagePath),
            'absolute_path_attempt1' => getcwd() . '/' . $imagePath,
            'file_exists_attempt1' => file_exists(getcwd() . '/' . $imagePath),
            'absolute_path_attempt2' => __DIR__ . '/../../public/' . $imagePath,
            'file_exists_attempt2' => file_exists(__DIR__ . '/../../public/' . $imagePath),
        ];
        
        // Pokušaj API poziv
        $possiblePaths = [
            $imagePath,
            getcwd() . '/' . $imagePath,
            __DIR__ . '/../../public/' . $imagePath,
            __DIR__ . '/../../' . $imagePath,
        ];
        
        $apiResult = null;
        $apiError = null;
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $debug['working_path'] = $path;
                try {
                    $start = microtime(true);
                    $result = $apiService->analyzeImage($path, 'Describe this image in detail.');
                    $latency = round((microtime(true) - $start) * 1000);
                    
                    $apiResult = [
                        'success' => true,
                        'path_used' => $path,
                        'model' => $result['modelUsed'],
                        'latency_ms' => $latency,
                        'response_preview' => substr($result['processedContent'], 0, 300),
                    ];
                    break;
                } catch (\Exception $e) {
                    $apiError = $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine();
                }
            }
        }
        
        if (!$apiResult && !$apiError) {
            $apiResult = ['success' => false, 'error' => 'No valid image path found'];
        } elseif ($apiError && !$apiResult) {
            $apiResult = ['success' => false, 'error' => $apiError];
        }
        
        return $this->json([
            'debug' => $debug,
            'api_result' => $apiResult,
            'content_request_status' => $contentRequest->getStatus(),
        ]);
    }
}