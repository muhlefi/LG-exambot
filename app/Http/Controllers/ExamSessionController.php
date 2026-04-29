<?php

namespace App\Http\Controllers;

use App\Exceptions\AiProviderException;
use App\Models\ExamSession;
use App\Models\QuestionStructure;
use App\Services\AiQuestionService;
use App\Services\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamSessionController extends Controller
{
    public function index()
    {
        $sessions = ExamSession::where('user_id', Auth::id())
            ->withCount(['structures', 'questions', 'quizzes'])
            ->latest()
            ->paginate(10);

        return view('sessions.index', compact('sessions'));
    }

    public function create()
    {
        return view('sessions.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'teacher_name' => ['required', 'string', 'max:255'],
            'school_name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'education_level' => ['required', 'string', 'max:100'],
            'learning_phase' => ['nullable', 'string', 'max:100'],
            'class_level' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'subtopic' => ['nullable', 'string', 'max:255'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('school-logos', 'public');
        }

        $data['user_id'] = Auth::id();
        $data['status'] = 'draft';

        $session = ExamSession::create($data);

        return redirect()->route('sessions.show', $session);
    }

    public function show(ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $examSession->load('structures');

        return view('sessions.show', compact('examSession'));
    }

    public function addStructure(Request $request, ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'question_type' => ['required', 'string', 'max:100'],
            'option_count' => ['required', 'integer', 'min:2', 'max:6'],
            'easy_count' => ['required', 'integer', 'min:0', 'max:100'],
            'medium_count' => ['required', 'integer', 'min:0', 'max:100'],
            'hard_count' => ['required', 'integer', 'min:0', 'max:100'],
            'cognitive_levels' => ['nullable', 'array'],
            'cognitive_levels.*' => ['string', 'max:100'],
            'has_question_image' => ['nullable', 'boolean'],
            'has_option_image' => ['nullable', 'boolean'],
            'has_diagram' => ['nullable', 'boolean'],
            'has_table' => ['nullable', 'boolean'],
        ]);

        $total = (int) $data['easy_count'] + (int) $data['medium_count'] + (int) $data['hard_count'];
        if ($total < 1) {
            return back()->withErrors(['easy_count' => 'Minimal total soal adalah 1.'])->withInput();
        }

        $examSession->structures()->create([
            ...$data,
            'cognitive_levels' => $data['cognitive_levels'] ?? [],
            'has_question_image' => $request->boolean('has_question_image'),
            'has_option_image' => $request->boolean('has_option_image'),
            'has_diagram' => $request->boolean('has_diagram'),
            'has_table' => $request->boolean('has_table'),
            'sort_order' => ((int) $examSession->structures()->max('sort_order')) + 1,
        ]);

        return back()->with('status', 'Struktur soal ditambahkan.');
    }

    public function duplicateStructure(ExamSession $examSession, QuestionStructure $structure)
    {
        $this->authorizeOwner($examSession);
        abort_unless($structure->exam_session_id === $examSession->id, 404);

        $copy = $structure->replicate();
        $copy->sort_order = ((int) $examSession->structures()->max('sort_order')) + 1;
        $copy->name = trim(($structure->name ?: $structure->question_type).' Copy');
        $copy->save();

        return back()->with('status', 'Struktur soal diduplikasi.');
    }

    public function destroyStructure(ExamSession $examSession, QuestionStructure $structure)
    {
        $this->authorizeOwner($examSession);
        abort_unless($structure->exam_session_id === $examSession->id, 404);

        $structure->delete();

        return back()->with('status', 'Struktur soal dihapus.');
    }

    public function generate(ExamSession $examSession, AiQuestionService $aiQuestionService)
    {
        $this->authorizeOwner($examSession);

        try {
            $created = $aiQuestionService->generate($examSession);
        } catch (AiProviderException $exception) {
            return back()->withErrors([
                'ai_provider' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('sessions.results', $examSession)
            ->with('status', "{$created} soal berhasil dibuat.");
    }

    public function results(ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $examSession->load('questions.options', 'questions.blueprint', 'quizzes');

        return view('sessions.results', compact('examSession'));
    }

    public function export(ExamSession $examSession, string $documentType, string $format, ExportService $exportService)
    {
        $this->authorizeOwner($examSession);
        abort_unless(in_array($documentType, ['questions', 'answers', 'blueprint'], true), 404);
        abort_unless(in_array($format, ['pdf', 'docx'], true), 404);

        return $exportService->download($examSession, $documentType, $format);
    }

    private function authorizeOwner(ExamSession $examSession): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->role === 'admin' || $examSession->user_id === $user->id), 403);
    }
}
