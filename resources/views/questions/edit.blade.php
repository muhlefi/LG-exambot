<x-layouts.app title="Edit Soal - LG ExamBot">
    <nav class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-ink/30">
        <a href="{{ route('dashboard') }}" class="hover:text-fern">Dashboard</a>
        <span>/</span>
        <a href="{{ route('sessions.index') }}" class="hover:text-fern">Sesi</a>
        <span>/</span>
        <a href="{{ route('sessions.show', $question->examSession) }}" class="hover:text-fern">{{ $question->examSession->title }}</a>
        <span>/</span>
        <a href="{{ route('sessions.results', $question->examSession) }}" class="hover:text-fern">Hasil</a>
        <span>/</span>
        <span class="text-fern">Edit Soal</span>
    </nav>

    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Editor Soal</p>
            <h1 class="ink-heading text-5xl font-black">Edit Butir Soal</h1>
        </div>
        <a href="{{ route('sessions.results', $question->examSession) }}" class="text-sm font-bold text-ink/50 hover:text-ink">Batal & Kembali</a>
    </div>

    <div class="paper-panel rounded-[2.5rem] p-8">
        <form method="POST" action="{{ route('questions.update', $question) }}" class="space-y-6" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="space-y-6">
                    <label class="block">
                        <span class="text-sm font-black">Teks Soal</span>
                        <textarea name="question_text" rows="8" required class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-5 py-4 outline-none focus:border-fern leading-7">{{ old('question_text', $question->question_text) }}</textarea>
                    </label>

                    <div class="rounded-xl border border-ink/10 bg-limewash/30 p-5" x-data="{ 
                        previewUrl: '{{ $question->question_image ? Storage::url($question->question_image) : '' }}',
                        handleFileChange(event) {
                            const file = event.target.files[0];
                            if (file) {
                                this.previewUrl = URL.createObjectURL(file);
                            }
                        }
                    }">
                        <span class="text-sm font-black block mb-3">🖼️ Gambar / Ilustrasi Manual (Opsional)</span>
                        
                        <div class="mb-4 relative group w-fit" x-show="previewUrl">
                            <img :src="previewUrl" class="h-40 w-auto rounded-xl shadow-md border-2 border-white object-cover">
                            @if($question->question_image)
                                <label class="mt-2 flex items-center gap-2 text-xs font-bold text-clay cursor-pointer">
                                    <input type="checkbox" name="remove_image" value="1" class="rounded border-ink/10">
                                    Hapus Gambar Saat Ini
                                </label>
                            @endif
                        </div>

                        <input type="file" name="image" accept="image/*" @change="handleFileChange" class="w-full text-xs text-ink/50 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-fern file:text-white hover:file:bg-fern-dark cursor-pointer">
                        <div class="mt-2 flex flex-col gap-1">
                            <p class="text-[10px] font-bold text-ink/40">* Batas maksimal 3MB.</p>
                            <p class="text-[10px] font-bold text-ink/40">* Jika Anda mengunggah gambar di sini, gambar ini akan diutamakan daripada tag [GAMBAR: ...] otomatis.</p>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="text-sm font-black">Kunci Jawaban</span>
                            <input name="answer_key" value="{{ old('answer_key', $question->answer_key) }}" required class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-5 py-3 outline-none focus:border-fern">
                        </label>
                        <div class="flex flex-col justify-center">
                            <p class="text-xs font-bold text-ink/40">Gunakan label opsi (misal: A) atau teks jawaban untuk isian.</p>
                        </div>
                    </div>

                    <label class="block">
                        <span class="text-sm font-black">Pembahasan / Penjelasan</span>
                        <textarea name="explanation" rows="4" class="mt-2 w-full rounded-xl border border-ink/10 bg-white px-5 py-4 outline-none focus:border-fern text-sm leading-6">{{ old('explanation', $question->explanation) }}</textarea>
                    </label>
                </div>

                <div class="space-y-4">
                    <span class="text-sm font-black">Opsi Jawaban (Jika ada)</span>
                    <div class="grid gap-3">
                        @foreach ($question->options as $index => $option)
                            <div class="flex items-start gap-3 rounded-xl border border-ink/10 bg-white p-4">
                                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-limewash text-sm font-black text-fern">{{ $option->option_label }}</span>
                                <input type="hidden" name="options[{{ $index }}][id]" value="{{ $option->id }}">
                                <textarea name="options[{{ $index }}][text]" rows="2" class="w-full bg-transparent text-sm outline-none focus:ring-0">{{ old("options.$index.text", $option->option_text) }}</textarea>
                            </div>
                        @endforeach
                    </div>
                    @if($question->options->isEmpty())
                        <div class="rounded-xl border border-dashed border-ink/10 p-8 text-center text-ink/40">
                            <p class="text-sm">Soal ini tidak memiliki opsi (Bentuk Isian/Essay).</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="pt-6 border-t border-ink/5">
                <button class="rounded-full bg-fern px-10 py-4 text-sm font-black text-white shadow-xl shadow-fern/20 transition hover:-translate-y-1">Simpan Perubahan Soal</button>
            </div>
        </form>
    </div>
</x-layouts.app>
