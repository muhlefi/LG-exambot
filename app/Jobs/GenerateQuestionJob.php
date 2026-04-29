<?php

namespace App\Jobs;

use App\Models\ExamSession;
use App\Services\AiQuestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateQuestionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly ExamSession $examSession) {}

    public function handle(AiQuestionService $aiQuestionService): void
    {
        $aiQuestionService->generate($this->examSession);
    }
}
