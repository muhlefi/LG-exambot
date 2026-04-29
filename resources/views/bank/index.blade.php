<x-layouts.app title="Bank Soal - LG ExamBot">
    <div class="mb-6">
        <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Bank Soal</p>
        <h1 class="ink-heading text-5xl font-black">Soal pribadi dan publik</h1>
    </div>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($questions as $question)
            <article class="paper-panel rounded-[2rem] p-5">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black uppercase text-fern">{{ $question->scope }}</span>
                    <span class="text-xs font-bold text-ink/50">{{ $question->question_type }}</span>
                </div>
                <h2 class="text-lg font-black">{{ $question->title }}</h2>
                <p class="mt-2 text-sm text-ink/60">{{ $question->subject }} · {{ $question->topic }}</p>
                <p class="mt-4 line-clamp-4 text-sm leading-6">{{ $question->question_text }}</p>
                <p class="mt-4 text-sm font-black text-fern">Kunci: {{ $question->answer_key }}</p>
            </article>
        @empty
            <div class="paper-panel rounded-[2rem] p-10 text-center text-ink/60 md:col-span-2 xl:col-span-3">
                Bank soal masih kosong. Fitur share dari hasil generate bisa ditambahkan pada fase berikutnya.
            </div>
        @endforelse
    </div>

    <div class="mt-5">{{ $questions->links() }}</div>
</x-layouts.app>
