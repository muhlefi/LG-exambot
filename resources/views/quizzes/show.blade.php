<x-layouts.app title="{{ $quiz->title }} - Quiz">
    <div class="grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="paper-panel rounded-[2rem] p-6">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Room Quiz</p>
            <h1 class="ink-heading mt-2 text-5xl font-black">{{ $quiz->title }}</h1>
            <div class="mt-6 rounded-[2rem] bg-ink p-6 text-white">
                <p class="text-sm font-bold text-white/60">Kode Room</p>
                <p class="mt-2 text-5xl font-black tracking-[0.2em] text-honey">{{ $quiz->quiz_code }}</p>
                <p class="mt-4 text-sm text-white/70">Bagikan kode ini ke siswa. Halaman join: {{ route('quizzes.join.form') }}</p>
            </div>

            <div class="mt-6 grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl bg-white/70 p-4"><p class="text-sm text-ink/50">Durasi</p><p class="font-black">{{ $quiz->duration }} menit</p></div>
                <div class="rounded-2xl bg-white/70 p-4"><p class="text-sm text-ink/50">Soal</p><p class="font-black">{{ $quiz->examSession->questions->count() }}</p></div>
                <div class="rounded-2xl bg-white/70 p-4"><p class="text-sm text-ink/50">Peserta</p><p class="font-black">{{ $quiz->participants->count() }}</p></div>
            </div>
        </section>

        <aside class="paper-panel rounded-[2rem] p-6">
            <h2 class="ink-heading text-3xl font-black">Leaderboard</h2>
            <div class="mt-4 space-y-2">
                @forelse ($quiz->participants as $participant)
                    <div class="flex items-center justify-between rounded-2xl bg-white/70 p-4 text-sm">
                        <span class="font-black">{{ $participant->rank ?: '-' }}. {{ $participant->student_name }}</span>
                        <span>{{ $participant->score }}</span>
                    </div>
                @empty
                    <p class="text-sm text-ink/60">Belum ada peserta.</p>
                @endforelse
            </div>
        </aside>
    </div>
</x-layouts.app>
