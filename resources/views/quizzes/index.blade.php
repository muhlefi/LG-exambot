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
                <div class="group relative overflow-hidden bg-white/50 transition-all hover:bg-white">
                    <div class="grid gap-6 p-8 md:grid-cols-[1fr_auto_auto]">
                        <div class="flex items-start gap-5">
                            <div class="grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-limewash text-fern shadow-sm transition-transform group-hover:scale-110">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div class="min-w-0">
                                <h2 class="ink-heading text-xl font-black truncate text-ink">{{ $quiz->title }}</h2>
                                <p class="mt-2 text-sm font-medium text-ink/60">{{ $quiz->examSession->subject }} · {{ $quiz->examSession->topic }}</p>
                                <div class="mt-3 flex items-center gap-3">
                                    <span class="rounded-full bg-ink px-2.5 py-0.5 text-[10px] font-black uppercase tracking-widest text-white">Kode: {{ $quiz->quiz_code }}</span>
                                    <span class="text-[10px] font-bold text-ink/30 italic">Dibuat {{ $quiz->created_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4 border-t border-ink/5 pt-4 md:border-t-0 md:pt-0">
                            <div class="text-right">
                                <p class="text-[10px] font-black uppercase tracking-widest text-ink/30">Durasi</p>
                                <p class="text-sm font-black text-ink">{{ $quiz->duration }} Menit</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <a href="{{ route('quizzes.show', $quiz) }}" class="rounded-full bg-fern px-6 py-2.5 text-xs font-black text-white shadow-lg shadow-fern/20 transition hover:scale-105 active:scale-95">Mulai Presentasi</a>
                            <form id="deleteQuiz{{ $quiz->id }}" method="POST" action="{{ route('quizzes.destroy', $quiz) }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" onclick="confirmDelete('deleteQuiz{{ $quiz->id }}', 'Quiz ini akan dihapus permanen!')" class="rounded-full bg-clay/10 p-2.5 text-clay transition hover:bg-clay hover:text-white">
                                    <svg class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-20 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full bg-ink/5 flex items-center justify-center text-ink/20 mb-6">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-black text-ink">Belum ada riwayat quiz</h3>
                    <p class="mt-2 text-sm text-ink/40 max-w-sm mx-auto">Riwayat akan muncul di sini setelah Anda mengaktifkan Presentation Mode dari halaman hasil generate.</p>
                    <a href="{{ route('sessions.index') }}" class="mt-8 inline-block rounded-full bg-ink px-8 py-3 text-sm font-black text-white shadow-lg shadow-ink/20 transition hover:bg-fern">Buka Daftar Sesi</a>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">{{ $quizzes->links() }}</div>
</x-layouts.app>
