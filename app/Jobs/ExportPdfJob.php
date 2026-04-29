<?php

namespace App\Jobs;

use App\Models\ExamSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExportPdfJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ExamSession $examSession,
        public readonly string $documentType = 'questions',
    ) {}

    public function handle(): void
    {
        // File export can be moved here when background export storage is enabled.
    }
}
