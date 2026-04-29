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
            foreach ($session->structures as $structure) {
                if ($this->shouldUseGemini($provider)) {
                    $generated = $this->generateForStructureWithGemini($session, $structure, $created);
                    if ($generated !== null) {
                        $created += $generated;
                        $usedProvider = true;

                        continue;
                    }
                }

                $created += $this->generateForStructureLocal($session, $structure, $created);
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

    private function generateForStructureWithGemini(ExamSession $session, QuestionStructure $structure, int $offset): ?int
    {
        try {
            $payload = $this->requestGeminiQuestions($session, $structure);
            if (! isset($payload['questions']) || ! is_array($payload['questions'])) {
                return null;
            }

            return $this->persistProviderQuestions($session, $structure, $offset, $payload['questions']);
        } catch (Throwable $exception) {
            if ($this->shouldAbortProviderFallback($exception)) {
                throw new AiProviderException($this->providerErrorMessage($exception), $exception);
            }

            Log::warning('Gemini generation failed, falling back to local draft.', [
                'exam_session_id' => $session->id,
                'question_structure_id' => $structure->id,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function requestGeminiQuestions(ExamSession $session, QuestionStructure $structure): array
    {
        $configuredModel = (string) config('services.ai.gemini_model', 'gemini-2.5-flash');
        $apiKey = (string) config('services.ai.gemini_key');
        $models = $this->candidateGeminiModels($configuredModel);
        $lastError = 'Gemini request failed.';
        $payloadVariants = [
            [
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ],
            ],
            [
                'generationConfig' => [],
            ],
            [],
        ];

        foreach ($models as $model) {
            $endpoint = sprintf(
                'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent',
                rawurlencode($model)
            );

            foreach ($payloadVariants as $variant) {
                $payload = array_merge([
                    'contents' => [[
                        'parts' => [[
                            'text' => $this->buildGeminiPrompt($session, $structure),
                        ]],
                    ]],
                ], $variant);

                $response = Http::timeout(60)
                    ->acceptJson()
                    ->withHeaders([
                        'x-goog-api-key' => $apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post($endpoint, $payload);

                if (! $response->successful()) {
                    $lastError = sprintf(
                        '[v1beta|%s] HTTP %d: %s',
                        $model,
                        $response->status(),
                        (string) data_get($response->json(), 'error.message', $response->body())
                    );
                    continue;
                }

                $text = data_get($response->json(), 'candidates.0.content.parts.0.text');
                if (! is_string($text) || trim($text) === '') {
                    $lastError = sprintf('[v1beta|%s] Gemini response is empty.', $model);
                    continue;
                }

                return $this->decodeProviderJson($text);
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

        if (Str::contains($message, ['http 429', 'quota exceeded', 'rate limit', 'billing'])) {
            return 'i gagal karena kuota atau billing API habis. Cek plan, billing, dan limit API key Gemini yang dipakai.';
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
            'gemini-2.5-flash',
            'gemini-2.0-flash',
            'gemini-2.0-flash-lite',
        ];

        return collect(array_merge([$normalized], $fallbacks))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn ($value) => trim($value))
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

    private function buildGeminiPrompt(ExamSession $session, QuestionStructure $structure): string
    {
        $total = (int) $structure->total_questions;
        $levels = implode(', ', $structure->cognitive_levels ?? ['C1 Mengingat', 'C2 Memahami', 'C3 Menerapkan']);
        $subtopic = $session->subtopic ?? '-';

        return <<<PROMPT
Buat soal asesmen berbahasa Indonesia dalam format JSON murni.

Identitas sesi:
- Mata pelajaran: {$session->subject}
- Materi: {$session->topic}
- Sub materi: {$subtopic}
- Jenjang/Kelas: {$session->education_level} / {$session->class_level}

Struktur yang harus dipenuhi:
- question_type: {$structure->question_type}
- jumlah total: {$total}
- distribusi: Mudah {$structure->easy_count}, Sedang {$structure->medium_count}, Sulit {$structure->hard_count}
- option_count (jika relevan): {$structure->option_count}
- level kognitif yang boleh: {$levels}

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
        $clean = preg_replace('/^```json\s*/i', '', $clean) ?? $clean;
        $clean = preg_replace('/^```\s*/', '', $clean) ?? $clean;
        $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;

        $decoded = json_decode($clean, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('Provider response is not valid JSON.');
        }

        return $decoded;
    }

    private function persistProviderQuestions(
        ExamSession $session,
        QuestionStructure $structure,
        int $offset,
        array $questions
    ): int {
        $targetCounts = [
            'Mudah' => (int) $structure->easy_count,
            'Sedang' => (int) $structure->medium_count,
            'Sulit' => (int) $structure->hard_count,
        ];
        $currentCounts = ['Mudah' => 0, 'Sedang' => 0, 'Sulit' => 0];

        $created = 0;
        foreach ($questions as $item) {
            if (! is_array($item)) {
                continue;
            }

            $difficulty = $this->normalizeDifficulty((string) ($item['difficulty'] ?? ''), $targetCounts, $currentCounts);
            if ($difficulty === null) {
                continue;
            }

            $currentCounts[$difficulty]++;
            $created++;
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

        if ($currentCounts !== $targetCounts) {
            throw new RuntimeException('Provider output does not satisfy required difficulty distribution.');
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
        $visual = collect([
            $structure->has_question_image ? 'sertakan konteks gambar' : null,
            $structure->has_diagram ? 'gunakan diagram sederhana' : null,
            $structure->has_table ? 'gunakan data tabel' : null,
        ])->filter()->implode(', ');

        $visualInstruction = $visual ? " Pertimbangkan instruksi visual: {$visual}." : '';

        return "Soal {$sequence} ({$difficulty}, {$cognitive}). Pada materi {$session->topic}"
            .($session->subtopic ? " submateri {$session->subtopic}" : '')
            .", susun jawaban paling tepat untuk bentuk {$structure->question_type}.{$visualInstruction}";
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
