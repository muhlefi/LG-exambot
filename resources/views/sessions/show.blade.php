<x-layouts.app title="{{ $examSession->title }} - Builder">
    <div class="mb-6 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Builder Struktur Soal</p>
            <h1 class="ink-heading text-5xl font-black text-ink">{{ $examSession->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">{{ $examSession->school_name }} · {{ $examSession->subject }} · {{ $examSession->topic }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('sessions.results', $examSession) }}" class="rounded-full border border-ink/10 bg-white/70 px-5 py-3 text-sm font-black text-ink">Lihat Hasil</a>
            <form method="POST" action="{{ route('sessions.generate', $examSession) }}">
                @csrf
                <button @disabled($examSession->structures->isEmpty()) class="rounded-full bg-fern px-5 py-3 text-sm font-black text-white disabled:cursor-not-allowed disabled:bg-ink/30">Generate Naskah Soal</button>
            </form>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-[420px_1fr]">
        <section class="paper-panel rounded-[2rem] p-6">
            <h2 class="ink-heading text-3xl font-black">Tambah Bagian</h2>
            <form method="POST" action="{{ route('sessions.structures.store', $examSession) }}" class="mt-6 space-y-5">
                @csrf
                <label class="block">
                    <span class="text-sm font-black">Nama Bagian</span>
                    <input name="name" value="{{ old('name') }}" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern" placeholder="Bagian A">
                </label>
                <label class="block">
                    <span class="text-sm font-black">Bentuk Soal</span>
                    <select name="question_type" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                        @foreach (['Pilihan Ganda','Pilihan Ganda Kompleks','Benar Salah','Menjodohkan','Isian Singkat','Essay','HOTS','Studi Kasus'] as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block">
                    <span class="text-sm font-black">Jumlah Opsi Jawaban</span>
                    <select name="option_count" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                        @foreach ([4,5,6] as $count)
                            <option value="{{ $count }}">{{ $count }} opsi</option>
                        @endforeach
                    </select>
                </label>
                <div class="grid grid-cols-3 gap-3">
                    <label class="block">
                        <span class="text-sm font-black">Mudah</span>
                        <input name="easy_count" type="number" min="0" value="{{ old('easy_count', 10) }}" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black">Sedang</span>
                        <input name="medium_count" type="number" min="0" value="{{ old('medium_count', 5) }}" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    </label>
                    <label class="block">
                        <span class="text-sm font-black">Sulit</span>
                        <input name="hard_count" type="number" min="0" value="{{ old('hard_count', 5) }}" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    </label>
                </div>

                <div>
                    <p class="text-sm font-black">Dimensi Kognitif</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach (['C1 Mengingat','C2 Memahami','C3 Menerapkan','C4 Menganalisis','C5 Mengevaluasi','C6 Mencipta'] as $level)
                            <label class="flex items-center gap-2 rounded-2xl bg-white/70 px-3 py-2 text-sm font-bold">
                                <input type="checkbox" name="cognitive_levels[]" value="{{ $level }}" @checked(in_array($level, old('cognitive_levels', ['C1 Mengingat','C2 Memahami','C3 Menerapkan'])))>
                                {{ $level }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-sm font-black">Tambahkan Media</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        @foreach ([
                            'has_question_image' => 'Gambar soal',
                            'has_option_image' => 'Gambar opsi',
                            'has_diagram' => 'Diagram',
                            'has_table' => 'Tabel',
                        ] as $name => $label)
                            <label class="flex items-center gap-2 rounded-2xl bg-white/70 px-3 py-2 text-sm font-bold">
                                <input type="checkbox" name="{{ $name }}" value="1">
                                {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <button class="w-full rounded-2xl bg-honey px-5 py-3 font-black text-ink">Tambahkan ke Struktur</button>
            </form>
        </section>

        <section class="paper-panel rounded-[2rem] p-6" x-data="structureSorter">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="ink-heading text-3xl font-black">Preview Struktur</h2>
                <span class="rounded-full bg-limewash px-4 py-2 text-sm font-black text-fern">{{ $examSession->structures->sum('total_questions') }} soal</span>
            </div>

            <div class="space-y-3" x-ref="list">
                @forelse ($examSession->structures as $structure)
                    <article class="rounded-[1.5rem] border border-ink/10 bg-white/70 p-5">
                        <div class="flex flex-col justify-between gap-4 md:flex-row">
                            <div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="drag-handle cursor-grab rounded-full bg-ink px-3 py-1 text-xs font-black text-white">drag</button>
                                    <h3 class="text-lg font-black">{{ $structure->name ?: 'Bagian '.$loop->iteration }}</h3>
                                </div>
                                <p class="mt-2 text-sm text-ink/60">{{ $structure->question_type }} · {{ $structure->total_questions }} soal · {{ $structure->option_count }} opsi</p>
                                <p class="mt-1 text-sm text-ink/60">Mudah {{ $structure->easy_count }}, Sedang {{ $structure->medium_count }}, Sulit {{ $structure->hard_count }}</p>
                                <p class="mt-1 text-sm text-ink/60">{{ implode(', ', $structure->cognitive_levels ?? []) }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2 md:justify-end">
                                @foreach (['has_question_image' => 'Gambar', 'has_option_image' => 'Opsi gambar', 'has_diagram' => 'Diagram', 'has_table' => 'Tabel'] as $field => $label)
                                    @if ($structure->{$field})
                                        <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black text-fern">{{ $label }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('sessions.structures.duplicate', [$examSession, $structure]) }}">
                                @csrf
                                <button class="rounded-full border border-ink/10 px-3 py-2 text-xs font-black">Duplikasi</button>
                            </form>
                            <form method="POST" action="{{ route('sessions.structures.destroy', [$examSession, $structure]) }}" onsubmit="return confirm('Hapus struktur ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-full border border-clay/30 px-3 py-2 text-xs font-black text-clay">Hapus</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-[1.5rem] border border-dashed border-ink/20 p-10 text-center text-ink/60">
                        Struktur soal belum ditambahkan.
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
