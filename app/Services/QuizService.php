<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\Quiz;
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
            'visibility' => $data['visibility'] ?? 'private',
            'status' => $data['status'] ?? 'inactive',
            'is_random_question' => (bool) ($data['is_random_question'] ?? false),
            'is_random_answer' => (bool) ($data['is_random_answer'] ?? false),
        ]);
    }

    private function uniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (Quiz::where('quiz_code', $code)->exists());

        return $code;
    }
}
