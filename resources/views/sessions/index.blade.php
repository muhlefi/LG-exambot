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
                <div class="group grid gap-4 p-6 transition-all hover:bg-white/90 md:grid-cols-[1fr_auto]">
                    <a href="{{ route('sessions.show', $session) }}" class="flex-1">
                        <div class="flex flex-wrap items-center gap-3">
                            <h2 class="text-xl font-black group-hover:text-fern transition-colors">{{ $session->title }}</h2>
                            <span class="rounded-full bg-limewash px-3 py-1 text-[10px] font-black uppercase tracking-wider text-fern">{{ $session->status }}</span>
                        </div>
                        <p class="mt-2 text-sm font-medium text-ink/50">{{ $session->education_level }} {{ $session->class_level }} · {{ $session->subject }} · {{ $session->topic }}</p>
                    </a>
                    <div class="flex items-center gap-6">
                        <div class="flex items-center gap-4 text-xs font-black uppercase tracking-widest text-ink/40">
                            <div class="flex flex-col items-end">
                                <span>{{ $session->structures_count }} Bagian</span>
                                <span>{{ $session->questions_count }} Soal</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <a href="{{ route('sessions.edit', $session) }}" class="rounded-full border border-ink/10 bg-white p-2 text-ink/60 hover:text-fern">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            </a>
                            <form method="POST" action="{{ route('sessions.destroy', $session) }}" onsubmit="return confirm('Hapus sesi ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-full border border-clay/20 bg-white p-2 text-clay hover:bg-clay hover:text-white">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                        <a href="{{ route('sessions.show', $session) }}" class="rounded-full bg-fern/10 p-2 text-fern opacity-0 group-hover:opacity-100 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                </div>
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
