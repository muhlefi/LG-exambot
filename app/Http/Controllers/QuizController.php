<?php

namespace App\Http\Controllers;

use App\Models\ExamSession;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function index()
    {
        $quizzes = Quiz::with('examSession')
            ->whereHas('examSession', fn ($query) => $query->where('user_id', Auth::id()))
            ->withCount('participants')
            ->latest()
            ->paginate(10);

        return view('quizzes.index', compact('quizzes'));
    }

    public function store(Request $request, ExamSession $examSession, QuizService $quizService)
    {
        abort_unless($examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'duration' => ['required', 'integer', 'min:1', 'max:240'],
            'is_random_question' => ['nullable', 'boolean'],
            'is_random_answer' => ['nullable', 'boolean'],
            'visibility' => ['required', 'in:public,private'],
            'password' => ['nullable', 'string', 'max:255'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ]);

        $quiz = $quizService->createFromSession($examSession, $data);

        return redirect()->route('quizzes.show', $quiz)->with('status', 'Quiz berhasil dibuat.');
    }

    public function show(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->load('examSession.questions.options', 'participants');

        return view('quizzes.show', compact('quiz'));
    }

    public function joinForm()
    {
        return view('quizzes.join');
    }

    public function join(Request $request, QuizService $quizService)
    {
        $data = $request->validate([
            'quiz_code' => ['required', 'string', 'max:20'],
            'student_name' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
        ]);

        $quiz = Quiz::where('quiz_code', strtoupper($data['quiz_code']))->where('status', 'active')->first();

        if (! $quiz || ! $quizService->passwordMatches($quiz, $data['password'] ?? null)) {
            return back()->withErrors(['quiz_code' => 'Kode atau password quiz tidak valid.'])->withInput();
        }

        if ($quiz->max_participants && $quiz->participants()->count() >= $quiz->max_participants) {
            return back()->withErrors(['quiz_code' => 'Room quiz sudah penuh.'])->withInput();
        }

        $participant = $quizService->join($quiz, $data['student_name']);

        return redirect()->route('quizzes.play', $participant);
    }

    public function play(QuizParticipant $participant)
    {
        $participant->load('quiz.examSession.questions.options');

        $questions = $participant->quiz->examSession->questions;
        if ($participant->quiz->is_random_question) {
            $questions = $questions->shuffle();
        }

        return view('quizzes.play', compact('participant', 'questions'));
    }

    public function submit(Request $request, QuizParticipant $participant, QuizService $quizService)
    {
        $participant->load('quiz.examSession.questions');

        $answers = $request->input('answers', []);
        $participant = $quizService->submit($participant, $answers);

        return redirect()->route('quizzes.leaderboard', $participant->quiz)->with('status', "Skor {$participant->score} tersimpan.");
    }

    public function leaderboard(Quiz $quiz)
    {
        $quiz->load('participants');

        return view('quizzes.leaderboard', compact('quiz'));
    }
}
