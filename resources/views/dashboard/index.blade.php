<x-layouts.app title="Dashboard - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Dashboard</p>
            <h1 class="ink-heading text-5xl font-black text-ink">Ruang kontrol asesmen</h1>
        </div>
        <a href="{{ route('sessions.create') }}" class="rounded-full bg-fern px-5 py-3 text-sm font-black text-white shadow-xl shadow-fern/20">Buat Sesi Baru</a>
    </div>

    <div class="grid gap-4 md:grid-cols-5">
        @foreach ([
            ['label' => 'Sesi', 'value' => $sessionCount],
            ['label' => 'Soal', 'value' => $questionCount],
            ['label' => 'Quiz', 'value' => $quizCount],
            ['label' => 'Export', 'value' => $exportCount],
            ['label' => 'AI Run', 'value' => $aiUsageCount],
        ] as $metric)
            <div class="paper-panel rounded-[1.7rem] p-5">
                <p class="text-sm font-bold text-ink/50">{{ $metric['label'] }}</p>
                <p class="mt-3 ink-heading text-4xl font-black">{{ $metric['value'] }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_360px]">
        <section class="paper-panel rounded-[2rem] p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="ink-heading text-3xl font-black">Sesi Terbaru</h2>
                <a href="{{ route('sessions.index') }}" class="text-sm font-black text-fern">Lihat semua</a>
            </div>
            <div class="space-y-3">
                @forelse ($recentSessions as $session)
                    <a href="{{ route('sessions.show', $session) }}" class="block rounded-2xl border border-ink/10 bg-white/60 p-4 transition hover:border-fern/40 hover:bg-white">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="font-black">{{ $session->title }}</p>
                                <p class="mt-1 text-sm text-ink/60">{{ $session->subject }} · {{ $session->topic }}</p>
                            </div>
                            <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black uppercase text-fern">{{ $session->status }}</span>
                        </div>
                    </a>
                @empty
                    <div class="rounded-2xl border border-dashed border-ink/20 p-8 text-center text-ink/60">Belum ada sesi. Mulai dari tombol Buat Sesi Baru.</div>
                @endforelse
            </div>
        </section>

        <aside class="rounded-[2rem] bg-ink p-6 text-white">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-honey">Alur MVP</p>
            <div class="mt-6 space-y-4">
                @foreach (['Isi identitas penyusun', 'Susun struktur soal', 'Generate naskah AI', 'Export PDF/DOCX', 'Aktifkan quiz'] as $step)
                    <div class="rounded-2xl bg-white/10 p-4 text-sm font-bold">{{ $loop->iteration }}. {{ $step }}</div>
                @endforeach
            </div>
        </aside>
    </div>
</x-layouts.app>
