<x-layouts.app title="Detail Hasil Quiz - LG ExamBot">
    <nav class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-ink/30">
        <a href="{{ route('dashboard') }}" class="hover:text-fern">Dashboard</a>
        <span>/</span>
        <a href="{{ route('quiz.results') }}" class="hover:text-fern">Hasil Quiz</a>
        <span>/</span>
        <span class="text-fern">{{ $quiz->title }}</span>
    </nav>

    <div class="mb-8 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Detail Hasil Quiz</p>
            <h1 class="ink-heading text-5xl font-black">{{ $quiz->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">{{ $quiz->examSession->title }} · Kode: {{ $quiz->quiz_code }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if($quiz->status === 'active')
                <form action="{{ route('quizzes.deactivate', $quiz) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-full bg-clay px-5 py-2.5 text-xs font-black text-white transition hover:-translate-y-0.5">Akhiri Quiz</button>
                </form>
            @else
                <form action="{{ route('quizzes.activate', $quiz) }}" method="POST">
                    @csrf
                    <button type="submit" class="rounded-full bg-fern px-5 py-2.5 text-xs font-black text-white transition hover:-translate-y-0.5">Aktifkan Kembali</button>
                </form>
            @endif
        </div>
    </div>

    <!-- Summary Stats -->
    <div class="mb-6 grid grid-cols-2 gap-4 xl:grid-cols-4">
        <div class="paper-panel rounded-2xl p-5">
            <p class="text-xs font-black uppercase tracking-widest text-ink/40">Total Peserta</p>
            <p class="mt-1 text-3xl font-black text-ink">{{ $quiz->participants->count() }}</p>
        </div>
        <div class="paper-panel rounded-2xl p-5">
            <p class="text-xs font-black uppercase tracking-widest text-ink/40">Rata-rata Skor</p>
            <p class="mt-1 text-3xl font-black text-fern">
                {{ $quiz->participants->count() > 0 ? round($quiz->participants->avg('score'), 1) : 0 }}
            </p>
        </div>
        <div class="paper-panel rounded-2xl p-5">
            <p class="text-xs font-black uppercase tracking-widest text-ink/40">Skor Tertinggi</p>
            <p class="mt-1 text-3xl font-black text-honey">
                {{ $quiz->participants->count() > 0 ? round($quiz->participants->max('score'), 1) : 0 }}
            </p>
        </div>
        <div class="paper-panel rounded-2xl p-5">
            <p class="text-xs font-black uppercase tracking-widest text-ink/40">Status</p>
            <p class="mt-1 text-lg font-black text-ink">
                @if($quiz->status === 'active') Aktif
                @elseif($quiz->status === 'ended') Berakhir
                @else Nonaktif @endif
            </p>
        </div>
    </div>

    <!-- Leaderboard -->
    <div class="paper-panel rounded-[2rem] p-6 mb-6">
        <h2 class="ink-heading text-2xl font-black mb-4">Peringkat Peserta</h2>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[500px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink/10 text-xs uppercase tracking-[0.18em] text-ink/50">
                        <th class="py-3 pr-4">Peringkat</th>
                        <th class="py-3 pr-4">Nama</th>
                        <th class="py-3 pr-4">Skor</th>
                        <th class="py-3 pr-4">Benar</th>
                        <th class="py-3 pr-4">Salah</th>
                        <th class="py-3 pr-4">Kosong</th>
                        <th class="py-3">Waktu</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/10">
                    @forelse($quiz->participants->sortByDesc('score')->values() as $index => $participant)
                        <tr class="hover:bg-ink/5 transition-colors">
                            <td class="py-3 pr-4">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-black
                                    @if($index === 0) bg-honey text-white
                                    @elseif($index === 1) bg-ink/30 text-white
                                    @elseif($index === 2) bg-clay/40 text-white
                                    @else bg-ink/5 text-ink/50 @endif">
                                    {{ $index + 1 }}
                                </span>
                            </td>
                            <td class="py-3 pr-4 font-black text-ink">{{ $participant->student_name }}</td>
                            <td class="py-3 pr-4">
                                <span class="font-black text-fern">{{ round($participant->score) }}</span>
                            </td>
                            <td class="py-3 pr-4 text-fern">{{ $participant->answers->where('is_correct', true)->count() }}</td>
                            <td class="py-3 pr-4 text-clay">{{ $participant->answers->where('is_correct', false)->count() }}</td>
                            <td class="py-3 pr-4 text-ink/40">{{ $participant->answers->whereNull('selected_answer')->count() }}</td>
                            <td class="py-3 text-ink/50 text-xs">{{ $participant->finished_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-ink/40 font-bold">Belum ada peserta.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Question Analysis -->
    <div class="paper-panel rounded-[2rem] p-6" x-data="{ showAnswer: false }">
        <div class="mb-4 flex items-center justify-between">
            <h2 class="ink-heading text-2xl font-black">Analisis Soal</h2>
            <button @click="showAnswer = !showAnswer" class="rounded-xl bg-ink/5 px-4 py-2 text-xs font-black text-ink transition hover:bg-ink/10">
                <span x-text="showAnswer ? 'Sembunyikan Kunci' : 'Tampilkan Kunci'"></span>
            </button>
        </div>
        <div class="space-y-4">
            @php $qNum = 0; @endphp
            @foreach($quiz->examSession->questions as $question)
                @php
                    $qNum++;
                    $correctPct = $quiz->participants->count() > 0
                        ? round(($question->correct_count / $quiz->participants->count()) * 100, 1)
                        : 0;
                @endphp
                <div class="rounded-xl border border-ink/10 bg-white/70 p-4">
                    <div class="flex items-start gap-3 mb-3">
                        <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-xs font-black bg-ink/5 text-ink/50">{{ $qNum }}</span>
                        <div class="text-sm font-bold text-ink flex-1">{!! Str::limit(strip_tags($question->question_text), 120) !!}</div>
                        <template x-if="showAnswer">
                            <span class="shrink-0 rounded-lg bg-fern/10 px-2 py-1 text-xs font-black text-fern">Kunci: {{ $question->answer_key }}</span>
                        </template>
                    </div>
                    <div class="ml-10 flex items-center gap-4">
                        <div class="flex-1 h-2 rounded-full bg-ink/5 overflow-hidden">
                            <div class="h-full bg-fern rounded-full transition-all" style="width: {{ $correctPct }}%"></div>
                        </div>
                        <span class="shrink-0 text-xs font-black text-ink/40 w-12 text-right">{{ $correctPct }}%</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
