<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class FaceAIService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.face_ai.base_url');
        $this->apiKey  = (string) config('services.face_ai.key');
        $this->timeout = (int) config('services.face_ai.timeout');
    }

    /**
     * Check if EAJ-Face-AI Django microservice is online
     */
    public function checkHealth(): bool
    {
        try {
            $response = Http::timeout(2)->get("{$this->baseUrl}/health/");
            return $response->successful() && ($response->json('status') === 'ok');
        } catch (Exception $e) {
            Log::error("FaceAI Service error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Extract 512-dimensional vector embedding from image base64
     */
    public function extractEmbedding(string $base64Image): array
    {
        $response = Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept'    => 'application/json',
        ])
        ->timeout($this->timeout)
        ->post("{$this->baseUrl}/register/", [
            'image' => $base64Image,
        ]);

        if ($response->failed()) {
            $error = $response->json('detail') ?? $response->json('error') ?? 'Face AI registration failed.';
            throw new Exception($error, $response->status());
        }

        return $response->json();
    }
}
