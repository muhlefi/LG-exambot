<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hasil Quiz - LG ExamBot</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .prose table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.5rem; }
        .prose th { background: #f9fafb; }
    </style>
</head>
<body class="min-h-screen bg-ink/5" x-data="{
    tab: 'summary',
    answers: {{ Js::from($participant && $participant->finished_at ? $participant->answers->map(fn($a) => ['question_id' => $a->question_id, 'selected' => $a->selected_answer, 'correct' => $a->is_correct, 'answer_key' => $a->question->answer_key])->values() : collect([])) }},
}">

    @if(session('error'))
        <script>Swal.fire({ icon: 'error', title: 'Error', text: '{{ session('error') }}', confirmButtonColor: '#4a7c59' })</script>
    @endif

    <div class="max-w-2xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-fern text-xl font-black text-white shadow-lg mb-4">
                LG
            </div>
            <h1 class="text-2xl font-black text-ink">Hasil Quiz</h1>
            <p class="mt-1 text-sm text-ink/50">{{ $quiz->title ?? 'Quiz' }}</p>
        </div>

        @if($isSubmitted && $participant)
        <!-- Score Card -->
        <div class="bg-white rounded-[2rem] border border-ink/5 p-8 text-center shadow-sm mb-6">
            <p class="text-sm font-black uppercase tracking-widest text-ink/40 mb-2">{{ $participant->student_name }}</p>
            
            <div class="inline-flex items-center justify-center w-32 h-32 rounded-full border-8 {{ $participant->score >= 70 ? 'border-fern bg-fern/10' : ($participant->score >= 50 ? 'border-honey bg-honey/10' : 'border-clay bg-clay/10') }} mb-4">
                <div>
                    <p class="text-4xl font-black text-ink leading-none">{{ round($participant->score) }}</p>
                    <p class="text-xs font-black text-ink/40 uppercase">Skor</p>
                </div>
            </div>

            <div class="flex justify-center gap-6 mt-4">
                <div class="text-center">
                    <p class="text-2xl font-black text-fern">{{ $participant->answers->where('is_correct', true)->count() }}</p>
                    <p class="text-xs font-black text-ink/40 uppercase">Benar</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-black text-clay">{{ $participant->answers->where('is_correct', false)->count() }}</p>
                    <p class="text-xs font-black text-ink/40 uppercase">Salah</p>
                </div>
                <div class="text-center">
                    <p class="text-2xl font-black text-ink/30">{{ $participant->answers->where('selected_answer', null)->count() }}</p>
                    <p class="text-xs font-black text-ink/40 uppercase">Kosong</p>
                </div>
            </div>

            <div class="mt-6 rounded-xl bg-ink/5 p-4">
                <p class="text-lg font-black text-ink {{ $participant->score >= 70 ? 'text-fern' : ($participant->score >= 50 ? 'text-honey' : 'text-clay') }}">
                    @if($participant->score >= 70)
                        Luar Biasa! Kamu telah memahami materi ini dengan baik.
                    @elseif($participant->score >= 50)
                        Bagus! Terus belajar untuk hasil yang lebih baik.
                    @else
                        Ayo semangat! Materi ini perlu dipelajari lagi.
                    @endif
                </p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="bg-white rounded-2xl border border-ink/5 overflow-hidden shadow-sm mb-6">
            <div class="flex border-b border-ink/5">
                <button @click="tab = 'summary'" :class="tab === 'summary' ? 'bg-fern text-white' : 'text-ink/50 hover:bg-ink/5'" class="flex-1 px-4 py-3 text-sm font-black transition-colors">
                    Ringkasan
                </button>
                <button @click="tab = 'detail'" :class="tab === 'detail' ? 'bg-fern text-white' : 'text-ink/50 hover:bg-ink/5'" class="flex-1 px-4 py-3 text-sm font-black transition-colors">
                    Detail Jawaban
                </button>
            </div>

            <div class="p-4">
                <!-- Summary Tab -->
                <div x-show="tab === 'summary'" class="space-y-3">
                    @foreach($quiz->examSession->questions as $index => $question)
                        @php
                            $answer = $participant->answers->where('question_id', $question->id)->first();
                            $isEssay = in_array($question->question_type, ['essay', 'fill_blank']);
                            $status = !$answer || $answer->selected_answer === null ? 'empty' : ($isEssay ? 'pending' : ($answer->is_correct ? 'correct' : 'wrong'));
                        @endphp
                        <div class="flex items-center gap-3 p-3 rounded-xl @if($status === 'correct') bg-fern/5 @elseif($status === 'wrong') bg-clay/5 @elseif($status === 'pending') bg-honey/5 @else bg-ink/5 @endif">
                            <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center text-xs font-black @if($status === 'correct') bg-fern text-white @elseif($status === 'wrong') bg-clay text-white @elseif($status === 'pending') bg-honey text-white @else bg-ink/20 text-ink/40 @endif">
                                {{ $index + 1 }}
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold text-ink truncate">{{ Str::limit(strip_tags($question->question_text), 60) }}</p>
                                @if($status === 'wrong' && $answer)
                                    <p class="text-xs text-clay mt-0.5">Jawabanmu: {{ $answer->selected_answer }} · Kunci: {{ $question->answer_key }}</p>
                                @elseif($status === 'empty')
                                    <p class="text-xs text-ink/40 mt-0.5">Tidak dijawab</p>
                                @elseif($status === 'pending')
                                    <p class="text-xs text-honey mt-0.5">Menunggu penilaian guru</p>
                                @endif
                            </div>
                            @if($status === 'correct')
                                <svg class="w-5 h-5 text-fern shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            @elseif($status === 'wrong')
                                <svg class="w-5 h-5 text-clay shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            @elseif($status === 'pending')
                                <svg class="w-5 h-5 text-honey shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Detail Tab -->
                <div x-show="tab === 'detail'" class="space-y-4">
                    @foreach($quiz->examSession->questions as $index => $question)
                        @php
                            $answer = $participant->answers->where('question_id', $question->id)->first();
                            $isEssay = in_array($question->question_type, ['essay', 'fill_blank']);
                            $status = !$answer || $answer->selected_answer === null ? 'empty' : ($isEssay ? 'pending' : ($answer->is_correct ? 'correct' : 'wrong'));
                        @endphp
                        <div class="rounded-xl border border-ink/10 bg-white p-4">
                            <div class="flex items-start gap-3 mb-3">
                                <span class="w-7 h-7 shrink-0 rounded-lg flex items-center justify-center text-xs font-black bg-ink/5 text-ink/50">{{ $index + 1 }}</span>
                                <div class="text-sm font-bold text-ink flex-1">{!! $question->question_text !!}</div>
                                @if($isEssay)
                                    <span class="shrink-0 rounded-lg bg-honey/10 px-2 py-1 text-[10px] font-black text-honey uppercase">Pending</span>
                                @endif
                            </div>
                            @if($isEssay)
                                <div class="ml-10 rounded-xl bg-ink/5 p-4">
                                    <p class="text-xs font-black text-ink/40 uppercase mb-2">Jawabanmu</p>
                                    <p class="text-sm font-bold text-ink">{{ $answer?->selected_answer ?: 'Belum dijawab' }}</p>
                                </div>
                            @else
                            <div class="space-y-2 ml-10">
                                @foreach($question->options as $option)
                                    <div class="flex items-center gap-2 text-sm rounded-lg p-2 @if($option->option_label === $question->answer_key) bg-fern/10 border border-fern/20 @elseif($status === 'wrong' && $option->option_label === $answer?->selected_answer) bg-clay/10 border border-clay/20 @endif">
                                        <span class="w-6 h-6 shrink-0 rounded-md flex items-center justify-center text-xs font-black @if($option->option_label === $question->answer_key) bg-fern text-white @else bg-ink/5 text-ink/50 @endif">
                                            {{ $option->option_label }}
                                        </span>
                                        <span class="text-ink">{!! $option->option_text !!}</span>
                                        @if($option->option_label === $question->answer_key)
                                            <svg class="w-4 h-4 text-fern ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            @endif
                            @if($question->explanation)
                                <div class="mt-3 rounded-lg bg-honey/10 border border-honey/20 p-3">
                                    <p class="text-xs font-black text-honey uppercase mb-1">Pembahasan</p>
                                    <p class="text-sm text-ink/70">{!! $question->explanation !!}</p>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('quiz.join') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-black text-sm bg-ink text-white transition hover:bg-ink/80">
                ← Coba Quiz Lain
            </a>
        </div>

        @else
        <!-- Not submitted yet -->
        <div class="bg-white rounded-[2rem] border border-ink/5 p-8 text-center shadow-sm">
            <div class="w-16 h-16 mx-auto rounded-full bg-ink/5 flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-ink/30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h2 class="text-xl font-black text-ink mb-2">Quiz Belum Selesai</h2>
            <p class="text-sm text-ink/50 mb-6">Kamu belum menyelesaikan quiz ini. Kembali dan lanjutkan mengerjakan.</p>
            <a href="{{ route('quiz.show', $quiz->quiz_code) }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-black text-sm bg-fern text-white transition hover:-translate-y-0.5">
                Lanjutkan Quiz →
            </a>
        </div>
        @endif
    </div>
</body>
</html>
