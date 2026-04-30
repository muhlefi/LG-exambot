<x-layouts.app title="Bank Soal - LG ExamBot">
    <div class="mb-6">
        <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Bank Soal</p>
        <h1 class="ink-heading text-5xl font-black">Koleksi butir soal Anda</h1>
        <p class="mt-2 text-ink/60 text-lg">Semua soal yang berhasil di-generate dari berbagai sesi tersimpan di sini.</p>
    </div>

    <div class="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($questions as $question)
            <article class="paper-panel flex flex-col rounded-[2.5rem] p-6 transition hover:border-fern/30">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                    <span class="rounded-full bg-limewash px-3 py-1 text-[10px] font-black uppercase tracking-wider text-fern">{{ $question->examSession->subject }}</span>
                    <span class="rounded-full bg-honey/10 px-3 py-1 text-[10px] font-black uppercase tracking-wider text-honey">{{ $question->difficulty }}</span>
                </div>
                
                <div class="flex-1">
                    <p class="text-xs font-bold text-ink/40 uppercase tracking-widest">{{ $question->question_type }}</p>
                    <p class="mt-3 font-bold leading-7 text-ink line-clamp-4">{{ $question->question_text }}</p>
                    
                    @if($question->options->isNotEmpty())
                        <div class="mt-4 space-y-1">
                            @foreach($question->options->take(2) as $option)
                                <div class="text-xs text-ink/60 truncate">
                                    <span class="font-black">{{ $option->option_label }}.</span> {{ $option->option_text }}
                                </div>
                            @endforeach
                            @if($question->options->count() > 2)
                                <p class="text-[10px] text-ink/30 font-bold italic">+ {{ $question->options->count() - 2 }} opsi lainnya</p>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="mt-6 pt-6 border-t border-ink/5">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase text-ink/30 tracking-widest">Dari Sesi</p>
                            <p class="text-xs font-bold text-ink/70 truncate max-w-[150px]">{{ $question->examSession->title }}</p>
                        </div>
                        <a href="{{ route('sessions.results', $question->examSession) }}" class="rounded-full bg-fern/10 p-2 text-fern hover:bg-fern hover:text-white transition-colors">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                    </div>
                </div>
            </article>
        @empty
            <div class="paper-panel rounded-[2.5rem] p-16 text-center md:col-span-2 xl:col-span-3">
                <div class="mx-auto w-20 h-20 rounded-full bg-limewash flex items-center justify-center text-fern mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
                <h3 class="text-2xl font-black text-ink">Bank soal masih kosong</h3>
                <p class="mt-2 text-ink/50 max-w-md mx-auto">Silakan buat sesi soal dan lakukan generate untuk mulai mengisi koleksi Bank Soal pribadi Anda.</p>
                <a href="{{ route('sessions.create') }}" class="mt-8 inline-block rounded-full bg-fern px-8 py-3 text-sm font-black text-white shadow-xl shadow-fern/20">Buat Sesi Pertama</a>
            </div>
        @endforelse
    </div>

    <div class="mt-10">{{ $questions->links() }}</div>
</x-layouts.app>
