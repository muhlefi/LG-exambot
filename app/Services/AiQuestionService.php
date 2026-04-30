<?php

namespace App\Services;

use App\Exceptions\AiProviderException;
use App\Models\AiUsageLog;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\QuestionBlueprint;
use App\Models\QuestionOption;
use App\Models\QuestionStructure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class AiQuestionService
{
    public function __construct(private readonly AiPromptBuilder $promptBuilder) {}

    public function generate(ExamSession $session): int
    {
        $session->loadMissing('structures');

        if ($session->structures->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($session): int {
            $session->questions()->delete();

            $created = 0;
            $provider = (string) config('services.ai.default_provider', 'local-draft');
            $usedProvider = false;

            // Group structures by question_type to minimize API requests
            $groups = $session->structures->groupBy('question_type');

            foreach ($groups as $questionType => $structures) {
                if ($this->shouldUseGemini($provider)) {
                    $generated = $this->generateForGroupWithGemini($session, $structures, $created);
                    if ($generated !== null) {
                        $created += $generated;
                        $usedProvider = true;

                        continue;
                    }
                }

                foreach ($structures as $structure) {
                    $created += $this->generateForStructureLocal($session, $structure, $created);
                }
            }

            AiUsageLog::create([
                'user_id' => $session->user_id,
                'exam_session_id' => $session->id,
                'provider' => $provider,
                'tokens_used' => Str::of($this->promptBuilder->build($session))->wordCount(),
                'status' => 'success',
                'metadata' => [
                    'mode' => $usedProvider ? 'provider-used' : 'local-draft',
                    'note' => $usedProvider
                        ? 'Gemini API used with per-structure fallback to local draft.'
                        : 'Local deterministic generator is used (provider unavailable or response invalid).',
                ],
            ]);

            $session->update(['status' => 'generated']);

            return $created;
        });
    }

    private function shouldUseGemini(string $provider): bool
    {
        return Str::lower(trim($provider)) === 'gemini' && filled(config('services.ai.gemini_key'));
    }

    private function generateForGroupWithGemini(ExamSession $session, \Illuminate\Support\Collection $structures, int $offset): ?int
    {
        try {
            $payload = $this->requestGeminiQuestions($session, $structures);
            if (! isset($payload['questions']) || ! is_array($payload['questions'])) {
                return null;
            }

            return $this->persistProviderQuestions($session, $structures, $offset, $payload['questions']);
        } catch (Throwable $exception) {
            if ($this->shouldAbortProviderFallback($exception)) {
                throw new AiProviderException($this->providerErrorMessage($exception), $exception);
            }

            Log::warning('Gemini generation failed, falling back to local draft.', [
                'exam_session_id' => $session->id,
                'question_structure_ids' => $structures->pluck('id')->all(),
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function requestGeminiQuestions(ExamSession $session, \Illuminate\Support\Collection $structures): array
    {
        $configuredModel = (string) config('services.ai.gemini_model', 'gemini-1.5-flash');
        $apiKey = (string) config('services.ai.gemini_key');
        $models = $this->candidateGeminiModels($configuredModel);
        $lastError = 'Gemini request failed.';
        $prompt = $this->buildGeminiPrompt($session, $structures);
        $maxRetries = 3;

        foreach ($models as $model) {
            $endpoint = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
                rawurlencode($model),
                $apiKey
            );

            $payload = [
                'contents' => [[
                    'parts' => [[
                        'text' => $prompt,
                    ]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'temperature' => 0.7,
                ],
            ];

            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                $request = Http::timeout(150)
                    ->acceptJson()
                    ->withHeaders(['Content-Type' => 'application/json']);

                if (app()->environment('local')) {
                    $request->withoutVerifying();
                }

                $response = $request->post($endpoint, $payload);

                if (! $response->successful()) {
                    $errorBody = (string) data_get($response->json(), 'error.message', $response->body());
                    $lastError = "[{$model}|attempt {$attempt}] HTTP {$response->status()}: {$errorBody}";

                    Log::info("Gemini attempt failed", [
                        'model' => $model,
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'error' => $errorBody,
                    ]);

                    // API Key salah → langsung gagal, jangan retry
                    if (in_array($response->status(), [401, 403])) {
                        throw new RuntimeException($lastError);
                    }

                    // 503 (overloaded) atau 429 (rate limit) → tunggu lalu retry
                    if (in_array($response->status(), [503, 429]) && $attempt < $maxRetries) {
                        $delay = $response->status() === 429 ? ($attempt * 15) : ($attempt * 5); // Lebih lama jika 429
                        Log::info("Retrying model {$model} in {$delay}s (attempt {$attempt}/{$maxRetries})");
                        sleep($delay);
                        continue;
                    }

                    break; // Gagal non-retryable → pindah ke model berikutnya
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                if (! is_string($text) || trim($text) === '') {
                    $lastError = sprintf('[%s] Gemini response is empty.', $model);
                    break;
                }

                try {
                    return $this->decodeProviderJson($text);
                } catch (RuntimeException $e) {
                    $lastError = "[{$model}] " . $e->getMessage();
                    Log::warning("JSON decode failed for model {$model}, attempt {$attempt}", [
                        'error' => $e->getMessage(),
                        'raw_length' => strlen($text),
                    ]);

                    // Jika JSON gagal parse, retry mungkin membantu
                    if ($attempt < $maxRetries) {
                        sleep(2);
                        continue;
                    }
                    break;
                }
            }
        }

        throw new RuntimeException($lastError);
    }

    private function shouldAbortProviderFallback(Throwable $exception): bool
    {
        $message = Str::lower($exception->getMessage());

        return Str::contains($message, [
            'http 429',
            'quota exceeded',
            'rate limit',
            'billing',
            'http 401',
            'http 403',
            'api key not valid',
            'permission denied',
        ]);
    }

    private function providerErrorMessage(Throwable $exception): string
    {
        $message = Str::lower($exception->getMessage());

        if (Str::contains($message, ['http 429', 'quota exceeded', 'rate limit'])) {
            return 'Generate Gemini gagal karena melebihi batas kecepatan (Rate Limit). Silakan tunggu 1 menit atau gunakan billing Pay-as-you-go untuk limit yang lebih tinggi.';
        }

        if (Str::contains($message, ['billing'])) {
            return 'Generate Gemini gagal karena billing API habis atau belum diaktifkan. Cek Google Cloud Console.';
        }

        if (Str::contains($message, ['http 401', 'http 403', 'api key not valid', 'permission denied'])) {
            return 'Generate Gemini gagal karena API key tidak valid atau tidak punya akses ke model yang dipilih.';
        }

        return 'Generate Gemini gagal karena provider AI menolak request.';
    }

    private function candidateGeminiModels(string $configuredModel): array
    {
        $normalized = $this->normalizeGeminiModel($configuredModel);
        $fallbacks = [
            'gemini-1.5-flash',
            'gemini-1.5-pro',
            'gemini-2.0-flash-exp',
            'gemini-1.0-pro',
        ];

        return collect(array_merge([$normalized], $fallbacks))
            ->filter(fn($value) => is_string($value) && trim($value) !== '')
            ->map(fn($value) => trim($value))
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeGeminiModel(string $model): string
    {
        $normalized = trim($model);

        if (Str::startsWith($normalized, 'models/')) {
            $normalized = Str::after($normalized, 'models/');
        }

        return $normalized;
    }

    private function buildGeminiPrompt(ExamSession $session, \Illuminate\Support\Collection $structures): string
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
            'has_question_image' => 'Gambar soal',
            'has_option_image' => 'Gambar opsi jawaban',
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
- Jika daftar media di atas kosong, buat soal 100% teks.
- Distribusikan secara acak: satu soal maksimal hanya boleh memiliki SATU jenis media dari daftar di atas.
- Sisakan sekitar 20% soal murni teks tanpa media untuk variasi.

Aturan Format Konten:
- TABEL: Wajib gunakan format Markdown standar dengan baris pemisah, contoh:
  | Judul 1 | Judul 2 |
  | --- | --- |
  | Data 1 | Data 2 |
- GAMBAR/DIAGRAM: Karena Anda berbasis teks, berikan deskripsi visual di dalam teks soal dengan format: [GAMBAR: deskripsi detail ilustrasi yang dibutuhkan] atau [DIAGRAM: deskripsi data diagram].
- MATEMATIKA/SAINS: Untuk notasi matematika, fisika, atau kimia, WAJIB gunakan format LaTeX dengan pembungkus `\$...\$` untuk inline dan `\$\$... \$\$` untuk block. Contoh: `\$E = mc^2\$` atau `\$\$\frac{-b \pm \sqrt{b^2 - 4ac}}{2a}\$\$`.

Kembalikan objek JSON:
{
  "questions": [
    {
      "question_text": "... jika butuh tabel/gambar masukkan di sini sesuai format di atas ...",
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
- Output harus JSON valid, tanpa markdown.
- Jumlah questions harus tepat {$total}.
- difficulty harus tepat mengikuti distribusi.
- options kosong untuk Essay/Studi Kasus/Isian Singkat.
- Untuk Benar Salah, opsi hanya Benar dan Salah.
PROMPT;
    }

    private function decodeProviderJson(string $text): array
    {
        $clean = trim($text);

        // 1. Hapus code fence markdown
        $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```\s*$/', '', $clean) ?? $clean;

        // 2. Coba decode langsung
        $decoded = json_decode($clean, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        // 3. Cari blok JSON terbesar di dalam teks (AI kadang menambahkan penjelasan)
        if (preg_match('/\{[\s\S]*"questions"\s*:\s*\[[\s\S]*\]\s*\}/', $clean, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                Log::info('JSON extracted from mixed AI response using regex fallback.');
                return $decoded;
            }
        }

        // 4. Cari dari kurung kurawal pertama sampai terakhir
        $firstBrace = strpos($clean, '{');
        $lastBrace = strrpos($clean, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $jsonCandidate = substr($clean, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($jsonCandidate, true);
            if (is_array($decoded)) {
                Log::info('JSON extracted using brace-matching fallback.');
                return $decoded;
            }
        }

        Log::warning('Failed to extract JSON from AI response', [
            'length' => strlen($text),
            'preview' => Str::limit($text, 500),
        ]);

        throw new RuntimeException('Provider response is not valid JSON. AI mungkin mengembalikan format yang salah.');
    }

    private function persistProviderQuestions(
        ExamSession $session,
        \Illuminate\Support\Collection $structures,
        int $offset,
        array $questions
    ): int {
        // Siapkan pelacak kuota per struktur soal
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
            if (! is_array($item)) {
                continue;
            }

            $difficulty = $this->normalizeDifficulty((string) ($item['difficulty'] ?? ''), $groupTargets, $groupCurrent);
            if ($difficulty === null) {
                continue;
            }

            // Cari struktur mana yang masih butuh tingkat kesulitan ini
            $targetStructureId = null;
            foreach ($registry as $id => $data) {
                if ($data['current'][$difficulty] < $data['targets'][$difficulty]) {
                    $targetStructureId = $id;
                    break;
                }
            }

            if ($targetStructureId === null) {
                continue;
            }

            $registry[$targetStructureId]['current'][$difficulty]++;
            $groupCurrent[$difficulty]++;
            $created++;

            $structure = $registry[$targetStructureId]['model'];
            $sequence = $offset + $created;
            $cognitive = $this->normalizeCognitiveLevel((string) ($item['cognitive_level'] ?? ''), $structure, $sequence);
            $answerKey = $this->normalizeAnswerKey($structure, $sequence, (string) ($item['answer_key'] ?? ''));

            $question = Question::create([
                'exam_session_id' => $session->id,
                'question_structure_id' => $structure->id,
                'question_type' => $structure->question_type,
                'question_text' => (string) ($item['question_text'] ?? $this->questionText($session, $structure, $difficulty, $cognitive, $sequence)),
                'explanation' => (string) ($item['explanation'] ?? $this->explanationText($session, $difficulty, $cognitive)),
                'difficulty' => $difficulty,
                'cognitive_level' => $cognitive,
                'answer_key' => $answerKey,
                'sort_order' => $sequence,
            ]);

            $this->createProviderOptions($question, $structure, $sequence, $item['options'] ?? null);
            $this->createProviderBlueprint($question, $session, $structure, $cognitive, $item['blueprint'] ?? null);
        }

        $totalTarget = array_sum($groupTargets);
        if ($created < $totalTarget) {
            Log::warning("Provider output partial", [
                'expected' => $totalTarget,
                'actual' => $created,
                'distribution_expected' => $groupTargets,
                'distribution_actual' => $groupCurrent,
            ]);
            throw new RuntimeException("AI hanya berhasil membuat {$created} dari {$totalTarget} soal yang diminta. Silakan coba lagi.");
        }

        return $created;
    }

    private function normalizeDifficulty(string $difficulty, array $targetCounts, array $currentCounts): ?string
    {
        $map = [
            'mudah' => 'Mudah',
            'easy' => 'Mudah',
            'sedang' => 'Sedang',
            'medium' => 'Sedang',
            'sulit' => 'Sulit',
            'hard' => 'Sulit',
        ];

        $normalized = $map[Str::lower(trim($difficulty))] ?? null;
        if ($normalized === null) {
            return null;
        }

        if ($currentCounts[$normalized] >= $targetCounts[$normalized]) {
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
        if (! filled($answerKey)) {
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

        if (! is_array($options)) {
            $this->createOptions($question, $structure, $sequence);

            return;
        }

        if ($structure->question_type === 'Benar Salah') {
            foreach (['Benar', 'Salah'] as $index => $label) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_label' => $label,
                    'option_text' => $label,
                    'is_correct' => $question->answer_key === $label,
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

    private function createProviderBlueprint(
        Question $question,
        ExamSession $session,
        QuestionStructure $structure,
        string $cognitive,
        mixed $blueprint
    ): void {
        if (! is_array($blueprint)) {
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

    private function generateForStructureLocal(ExamSession $session, QuestionStructure $structure, int $offset): int
    {
        $created = 0;
        $distributions = [
            'Mudah' => $structure->easy_count,
            'Sedang' => $structure->medium_count,
            'Sulit' => $structure->hard_count,
        ];

        foreach ($distributions as $difficulty => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $created++;
                $sequence = $offset + $created;
                $cognitive = $this->pickCognitiveLevel($structure, $sequence);
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
        // Ambil daftar media yang diaktifkan
        $availableMedia = collect([
            'has_question_image' => 'sertakan konteks gambar',
            'has_option_image' => 'sertakan gambar pada opsi jawaban',
            'has_diagram' => 'gunakan diagram sederhana',
            'has_table' => 'gunakan data tabel',
        ])->filter(fn($label, $key) => $structure->{$key});

        $visualInstruction = '';
        
        // Randomisasi: 70% peluang mendapatkan media jika ada media yang tersedia
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
            foreach (['Benar', 'Salah'] as $index => $label) {
                QuestionOption::create([
                    'question_id' => $question->id,
                    'option_label' => $label,
                    'option_text' => $label,
                    'is_correct' => $question->answer_key === $label,
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
}
