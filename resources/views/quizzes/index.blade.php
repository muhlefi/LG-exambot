<x-layouts.app title="Quiz - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Mode Quiz</p>
            <h1 class="ink-heading text-5xl font-black">Quiz aktif dan riwayat</h1>
        </div>

    </div>

    <div class="paper-panel overflow-hidden rounded-[2rem]">
        <div class="divide-y divide-ink/10">
            @forelse ($quizzes as $quiz)
                <a href="{{ route('quizzes.show', $quiz) }}" class="grid gap-4 p-5 transition hover:bg-white/70 md:grid-cols-[1fr_auto]">
                    <div>
                        <h2 class="text-lg font-black">{{ $quiz->title }}</h2>
                        <p class="mt-2 text-sm text-ink/60">{{ $quiz->examSession->subject }} · {{ $quiz->examSession->topic }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2 text-sm font-bold text-ink/60">
                        <span class="rounded-full bg-honey px-3 py-1 text-[10px] font-black uppercase tracking-wider text-ink">Presentation Mode</span>
                        <div class="text-right">
                            <p>Kode: <span class="text-ink">{{ $quiz->quiz_code }}</span></p>
                        </div>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center text-ink/60">Belum ada quiz. Buat quiz dari halaman hasil generate sesi.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">{{ $quizzes->links() }}</div>
</x-layouts.app>
