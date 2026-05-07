<?php

namespace App\Jobs;

use App\Models\ExamSession;
use App\Services\AiQuestionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateQuestionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected ExamSession $session
    ) {}

    /**
     * Execute the job.
     */
    public function handle(AiQuestionService $service): void
    {
        Log::info("Starting background question generation for session: {$this->session->id}");
        
        $this->session->update(['status' => 'processing']);
        
        try {
            $created = $service->generate($this->session);
            Log::info("Successfully generated {$created} questions in background for session: {$this->session->id}");
        } catch (Throwable $e) {
            Log::error("Background generation failed: " . $e->getMessage(), [
                'session_id' => $this->session->id,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->session->update(['status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $this->session->update(['status' => 'failed']);
    }
}
