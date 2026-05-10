<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizParticipant;
use App\Models\QuizAnswer;
use App\Models\Question;
use App\Services\QuizService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class QuizGuestController extends Controller
{
    public function __construct(private QuizService $quizService)
    {
    }

    public function join()
    {
        return view('guest.join');
    }

    public function show($code)
    {
        $quiz = Quiz::where('quiz_code', $code)
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->first();

        if (!$quiz) {
            return redirect()->route('quiz.join')->with('error', 'Kode quiz tidak ditemukan atau quiz sudah tidak aktif.');
        }

        return view('guest.quiz', compact('quiz'));
    }

    public function start(Request $request, $code)
    {
        $quiz = Quiz::where('quiz_code', $code)
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->first();

        if (!$quiz) {
            return redirect()->route('quiz.join')->with('error', 'Kode quiz tidak ditemukan atau quiz sudah tidak aktif.');
        }

        $validated = $request->validate([
            'student_name' => 'required|string|min:2|max:100',
        ]);

        $participant = QuizParticipant::create([
            'quiz_id' => $quiz->id,
            'student_name' => trim($validated['student_name']),
            'score' => 0,
        ]);

        Session::put('quiz_participant_' . $code, $participant->id);

        return redirect()->route('quiz.attempt', $code);
    }

    public function attempt($code)
    {
        $quiz = Quiz::where('quiz_code', $code)
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->first();

        if (!$quiz) {
            return redirect()->route('quiz.join')->with('error', 'Kode quiz tidak ditemukan atau quiz sudah tidak aktif.');
        }

        $participantId = Session::get('quiz_participant_' . $code);
        if (!$participantId) {
            return redirect()->route('quiz.show', $code)->with('error', 'Silakan masukkan nama terlebih dahulu.');
        }

        $participant = QuizParticipant::where('id', $participantId)
            ->where('quiz_id', $quiz->id)
            ->whereNull('finished_at')
            ->first();

        if (!$participant) {
            return redirect()->route('quiz.show', $code)->with('error', 'Sesi tidak valid atau quiz sudah selesai.');
        }

        if ($participant->finished_at) {
            return redirect()->route('quiz.result', $code);
        }

        $quiz->load(['examSession' => function ($q) {
            $q->with(['questions' => function ($q) {
                $q->with('options')->orderBy('sort_order');
            }]);
        }]);

        $questions = $quiz->examSession->questions;

        if ($quiz->is_random_question && $questions->count() > 1) {
            $questions = $questions->shuffle()->values();
        }

        if ($quiz->is_random_answer) {
            $questions = $questions->map(function ($question) {
                $question->setRelation('options', $question->options->shuffle()->values());
                return $question;
            });
        }

        $quizUrl = url()->current();
        $qrCodeUrl = \App\Http\Controllers\Controller::generateQrCode($quizUrl);

        return view('guest.attempt', compact('quiz', 'participant', 'questions', 'qrCodeUrl'));
    }

    public function submit(Request $request, $code)
    {
        $quiz = Quiz::where('quiz_code', $code)
            ->where('visibility', 'public')
            ->where('status', 'active')
            ->first();

        if (!$quiz) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Quiz tidak ditemukan.'], 404);
            }
            return redirect()->route('quiz.join')->with('error', 'Quiz tidak ditemukan.');
        }

        $participantId = Session::get('quiz_participant_' . $code);
        if (!$participantId) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Sesi tidak valid.'], 403);
            }
            return redirect()->route('quiz.show', $code);
        }

        $participant = QuizParticipant::where('id', $participantId)
            ->where('quiz_id', $quiz->id)
            ->whereNull('finished_at')
            ->first();

        if (!$participant) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Quiz sudah selesai.'], 403);
            }
            return redirect()->route('quiz.result', $code);
        }

        $answers = $request->input('answers', []);

        $quiz->load(['examSession' => function ($q) {
            $q->with('questions.options');
        }]);

        $correctCount = 0;
        $totalQuestions = $quiz->examSession->questions->count();

        foreach ($quiz->examSession->questions as $question) {
            $selectedAnswer = $answers[$question->id] ?? null;
            $isCorrect = $selectedAnswer !== null && $selectedAnswer === $question->answer_key;

            if ($isCorrect) {
                $correctCount++;
            }

            QuizAnswer::updateOrCreate(
                [
                    'participant_id' => $participant->id,
                    'question_id' => $question->id,
                ],
                [
                    'selected_answer' => $selectedAnswer,
                    'is_correct' => $isCorrect,
                ]
            );
        }

        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;

        $participant->update([
            'score' => $score,
            'finished_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => route('quiz.result', $code),
            ]);
        }

        return redirect()->route('quiz.result', $code);
    }

    public function result($code)
    {
        $quiz = Quiz::where('quiz_code', $code)->first();

        if (!$quiz) {
            return redirect()->route('quiz.join')->with('error', 'Quiz tidak ditemukan.');
        }

        $participantId = Session::get('quiz_participant_' . $code);
        $participant = $participantId
            ? QuizParticipant::where('id', $participantId)->where('quiz_id', $quiz->id)->first()
            : null;

        $quiz->load(['examSession' => function ($q) {
            $q->with(['questions' => function ($q) {
                $q->with('options')->orderBy('sort_order');
            }]);
        }]);

        $alreadyFinished = $participant && $participant->finished_at;
        $isSubmitted = $participant && $participant->finished_at !== null;

        return view('guest.result', compact('quiz', 'participant', 'alreadyFinished', 'isSubmitted'));
    }
}
