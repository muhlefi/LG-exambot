<x-layouts.app title="Hasil Generate - LG ExamBot">
    <nav class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-ink/30">
        <a href="{{ route('dashboard') }}" class="hover:text-fern">Dashboard</a>
        <span>/</span>
        <a href="{{ route('sessions.index') }}" class="hover:text-fern">Sesi</a>
        <span>/</span>
        <a href="{{ route('sessions.show', $examSession) }}" class="hover:text-fern">{{ $examSession->title }}</a>
        <span>/</span>
        <span class="text-fern">Hasil Generate</span>
    </nav>

    <div class="mb-8 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Hasil Generate</p>
            <h1 class="ink-heading text-5xl font-black">{{ $examSession->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">{{ $examSession->questions->count() }} soal · {{ $examSession->subject }} · {{ $examSession->topic }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('sessions.print', $examSession) }}" target="_blank" class="rounded-full bg-ink px-4 py-2 text-xs font-black text-white shadow-lg shadow-ink/20 transition hover:scale-105">Cetak Naskah (Browser)</a>
            
            <div class="flex gap-1 rounded-full bg-fern/10 p-1">
                <span class="flex items-center px-3 text-[9px] font-black uppercase text-fern">Export PDF:</span>
                @foreach(['answers' => 'Kunci', 'blueprint' => 'Kisi-kisi'] as $type => $label)
                    <a href="{{ route('sessions.export', [$examSession, $type, 'pdf']) }}" class="rounded-full bg-white px-3 py-1 text-[9px] font-black text-fern hover:bg-fern hover:text-white transition-colors">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>

    <style>
        .prose table { width: 100%; border-collapse: collapse; margin: 1.5rem 0; background: white; border-radius: 1rem; overflow: hidden; }
        .prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.75rem 1rem; text-align: left; }
        .prose th { background-color: #f9fafb; font-weight: 800; }
    </style>

    <div class="grid gap-6 xl:grid-cols-[1fr_360px]" x-data="{ 
        tab: 'questions',
        selectedQuestions: [],
        allSelected: false,
        toggleAll() {
            if (this.allSelected) {
                this.selectedQuestions = [];
                this.allSelected = false;
            } else {
                this.selectedQuestions = Array.from(document.querySelectorAll('.question-checkbox')).map(el => el.value);
                this.allSelected = true;
            }
        },
        renderMath() {
            this.$nextTick(() => {
                if (window.renderMathInElement) {
                    renderMathInElement(document.body, {
                        delimiters: [
                            {left: '$$', right: '$$', display: true},
                            {left: '$', right: '$', display: false}
                        ],
                        throwOnError: false
                    });
                }
            });
        }
    }" x-init="renderMath()" x-effect="tab, renderMath()">
        <section class="paper-panel rounded-[2rem] p-6">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap gap-2 p-1.5 bg-ink/5 rounded-full border border-ink/5">
                    @foreach (['questions' => 'Naskah Soal', 'answers' => 'Kunci Jawaban', 'blueprint' => 'Kisi-Kisi'] as $key => $label)
                        <button type="button" @click="tab = '{{ $key }}'" 
                            :class="tab === '{{ $key }}' ? 'bg-white text-ink shadow-sm' : 'text-ink/50 hover:text-ink hover:bg-white/40'" 
                            class="rounded-full px-5 py-2 text-sm font-black transition-all duration-200">{{ $label }}</button>
                    @endforeach
                </div>

                <div x-show="tab === 'questions' && selectedQuestions.length > 0" x-transition class="flex items-center gap-3">
                    <span class="text-xs font-black text-ink/50"><span x-text="selectedQuestions.length"></span> dipilih</span>
                    <form id="batchDeleteForm" method="POST" action="{{ route('sessions.questions.batch-destroy', $examSession) }}">
                        @csrf
                        @method('DELETE')
                        <template x-for="id in selectedQuestions" :key="id">
                            <input type="hidden" name="question_ids[]" :value="id">
                        </template>
                        <button type="button" onclick="confirmDelete('batchDeleteForm', 'Semua soal yang dipilih akan dihapus permanen!')" class="rounded-full bg-clay px-4 py-2 text-xs font-black text-white shadow-lg shadow-clay/20">Hapus Masal</button>
                    </form>
                </div>
            </div>

            <div x-show="tab === 'questions'" class="space-y-5">
                <div class="flex items-center gap-3 px-5 py-2">
                    <label class="flex items-center gap-2 text-xs font-black text-ink/40 cursor-pointer">
                        <input type="checkbox" @click="toggleAll()" :checked="allSelected" class="h-4 w-4 rounded border-ink/10 text-fern focus:ring-0">
                        Pilih Semua
                    </label>
                </div>

                @forelse ($examSession->questions as $question)
                    <article class="stagger-in rounded-[1.5rem] border border-ink/10 bg-white/70 p-5 transition hover:border-fern/30 group" style="animation-delay: {{ $loop->index * 0.1 }}s">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <input type="checkbox" x-model="selectedQuestions" value="{{ $question->id }}" class="question-checkbox h-4 w-4 rounded border-ink/10 text-fern focus:ring-0">
                            <div class="flex flex-wrap gap-2">
                                <span class="rounded-lg bg-ink px-3 py-1 text-[10px] font-black uppercase text-white">No. {{ $loop->iteration }}</span>
                                <span class="rounded-lg bg-fern/10 px-3 py-1 text-[10px] font-black uppercase text-fern">Difficulty: {{ $question->difficulty }}</span>
                                <span class="rounded-lg bg-honey/10 px-3 py-1 text-[10px] font-black uppercase text-honey-dark">{{ $question->cognitive_level }}</span>
                            </div>
                            </div>
                            <div class="flex items-center gap-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('questions.edit', $question) }}" class="text-ink/40 transition hover:text-fern">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </a>
                                <form id="deleteForm{{ $question->id }}" method="POST" action="{{ route('questions.destroy', $question) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete('deleteForm{{ $question->id }}')" class="text-clay/40 transition hover:text-clay">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="prose max-w-none font-bold leading-7 text-ink">
                            {!! $question->formatted_text !!}
                        </div>

                        @if ($question->question_image)
                            <div class="mt-4">
                                <img src="{{ Storage::url($question->question_image) }}" class="rounded-[1.5rem] border-4 border-white shadow-lg max-h-[300px]">
                            </div>
                        @endif
                        @if ($question->options->isNotEmpty())
                            <div class="mt-4 grid gap-2 md:grid-cols-2">
                                @foreach ($question->options as $option)
                                    <div class="rounded-xl border border-ink/10 bg-white px-4 py-3 text-sm">
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

                @if ($examSession->questions->isNotEmpty())
                    <div class="stagger-in flex flex-col items-center justify-center py-12 text-center" style="animation-delay: {{ $examSession->questions->count() * 0.1 + 0.5 }}s">
                        <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-fern/10 text-fern shadow-inner">
                            <svg class="h-10 w-10 animate-bounce" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="ink-heading text-2xl font-black text-ink">Semua soal siap!</h3>
                        <p class="mt-2 max-w-xs text-sm text-ink/60 font-bold">Naskah, kunci jawaban, dan kisi-kisi telah berhasil disusun oleh AI.</p>
                        <div class="mt-6 flex gap-3">
                            <button @click="tab = 'answers'" class="rounded-full bg-honey/10 px-6 py-2 text-xs font-black text-honey-dark hover:bg-honey hover:text-white transition-colors">Lihat Kunci</button>
                            <button @click="tab = 'blueprint'" class="rounded-full bg-fern/10 px-6 py-2 text-xs font-black text-fern hover:bg-fern hover:text-white transition-colors">Lihat Kisi-Kisi</button>
                        </div>
                    </div>
                @endif
            </div>

            <div x-show="tab === 'answers'" class="space-y-3">
                @foreach ($examSession->questions as $question)
                    <div class="rounded-xl border border-ink/10 bg-white/70 p-4">
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
                    <input name="title" value="{{ old('title', 'Quiz '.$examSession->title) }}" class="w-full rounded-xl border border-white/20 bg-white/10 px-5 py-4 outline-none placeholder:text-white/60 focus:bg-white/20" placeholder="Judul quiz">
                    <div class="flex items-center gap-3 rounded-xl border border-white/20 bg-white/10 px-5 py-4">
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
                    <button class="mt-4 w-full rounded-xl bg-white px-5 py-4 font-black text-fern shadow-xl transition hover:-translate-y-1">Aktifkan Presentation Mode</button>
                </form>
            </div>

            <div class="paper-panel rounded-[2rem] p-6">
                <h2 class="ink-heading text-3xl font-black">Quiz dari sesi ini</h2>
                <div class="mt-4 space-y-2">
                    @forelse ($examSession->quizzes as $quiz)
                        <a href="{{ route('quizzes.show', $quiz) }}" class="block rounded-xl bg-white/70 p-4 text-sm font-black">{{ $quiz->title }} · {{ $quiz->quiz_code }}</a>
                    @empty
                        <p class="text-sm text-ink/60">Belum ada quiz.</p>
                    @endforelse
                </div>
            </div>
        </aside>
    </div>
</x-layouts.app>
