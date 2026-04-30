<x-layouts.app title="Hasil Generate - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Hasil Generate</p>
            <h1 class="ink-heading text-5xl font-black">{{ $examSession->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">{{ $examSession->questions->count() }} soal · {{ $examSession->subject }} · {{ $examSession->topic }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['questions' => 'Naskah', 'answers' => 'Kunci', 'blueprint' => 'Kisi-kisi'] as $type => $label)
                <a href="{{ route('sessions.export', [$examSession, $type, 'pdf']) }}" class="rounded-full bg-fern px-4 py-2 text-xs font-black text-white shadow-lg shadow-fern/20 transition hover:scale-105">{{ $label }} PDF</a>
                <a href="{{ route('sessions.export', [$examSession, $type, 'docx']) }}" class="rounded-full border border-fern/20 bg-white/70 px-4 py-2 text-xs font-black text-fern transition hover:bg-limewash">{{ $label }} DOCX</a>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]">
        <section class="paper-panel rounded-[2rem] p-6" x-data="{ tab: 'questions' }">
            <div class="mb-6 flex flex-wrap gap-2">
                @foreach (['questions' => 'Naskah Soal', 'answers' => 'Kunci Jawaban', 'blueprint' => 'Kisi-Kisi'] as $key => $label)
                    <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-honey text-ink' : 'bg-white/70 text-ink/60'" class="rounded-full px-4 py-2 text-sm font-black">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="tab === 'questions'" class="space-y-5">
                @forelse ($examSession->questions as $question)
                    <article class="rounded-[1.5rem] border border-ink/10 bg-white/70 p-5">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-fern px-3 py-1 text-xs font-black text-white">No. {{ $loop->iteration }}</span>
                                <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black text-fern">{{ $question->difficulty }}</span>
                                <span class="rounded-full bg-honey/10 px-3 py-1 text-xs font-black text-honey">{{ $question->cognitive_level }}</span>
                            </div>
                            <form method="POST" action="{{ route('questions.destroy', $question) }}" onsubmit="return confirm('Hapus soal ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-clay/50 transition hover:text-clay">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                        <p class="font-bold leading-7">{{ $question->question_text }}</p>
                        @if ($question->options->isNotEmpty())
                            <div class="mt-4 grid gap-2 md:grid-cols-2">
                                @foreach ($question->options as $option)
                                    <div class="rounded-2xl border border-ink/10 bg-white px-4 py-3 text-sm">
                                        <span class="font-black">{{ $option->option_label }}.</span> {{ $option->option_text }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-ink/20 p-10 text-center text-ink/60">
                        Belum ada soal. Kembali ke builder lalu klik Generate Naskah Soal.
                    </div>
                @endforelse
            </div>

            <div x-show="tab === 'answers'" class="space-y-3">
                @foreach ($examSession->questions as $question)
                    <div class="rounded-2xl border border-ink/10 bg-white/70 p-4">
                        <p class="font-black">No. {{ $loop->iteration }} · Jawaban: {{ $question->answer_key }}</p>
                        <p class="mt-2 text-sm leading-6 text-ink/70">{{ $question->explanation }}</p>
                    </div>
                @endforeach
            </div>

            <div x-show="tab === 'blueprint'" class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead>
                        <tr class="border-b border-ink/10 text-xs uppercase tracking-[0.18em] text-ink/50">
                            <th class="py-3">No</th>
                            <th>Kompetensi</th>
                            <th>Indikator</th>
                            <th>Level</th>
                            <th>Bentuk</th>
                            <th>Jawaban</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink/10">
                        @foreach ($examSession->questions as $question)
                            <tr>
                                <td class="py-3 font-black">{{ $loop->iteration }}</td>
                                <td>{{ $question->blueprint?->competency }}</td>
                                <td>{{ $question->blueprint?->indicator }}</td>
                                <td>{{ $question->cognitive_level }}</td>
                                <td>{{ $question->question_type }}</td>
                                <td>{{ $question->answer_key }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <aside class="space-y-6">
            <div class="rounded-[2.5rem] bg-fern p-8 text-white shadow-2xl shadow-fern/20">
                <h2 class="ink-heading text-3xl font-black">Buat Quiz</h2>
                <p class="mt-1 text-sm font-bold opacity-80">Aktifkan mode presentasi dari sesi ini.</p>
                <form method="POST" action="{{ route('quizzes.store', $examSession) }}" class="mt-6 space-y-4">
                    @csrf
                    <input name="title" value="{{ old('title', 'Quiz '.$examSession->title) }}" class="w-full rounded-2xl border border-white/20 bg-white/10 px-5 py-4 outline-none placeholder:text-white/60 focus:bg-white/20" placeholder="Judul quiz">
                    <div class="flex items-center gap-3 rounded-2xl border border-white/20 bg-white/10 px-5 py-4">
                        <span class="text-sm font-bold opacity-60">Durasi</span>
                        <input name="duration" type="number" min="1" max="240" value="30" class="w-full bg-transparent font-black outline-none">
                        <span class="text-xs font-black uppercase opacity-60">Menit</span>
                    </div>
                    
                    <div class="space-y-2 pt-2">
                        <label class="flex items-center gap-3 text-sm font-black cursor-pointer group">
                            <input name="is_random_question" value="1" type="checkbox" checked class="h-5 w-5 rounded-lg border-white/20 bg-white/10 text-honey focus:ring-0">
                            <span class="group-hover:translate-x-1 transition-transform">Acak urutan soal</span>
                        </label>
                        <label class="flex items-center gap-3 text-sm font-black cursor-pointer group">
                            <input name="is_random_answer" value="1" type="checkbox" checked class="h-5 w-5 rounded-lg border-white/20 bg-white/10 text-honey focus:ring-0">
                            <span class="group-hover:translate-x-1 transition-transform">Acak urutan jawaban</span>
                        </label>
                    </div>
                    <button class="mt-4 w-full rounded-2xl bg-white px-5 py-4 font-black text-fern shadow-xl transition hover:-translate-y-1">Aktifkan Presentation Mode</button>
                </form>
            </div>

            <div class="paper-panel rounded-[2rem] p-6">
                <h2 class="ink-heading text-3xl font-black">Quiz dari sesi ini</h2>
                <div class="mt-4 space-y-2">
                    @forelse ($examSession->quizzes as $quiz)
                        <a href="{{ route('quizzes.show', $quiz) }}" class="block rounded-2xl bg-white/70 p-4 text-sm font-black">{{ $quiz->title }} · {{ $quiz->quiz_code }}</a>
                    @empty
                        <p class="text-sm text-ink/60">Belum ada quiz.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
