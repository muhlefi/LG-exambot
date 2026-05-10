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

        return redirect()->route('quizzes.show', $quiz)->with('status', 'Quiz berhasil dibuat.');
    }

    public function show(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->load('examSession.questions.options');

        return view('quizzes.show', compact('quiz'));
    }

    public function destroy(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->delete();

        return redirect()->route('quizzes.index')->with('status', 'Quiz berhasil dihapus.');
    }

    public function activate(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->update([
            'visibility' => 'public',
            'status' => 'active',
        ]);

        return back()->with('status', 'Quiz berhasil diaktifkan.');
    }

    public function deactivate(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->update([
            'visibility' => 'private',
            'status' => 'ended',
        ]);

        return back()->with('status', 'Quiz berhasil dinonaktifkan.');
    }

    public function results(Request $request)
    {
        $query = Quiz::with(['examSession', 'participants'])
            ->whereHas('examSession', fn ($q) => $q->where('user_id', Auth::id()))
            ->withCount('participants');

        if ($request->filled('session_id')) {
            $query->where('exam_session_id', $request->session_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $quizzes = $query->latest()->paginate(20);
        $sessions = ExamSession::where('user_id', Auth::id())->latest()->get();

        return view('quiz-results.index', compact('quizzes', 'sessions'));
    }

    public function resultDetail(Quiz $quiz)
    {
        abort_unless($quiz->examSession->user_id === Auth::id() || Auth::user()?->role === 'admin', 403);

        $quiz->load(['examSession.questions.options', 'participants.answers']);

        return view('quiz-results.detail', compact('quiz'));
    }
}
