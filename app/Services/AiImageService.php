<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AiImageService
{
    /**
     * Generate an image using OpenAI DALL-E 3 and save it to local storage.
     *
     * @param string $prompt
     * @param bool $isDiagram
     * @return string|null Returns the local storage path if successful, null otherwise.
     */
    public function generateAndSave(string $prompt, bool $isDiagram = false): ?string
    {
        // Menggunakan Hugging Face Inference API (Gratis, tapi butuh API Key/Token)
        $apiKey = env('HUGGINGFACE_API_KEY');
        
        if (empty($apiKey)) {
            throw new \App\Exceptions\ImageGenerationException('HUGGINGFACE_API_KEY is missing. Silakan isi di .env');
        }

        $style = "professional educational style, clear lines, high resolution, minimalist white background, flat vector illustration.";
        $context = $isDiagram ? "detailed schematic educational diagram of " : "clear educational illustration of ";
        $fullPrompt = $context . $prompt . ", " . $style;

        $model = 'black-forest-labs/FLUX.1-schnell';
        $url = "https://api-inference.huggingface.co/models/{$model}";

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
                ->post($url, [
                    'inputs' => Str::limit($fullPrompt, 900),
                ]);

            if (!$response->successful()) {
                throw new \App\Exceptions\ImageGenerationException('API Hugging Face Gagal: ' . ($response->json('error') ?? $response->body()));
            }

            $imageContent = $response->body();
            
            if (empty($imageContent)) {
                throw new \App\Exceptions\ImageGenerationException('API mengembalikan konten kosong.');
            }

            // Simpan ke storage lokal
            $filename = 'questions/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, $imageContent);

            return $filename;
            
        } catch (\Throwable $e) {
            if ($e instanceof \App\Exceptions\ImageGenerationException) {
                throw $e;
            }
            throw new \App\Exceptions\ImageGenerationException('Terjadi kesalahan saat memproses gambar: ' . $e->getMessage(), $e);
        }
    }
}
