<x-layouts.app title="Hasil Quiz - LG ExamBot">
    <nav class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-ink/30">
        <a href="{{ route('dashboard') }}" class="hover:text-fern">Dashboard</a>
        <span>/</span>
        <span class="text-fern">Hasil Quiz</span>
    </nav>

    <div class="mb-8 flex flex-col justify-between gap-4 xl:flex-row xl:items-end">
        <div>
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Hasil Quiz</p>
            <h1 class="ink-heading text-5xl font-black">Hasil Quiz</h1>
            <p class="mt-2 text-sm text-ink/60">Lihat hasil dan statistik quiz dari semua sesi.</p>
        </div>
    </div>

    <div class="paper-panel rounded-[2rem] p-6">
        <form method="GET" class="mb-6 flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs font-black uppercase tracking-[0.15em] text-ink/40 mb-1">Sesi</label>
                <select name="session_id" class="rounded-xl border border-ink/10 bg-ink/5 px-4 py-2.5 text-sm font-bold outline-none focus:border-fern">
                    <option value="">Semua Sesi</option>
                    @foreach($sessions as $session)
                        <option value="{{ $session->id }}" {{ request('session_id') == $session->id ? 'selected' : '' }}>
                            {{ $session->title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-black uppercase tracking-[0.15em] text-ink/40 mb-1">Status</label>
                <select name="status" class="rounded-xl border border-ink/10 bg-ink/5 px-4 py-2.5 text-sm font-bold outline-none focus:border-fern">
                    <option value="">Semua Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="ended" {{ request('status') == 'ended' ? 'selected' : '' }}>Berakhir</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-fern px-5 py-2.5 text-sm font-black text-white transition hover:-translate-y-0.5">Filter</button>
            @if(request()->hasAny(['session_id', 'status']))
                <a href="{{ route('quiz.results') }}" class="rounded-xl bg-ink/5 px-5 py-2.5 text-sm font-black text-ink/50 transition hover:bg-ink/10">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead>
                    <tr class="border-b border-ink/10 text-xs uppercase tracking-[0.18em] text-ink/50">
                        <th class="py-3 pr-4">Judul Quiz</th>
                        <th class="py-3 pr-4">Sesi</th>
                        <th class="py-3 pr-4">Kode</th>
                        <th class="py-3 pr-4">Durasi</th>
                        <th class="py-3 pr-4">Peserta</th>
                        <th class="py-3 pr-4">Status</th>
                        <th class="py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink/10">
                    @forelse($quizzes as $quiz)
                        <tr class="hover:bg-ink/5 transition-colors">
                            <td class="py-3 pr-4 font-black text-ink">{{ $quiz->title }}</td>
                            <td class="py-3 pr-4 text-ink/60">{{ $quiz->examSession->title ?? '-' }}</td>
                            <td class="py-3 pr-4">
                                <span class="rounded-lg bg-ink/5 px-2 py-1 font-mono font-black text-xs">{{ $quiz->quiz_code }}</span>
                            </td>
                            <td class="py-3 pr-4 text-ink/60">{{ $quiz->duration }} menit</td>
                            <td class="py-3 pr-4">
                                <span class="font-black text-fern">{{ $quiz->participants_count ?? 0 }}</span>
                            </td>
                            <td class="py-3 pr-4">
                                <span class="rounded-lg px-2 py-1 text-xs font-black
                                    @if($quiz->status === 'active') bg-fern/10 text-fern
                                    @elseif($quiz->status === 'ended') bg-clay/10 text-clay
                                    @else bg-ink/10 text-ink/50 @endif">
                                    @if($quiz->status === 'active') Aktif
                                    @elseif($quiz->status === 'ended') Berakhir
                                    @else Nonaktif @endif
                                </span>
                            </td>
                            <td class="py-3">
                                <a href="{{ route('quiz.result.detail', $quiz) }}" class="rounded-lg bg-fern/10 px-3 py-1.5 text-xs font-black text-fern transition hover:bg-fern hover:text-white">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-ink/40 font-bold">Belum ada quiz.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quizzes->hasPages())
            <div class="mt-4 flex justify-center">
                {{ $quizzes->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
