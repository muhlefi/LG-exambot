<x-layouts.app title="Leaderboard - LG ExamBot">
    <div class="mx-auto max-w-3xl">
        <div class="rounded-[2rem] bg-ink p-8 text-white">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-honey">Leaderboard</p>
            <h1 class="ink-heading mt-2 text-5xl font-black">{{ $quiz->title }}</h1>
        </div>
        <div class="paper-panel mt-6 rounded-[2rem] p-6">
            <div class="space-y-3">
                @forelse ($quiz->participants as $participant)
                    <div class="flex items-center justify-between rounded-2xl bg-white/70 p-5">
                        <div>
                            <p class="font-black">{{ $participant->rank ?: '-' }}. {{ $participant->student_name }}</p>
                            <p class="text-sm text-ink/60">{{ $participant->finished_at?->format('d M Y H:i') ?: 'Belum selesai' }}</p>
                        </div>
                        <p class="ink-heading text-3xl font-black">{{ $participant->score }}</p>
                    </div>
                @empty
                    <p class="text-center text-ink/60">Belum ada peserta.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
