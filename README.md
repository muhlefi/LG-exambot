# LG ExamBot

LG ExamBot adalah aplikasi web berbasis Laravel untuk membantu guru, penyusun soal, sekolah, bimbel, atau institusi pendidikan dalam membuat paket evaluasi pembelajaran secara lebih cepat.

Aplikasi ini mendukung pembuatan:

- Naskah soal
- Kunci jawaban
- Kisi-kisi soal
- Paket quiz online
- Export dokumen PDF dan DOCX
- Manajemen sesi penyusunan soal
- Tutorial penggunaan aplikasi

Generator soal saat ini memakai mode `local-draft` sebagai fallback agar aplikasi bisa diuji tanpa API key AI. Struktur service sudah disiapkan untuk integrasi OpenAI atau Gemini.

## Tech Stack

- Backend: Laravel 13
- Frontend: Blade, Tailwind CSS 4, AlpineJS
- Realtime/UI component: Livewire 4 siap pakai
- Drag and drop: SortableJS
- Database: MySQL
- Queue/cache: Redis
- Redis client: Predis
- Export PDF: DomPDF
- Export DOCX: PHPWord
- Build tool: Vite
- Testing: PHPUnit

## Fitur Utama

- Login dan register user
- Role user: `admin`, `teacher`, `student`
- Dashboard statistik penggunaan
- Form identitas penyusun dan informasi akademik
- Builder struktur soal
- Pilihan bentuk soal: pilihan ganda, pilihan ganda kompleks, benar/salah, menjodohkan, isian singkat, essay, HOTS, studi kasus
- Pengaturan jumlah opsi jawaban
- Pengaturan jumlah soal berdasarkan tingkat kesulitan
- Pengaturan level kognitif C1 sampai C6
- Opsi kebutuhan gambar, diagram, dan tabel
- Preview struktur soal
- Duplikasi dan hapus struktur soal
- Generate naskah soal
- Generate kunci jawaban
- Generate kisi-kisi soal
- Export naskah, kunci, dan kisi-kisi ke PDF
- Export naskah, kunci, dan kisi-kisi ke DOCX
- Pembuatan quiz dari paket soal
- Join quiz menggunakan kode room
- Password room opsional
- Random soal dan random jawaban
- Submit jawaban siswa
- Skor otomatis
- Leaderboard
- Tutorial in-app
- Bank soal awal
- Pengaturan template dokumen dan API AI

## Struktur Folder Penting

```text
app/
  Http/Controllers/
    AuthController.php
    DashboardController.php
    ExamSessionController.php
    QuizController.php
    TutorialController.php
    BankQuestionController.php
    SettingsController.php
  Jobs/
    GenerateQuestionJob.php
    ExportPdfJob.php
  Models/
    ExamSession.php
    QuestionStructure.php
    Question.php
    QuestionOption.php
    QuestionBlueprint.php
    Quiz.php
    QuizParticipant.php
    QuizAnswer.php
  Services/
    AiPromptBuilder.php
    AiQuestionService.php
    ExportService.php
    QuizService.php

database/
  migrations/
  seeders/

resources/
  css/app.css
  js/app.js
  views/
    auth/
    dashboard/
    sessions/
    quizzes/
    tutorial/
    bank/
    settings/
    exports/

routes/
  web.php
```

## Kebutuhan Sistem

Pastikan sudah terinstall:

- PHP 8.3 atau lebih baru
- Composer
- Node.js dan npm
- MySQL
- Redis

Di Windows PowerShell, jika perintah `npm` terblokir karena execution policy, gunakan `npm.cmd`.

## Instalasi

Masuk ke folder project:

```bash
cd "D:\LefiArchive\Tugas\joki\Jaski IT\LG-exambot"
```

Install dependency PHP:

```bash
composer install
```

Install dependency frontend:

```bash
npm.cmd install
```

Salin file environment:

```bash
copy .env.example .env
```

Generate application key:

```bash
php artisan key:generate
```

Buat symbolic link storage:

```bash
php artisan storage:link
```

## Konfigurasi Environment

Contoh konfigurasi `.env`:

```env
APP_NAME="LG ExamBot"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID
```

Konfigurasi database MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=lg_exambot
DB_USERNAME=root
DB_PASSWORD=
```

Konfigurasi Redis:

```env
QUEUE_CONNECTION=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

Konfigurasi AI:

```env
AI_PROVIDER=local-draft
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
GEMINI_API_KEY=
GEMINI_MODEL=gemini-1.5-flash
```

Jika API key belum diisi, aplikasi tetap bisa membuat soal memakai generator draft lokal.

## Database

Buat database MySQL:

```sql
CREATE DATABASE lg_exambot;
```

Jalankan migration dan seeder:

```bash
php artisan migrate --seed
```

Seeder akan membuat akun demo, template dokumen awal, dan tutorial awal.

## Akun Demo

Setelah menjalankan seeder, akun berikut tersedia:

- Admin: `admin@lg-exambot.test` / `password`
- Guru: `guru@lg-exambot.test` / `password`

## Menjalankan Aplikasi

Mode development:

```bash
php artisan serve
npm.cmd run dev
```

Buka:

```text
http://127.0.0.1:8000
```

Build production asset:

```bash
npm.cmd run build
```

## Queue Worker

Untuk menjalankan queue Redis:

```bash
php artisan queue:work
```

