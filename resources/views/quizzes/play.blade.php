<x-layouts.app title="Mengerjakan Quiz">
    <form method="POST" action="{{ route('quizzes.submit', $participant) }}" class="space-y-5">
        @csrf
        <div class="paper-panel rounded-[2rem] p-6">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">{{ $participant->quiz->quiz_code }}</p>
            <h1 class="ink-heading mt-2 text-5xl font-black">{{ $participant->quiz->title }}</h1>
            <p class="mt-2 text-sm text-ink/60">Peserta: {{ $participant->student_name }} · Durasi {{ $participant->quiz->duration }} menit</p>
        </div>

        @foreach ($questions as $question)
            <article class="paper-panel rounded-[2rem] p-6">
                <div class="mb-3 flex items-center gap-2">
                    <span class="rounded-full bg-ink px-3 py-1 text-xs font-black text-white">Soal {{ $loop->iteration }}</span>
                    <span class="rounded-full bg-limewash px-3 py-1 text-xs font-black text-fern">{{ $question->difficulty }}</span>
                </div>
                <p class="font-bold leading-7">{{ $question->question_text }}</p>
                @if ($question->options->isNotEmpty())
                    @php $options = $participant->quiz->is_random_answer ? $question->options->shuffle() : $question->options; @endphp
                    <div class="mt-4 grid gap-2">
                        @foreach ($options as $option)
                            <label class="flex items-start gap-3 rounded-2xl border border-ink/10 bg-white/70 p-4">
                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->option_label }}" class="mt-1">
                                <span><strong>{{ $option->option_label }}.</strong> {{ $option->option_text }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <textarea name="answers[{{ $question->id }}]" rows="3" class="mt-4 w-full rounded-2xl border border-ink/10 bg-white p-4 outline-none focus:border-fern" placeholder="Tulis jawaban..."></textarea>
                @endif
            </article>
        @endforeach

        <button class="w-full rounded-[2rem] bg-honey px-6 py-4 text-lg font-black text-ink">Kirim Jawaban</button>
    </form>
</x-layouts.app>
