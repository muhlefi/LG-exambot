<x-layouts.app title="Hasil Generate - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Hasil Generate</p>
            <h1 class="ink-heading text-5xl font-black">{{ $examSession->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">{{ $examSession->questions->count() }} soal · {{ $examSession->subject }} · {{ $examSession->topic }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach (['questions' => 'Naskah', 'answers' => 'Kunci', 'blueprint' => 'Kisi-kisi'] as $type => $label)
                <a href="{{ route('sessions.export', [$examSession, $type, 'pdf']) }}" class="rounded-full bg-ink px-4 py-2 text-xs font-black text-white">{{ $label }} PDF</a>
                <a href="{{ route('sessions.export', [$examSession, $type, 'docx']) }}" class="rounded-full border border-ink/10 bg-white/70 px-4 py-2 text-xs font-black">{{ $label }} DOCX</a>
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
                        <div class="mb-3 flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-ink px-3 py-1 text-xs font-black text-white">No. {{ $loop->iteration }}</span>
                            <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black text-fern">{{ $question->difficulty }}</span>
                            <span class="rounded-full bg-honey/40 px-3 py-1 text-xs font-black text-ink">{{ $question->cognitive_level }}</span>
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
            <div class="rounded-[2rem] bg-ink p-6 text-white">
                <h2 class="ink-heading text-3xl font-black">Buat Quiz</h2>
                <form method="POST" action="{{ route('quizzes.store', $examSession) }}" class="mt-5 space-y-4">
                    @csrf
                    <input name="title" value="{{ old('title', 'Quiz '.$examSession->title) }}" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 outline-none placeholder:text-white/40" placeholder="Judul quiz">
                    <input name="duration" type="number" min="1" max="240" value="30" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 outline-none">
                    <select name="visibility" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 outline-none">
                        <option class="text-ink" value="private">Private</option>
                        <option class="text-ink" value="public">Public</option>
                    </select>
                    <input name="password" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 outline-none placeholder:text-white/40" placeholder="Password room opsional">
                    <input name="max_participants" type="number" min="1" class="w-full rounded-2xl border border-white/10 bg-white/10 px-4 py-3 outline-none placeholder:text-white/40" placeholder="Maks peserta">
                    <label class="flex items-center gap-2 text-sm font-bold"><input name="is_random_question" value="1" type="checkbox" checked> Acak soal</label>
                    <label class="flex items-center gap-2 text-sm font-bold"><input name="is_random_answer" value="1" type="checkbox" checked> Acak jawaban</label>
                    <button class="w-full rounded-2xl bg-honey px-5 py-3 font-black text-ink">Aktifkan Quiz</button>
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
