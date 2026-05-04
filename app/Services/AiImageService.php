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
        // Token bisa didapat gratis di: huggingface.co/settings/tokens
        $apiKey = env('HUGGINGFACE_API_KEY');
        
        if (empty($apiKey)) {
            Log::warning('HUGGINGFACE_API_KEY is missing. Silakan isi di .env');
            return null;
        }

        $style = "professional educational style, clear lines, high resolution, minimalist white background, flat vector illustration.";
        $context = $isDiagram ? "detailed schematic educational diagram of " : "clear educational illustration of ";
        $fullPrompt = $context . $prompt . ", " . $style;

        // Model FLUX.1 sangat pintar memahami teks dan konteks (setara Midjourney/DALL-E)
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
                Log::error('Hugging Face Image Generation failed: ' . $response->body());
                return null;
            }

            // Hugging Face mengembalikan file raw binary gambar (JPEG/PNG) langsung
            $imageContent = $response->body();
            
            if (empty($imageContent)) {
                return null;
            }

            // Simpan ke storage lokal
            $filename = 'questions/' . Str::uuid() . '.jpg';
            Storage::disk('public')->put($filename, $imageContent);

            return $filename;
            
        } catch (\Throwable $e) {
            Log::error('AiImageService Error: ' . $e->getMessage());
            return null;
        }
    }
}
