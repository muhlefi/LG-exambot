<x-layouts.app title="Dashboard - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Dashboard</p>
            <h1 class="ink-heading text-5xl font-black text-ink">Ruang kontrol asesmen</h1>
        </div>
        <a href="{{ route('sessions.create') }}" class="rounded-full bg-fern px-5 py-3 text-sm font-black text-white shadow-xl shadow-fern/20">Buat Sesi Baru</a>
    </div>

    <div class="grid gap-6 md:grid-cols-5">
        @foreach ([
            ['label' => 'Sesi', 'value' => $sessionCount, 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'color' => 'bg-fern'],
            ['label' => 'Soal', 'value' => $questionCount, 'icon' => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'bg-honey'],
            ['label' => 'Quiz', 'value' => $quizCount, 'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z M21 12a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'bg-ink'],
            ['label' => 'Export', 'value' => $exportCount, 'icon' => 'M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'bg-fern/60'],
            ['label' => 'AI Run', 'value' => $aiUsageCount, 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'color' => 'bg-honey/60'],
        ] as $metric)
            <div class="paper-panel hover-lift flex flex-col justify-between rounded-[2rem] p-6 group">
                <div class="flex items-center justify-between mb-4">
                    <span class="grid h-10 w-10 place-items-center rounded-xl {{ $metric['color'] }} text-white shadow-lg transition-transform group-hover:scale-110">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="{{ $metric['icon'] }}"></path></svg>
                    </span>
                    <p class="text-xs font-black uppercase tracking-widest text-ink/40">{{ $metric['label'] }}</p>
                </div>
                <p class="ink-heading text-4xl font-black">{{ $metric['value'] }}</p>
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
                    <a href="{{ route('sessions.show', $session) }}" class="block hover-lift rounded-2xl border border-ink/10 bg-white/60 p-4 transition hover:border-fern/40 hover:bg-white">
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

        <aside class="rounded-[2.5rem] bg-gradient-to-br from-ink to-ink/90 p-8 text-white shadow-2xl shadow-ink/20">
            <div class="flex items-center gap-3 mb-8">
                <span class="h-1.5 w-1.5 rounded-full bg-honey animate-pulse"></span>
                <p class="text-xs font-black uppercase tracking-[0.3em] text-honey/80">Alur Kerja LG</p>
            </div>
            <div class="space-y-6">
                @foreach (['Isi identitas penyusun', 'Susun struktur soal', 'Generate naskah AI', 'Export PDF/DOCX', 'Aktifkan quiz'] as $step)
                    <div class="flex gap-4 group">
                        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-white/10 text-[10px] font-black text-honey transition-all group-hover:bg-honey group-hover:text-ink">{{ $loop->iteration }}</span>
                        <p class="text-sm font-bold text-white/70 group-hover:text-white transition-colors">{{ $step }}</p>
                    </div>
                @endforeach
            </div>
            <div class="mt-12 rounded-3xl bg-white/5 p-6 border border-white/5">
                <p class="text-[10px] font-black uppercase tracking-widest text-honey/50">Tips Pro</p>
                <p class="mt-2 text-xs leading-5 text-white/40 font-medium italic">Gunakan "Batasan Teori" agar AI menghasilkan soal yang lebih presisi sesuai kurikulum Anda.</p>
            </div>
        </aside>
    </div>
</x-layouts.app>