Saat ini proses generate soal masih dijalankan langsung dari controller agar MVP mudah diuji. Job `GenerateQuestionJob` sudah tersedia jika ingin dipindahkan menjadi asynchronous.

## Alur Penggunaan

1. Login sebagai guru.
2. Buka menu `Sesi Soal`.
3. Klik `Buat Sesi Baru`.
4. Isi identitas penyusun, sekolah, jenjang, kelas, semester, mata pelajaran, dan materi.
5. Tambahkan struktur soal.
6. Pilih bentuk soal, jumlah opsi, tingkat kesulitan, level kognitif, dan kebutuhan media.
7. Klik `Tambahkan ke Struktur`.
8. Klik `Generate Naskah Soal`.
9. Buka tab `Naskah Soal`, `Kunci Jawaban`, atau `Kisi-Kisi`.
10. Export dokumen ke PDF atau DOCX.
11. Jika diperlukan, buat quiz dari halaman hasil generate.
12. Bagikan kode room quiz ke siswa.
13. Siswa join melalui halaman `Join Quiz`.
14. Lihat skor dan leaderboard.

## Role Pengguna

### Admin

- Mengakses dashboard
- Melihat fitur utama aplikasi
- Disiapkan untuk pengelolaan user, template, tutorial, monitoring AI, dan moderasi konten

### Guru / Penyusun

- Membuat sesi soal
- Menyusun struktur soal
- Generate soal
- Export PDF/DOCX
- Membuat quiz
- Melihat leaderboard

### Siswa

- Join quiz menggunakan kode room
- Menjawab soal
- Melihat leaderboard

## Route Utama

- `/` landing page
- `/login` login
- `/register` register
- `/dashboard` dashboard
- `/sessions` daftar sesi soal
- `/sessions/create` buat sesi soal
- `/sessions/{examSession}` builder struktur soal
- `/sessions/{examSession}/results` hasil generate
- `/sessions/{examSession}/export/{documentType}/{format}` export dokumen
- `/quizzes` daftar quiz guru
- `/quiz/join` halaman join quiz siswa
- `/quiz/play/{participant}` halaman pengerjaan quiz
- `/quiz/{quiz}/leaderboard` leaderboard
- `/tutorial` tutorial aplikasi
- `/bank-soal` bank soal
- `/settings` pengaturan

## Tabel Database Utama

- `users`
- `exam_sessions`
- `question_structures`
- `questions`
- `question_options`
- `blueprints`
- `quizzes`
- `quiz_participants`
- `quiz_answers`
- `document_templates`
- `export_logs`
- `ai_usage_logs`
- `tutorial_lessons`
- `bank_questions`

Catatan: tabel sesi pembuatan soal memakai nama `exam_sessions`, bukan `sessions`, agar tidak konflik dengan tabel session bawaan Laravel.

## Export Dokumen

Jenis dokumen:

- `questions`: naskah soal
- `answers`: kunci jawaban dan pembahasan
- `blueprint`: kisi-kisi soal

Format:

- `pdf`
- `docx`

Contoh route:

```text
/sessions/1/export/questions/pdf
/sessions/1/export/answers/docx
/sessions/1/export/blueprint/pdf
```

## AI Service

File utama:

```text
app/Services/AiPromptBuilder.php
app/Services/AiQuestionService.php
```

`AiPromptBuilder` bertugas membuat prompt berdasarkan identitas sesi dan struktur soal.

`AiQuestionService` bertugas membuat soal, opsi, kunci, pembahasan, dan kisi-kisi. Saat ini service menggunakan local deterministic generator agar aplikasi tetap berjalan tanpa API key.

Jika ingin integrasi provider sungguhan, tambahkan client HTTP di `AiQuestionService`, lalu gunakan output JSON provider untuk membuat record `questions`, `question_options`, dan `blueprints`.

## Testing

Jalankan test:

```bash
php artisan test
```

Jalankan build frontend:

```bash
npm.cmd run build
```

Jalankan formatter PHP:

```bash
vendor\bin\pint.bat
```

## Troubleshooting

### PowerShell memblokir npm

Gunakan:

```bash
npm.cmd install
npm.cmd run dev
npm.cmd run build
```

### Vite manifest not found

Jalankan:

```bash
npm.cmd run build
```

Atau untuk development:

```bash
npm.cmd run dev
```

### Storage tidak bisa menulis file

Pastikan `storage` dan `bootstrap/cache` writable. Di Windows, jalankan terminal sebagai user yang sama dengan web server.

### Redis belum berjalan

Jika Redis belum tersedia, sementara bisa ubah `.env`:

```env
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Lalu jalankan:

```bash
php artisan config:clear
```

### Database belum ada

Buat database `lg_exambot`, lalu jalankan:

```bash
php artisan migrate --seed
```

## Status Implementasi

Sudah tersedia:

- MVP aplikasi web
- Database schema
- UI dashboard dan builder
- Generate soal draft
- Export PDF/DOCX
- Quiz basic
- Seeder demo
- Test workflow utama

Rencana pengembangan berikutnya:

- Integrasi OpenAI/Gemini live
- Auto-save draft
- Editor soal per item
- Upload dan generate gambar per soal
- Share soal ke bank soal publik
- Manajemen template kop surat
- Analitik penggunaan AI dan quiz
- Queue async penuh untuk generate dan export
