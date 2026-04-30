<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\Quiz;
use Illuminate\Support\Str;

class QuizService
{
    /**
     * Create a new quiz in Presentation Mode from an exam session.
     */
    public function createFromSession(ExamSession $session, array $data): Quiz
    {
        return Quiz::create([
            'user_id' => auth()->id(),
            'exam_session_id' => $session->id,
            'title' => $data['title'],
            'quiz_code' => $this->uniqueCode(),
            'duration' => $data['duration'] ?? 30,
            'visibility' => 'private',
            'is_random_question' => (bool) ($data['is_random_question'] ?? false),
            'is_random_answer' => (bool) ($data['is_random_answer'] ?? false),
        ]);
    }

    /**
     * Generate a unique 6-character quiz code.
     */
    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Quiz::where('quiz_code', $code)->exists());

        return $code;
    }
}
