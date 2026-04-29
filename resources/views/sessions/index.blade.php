<x-layouts.app title="Sesi Soal - LG ExamBot">
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Sesi Soal</p>
            <h1 class="ink-heading text-5xl font-black text-ink">Draft dan paket ujian</h1>
        </div>
        <a href="{{ route('sessions.create') }}" class="rounded-full bg-fern px-5 py-3 text-sm font-black text-white">Buat Sesi Baru</a>
    </div>

    <div class="paper-panel overflow-hidden rounded-[2rem]">
        <div class="divide-y divide-ink/10">
            @forelse ($sessions as $session)
                <a href="{{ route('sessions.show', $session) }}" class="grid gap-4 p-5 transition hover:bg-white/70 md:grid-cols-[1fr_auto]">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-black">{{ $session->title }}</h2>
                            <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black uppercase text-fern">{{ $session->status }}</span>
                        </div>
                        <p class="mt-2 text-sm text-ink/60">{{ $session->education_level }} {{ $session->class_level }} · {{ $session->subject }} · {{ $session->topic }}</p>
                    </div>
                    <div class="flex gap-3 text-sm font-bold text-ink/60">
                        <span>{{ $session->structures_count }} struktur</span>
                        <span>{{ $session->questions_count }} soal</span>
                        <span>{{ $session->quizzes_count }} quiz</span>
                    </div>
                </a>
            @empty
                <div class="p-10 text-center">
                    <p class="font-black">Belum ada sesi.</p>
                    <p class="mt-2 text-sm text-ink/60">Buat sesi baru untuk mulai menyusun paket soal.</p>
                </div>
            @endforelse
        </div>
    </div>

    <div class="mt-5">{{ $sessions->links() }}</div>
</x-layouts.app>
