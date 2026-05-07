<?php

namespace App\Services;

use App\Exceptions\AiProviderException;
use App\Models\AiUsageLog;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionBlueprint;
use App\Models\QuestionOption;
use App\Models\QuestionStructure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiQuestionService
{
    public function __construct(
        private readonly AiPromptBuilder $promptBuilder,
        private readonly AiImageService $imageService
    ) {}

    /**
     * Generate questions for a single structure.
     * This is used for chunked generation to avoid server timeouts.
     */
    public function generateSingle(ExamSession $session, QuestionStructure $structure): int
    {
        $initialOffset = $session->questions()->count();
        $created = 0;
        
        $providersToTry = $this->getAvailableProviders();
        $generated = null;
        $usedProviderName = 'none';

        foreach ($providersToTry as $providerName) {
            Log::info("Step-generation: Attempting {$providerName} for structure {$structure->id}");
            $generated = $this->tryProvider($providerName, $session, collect([$structure]), $initialOffset);
            
            if ($generated !== null && $generated > 0) {
                $created = $generated;
                $usedProviderName = $providerName;
                break;
            }
        }

        // Fallback
        if ($created === 0) {
            Log::warning("Step-generation: Falling back to local for structure {$structure->id}");
            $created = $this->generateForStructureLocal($session, $structure, $initialOffset);
        }

        // Log usage (simplified for single step)
        AiUsageLog::create([
            'user_id' => $session->user_id,
            'exam_session_id' => $session->id,
            'provider' => $usedProviderName,
            'tokens_used' => 0, // Estimasi atau abaikan untuk step
            'status' => 'success',
            'metadata' => ['structure_id' => $structure->id, 'mode' => 'chunked'],
        ]);

        return $created;
    }

    public function generate(ExamSession $session): int
    {
        $session->loadMissing('structures');

        if ($session->structures->isEmpty()) {
            return 0;
        }

        // Kita gunakan sistem append (menambah), bukan menimpa (delete)
        // Jadi user bisa generate berkali-kali untuk menambah koleksi soal
        $initialOffset = $session->questions()->count();
        $created = 0;
        $usedProviderName = 'none';
        $usedProvider = false;

        // Group structures by question_type to minimize API requests
        $groups = $session->structures->groupBy('question_type');

        foreach ($groups as $questionType => $structures) {
            $groupGenerated = false;

            // Coba semua provider yang punya API key
            $providersToTry = $this->getAvailableProviders();

            foreach ($providersToTry as $providerName) {
                Log::info("Attempting to generate with {$providerName}", [
                    'exam_session_id' => $session->id,
                    'question_type' => $questionType,
                ]);

                $generated = $this->tryProvider($providerName, $session, $structures, $initialOffset + $created);
                
                if ($generated !== null && $generated > 0) {
                    $created += $generated;
                    $usedProvider = true;
                    $usedProviderName = $providerName;
                    $groupGenerated = true;
                    Log::info("✅ Successfully generated with {$providerName}", [
                        'questions_count' => $generated,
                        'exam_session_id' => $session->id,
                    ]);
                    break;
                }
                
                Log::info("{$providerName} failed, trying next provider");
            }

            // Final fallback to local-draft if all providers failed
            if (!$groupGenerated) {
                Log::warning("All AI providers failed for question type {$questionType}, falling back to local draft", [
                    'exam_session_id' => $session->id,
                ]);
                foreach ($structures as $structure) {
                    $created += $this->generateForStructureLocal($session, $structure, $initialOffset + $created);
                }
            }
        }

        AiUsageLog::create([
            'user_id' => $session->user_id,
            'exam_session_id' => $session->id,
            'provider' => $usedProviderName,
            'tokens_used' => Str::of($this->promptBuilder->build($session))->wordCount(),
            'status' => 'success',
            'metadata' => [
                'mode' => $usedProvider ? 'provider-used' : 'local-draft',
                'note' => $usedProvider
                    ? "{$usedProviderName} API used with fallback to local draft."
                    : 'Local deterministic generator is used (all providers unavailable or failed).',
            ],
        ]);

        $session->update(['status' => 'generated']);

        return $created;
    }

    private function getAvailableProviders(): array
    {
        $providers = [];
        
        if (filled(config('services.ai.gemini_key'))) {
            $providers[] = 'gemini';
        }
        if (filled(config('services.ai.groq_key'))) {
            $providers[] = 'groq';
        }
        if (filled(config('services.ai.deepseek_key'))) {
            $providers[] = 'deepseek';
        }
        if (filled(config('services.ai.openai_key'))) {
            $providers[] = 'openai';
        }
        if (filled(config('services.ai.mistral_key'))) {
            $providers[] = 'mistral';
        }
        
        return $providers;
    }

    private function tryProvider(string $provider, ExamSession $session, Collection $structures, int $offset): ?int
    {
        return match($provider) {
            'gemini' => $this->generateWithGemini($session, $structures, $offset),
            'groq' => $this->generateWithGroq($session, $structures, $offset),
            'deepseek' => $this->generateWithDeepSeek($session, $structures, $offset),
            'openai' => $this->generateWithOpenAI($session, $structures, $offset),
            'mistral' => $this->generateWithMistral($session, $structures, $offset),
            default => null,
        };
    }

    // ================== GEMINI ==================
    
    private function generateWithGemini(ExamSession $session, Collection $structures, int $offset): ?int
    {
        try {
            $payload = $this->requestGemini($session, $structures);
            if ($payload === null || !isset($payload['questions']) || !is_array($payload['questions'])) {
                return null;
            }
            return $this->persistProviderQuestions($session, $structures, $offset, $payload['questions']);
        } catch (Throwable $e) {
            Log::warning("Gemini error: " . $e->getMessage());
            return null;
        }
    }

    private function requestGemini(ExamSession $session, Collection $structures): ?array
    {
        $apiKey = config('services.ai.gemini_key');
        $model = $session->ai_model ?: config('services.ai.gemini_model', 'gemini-1.5-flash');
        $prompt = $this->buildPrompt($session, $structures);
        
        $modelsToTry = [
            $model,
            'gemini-3.1-flash-lite',
            'gemini-2.5-flash-lite',
            'gemini-3.0-flash',
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-1.5-flash',
        ];
        
        foreach ($modelsToTry as $modelName) {
            $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent?key={$apiKey}";
            
            $payload = [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.7,
                    'maxOutputTokens' => 8192,
                ],
            ];
            
            try {
                $response = Http::timeout(120)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
                    ->post($endpoint, $payload);
                
                if (!$response->successful()) {
                    $status = $response->status();
                    $errorBody = $response->json('error.message') ?? $response->body();
                    
                    // Rate limit atau quota habis → coba model lain atau provider lain
                    if ($status === 429 || Str::contains($errorBody, ['quota', 'rate limit'])) {
                        Log::info("Gemini {$modelName} rate limit/quota exceeded, trying next model");
                        continue;
                    }
                    
                    // Auth error → skip semua Gemini
                    if (in_array($status, [401, 403])) {
                        Log::warning("Gemini auth error, skipping all Gemini models");
                        return null;
                    }
                    
                    // Error lain → coba model berikutnya
                    continue;
                }
                
                $text = $response->json('candidates.0.content.parts.0.text');
                if (empty($text)) {
                    continue;
                }
                
                return $this->decodeJson($text);
                
            } catch (Throwable $e) {
                Log::warning("Gemini {$modelName} request error: " . $e->getMessage());
                continue;
            }
        }
        
        return null;
    }

    // ================== GROQ ==================
    
    private function generateWithGroq(ExamSession $session, Collection $structures, int $offset): ?int
    {
        try {
            $payload = $this->requestGroq($session, $structures);
            if ($payload === null || !isset($payload['questions']) || !is_array($payload['questions'])) {
                return null;
            }
            return $this->persistProviderQuestions($session, $structures, $offset, $payload['questions']);
        } catch (Throwable $e) {
            Log::warning("Groq error: " . $e->getMessage());
            return null;
        }
    }

    private function requestGroq(ExamSession $session, Collection $structures): ?array
    {
        $apiKey = config('services.ai.groq_key');
        $model = $session->ai_model ?: config('services.ai.groq_model', 'llama-3.3-70b-versatile');
        $prompt = $this->buildPrompt($session, $structures);
        
        $modelsToTry = [
            $model,
            'llama-3.3-70b-versatile',
            'llama-3.1-70b-versatile',
            'llama3-70b-8192',
            'mixtral-8x7b-32768',
            'gemma2-9b-it',
        ];
        
        foreach ($modelsToTry as $modelName) {
            try {
                $response = Http::timeout(120)
                    ->withHeaders([
                        'Authorization' => "Bearer {$apiKey}",
                        'Content-Type' => 'application/json',
                    ])
                    ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
                    ->post('https://api.groq.com/openai/v1/chat/completions', [
                        'model' => $modelName,
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                        'temperature' => 0.7,
                        'max_tokens' => 8192,
                        'response_format' => ['type' => 'json_object'],
                    ]);
                
                if (!$response->successful()) {
                    $status = $response->status();
                    $errorBody = $response->json('error.message') ?? $response->body();
                    
                    if ($status === 429 || Str::contains($errorBody, ['quota', 'rate limit', 'rate_limit'])) {
                        Log::info("Groq {$modelName} rate limit/quota exceeded, trying next model");
                        continue;
                    }
                    
                    if (in_array($status, [401, 403])) {
                        Log::warning("Groq auth error, skipping all Groq models");
                        return null;
                    }
                    
                    if ($status === 404) {
                        Log::info("Groq model {$modelName} not found, trying next model");
                        continue;
                    }
                    
                    continue;
                }
                
                $text = $response->json('choices.0.message.content');
                if (empty($text)) {
                    continue;
                }
                
                return $this->decodeJson($text);
                
            } catch (Throwable $e) {
                Log::warning("Groq {$modelName} request error: " . $e->getMessage());
                continue;
            }
        }
        
        return null;
    }

    // ================== DEEPSEEK ==================
    
    private function generateWithDeepSeek(ExamSession $session, Collection $structures, int $offset): ?int
    {
        try {
            $payload = $this->requestDeepSeek($session, $structures);
            if ($payload === null || !isset($payload['questions']) || !is_array($payload['questions'])) {
                return null;
            }
            return $this->persistProviderQuestions($session, $structures, $offset, $payload['questions']);
        } catch (Throwable $e) {
            Log::warning("DeepSeek error: " . $e->getMessage());
            return null;
        }
    }

    private function requestDeepSeek(ExamSession $session, Collection $structures): ?array
    {
        $apiKey = config('services.ai.deepseek_key');
        $model = $session->ai_model ?: config('services.ai.deepseek_model', 'deepseek-chat');
        $prompt = $this->buildPrompt($session, $structures);
        
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
                ->post('https://api.deepseek.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens' => 8192,
                    'response_format' => ['type' => 'json_object'],
                ]);
            
            if (!$response->successful()) {
                $status = $response->status();
                $errorBody = $response->json('error.message') ?? $response->body();
                
                if ($status === 429 || Str::contains($errorBody, ['quota', 'rate limit'])) {
                    Log::info("DeepSeek rate limit/quota exceeded");
                    return null;
                }
                
                if (in_array($status, [401, 403])) {
                    Log::warning("DeepSeek auth error");
                    return null;
                }
                
                return null;
            }
            
            $text = $response->json('choices.0.message.content');
            if (empty($text)) {
                return null;
            }
            
            return $this->decodeJson($text);
            
        } catch (Throwable $e) {
            Log::warning("DeepSeek request error: " . $e->getMessage());
            return null;
        }
    }

    // ================== OPENAI ==================
    
    private function generateWithOpenAI(ExamSession $session, Collection $structures, int $offset): ?int
    {
        try {
            $payload = $this->requestOpenAI($session, $structures);
            if ($payload === null || !isset($payload['questions']) || !is_array($payload['questions'])) {
                return null;
            }
            return $this->persistProviderQuestions($session, $structures, $offset, $payload['questions']);
        } catch (Throwable $e) {
            Log::warning("OpenAI error: " . $e->getMessage());
            return null;
        }
    }

    private function requestOpenAI(ExamSession $session, Collection $structures): ?array
    {
        $apiKey = config('services.ai.openai_key');
        $model = $session->ai_model ?: config('services.ai.openai_model', 'gpt-4o-mini');
        $prompt = $this->buildPrompt($session, $structures);
        
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens' => 8192,
                    'response_format' => ['type' => 'json_object'],
                ]);
            
            if (!$response->successful()) {
                $status = $response->status();
                $errorBody = $response->json('error.message') ?? $response->body();
                
                if ($status === 429 || Str::contains($errorBody, ['quota', 'rate limit'])) {
                    Log::info("OpenAI rate limit/quota exceeded");
                    return null;
                }
                
                if (in_array($status, [401, 403])) {
                    Log::warning("OpenAI auth error");
                    return null;
                }
                
                return null;
            }
            
            $text = $response->json('choices.0.message.content');
            if (empty($text)) {
                return null;
            }
            
            return $this->decodeJson($text);
            
        } catch (Throwable $e) {
            Log::warning("OpenAI request error: " . $e->getMessage());
            return null;
        }
    }

    // ================== MISTRAL ==================
    
    private function generateWithMistral(ExamSession $session, Collection $structures, int $offset): ?int
    {
        try {
            $payload = $this->requestMistral($session, $structures);
            if ($payload === null || !isset($payload['questions']) || !is_array($payload['questions'])) {
                return null;
            }
            return $this->persistProviderQuestions($session, $structures, $offset, $payload['questions']);
        } catch (Throwable $e) {
            Log::warning("Mistral error: " . $e->getMessage());
            return null;
        }
    }

    private function requestMistral(ExamSession $session, Collection $structures): ?array
    {
        $apiKey = config('services.ai.mistral_key');
        $model = $session->ai_model ?: config('services.ai.mistral_model', 'mistral-large-latest');
        $prompt = $this->buildPrompt($session, $structures);
        
        try {
            $response = Http::timeout(120)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->when(app()->environment('local'), fn($http) => $http->withoutVerifying())
                ->post('https://api.mistral.ai/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [['role' => 'user', 'content' => $prompt]],
                    'temperature' => 0.7,
                    'max_tokens' => 8192,
                    'response_format' => ['type' => 'json_object'],
                ]);
            
            if (!$response->successful()) {
                $status = $response->status();
                $errorBody = $response->json('message') ?? $response->body();
                
                if ($status === 429 || Str::contains($errorBody, ['quota', 'rate limit'])) {
                    Log::info("Mistral rate limit/quota exceeded");
                    return null;
                }
                
                if (in_array($status, [401, 403])) {
                    Log::warning("Mistral auth error");
                    return null;
                }
                
                return null;
            }
            
            $text = $response->json('choices.0.message.content');
            if (empty($text)) {
                return null;
            }
            
            return $this->decodeJson($text);
            
        } catch (Throwable $e) {
            Log::warning("Mistral request error: " . $e->getMessage());
            return null;
        }
    }

    // ================== HELPER METHODS ==================

    private function buildPrompt(ExamSession $session, Collection $structures): string
    {
        $questionType = $structures->first()->question_type;
        $total = $structures->sum('total_questions');
        $easy = $structures->sum('easy_count');
        $medium = $structures->sum('medium_count');
        $hard = $structures->sum('hard_count');
        $optionCount = $structures->max('option_count');

        $levels = $structures->pluck('cognitive_levels')
            ->flatten()
            ->unique()
            ->filter()
            ->values()
            ->implode(', ');

        if (empty($levels)) {
            $levels = 'C1 Mengingat, C2 Memahami, C3 Menerapkan';
        }

        $subtopic = $session->subtopic ?? '-';

        $visual = collect([
            'has_question_image' => 'Gambar/Ilustrasi soal',
            'has_diagram' => 'Diagram',
            'has_table' => 'Tabel data',
        ])->filter(fn($label, $key) => $structures->contains($key, true))
          ->values()
          ->implode(', ');

        if (empty($visual)) {
            $visual = 'Hanya teks (tanpa media khusus)';
        }

        return <<<PROMPT
Buat soal asesmen berbahasa Indonesia dalam format JSON murni.

Identitas sesi:
- Mata pelajaran: {$session->subject}
- Materi: {$session->topic}
- Batasan Teori: {$subtopic} (PENTING: Hanya buat soal dalam cakupan teori ini)
- Jenjang/Kelas: {$session->education_level} / {$session->class_level}

Struktur yang harus dipenuhi:
- question_type: {$questionType}
- jumlah total: {$total}
- distribusi: Mudah {$easy}, Sedang {$medium}, Sulit {$hard}
- option_count (jika relevan): {$optionCount}
- level kognitif yang boleh: {$levels}

Aturan Variasi Media (SANGAT KETAT):
- Media yang diperbolehkan hanya: {$visual}
- JANGAN PERNAH memberikan media yang tidak ada di daftar atas.
- JANGAN sertakan gambar pada opsi jawaban (Opsi hanya boleh teks).
- Distribusikan secara acak: satu soal maksimal hanya boleh memiliki SATU jenis media dari daftar di atas.
- Jika opsi media (Gambar/Diagram/Tabel) aktif, WAJIB sertakan media tersebut pada minimal satu soal.
- Gunakan media secara selektif: targetkan total soal yang memiliki media adalah sekitar 20% (1/5) dari jumlah seluruh soal. Sisanya (80%) harus berupa teks murni tanpa media.

Aturan Format Konten (WAJIB DIPATUHI):
- TABEL: Jika soal memerlukan data terstruktur (seperti data pengamatan, hasil eksperimen, atau perbandingan), WAJIB gunakan format Markdown Table standar GFM.
  Contoh:
  | Header 1 | Header 2 |
  |----------|----------|
  | Data A   | Data B   |
  JANGAN gunakan format HTML atau teks biasa. Pastikan ada baris kosong sebelum dan sesudah tabel.
- GAMBAR/DIAGRAM: Berikan deskripsi visual dengan format: [GAMBAR: deskripsi detail] atau [DIAGRAM: deskripsi detail].
- MATEMATIKA/SAINS: WAJIB gunakan format LaTeX dengan pembungkus \$...\$ untuk inline dan \$\$...\$\$ untuk block. Pastikan simbol matematika kompleks berada di dalam blok LaTeX.
- FORMATTING: Gunakan Markdown standar untuk penebalan (**teks**) atau miring (*teks*).
- ANTI-DUPLIKASI: 
  1. JANGAN sertakan pilihan jawaban atau instruksi seperti "(Benar/Salah)" atau "(A/B/C/D)" di dalam `question_text`.
  2. JANGAN membuat soal yang sama atau sangat mirip dalam satu paket.
  3. Pastikan kunci jawaban sinkron dengan opsi yang diberikan.

Kembalikan objek JSON:
{
  "questions": [
    {
      "question_text": "...",
      "question_type": "...",
      "difficulty": "Mudah|Sedang|Sulit",
      "cognitive_level": "...",
      "options": [{"option_label":"A","option_text":"..."}],
      "answer_key": "...",
      "explanation": "...",
      "blueprint": {
        "competency": "...",
        "indicator": "...",
        "material": "...",
        "cognitive_dimension": "...",
        "question_type": "..."
      }
    }
  ]
}

Aturan:
- Output harus JSON murni, tanpa pembungkus markdown ```json.
- Jangan berikan penjelasan di luar JSON.
- Pastikan semua simbol matematika dan tabel ter-render dengan format yang diminta.
PROMPT;
    }

    private function decodeJson(string $text): ?array
    {
        $clean = trim($text);
        
        // Remove markdown code fences
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```\s*$/', '', $clean) ?? $clean;
        
        // Try direct decode
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        
        // Try to extract JSON object
        if (preg_match('/\{[\s\S]*"questions"\s*:\s*\[[\s\S]*\]\s*\}/', $clean, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        // Try brace matching
        $firstBrace = strpos($clean, '{');
        $lastBrace = strrpos($clean, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $jsonCandidate = substr($clean, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($jsonCandidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        
        Log::warning('Failed to extract JSON from AI response', [
            'length' => strlen($text),
            'preview' => Str::limit($text, 500),
        ]);
        
        return null;
    }

    // ================== LOCAL DRAFT ==================

    private function generateForStructureLocal(ExamSession $session, QuestionStructure $structure, int $offset): int
    {
        $created = 0;
        $distributions = [
            'Mudah' => $structure->easy_count,
            'Sedang' => $structure->medium_count,
            'Sulit' => $structure->hard_count,
        ];

        // Pastikan koneksi database tetap hidup
        DB::reconnect();

        foreach ($distributions as $difficulty => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $created++;
                $sequence = $offset + $created;
                $cognitive = $this->pickCognitiveLevel($structure, $sequence);
                
                DB::transaction(function () use ($session, $structure, $difficulty, $cognitive, $sequence) {
                    $question = Question::create([
                        'exam_session_id' => $session->id,
                        'question_structure_id' => $structure->id,
                        'question_type' => $structure->question_type,
                        'question_text' => $this->questionText($session, $structure, $difficulty, $cognitive, $sequence),
                        'explanation' => $this->explanationText($session, $difficulty, $cognitive),
                        'difficulty' => $difficulty,
                        'cognitive_level' => $cognitive,
                        'answer_key' => $this->answerKey($structure, $sequence),
                        'sort_order' => $sequence,
                    ]);

                    $this->createOptions($question, $structure, $sequence);
                    $this->createBlueprint($question, $session, $structure, $cognitive);
                });
            }
        }

        return $created;
    }

    private function pickCognitiveLevel(QuestionStructure $structure, int $sequence): string
    {
        $levels = $structure->cognitive_levels ?: ['C1 Mengingat', 'C2 Memahami', 'C3 Menerapkan'];
        return $levels[($sequence - 1) % count($levels)];
    }

    private function questionText(ExamSession $session, QuestionStructure $structure, string $difficulty, string $cognitive, int $sequence): string
    {
        $availableMedia = collect([
            'has_question_image' => 'sertakan konteks gambar',
            'has_option_image' => 'sertakan gambar pada opsi jawaban',
            'has_diagram' => 'gunakan diagram sederhana',
            'has_table' => 'gunakan data tabel',
        ])->filter(fn($label, $key) => $structure->{$key});

        $visualInstruction = '';
        
        if ($availableMedia->isNotEmpty() && rand(1, 100) <= 70) {
            $picked = $availableMedia->random();
            $visualInstruction = " Pertimbangkan instruksi visual: {$picked}.";
        }

        return "Soal {$sequence} ({$difficulty}, {$cognitive}). Pada materi {$session->topic}"
            . ($session->subtopic ? " submateri {$session->subtopic}" : '')
            . ", susun jawaban paling tepat untuk bentuk {$structure->question_type}.{$visualInstruction}";
    }

    private function explanationText(ExamSession $session, string $difficulty, string $cognitive): string
    {
        return "Pembahasan menekankan keterkaitan konsep {$session->topic} dengan level {$cognitive} dan tingkat {$difficulty}.";
    }

    private function answerKey(QuestionStructure $structure, int $sequence): string
    {
        if (in_array($structure->question_type, ['Essay', 'Studi Kasus'], true)) {
            return 'Rubrik';
        }
        if ($structure->question_type === 'Isian Singkat') {
            return 'Jawaban inti';
        }
        if ($structure->question_type === 'Benar Salah') {
            return $sequence % 2 === 0 ? 'Benar' : 'Salah';
        }
        $labels = range('A', 'Z');
        return $labels[($sequence - 1) % max(1, $structure->option_count)];
    }

    private function createOptions(Question $question, QuestionStructure $structure, int $sequence): void
    {
        if (in_array($structure->question_type, ['Essay', 'Studi Kasus', 'Isian Singkat'], true)) {
            return;
        }
        if ($structure->question_type === 'Benar Salah') {
            foreach (['Benar', 'Salah'] as $index => $text) {
                $label = $index === 0 ? 'A' : 'B';
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_label' => $label,
                    'option_text' => $text,
                    'is_correct' => $question->answer_key === $text,
                    'sort_order' => $index + 1,
                ]);
            }
            return;
        }
        foreach (array_slice(range('A', 'Z'), 0, $structure->option_count) as $index => $label) {
            QuestionOption::create([
                'question_id' => $question->id,
                'option_label' => $label,
                'option_text' => "Opsi {$label} untuk soal {$sequence}",
                'is_correct' => $question->answer_key === $label,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function createBlueprint(Question $question, ExamSession $session, QuestionStructure $structure, string $cognitive): void
    {
        QuestionBlueprint::create([
            'question_id' => $question->id,
            'competency' => "Menguasai konsep {$session->subject} pada topik {$session->topic}",
            'indicator' => "Peserta didik mampu menjawab soal {$structure->question_type} level {$cognitive}",
            'material' => $session->subtopic ?: $session->topic,
            'cognitive_dimension' => $cognitive,
            'question_type' => $structure->question_type,
        ]);
    }

    // ================== PERSISTENCE ==================

    private function persistProviderQuestions(ExamSession $session, Collection $structures, int $offset, array $questions): int
    {
        // Sangat penting: AI request memakan waktu lama, koneksi DB bisa timeout.
        // Kita paksa reconnect sebelum mulai menulis ke database.
        DB::reconnect();

        $registry = $structures->mapWithKeys(fn($s) => [
            $s->id => [
                'model' => $s,
                'targets' => [
                    'Mudah' => (int) $s->easy_count,
                    'Sedang' => (int) $s->medium_count,
                    'Sulit' => (int) $s->hard_count,
                ],
                'current' => ['Mudah' => 0, 'Sedang' => 0, 'Sulit' => 0],
            ],
        ])->toArray();

        $groupTargets = [
            'Mudah' => $structures->sum('easy_count'),
            'Sedang' => $structures->sum('medium_count'),
            'Sulit' => $structures->sum('hard_count'),
        ];
        $groupCurrent = ['Mudah' => 0, 'Sedang' => 0, 'Sulit' => 0];

        $created = 0;
        foreach ($questions as $item) {
            if (!is_array($item)) continue;

            $difficulty = $this->normalizeDifficulty((string) ($item['difficulty'] ?? ''), $groupTargets, $groupCurrent);
            if ($difficulty === null) continue;

            $targetStructureId = null;
            foreach ($registry as $id => $data) {
                if ($data['current'][$difficulty] < $data['targets'][$difficulty]) {
                    $targetStructureId = $id;
                    break;
                }
            }
            if ($targetStructureId === null) continue;

            $registry[$targetStructureId]['current'][$difficulty]++;
            $groupCurrent[$difficulty]++;
            $created++;

            $structure = $registry[$targetStructureId]['model'];
            $sequence = $offset + $created;
            $cognitive = $this->normalizeCognitiveLevel((string) ($item['cognitive_level'] ?? ''), $structure, $sequence);
            $answerKey = $this->normalizeAnswerKey($structure, $sequence, (string) ($item['answer_key'] ?? ''));

            // Memproses Generate Gambar secara otomatis saat AI membuat soal
            $rawText = (string) ($item['question_text'] ?? $this->questionText($session, $structure, $difficulty, $cognitive, $sequence));
            $questionImage = null;

            if (preg_match('/\[(?:GAMBAR|DIAGRAM):\s*(.*?)\]/i', $rawText, $matches)) {
                $description = $matches[1];
                $isDiagram = Str::contains(strtoupper($matches[0]), 'DIAGRAM');
                
                // Panggil layanan DALL-E 3 untuk membuat & menyimpan gambar
                // Catatan: Ini dilakukan di luar transaksi DB karena lambat
                try {
                    $questionImage = $this->imageService->generateAndSave($description, $isDiagram);
                } catch (Throwable $e) {
                    Log::warning("Image generation failed for question {$sequence}: " . $e->getMessage());
                }
                
                // Hapus tag [GAMBAR/DIAGRAM] karena kita sudah punya file gambarnya
                $rawText = trim(preg_replace('/\[(?:GAMBAR|DIAGRAM):\s*(.*?)\]/i', '', $rawText));
            }

            // Gunakan transaksi kecil untuk setiap satu soal agar data konsisten (soal + opsi + blueprint)
            DB::transaction(function () use ($session, $structure, $rawText, $questionImage, $item, $difficulty, $cognitive, $answerKey, $sequence) {
                $question = Question::create([
                    'exam_session_id' => $session->id,
                    'question_structure_id' => $structure->id,
                    'question_type' => $structure->question_type,
                    'question_text' => $rawText,
                    'question_image' => $questionImage,
                    'explanation' => (string) ($item['explanation'] ?? $this->explanationText($session, $difficulty, $cognitive)),
                    'difficulty' => $difficulty,
                    'cognitive_level' => $cognitive,
                    'answer_key' => $answerKey,
                    'sort_order' => $sequence,
                ]);

                $this->createProviderOptions($question, $structure, $sequence, $item['options'] ?? null);
                $this->createProviderBlueprint($question, $session, $structure, $cognitive, $item['blueprint'] ?? null);
            });
        }

        return $created;
    }

    private function normalizeDifficulty(string $difficulty, array $targetCounts, array $currentCounts): ?string
    {
        $map = [
            'mudah' => 'Mudah', 'easy' => 'Mudah',
            'sedang' => 'Sedang', 'medium' => 'Sedang',
            'sulit' => 'Sulit', 'hard' => 'Sulit',
        ];
        $normalized = $map[Str::lower(trim($difficulty))] ?? null;
        if ($normalized === null || $currentCounts[$normalized] >= $targetCounts[$normalized]) {
            return null;
        }
        return $normalized;
    }

    private function normalizeCognitiveLevel(string $cognitiveLevel, QuestionStructure $structure, int $sequence): string
    {
        return filled($cognitiveLevel) ? $cognitiveLevel : $this->pickCognitiveLevel($structure, $sequence);
    }

    private function normalizeAnswerKey(QuestionStructure $structure, int $sequence, string $answerKey): string
    {
        if (!filled($answerKey)) {
            return $this->answerKey($structure, $sequence);
        }
        if ($structure->question_type === 'Benar Salah') {
            $normalized = Str::lower(trim($answerKey));
            return $normalized === 'true' || $normalized === 'benar' ? 'Benar' : 'Salah';
        }
        return trim($answerKey);
    }

    private function createProviderOptions(Question $question, QuestionStructure $structure, int $sequence, mixed $options): void
    {
        if (in_array($structure->question_type, ['Essay', 'Studi Kasus', 'Isian Singkat'], true)) {
            return;
        }
        if (!is_array($options)) {
            $this->createOptions($question, $structure, $sequence);
            return;
        }
        if ($structure->question_type === 'Benar Salah') {
            foreach (['Benar', 'Salah'] as $index => $text) {
                $label = $index === 0 ? 'A' : 'B';
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_label' => $label,
                    'option_text' => $text,
                    'is_correct' => $question->answer_key === $text,
                    'sort_order' => $index + 1,
                ]);
            }
            return;
        }
        $labels = array_slice(range('A', 'Z'), 0, max(1, (int) $structure->option_count));
        foreach ($labels as $index => $label) {
            $option = $options[$index] ?? [];
            $optionText = is_array($option) ? (string) ($option['option_text'] ?? '') : '';
            $optionLabel = is_array($option) ? (string) ($option['option_label'] ?? $label) : $label;
            $normalizedLabel = strtoupper(trim($optionLabel));
            $normalizedLabel = in_array($normalizedLabel, $labels, true) ? $normalizedLabel : $label;
            QuestionOption::create([
                'question_id' => $question->id,
                'option_label' => $normalizedLabel,
                'option_text' => $optionText !== '' ? $optionText : "Opsi {$label} untuk soal {$sequence}",
                'is_correct' => $question->answer_key === $normalizedLabel,
                'sort_order' => $index + 1,
            ]);
        }
    }

    private function createProviderBlueprint(Question $question, ExamSession $session, QuestionStructure $structure, string $cognitive, mixed $blueprint): void
    {
        if (!is_array($blueprint)) {
            $this->createBlueprint($question, $session, $structure, $cognitive);
            return;
        }
        QuestionBlueprint::create([
            'question_id' => $question->id,
            'competency' => (string) ($blueprint['competency'] ?? "Menguasai konsep {$session->subject} pada topik {$session->topic}"),
            'indicator' => (string) ($blueprint['indicator'] ?? "Peserta didik mampu menjawab soal {$structure->question_type} level {$cognitive}"),
            'material' => (string) ($blueprint['material'] ?? ($session->subtopic ?: $session->topic)),
            'cognitive_dimension' => (string) ($blueprint['cognitive_dimension'] ?? $cognitive),
            'question_type' => (string) ($blueprint['question_type'] ?? $structure->question_type),
        ]);
    }
}