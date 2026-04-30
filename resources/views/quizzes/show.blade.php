<x-layouts.app title="{{ $quiz->title }} - Presentation Mode">
    <div x-data="{ 
            currentQuestion: 0, 
            showAnswer: false, 
            questions: {{ Js::from($quiz->examSession->questions) }} 
        }" 
        class="paper-panel rounded-[2rem] p-6 lg:p-10 min-h-[60vh] flex flex-col relative"
    >
        <template x-if="currentQuestion < questions.length">
            <div class="flex-1" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
                <div class="flex items-center justify-between border-b border-ink/10 pb-4 mb-6">
                    <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Soal <span x-text="currentQuestion + 1"></span> dari <span x-text="questions.length"></span></p>
                    <h1 class="ink-heading text-2xl font-black">{{ $quiz->title }}</h1>
                </div>

                <div class="prose max-w-none text-lg md:text-3xl font-bold text-ink mb-10 leading-snug" x-html="questions[currentQuestion].question_text"></div>

                <div class="space-y-4">
                    <template x-for="(option, index) in questions[currentQuestion].options" :key="option.id">
                        <div 
                            class="flex items-center gap-5 p-5 rounded-2xl border-2 transition-all duration-500 transform"
                            :class="{
                                'border-fern bg-limewash text-fern font-black scale-[1.02] shadow-lg shadow-fern/10': showAnswer && option.is_correct,
                                'border-ink/10 bg-white/50 text-ink opacity-40 grayscale': showAnswer && !option.is_correct,
                                'border-ink/10 bg-white hover:border-fern/30 cursor-default': !showAnswer
                            }"
                        >
                            <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl text-xl font-black"
                                :class="showAnswer && option.is_correct ? 'bg-fern text-white' : 'bg-ink/5 text-ink/50'"
                                x-text="String.fromCharCode(65 + index)">
                            </span>
                            <span class="text-xl md:text-2xl" x-html="option.option_text"></span>
                            
                            <template x-if="showAnswer && option.is_correct">
                                <span class="ml-auto flex h-10 w-10 items-center justify-center rounded-full bg-fern text-white animate-bounce">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                                </span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <template x-if="currentQuestion >= questions.length">
            <div class="flex flex-col items-center justify-center flex-1 text-center py-20">
                <div class="grid h-24 w-24 place-items-center rounded-full bg-honey text-ink mb-6">
                    <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="ink-heading text-4xl font-black mb-4">Sesi Quiz Selesai</h2>
                <p class="text-ink/60 mb-8 max-w-md">Semua pertanyaan telah ditampilkan dalam mode presentasi.</p>
                <a href="{{ route('quizzes.index') }}" class="rounded-full bg-ink px-8 py-4 text-lg font-bold text-white transition hover:bg-fern shadow-xl shadow-ink/20">Kembali ke Daftar Quiz</a>
            </div>
        </template>

        <div class="mt-10 flex items-center justify-between border-t border-ink/10 pt-6" x-show="currentQuestion < questions.length">
            <a href="{{ route('quizzes.index') }}" class="rounded-full border border-ink/20 px-6 py-3 text-sm font-bold text-ink hover:bg-ink/5 transition">Akhiri Sesi</a>
            
            <div class="flex gap-4">
                <button 
                    x-show="!showAnswer"
                    @click="showAnswer = true"
                    class="rounded-full bg-honey px-6 py-3 text-sm font-bold text-ink shadow-lg shadow-honey/20 transition hover:scale-105"
                >
                    Tampilkan Jawaban
                </button>

                <button 
                    x-show="showAnswer"
                    @click="currentQuestion++; showAnswer = false"
                    class="rounded-full bg-fern px-6 py-3 text-sm font-bold text-white shadow-lg shadow-fern/20 transition hover:scale-105 flex items-center gap-2"
                >
                    Pertanyaan Selanjutnya
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </div>
        </div>
    </div>
</x-layouts.app>
