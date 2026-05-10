<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quiz->title }} - LG ExamBot</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-honey/30 via-white to-fern/10 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-fern text-xl font-black text-white shadow-lg shadow-fern/30 mb-4">
                LG
            </div>
            <h1 class="text-2xl font-black text-ink">{{ $quiz->title }}</h1>
            <p class="mt-2 text-sm text-ink/50">Quiz siap dimulai. Masukkan nama kamu di bawah.</p>
        </div>

        <div class="bg-white rounded-[2rem] p-8 shadow-xl shadow-ink/5">
            <form action="{{ route('quiz.start', $quiz->quiz_code) }}" method="POST" class="space-y-6">
                @csrf
                <div>
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-ink/50 mb-2">Nama Lengkap</label>
                    <input type="text" name="student_name" placeholder="Ketik nama kamu..."
                        class="w-full rounded-xl border-2 border-ink/10 bg-ink/5 px-6 py-4 text-lg font-bold outline-none focus:border-fern focus:bg-fern/5 transition-all"
                        minlength="2" maxlength="100" required autofocus>
                    @error('student_name')
                        <p class="mt-2 text-xs font-black text-clay">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-ink/5 rounded-xl p-4 space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-black text-ink/50">Durasi</span>
                        <span class="font-black text-fern">{{ $quiz->duration }} menit</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-black text-ink/50">Jumlah Soal</span>
                        <span class="font-black text-fern">{{ $quiz->examSession->questions->count() }}</span>
                    </div>
                </div>

                <button type="submit" class="w-full rounded-xl bg-fern px-6 py-4 font-black text-white shadow-lg shadow-fern/20 transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-fern/30">
                    Mulai Quiz
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('quiz.join') }}" class="text-xs font-black text-ink/30 hover:text-ink/50 uppercase tracking-widest">
                    ← Kembali
                </a>
            </div>
        </div>
    </div>
</body>
</html>
