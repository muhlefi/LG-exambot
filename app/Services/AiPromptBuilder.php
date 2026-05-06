<?php

namespace App\Services;

use App\Models\ExamSession;
use App\Models\QuestionStructure;

class AiPromptBuilder
{
    public function build(ExamSession $session): string
    {
        $session->loadMissing('structures');

        $structureLines = $session->structures
            ->map(function (QuestionStructure $structure, int $index): string {
                $levels = implode(', ', $structure->cognitive_levels ?? ['C1', 'C2', 'C3']);

                return sprintf(
                    '%d. %s: %d soal, opsi %d, mudah %d, sedang %d, sulit %d, level %s.',
                    $index + 1,
                    $structure->question_type,
                    $structure->total_questions,
                    $structure->option_count,
                    $structure->easy_count,
                    $structure->medium_count,
                    $structure->hard_count,
                    $levels
                );
            })
            ->implode(PHP_EOL);

        return <<<PROMPT
        Buat paket soal berbahasa Indonesia untuk kebutuhan asesmen sekolah.

        Identitas:
        - Penyusun: {$session->teacher_name}
        - Sekolah: {$session->school_name}
        - Jenjang/Kelas: {$session->education_level} / {$session->class_level}
        - Semester/Tahun: {$session->semester} / {$session->academic_year}
        - Mata pelajaran: {$session->subject}
        - Materi: {$session->topic}
        - Sub materi: {$session->subtopic}

        Struktur soal:
        {$structureLines}

        Keluarkan JSON berisi questions[]. Setiap item wajib punya:
        question_text, question_type, difficulty, cognitive_level, options[], answer_key, explanation, blueprint.
        Blueprint wajib berisi competency, indicator, material, cognitive_dimension, question_type.

        Aturan Penting:
        1. ANTI-DUPLIKASI: JANGAN sertakan pilihan jawaban atau instruksi seperti "(Benar/Salah)" di dalam `question_text`.
        2. TABEL: Gunakan format Markdown Table GFM untuk data terstruktur.
        3. Hindari jawaban ambigu dan duplikasi soal.
        PROMPT;
    }
}
