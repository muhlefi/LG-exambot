<?php

namespace App\Http\Controllers;

use App\Exceptions\AiProviderException;
use App\Models\ExamSession;
use App\Models\Question;
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
            'education_level' => ['required', 'string', 'max:100'],
            'class_level' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'subtopic' => ['nullable', 'string', 'max:10000'],
        ]);

        $data['school_name'] = 'BIMBINGAN BELAJAR L-G Learning';
        $data['user_id'] = Auth::id();
        $data['status'] = 'draft';

        $session = ExamSession::create($data);

        return redirect()->route('sessions.show', $session);
    }

    public function show(ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $examSession->load('structures')->loadCount('questions');

        return view('sessions.show', compact('examSession'));
    }

    public function edit(ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);
        return view('sessions.edit', compact('examSession'));
    }

    public function update(Request $request, ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'teacher_name' => ['required', 'string', 'max:255'],
            'education_level' => ['required', 'string', 'max:100'],
            'class_level' => ['required', 'string', 'max:100'],
            'semester' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:255'],
            'topic' => ['required', 'string', 'max:255'],
            'subtopic' => ['nullable', 'string', 'max:10000'],
        ]);

        $examSession->update($data);

        return redirect()->route('sessions.show', $examSession)->with('status', 'Sesi berhasil diperbarui.');
    }

    public function destroy(ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $examSession->delete();

        return redirect()->route('sessions.index')->with('status', 'Sesi berhasil dihapus.');
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
        $currentTotal = (int) $examSession->structures->sum('total_questions');

        if ($total < 1) {
            return back()->withErrors(['easy_count' => 'Minimal total soal adalah 1.'])->withInput();
        }

        if (($currentTotal + $total) > 40) {
            return back()->withErrors(['easy_count' => "Batas maksimal soal per sesi adalah 40. Saat ini sudah ada {$currentTotal} soal, Anda mencoba menambahkan {$total} soal lagi."])->withInput();
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

        $currentTotal = (int) $examSession->structures->sum('total_questions');
        $newTotal = $currentTotal + $structure->total_questions;

        if ($newTotal > 40) {
            return back()->withErrors(['status' => "Gagal duplikasi: Batas maksimal 40 soal terlampaui."]);
        }

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

        // Jika ingin synchronous (cara lama tapi sudah diperbaiki DB-nya):
        // set_time_limit(300);
        // $created = $aiQuestionService->generate($examSession);
        // return redirect()->route('sessions.results', $examSession)->with('status', "{$created} soal berhasil dibuat.");

        // Cara Production Ready (Background Job):
        \App\Jobs\GenerateQuestionsJob::dispatch($examSession);

        return redirect()
            ->route('sessions.show', $examSession)
            ->with('status', "Proses pembuatan soal sedang berjalan di background. Silakan tunggu beberapa saat dan refresh halaman.");
    }

    public function results(ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);

        $examSession->load('questions.options', 'questions.blueprint', 'quizzes');

        return view('sessions.results', compact('examSession'));
    }

    public function print(ExamSession $examSession, string $type = 'questions')
    {
        $this->authorizeOwner($examSession);
        
        $examSession->load('questions.options', 'questions.blueprint');
        
        return view('sessions.print', [
            'examSession' => $examSession,
            'type' => $type
        ]);
    }

    public function export(ExamSession $examSession, string $documentType, string $format, ExportService $exportService)
    {
        $this->authorizeOwner($examSession);
        set_time_limit(180);
        abort_unless(in_array($documentType, ['questions', 'answers', 'blueprint'], true), 404);
        abort_unless(in_array($format, ['pdf', 'docx'], true), 404);

        return $exportService->download($examSession, $documentType, $format);
    }

    public function editQuestion(Question $question)
    {
        $session = $question->examSession;
        $this->authorizeOwner($session);

        $question->load('options');

        return view('questions.edit', compact('question'));
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $session = $question->examSession;
        $this->authorizeOwner($session);

        $data = $request->validate([
            'question_text' => 'required|string',
            'answer_key' => 'required|string',
            'explanation' => 'nullable|string',
            'image' => 'nullable|image|max:3072',
            'remove_image' => 'nullable|boolean',
            'options' => 'nullable|array',
            'options.*.id' => 'required|exists:question_options,id',
            'options.*.text' => 'required|string',
        ]);

        $updateData = [
            'question_text' => $data['question_text'],
            'answer_key' => $data['answer_key'],
            'explanation' => $data['explanation'],
        ];

        if ($request->boolean('remove_image')) {
            if ($question->question_image) {
                \Illuminate\Support\Facades\Storage::delete($question->question_image);
                $updateData['question_image'] = null;
            }
        }

        if ($request->hasFile('image')) {
            if ($question->question_image) {
                \Illuminate\Support\Facades\Storage::delete($question->question_image);
            }
            $updateData['question_image'] = $request->file('image')->store('questions', 'public');
        }

        $question->update($updateData);

        if (!empty($data['options'])) {
            foreach ($data['options'] as $optionData) {
                $question->options()->where('id', $optionData['id'])->update([
                    'option_text' => $optionData['text'],
                ]);
            }
        }

        return redirect()->route('sessions.results', $session)->with('status', 'Soal berhasil diperbarui.');
    }

    public function destroyQuestion(Question $question)
    {
        $session = $question->examSession;
        $this->authorizeOwner($session);

        $question->delete();

        return back()->with('status', 'Soal berhasil dihapus.');
    }

    public function batchDestroyQuestions(Request $request, ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);
        $ids = $request->input('question_ids', []);
        
        if (empty($ids)) {
            return back()->withErrors(['question_ids' => 'Pilih setidaknya satu soal.']);
        }

        Question::where('exam_session_id', $examSession->id)
            ->whereIn('id', $ids)
            ->delete();

        return back()->with('status', count($ids) . ' soal berhasil dihapus secara massal.');
    }

    public function searchBank(Request $request, ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);
        $query = $request->input('q');

        $questions = Question::whereHas('examSession', function ($q) {
                $q->where('user_id', Auth::id());
            })
            ->where('exam_session_id', '!=', $examSession->id)
            ->when($query, function ($q) use ($query) {
                $q->where('question_text', 'like', "%{$query}%")
                  ->orWhereHas('examSession', fn($sq) => $sq->where('subject', 'like', "%{$query}%")->orWhere('topic', 'like', "%{$query}%"));
            })
            ->with(['examSession', 'options', 'blueprint'])
            ->latest()
            ->limit(20)
            ->get();

        return response()->json($questions);
    }

    public function importFromBank(Request $request, ExamSession $examSession)
    {
        $this->authorizeOwner($examSession);
        $ids = $request->validate([
            'question_ids' => 'required|array',
            'question_ids.*' => 'exists:questions,id'
        ]);

        $created = 0;
        foreach ($ids['question_ids'] as $id) {
            $sourceQuestion = Question::with(['options', 'blueprint'])->find($id);
            if (!$sourceQuestion) continue;

            $newQuestion = $sourceQuestion->replicate();
            $newQuestion->exam_session_id = $examSession->id;
            // Kita reset structure_id karena ini import kustom
            $newQuestion->question_structure_id = null;
            $newQuestion->sort_order = $examSession->questions()->max('sort_order') + 1;
            $newQuestion->save();

            foreach ($sourceQuestion->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }

            if ($sourceQuestion->blueprint) {
                $newBlueprint = $sourceQuestion->blueprint->replicate();
                $newBlueprint->question_id = $newQuestion->id;
                $newBlueprint->save();
            }

            $created++;
        }

        $examSession->update(['status' => 'generated']);

        return back()->with('status', "{$created} soal berhasil diimpor dari bank soal.");
    }

    private function authorizeOwner(ExamSession $examSession): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->role === 'admin' || $examSession->user_id === $user->id), 403);
    }
}
