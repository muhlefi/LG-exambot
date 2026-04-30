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
            ->latest()
            ->paginate(10);

        return view('quizzes.index', compact('quizzes'));
    }

    public function store(Request $request, ExamSession $examSession, QuizService $quizService)
    {
        abort_unless($examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'duration' => 'required|integer|min:1',
            'is_random_question' => 'boolean',
            'is_random_answer' => 'boolean',
        ]);

        $quiz = $quizService->createFromSession($examSession, $data);

        return redirect()->route('quizzes.show', $quiz)->with('status', 'Presentation Mode aktif.');
    }

    public function show(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->load('examSession.questions.options');

        return view('quizzes.show', compact('quiz'));
    }


}
