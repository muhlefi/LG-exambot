<x-layouts.app title="{{ $examSession->title }} - Builder">
    <div x-data="{ 
        generating: false, 
        questionType: 'Pilihan Ganda',
        cognitiveLevels: ['C1 Mengingat', 'C2 Memahami', 'C3 Menerapkan'],
        
        isChoice() { 
            return ['Pilihan Ganda', 'Pilihan Ganda Kompleks', 'HOTS'].includes(this.questionType) 
        },
        
        updateDefaults() {
            if (this.questionType === 'HOTS') {
                this.cognitiveLevels = ['C4 Menganalisis', 'C5 Mengevaluasi', 'C6 Mencipta'];
            } else if (['Essay', 'Studi Kasus'].includes(this.questionType)) {
                this.cognitiveLevels = ['C3 Menerapkan', 'C4 Menganalisis', 'C5 Mengevaluasi'];
            } else if (this.questionType === 'Isian Singkat') {
                this.cognitiveLevels = ['C1 Mengingat', 'C2 Memahami'];
            } else {
                this.cognitiveLevels = ['C1 Mengingat', 'C2 Memahami', 'C3 Menerapkan'];
            }
        }
    }" x-init="$watch('questionType', () => updateDefaults())">
    <nav class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-ink/30">
        <a href="{{ route('dashboard') }}" class="hover:text-fern">Dashboard</a>
        <span>/</span>
        <a href="{{ route('sessions.index') }}" class="hover:text-fern">Sesi</a>
        <span>/</span>
        <span class="text-fern">{{ $examSession->title }}</span>
    </nav>
    <div class="mb-8 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <div class="flex items-center gap-4">
                <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Builder Struktur Soal</p>
                <a href="{{ route('sessions.edit', $examSession) }}" class="rounded-full border border-ink/10 bg-white p-2 text-ink/40 transition-all hover:text-fern">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                </a>
            </div>
            <h1 class="ink-heading text-5xl font-black text-ink">{{ $examSession->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">{{ $examSession->school_name }} · {{ $examSession->subject }} · {{ $examSession->topic }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('sessions.results', $examSession) }}" class="rounded-full border border-fern/30 bg-white/70 px-5 py-3 text-sm font-black text-fern transition hover:bg-limewash">Lihat Hasil</a>
            <form method="POST" action="{{ route('sessions.generate', $examSession) }}" @submit="generating = true">
                @csrf
                <button @disabled($examSession->structures->isEmpty()) class="rounded-full bg-fern px-6 py-3 text-sm font-black text-white shadow-lg shadow-fern/20 transition hover:scale-105 disabled:cursor-not-allowed disabled:bg-ink/30 disabled:shadow-none">Generate Naskah Soal</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <!-- Loading Overlay -->
        <template x-if="generating">
            <div class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-white/90 backdrop-blur-md">
                <div class="relative">
                    <div class="h-24 w-24 animate-spin rounded-full border-8 border-fern/10 border-t-fern"></div>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-black uppercase text-fern">AI</span>
                    </div>
                </div>
                <h2 class="ink-heading mt-8 text-4xl font-black text-ink">Menyusun Naskah Soal...</h2>
                <p class="mt-4 animate-pulse text-sm font-bold text-ink/50">Mohon tunggu, AI sedang merancang butir soal sesuai struktur Anda.</p>
            </div>
        </template>

        <section class="paper-panel rounded-[2rem] p-6">
            <h2 class="ink-heading text-3xl font-black">Tambah Bagian</h2>
            <form method="POST" action="{{ route('sessions.structures.store', $examSession) }}" class="mt-6 space-y-5">
                @csrf
                <label class="block">
                    <span class="text-sm font-black">Nama Bagian</span>
                    <input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern" placeholder="Bagian A">
                </label>
                <label class="block">
                    <span class="text-sm font-black">Bentuk Soal</span>
                    <select name="question_type" x-model="questionType" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                        @foreach (['Pilihan Ganda','Pilihan Ganda Kompleks','Benar Salah','Menjodohkan','Isian Singkat','Essay','HOTS','Studi Kasus'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block" x-show="['Pilihan Ganda', 'Pilihan Ganda Kompleks', 'HOTS'].includes(questionType)" x-cloak x-transition>
                    <span class="text-sm font-black">Jumlah Opsi Jawaban</span>
                    <select name="option_count" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                        @foreach ([3,4,5] as $count)
                            <option value="{{ $count }}" @selected($count == 4)>{{ $count }} opsi</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="block">
                        <span class="text-sm font-black">Mudah</span>
                        <input name="easy_count" type="number" min="0" value="{{ old('easy_count', 10) }}" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black">Sedang</span>
                        <input name="medium_count" type="number" min="0" value="{{ old('medium_count', 5) }}" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black">Sulit</span>
                        <input name="hard_count" type="number" min="0" value="{{ old('hard_count', 5) }}" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    </label>
                </div>

                <div>
                    <p class="text-sm font-black">Dimensi Kognitif</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach (['C1 Mengingat','C2 Memahami','C3 Menerapkan','C4 Menganalisis','C5 Mengevaluasi','C6 Mencipta'] as $level)
                            @php
                                $isHigh = in_array($level, ['C4 Menganalisis','C5 Mengevaluasi','C6 Mencipta']);
                                $isLow = in_array($level, ['C1 Mengingat','C2 Memahami']);
                            @endphp
                            <label 
                                class="flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm font-bold transition-all"
                                x-show="questionType !== 'HOTS' || {{ $isHigh ? 'true' : 'false' }}"
                                :class="cognitiveLevels.includes('{{ $level }}') ? 'border-fern bg-fern/5' : 'border-transparent'"
                                x-transition
                            >
                                <input type="checkbox" name="cognitive_levels[]" value="{{ $level }}" x-model="cognitiveLevels">
                                {{ $level }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-sm font-black">Tambahkan Media</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <label class="flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm font-bold">
                            <input type="checkbox" name="has_question_image" value="1">
                            Gambar soal
                        </label>
                        <label class="flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm font-bold" x-show="isChoice()" x-cloak x-transition>
                            <input type="checkbox" name="has_option_image" value="1">
                            Gambar opsi
                        </label>
                        <label class="flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm font-bold">
                            <input type="checkbox" name="has_diagram" value="1">
                            Diagram
                        </label>
                        <label class="flex items-center gap-2 rounded-xl bg-white/70 px-3 py-2 text-sm font-bold">
                            <input type="checkbox" name="has_table" value="1">
                            Tabel
                        </label>
                    </div>
                </div>

                <button class="w-full rounded-xl bg-fern px-5 py-4 font-black text-white shadow-lg shadow-fern/20 transition hover:-translate-y-0.5">Tambahkan ke Struktur</button>
            </form>
        </section>

        <section class="paper-panel rounded-[2rem] p-6" x-data="structureSorter">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="ink-heading text-3xl font-black">Preview Struktur</h2>
                <div class="flex items-center gap-4">
                    <div class="flex flex-col items-end">
                        <span class="text-[10px] font-black uppercase tracking-widest text-ink/30 leading-none mb-1">Target Total</span>
                        <span class="rounded-full bg-limewash px-6 py-2 text-sm font-black {{ $examSession->structures->sum('total_questions') > 40 ? 'text-clay border-clay/20' : 'text-fern border-fern/10' }} border shadow-sm">
                            {{ $examSession->structures->sum('total_questions') }} <span class="opacity-40">/ 40</span> Butir Soal
                        </span>
                    </div>
                    <form method="POST" action="{{ route('sessions.generate', $examSession) }}" @submit="generating = true">
                        @csrf
                        <button @disabled($examSession->structures->isEmpty() || $examSession->structures->sum('total_questions') > 40) class="rounded-full bg-fern px-10 py-4 text-sm font-black text-white shadow-xl shadow-fern/20 transition hover:scale-105 active:scale-95 disabled:cursor-not-allowed disabled:bg-ink/30 disabled:shadow-none">Generate Naskah</button>
                    </form>
                </div>
            </div>

            <div class="space-y-4" x-ref="list">
                @forelse ($examSession->structures as $structure)
                    <article class="group relative rounded-[2rem] border border-ink/5 bg-white/40 p-6 transition-all hover:bg-white hover:shadow-xl hover:shadow-ink/5">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <button type="button" class="drag-handle cursor-grab rounded-xl bg-ink/5 p-2.5 text-ink/40 transition hover:bg-fern/10 hover:text-fern">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 8h16M4 16h16"></path></svg>
                                    </button>
                                    <div>
                                        <h3 class="text-xl font-black text-ink leading-tight">{{ $structure->name ?: 'Bagian '.$loop->iteration }}</h3>
                                        <p class="text-xs font-bold text-ink/30 uppercase tracking-widest mt-1">{{ $structure->question_type }}</p>
                                    </div>
                                </div>

                                <!-- Metadata Row -->
                                <div class="mt-6 flex flex-wrap gap-2">
                                    <div class="flex items-center gap-2 rounded-xl bg-ink/5 px-3 py-1.5 border border-ink/5">
                                        <span class="text-[10px] font-black uppercase text-ink/30">Total</span>
                                        <span class="text-xs font-black text-ink/70">{{ $structure->total_questions }} Butir</span>
                                    </div>
                                    <div class="flex items-center gap-2 rounded-xl bg-ink/5 px-3 py-1.5 border border-ink/5">
                                        <span class="text-[10px] font-black uppercase text-ink/30">Opsi</span>
                                        <span class="text-xs font-black text-ink/70">{{ $structure->option_count }} Pilihan</span>
                                    </div>
                                </div>

                                <!-- Difficulty Bar -->
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <div class="rounded-xl bg-fern/5 p-3 border border-fern/10">
                                        <p class="text-[9px] font-black uppercase tracking-tighter text-fern/60">Mudah</p>
                                        <p class="text-lg font-black text-fern leading-none mt-1">{{ $structure->easy_count }}</p>
                                    </div>
                                    <div class="rounded-xl bg-honey/5 p-3 border border-honey/10">
                                        <p class="text-[9px] font-black uppercase tracking-tighter text-honey-dark/60">Sedang</p>
                                        <p class="text-lg font-black text-honey-dark leading-none mt-1">{{ $structure->medium_count }}</p>
                                    </div>
                                    <div class="rounded-xl bg-clay/5 p-3 border border-clay/10">
                                        <p class="text-[9px] font-black uppercase tracking-tighter text-clay/60">Sulit</p>
                                        <p class="text-lg font-black text-clay leading-none mt-1">{{ $structure->hard_count }}</p>
                                    </div>
                                </div>

                                <!-- Cognitive Levels -->
                                @if($structure->cognitive_levels)
                                    <div class="mt-4 flex flex-wrap gap-1.5">
                                        @foreach($structure->cognitive_levels as $level)
                                            <span class="rounded-lg bg-white border border-ink/5 px-2 py-1 text-[10px] font-bold text-ink/40">{{ $level }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <!-- Media & Quick Actions -->
                            <div class="flex flex-col items-end gap-3 shrink-0">
                                <div class="flex flex-col items-end gap-1.5">
                                    @foreach (['has_question_image' => 'Gambar', 'has_option_image' => 'Opsi gambar', 'has_diagram' => 'Diagram', 'has_table' => 'Tabel'] as $field => $label)
                                        @if ($structure->{$field})
                                            <span class="flex items-center gap-1.5 rounded-full bg-limewash px-3 py-1 text-[9px] font-black uppercase text-fern shadow-sm">
                                                <span class="h-1 w-1 rounded-full bg-fern"></span>
                                                {{ $label }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Action Footer -->
                        <div class="mt-8 flex items-center justify-between pt-5 border-t border-ink/5">
                            <p class="text-[10px] font-bold italic text-ink/20">Ditambahkan {{ $structure->created_at->diffForHumans() }}</p>
                            <div class="flex items-center gap-3">
                                <form method="POST" action="{{ route('sessions.structures.duplicate', [$examSession, $structure]) }}">
                                    @csrf
                                    <button class="flex items-center gap-2 rounded-xl bg-ink/5 px-4 py-2 text-[11px] font-black text-ink/40 transition hover:bg-fern/10 hover:text-fern">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>
                                        Duplikasi
                                    </button>
                                </form>
                                <form id="deleteStructure{{ $structure->id }}" method="POST" action="{{ route('sessions.structures.destroy', [$examSession, $structure]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" onclick="confirmDelete('deleteStructure{{ $structure->id }}', 'Struktur soal ini akan dihapus!')" class="flex items-center gap-2 rounded-xl bg-clay/5 px-4 py-2 text-[11px] font-black text-clay transition hover:bg-clay hover:text-white">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="flex flex-col items-center justify-center rounded-[2.5rem] border-2 border-dashed border-ink/5 bg-ink/5 p-16 text-center">
                        <div class="h-16 w-16 rounded-full bg-white flex items-center justify-center text-ink/10 mb-6 shadow-inner">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg font-black text-ink/30">Belum ada struktur</h3>
                        <p class="mt-2 text-sm text-ink/20 max-w-[200px] mx-auto">Gunakan formulir di atas untuk merancang kerangka soal Anda.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
    </div>
</x-layouts.app>
