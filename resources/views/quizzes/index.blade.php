<x-layouts.app title="Quiz - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Mode Quiz</p>
            <h1 class="ink-heading text-5xl font-black">Quiz aktif dan riwayat</h1>
        </div>
        <a href="{{ route('quizzes.join.form') }}" class="rounded-full border border-ink/10 bg-white/70 px-5 py-3 text-sm font-black">Halaman Join</a>
    </div>

    <div class="paper-panel overflow-hidden rounded-[2rem]">
        <div class="divide-y divide-ink/10">
            @forelse ($quizzes as $quiz)
                <a href="{{ route('quizzes.show', $quiz) }}" class="grid gap-4 p-5 transition hover:bg-white/70 md:grid-cols-[1fr_auto]">
                    <div>
                        <h2 class="text-lg font-black">{{ $quiz->title }}</h2>
                        <p class="mt-2 text-sm text-ink/60">{{ $quiz->examSession->subject }} · {{ $quiz->examSession->topic }}</p>
                    </div>
                    <div class="text-sm font-bold text-ink/60">
                        <p>Kode: <span class="text-ink">{{ $quiz->quiz_code }}</span></p>
                        <p>{{ $quiz->participants_count }} peserta</p>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center text-ink/60">Belum ada quiz. Buat quiz dari halaman hasil generate sesi.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">{{ $quizzes->links() }}</div>
</x-layouts.app>
