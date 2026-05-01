<x-layouts.app title="LG ExamBot">
    <section class="grid items-center gap-10 py-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="stagger-in">
            <p class="mb-5 inline-flex rounded-full border border-fern/20 bg-white/60 px-4 py-2 text-sm font-black uppercase tracking-[0.24em] text-fern">Laravel AI Exam Builder</p>
            <h1 class="ink-heading text-5xl font-black leading-[0.92] text-ink sm:text-7xl">Buat soal, kisi-kisi, kunci, dan quiz dari satu studio.</h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-ink/70">
                Platform untuk guru dan institusi pendidikan: builder struktur soal, generator AI, export PDF/DOCX, bank soal, dan mode quiz interaktif.
            </p>
            <div class="mt-8 flex flex-wrap gap-3">
                <a href="{{ route('login') }}" class="rounded-full bg-fern px-6 py-3 text-sm font-black text-white shadow-xl shadow-fern/20 transition hover:-translate-y-0.5">Mulai Buat Sesi</a>
            </div>
        </div>

        <div class="paper-panel stagger-in rounded-[2.5rem] p-6" style="animation-delay: 120ms">
            <div class="rounded-[2rem] bg-ink p-6 text-white">
                <p class="text-sm font-bold uppercase tracking-[0.24em] text-honey">Preview Paket</p>
                <h2 class="mt-5 ink-heading text-4xl font-black">Asesmen Ekosistem</h2>
                <div class="mt-6 space-y-3">
                    @foreach (['Pilihan Ganda HOTS', 'Kunci Jawaban Otomatis', 'Kisi-Kisi Kurikulum', 'Export PDF & DOCX'] as $item)
                        <div class="rounded-2xl bg-white/10 p-4 text-sm font-bold">{{ $item }}</div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.app>
