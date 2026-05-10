<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $quiz->title }} - LG ExamBot</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        .prose table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
        .prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.5rem; }
        .prose th { background: #f9fafb; }
    </style>
</head>
<body class="min-h-screen bg-ink/5" x-data="{
    currentIndex: 0,
    answers: {},
    textAnswers: {},
    questions: {{ Js::from($questions->values()) }},
    submitted: false,
    get currentQuestion() { return this.questions[this.currentIndex] },
    get progress() { return Math.round(((this.currentIndex + 1) / this.questions.length) * 100) },
    next() { if(this.currentIndex < this.questions.length - 1) { this.currentIndex++; this.scrollTop() } },
    prev() { if(this.currentIndex > 0) { this.currentIndex--; this.scrollTop() } },
    scrollTop() { window.scrollTo({ top: 0, behavior: 'smooth' }) },
    selectAnswer(optionLabel) {
        this.answers[this.currentQuestion.id] = optionLabel;
    },
    isSelected(optionLabel) {
        return this.answers[this.currentQuestion.id] === optionLabel;
    },
    setTextAnswer(value) {
        this.textAnswers[this.currentQuestion.id] = value;
    },
    getTextAnswer() {
        return this.textAnswers[this.currentQuestion.id] || '';
    },
    isEssay() {
        return this.currentQuestion && this.currentQuestion.question_type === 'essay';
    },
    isFillBlank() {
        return this.currentQuestion && this.currentQuestion.question_type === 'fill_blank';
    },
    answeredCount() {
        return Object.keys(this.answers).length + Object.keys(this.textAnswers).length;
    },
    submit() {
        if (this.submitted) return;
        this.submitted = true;
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('quiz.submit', $quiz->quiz_code) }}';
        
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = '_token';
        csrf.value = '{{ csrf_token() }}';
        form.appendChild(csrf);
        
        Object.entries(this.answers).forEach(([qId, answer]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'answers[' + qId + ']';
            input.value = answer;
            form.appendChild(input);
        });
        
        Object.entries(this.textAnswers).forEach(([qId, answer]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'answers[' + qId + ']';
            input.value = answer;
            form.appendChild(input);
        });
        
        document.body.appendChild(form);
        form.submit();
    },
    renderMath() {
        this.$nextTick(() => {
            if (window.renderMathInElement) {
                renderMathInElement(document.body, {
                    delimiters: [
                        {left: '$$', right: '$$', display: true},
                        {left: '$', right: '$', display: false}
                    ],
                    throwOnError: false
                });
            }
        });
    }
}" x-init="renderMath()" x-effect="currentIndex, renderMath()">
    
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white/90 backdrop-blur-md border-b border-ink/5">
        <div class="max-w-3xl mx-auto px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-fern flex items-center justify-center text-xs font-black text-white shrink-0">LG</div>
                <div class="min-w-0">
                    <p class="text-xs font-black text-ink/40 truncate max-w-[150px] sm:max-w-[250px]">{{ $participant->student_name }}</p>
                    <p class="text-sm font-black text-ink leading-none truncate max-w-[150px] sm:max-w-[250px]">{{ $quiz->title }}</p>
                </div>
            </div>
            <div class="text-right shrink-0">
                <p class="text-[10px] font-black text-ink/40 uppercase tracking-widest">Sisa Waktu</p>
                <p class="text-xl font-black text-clay" id="timer">--:--</p>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="h-1 bg-ink/5">
            <div class="h-full bg-fern transition-all duration-500" :style="'width: ' + progress + '%'"></div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-3xl mx-auto px-4 py-6 pb-32">
        <div x-show="currentQuestion" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0">
            
            <!-- Question Number & Progress -->
            <div class="flex items-center justify-between mb-4">
                <span class="rounded-full bg-honey px-4 py-1.5 text-xs font-black text-white">
                    Soal <span x-text="currentIndex + 1"></span> / <span x-text="questions.length"></span>
                </span>
                <div class="flex items-center gap-2">
                    <template x-if="isEssay()">
                        <span class="rounded-full bg-honey/20 px-3 py-1 text-[10px] font-black text-honey uppercase tracking-widest">Esai</span>
                    </template>
                    <template x-if="isFillBlank()">
                        <span class="rounded-full bg-fern/20 px-3 py-1 text-[10px] font-black text-fern uppercase tracking-widest">Isian</span>
                    </template>
                    <span class="text-xs font-black text-ink/40">
                        <span x-text="answeredCount()"></span> dijawab
                    </span>
                </div>
            </div>

            <!-- Question Text -->
            <div class="bg-white rounded-2xl border border-ink/5 p-6 mb-6 shadow-sm">
                <div class="prose max-w-none text-base font-bold text-ink leading-relaxed"
                    x-html="marked.parse(currentQuestion.question_text || '')">
                </div>

                <!-- Question Image -->
                <template x-if="currentQuestion.question_image">
                    <div class="mt-4">
                        <img :src="'/storage/' + currentQuestion.question_image" class="max-h-[300px] rounded-xl w-full object-contain border border-ink/5">
                    </div>
                </template>
            </div>

            <!-- Multiple Choice Options -->
            <template x-if="!isEssay() && !isFillBlank() && currentQuestion.options">
                <div class="space-y-3">
                    <template x-for="option in currentQuestion.options" :key="option.id">
                        <button 
                            type="button"
                            @click="selectAnswer(option.option_label)"
                            class="w-full flex items-center gap-4 rounded-2xl border-2 p-4 text-left transition-all duration-200"
                            :class="isSelected(option.option_label) 
                                ? 'border-fern bg-fern/5 shadow-sm shadow-fern/10' 
                                : 'border-ink/10 bg-white hover:border-fern/30 hover:bg-fern/5'"
                        >
                            <span 
                                class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-sm font-black transition-all"
                                :class="isSelected(option.option_label) 
                                    ? 'bg-fern text-white' 
                                    : 'bg-ink/5 text-ink/50'"
                                x-text="option.option_label"
                            ></span>
                            <span class="text-sm font-bold text-ink flex-1" x-html="marked.parseInline(option.option_text || '')"></span>
                            <template x-if="isSelected(option.option_label)">
                                <svg class="w-5 h-5 text-fern shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </template>
                        </button>
                    </template>
                </div>
            </template>

            <!-- Essay / Fill in Blank Text Input -->
            <template x-if="isEssay() || isFillBlank()">
                <div>
                    <textarea 
                        x-model="textAnswers[currentQuestion.id]"
                        @input="setTextAnswer($event.target.value)"
                        placeholder="{{ isEssay() ? 'Tulis jawaban esaimu di sini...' : 'Isi jawaban di sini...' }}"
                        class="w-full min-h-[150px] rounded-2xl border-2 border-ink/10 bg-white p-4 text-sm font-bold text-ink outline-none focus:border-fern focus:bg-fern/5 transition-all resize-none"
                    ></textarea>
                    <p class="mt-2 text-xs font-bold text-ink/40">Jawaban akan dinilai oleh guru.</p>
                </div>
            </template>
        </div>
    </main>

    <!-- Footer Navigation -->
    <footer class="fixed bottom-0 left-0 right-0 bg-white/95 backdrop-blur-md border-t border-ink/5 p-4 z-20">
        <div class="max-w-3xl mx-auto flex items-center justify-between gap-3">
            <button 
                @click="prev()" 
                :disabled="currentIndex === 0"
                class="px-4 sm:px-6 py-3 rounded-xl font-black text-sm border-2 border-ink/10 text-ink/50 disabled:opacity-30 transition hover:bg-ink/5 whitespace-nowrap"
            >
                ← Sebelumnya
            </button>
            
            <template x-if="currentIndex < questions.length - 1">
                <button @click="next()" class="px-4 sm:px-8 py-3 rounded-xl font-black text-sm bg-ink text-white transition hover:bg-ink/80 whitespace-nowrap">
                    Selanjutnya →
                </button>
            </template>
            
            <template x-if="currentIndex === questions.length - 1">
                <button 
                    @click="submit()" 
                    :disabled="submitted"
                    class="px-4 sm:px-8 py-3 rounded-xl font-black text-sm bg-fern text-white shadow-lg shadow-fern/20 transition hover:-translate-y-0.5 disabled:opacity-50 whitespace-nowrap"
                >
                    <span x-text="submitted ? 'Mengirim...' : 'Selesai & Kirim'"></span>
                </button>
            </template>
        </div>
    </footer>

    <script>
        let duration = {{ $quiz->duration * 60 }};
        const timerDisplay = document.getElementById('timer');
        let timerInterval;

        function updateTimer() {
            let minutes = Math.floor(duration / 60);
            let seconds = duration % 60;
            timerDisplay.textContent = minutes.toString().padStart(2, '0') + ':' + seconds.toString().padStart(2, '0');

            if (duration <= 60) {
                timerDisplay.classList.remove('text-clay');
                timerDisplay.classList.add('text-red-500', 'animate-pulse');
            }

            if (duration <= 0) {
                clearInterval(timerInterval);
                document.querySelector('[x-data]').__x.$data.submit();
            }
            duration--;
        }

        updateTimer();
        timerInterval = setInterval(updateTimer, 1000);

        window.addEventListener('beforeunload', (e) => {
            if (!document.querySelector('[x-data]').__x.$data.submitted) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
