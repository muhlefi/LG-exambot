<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\ExportLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportService
{
    public function download(ExamSession $session, string $documentType, string $format): Response|BinaryFileResponse
    {
        $session->loadMissing('questions.options', 'questions.blueprint');

        return match ($format) {
            'pdf' => $this->downloadPdf($session, $documentType),
            'docx' => $this->downloadDocx($session, $documentType),
            default => abort(404),
        };
    }

    private function downloadPdf(ExamSession $session, string $documentType): Response
    {
        ExportLog::create([
            'user_id' => $session->user_id,
            'exam_session_id' => $session->id,
            'document_type' => $documentType,
            'format' => 'pdf',
        ]);

        return Pdf::loadView('exports.document', [
            'session' => $session,
            'documentType' => $documentType,
        ])->setPaper('a4')->download($this->filename($session, $documentType, 'pdf'));
    }

    private function downloadDocx(ExamSession $session, string $documentType): BinaryFileResponse
    {
        Storage::disk('local')->makeDirectory('exports');

        $path = storage_path('app/exports/'.$this->filename($session, $documentType, 'docx'));
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();

        $section->addText($session->school_name, ['bold' => true, 'size' => 16]);
        $section->addText(strtoupper($this->documentTitle($documentType)), ['bold' => true, 'size' => 14]);
        $section->addText("Mata Pelajaran: {$session->subject}");
        $section->addText("Kelas/Semester: {$session->class_level} / {$session->semester}");
        $section->addTextBreak();

        foreach ($session->questions as $index => $question) {
            $section->addText(($index + 1).'. '.$question->question_text);
            foreach ($question->options as $option) {
                $section->addText("   {$option->option_label}. {$option->option_text}");
            }

            if ($documentType !== 'questions') {
                $section->addText("Kunci: {$question->answer_key}");
                $section->addText("Pembahasan: {$question->explanation}");
            }

            if ($documentType === 'blueprint' && $question->blueprint) {
                $section->addText("Kisi-kisi: {$question->blueprint->indicator}");
            }

            $section->addTextBreak();
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        ExportLog::create([
            'user_id' => $session->user_id,
            'exam_session_id' => $session->id,
            'document_type' => $documentType,
            'format' => 'docx',
            'path' => $path,
        ]);

        return response()->download($path)->deleteFileAfterSend(true);
    }

    private function filename(ExamSession $session, string $documentType, string $extension): string
    {
        return Str::slug($session->title.' '.$documentType).'.'.$extension;
    }

    private function documentTitle(string $documentType): string
    {
        return match ($documentType) {
            'answers' => 'Kunci Jawaban',
            'blueprint' => 'Kisi-Kisi Soal',
            default => 'Naskah Soal',
        };
    }
}
