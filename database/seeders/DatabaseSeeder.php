<?php

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use App\Models\TutorialLesson;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@lg-exambot.test'],
            [
                'name' => 'Admin LG ExamBot',
                'password' => 'password',
                'role' => 'admin',
            ],
        );

        User::firstOrCreate(
            ['email' => 'guru@lg-exambot.test'],
            [
                'name' => 'Guru Demo',
                'password' => 'password',
                'role' => 'teacher',
            ],
        );

        DocumentTemplate::firstOrCreate(
            ['name' => 'Template Ujian Standar'],
            [
                'school_name' => 'Satuan Pendidikan Demo',
                'header_text' => 'Penilaian Sumatif Akhir Materi',
                'footer_text' => 'Dokumen dibuat menggunakan LG ExamBot',
                'is_default' => true,
            ],
        );

        $lessons = [
            ['Tutorial Dasar', 'Cara membuat sesi', 'Isi identitas penyusun, informasi akademik, mata pelajaran, dan materi sebelum menyusun struktur soal.'],
            ['Tutorial Dasar', 'Cara generate soal', 'Tambahkan bentuk soal, distribusi kesulitan, level kognitif, lalu klik Generate Naskah Soal.'],
            ['Tutorial Dasar', 'Cara export', 'Buka tab hasil generate, pilih Naskah/Kunci/Kisi-kisi, lalu export ke PDF atau DOCX.'],
            ['Quiz', 'Cara membuat quiz', 'Dari halaman hasil generate, isi judul quiz, durasi, visibilitas, password opsional, lalu aktifkan room.'],
            ['FAQ', 'Jika AI key belum diisi', 'Aplikasi memakai generator draft lokal agar alur builder tetap bisa diuji tanpa biaya API.'],
        ];

        foreach ($lessons as $index => [$category, $title, $body]) {
            TutorialLesson::firstOrCreate(
                ['title' => $title],
                [
                    'category' => $category,
                    'body' => $body,
                    'sort_order' => $index + 1,
                    'published_at' => now(),
                ],
            );
        }
    }
}
