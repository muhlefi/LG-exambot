<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizParticipant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class QuizService
{
    public function createFromSession(ExamSession $session, array $data): Quiz
    {
        return Quiz::create([
            'exam_session_id' => $session->id,
            'title' => $data['title'],
            'quiz_code' => $this->uniqueCode(),
            'duration' => $data['duration'] ?? 30,
            'is_random_question' => (bool) ($data['is_random_question'] ?? false),
            'is_random_answer' => (bool) ($data['is_random_answer'] ?? false),
            'visibility' => $data['visibility'] ?? 'private',
            'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null,
            'max_participants' => $data['max_participants'] ?? null,
            'status' => 'active',
        ]);
    }

    public function join(Quiz $quiz, string $studentName): QuizParticipant
    {
        return QuizParticipant::create([
            'quiz_id' => $quiz->id,
            'student_name' => $studentName,
        ]);
    }

    public function submit(QuizParticipant $participant, array $answers): QuizParticipant
    {
        $quiz = $participant->quiz()->with('examSession.questions.options')->firstOrFail();
        $score = 0;

        foreach ($quiz->examSession->questions as $question) {
            $selected = $answers[$question->id] ?? null;
            $isCorrect = $this->isCorrect($question, $selected);

            QuizAnswer::updateOrCreate(
                [
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ],
                [
                    'selected_answer' => $selected,
                    'is_correct' => $isCorrect,
                ]
            );

            $score += $isCorrect ? 1 : 0;
        }

        $participant->update([
            'score' => $score,
            'finished_at' => now(),
        ]);

        $this->refreshRanks($quiz);

        return $participant->refresh();
    }

    public function passwordMatches(Quiz $quiz, ?string $password): bool
    {
        return blank($quiz->password) || (filled($password) && Hash::check($password, $quiz->password));
    }

    private function isCorrect(Question $question, ?string $selected): bool
    {
        if (blank($selected)) {
            return false;
        }

        return Str::lower(trim($selected)) === Str::lower(trim((string) $question->answer_key));
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Quiz::where('quiz_code', $code)->exists());

        return $code;
    }

    private function refreshRanks(Quiz $quiz): void
    {
        $quiz->participants()
            ->whereNotNull('finished_at')
            ->orderByDesc('score')
            ->orderBy('finished_at')
            ->get()
            ->each(fn (QuizParticipant $participant, int $index) => $participant->update(['rank' => $index + 1]));
    }
}
