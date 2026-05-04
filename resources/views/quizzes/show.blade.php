<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Presentation: {{ $quiz->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
    <style>
    <style>
        [x-cloak] { display: none !important; }
        .prose table { width: 100%; border-collapse: collapse; margin: 2rem 0; font-size: 1.5rem; }
        .prose th, .prose td { border: 1px solid rgba(0,0,0,0.1); padding: 1.5rem; text-align: left; }
        .prose th { background-color: rgba(0,0,0,0.05); }
    </style>
</head>
<body class="text-ink font-sans antialiased overflow-x-hidden">
    <div x-data="{ 
        currentIndex: 0,
        showAnswer: false,
        questions: {{ Js::from($quiz->examSession->questions) }},
        get currentQuestion() { return this.questions[this.currentIndex] },
        next() { if(this.currentIndex < this.questions.length - 1) { this.currentIndex++; this.showAnswer = false } },
        prev() { if(this.currentIndex > 0) { this.currentIndex--; this.showAnswer = false } },
        renderMath() { 
            this.$nextTick(() => { 
                renderMathInElement(this.$el, { 
                    delimiters: [
                        {left: '$$', right: '$$', display: true}, 
                        {left: '$', right: '$', display: false}
                    ],
                    throwOnError : false
                }); 
            }); 
        }
    }" 
    x-init="renderMath()"
    x-effect="currentIndex, showAnswer, renderMath()"
    class="min-h-screen w-full flex flex-col p-6 lg:p-12">
        
        <!-- Header -->
        <header class="flex items-center justify-between mb-8">
            <div class="flex items-center gap-4">
                <div class="grid h-12 w-12 place-items-center rounded-2xl bg-fern text-xl font-black text-white shadow-lg shadow-fern/30">LG</div>
                <div>
                    <h1 class="text-2xl font-black leading-none">{{ $quiz->title }}</h1>
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-honey mt-1">Presentation Mode</p>
                </div>
            </div>
            <div class="flex items-center gap-6">
                <div class="text-right">
                    <p class="text-sm font-black text-ink/40 uppercase tracking-widest">Waktu Tersisa</p>
                    <p class="text-2xl font-black text-fern" id="timer">--:--</p>
                </div>
                <a href="{{ route('quizzes.index') }}" class="rounded-full bg-white p-3 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all text-ink/50 hover:text-clay border border-ink/5">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </a>
            </div>
        </header>

        <!-- Main Quiz Area -->
        <main class="flex-1 flex flex-col items-center justify-center max-w-6xl mx-auto w-full">
            <div class="w-full space-y-10" x-show="currentQuestion" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-y-10" x-transition:enter-end="opacity-100 translate-y-0">
                
                <!-- Question Number -->
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-honey px-6 py-2 text-sm font-black text-white shadow-md shadow-honey/20 uppercase tracking-widest">Soal <span x-text="currentIndex + 1"></span> dari {{ $quiz->examSession->questions->count() }}</span>
                    <span class="text-ink/20 font-black">/</span>
                    <span class="text-ink/40 font-bold uppercase tracking-widest text-xs" x-text="currentQuestion.difficulty"></span>
                </div>

                <!-- Question Text -->
                <div class="space-y-6 w-full">
                    <div class="prose prose-slate max-w-none text-4xl lg:text-5xl font-black leading-[1.2] text-ink" 
                         x-html="marked.parse(currentQuestion.question_text || '')
                            .replace(/\[GAMBAR: (.*?)\]/gi, (match, p1) => {
                                const url = 'https://image.pollinations.ai/prompt/' + encodeURIComponent('clear educational illustration of ' + p1) + '?width=800&height=600&nologo=true&seed=' + p1.length;
                                return `<div class='my-10 overflow-hidden rounded-[3rem] border-4 border-honey/20 bg-white/60 shadow-xl backdrop-blur-sm'>
                                            <div class='p-8 border-b border-honey/10'>
                                                <span class='block text-sm font-black uppercase tracking-[0.3em] text-honey mb-2'>🖼️ ILUSTRASI AI</span>
                                                <p class='text-xl font-bold text-ink/60 italic'>${p1}</p>
                                            </div>
                                            <div class='p-6'>
                                                <img src='${url}' class='w-full rounded-[2rem] shadow-md' loading='lazy'>
                                            </div>
                                        </div>`;
                            })
                            .replace(/\[DIAGRAM: (.*?)\]/gi, (match, p1) => {
                                const url = 'https://image.pollinations.ai/prompt/' + encodeURIComponent('detailed educational diagram about ' + p1) + '?width=800&height=600&nologo=true&seed=' + p1.length;
                                return `<div class='my-10 overflow-hidden rounded-[3rem] border-4 border-fern/20 bg-white/60 shadow-xl backdrop-blur-sm'>
                                            <div class='p-8 border-b border-fern/10'>
                                                <span class='block text-sm font-black uppercase tracking-[0.3em] text-fern mb-2'>📊 STRUKTUR DIAGRAM</span>
                                                <p class='text-xl font-bold text-ink/60 italic'>${p1}</p>
                                            </div>
                                            <div class='p-6'>
                                                <img src='${url}' class='w-full rounded-[2rem] shadow-md' loading='lazy'>
                                            </div>
                                        </div>`;
                            })">
                    </div>
                    
                    <!-- Media Area (Image/Table Placeholder) -->
                    <template x-if="currentQuestion.question_image">
                        <div class="mt-6">
                            <img :src="'/storage/' + currentQuestion.question_image" class="rounded-[2rem] max-h-[400px] border-4 border-white shadow-xl">
                        </div>
                    </template>
                </div>

                <!-- Options -->
                <div class="grid gap-4 md:grid-cols-2 mt-12">
                    <template x-for="option in currentQuestion.options" :key="option.id">
                        <div 
                            class="group relative flex items-center gap-6 rounded-[2rem] border-2 bg-white/80 backdrop-blur-md p-6 transition-all duration-300 shadow-sm hover:shadow-md"
                            :class="showAnswer && option.option_label === currentQuestion.answer_key ? 'border-fern bg-limewash/50 scale-[1.02] ring-4 ring-fern/10' : 'border-ink/5 hover:border-fern/30'"
                        >
                            <span 
                                class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl text-2xl font-black transition-colors"
                                :class="showAnswer && option.option_label === currentQuestion.answer_key ? 'bg-fern text-white shadow-lg shadow-fern/40' : 'bg-ink/5 text-ink/50 group-hover:bg-fern/10 group-hover:text-fern'"
                                x-text="option.option_label"
                            ></span>
                            <p class="text-2xl font-bold text-ink" x-html="marked.parseInline(option.option_text)"></p>
                            
                            <!-- Correct Badge -->
                            <template x-if="showAnswer && option.option_label === currentQuestion.answer_key">
                                <div class="absolute -right-4 -top-4 flex h-10 w-10 animate-bounce items-center justify-center rounded-full bg-fern text-white shadow-xl ring-4 ring-white">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </main>

        <!-- Footer Controls -->
        <footer class="mt-12 flex items-center justify-between">
            <div class="flex gap-4">
                <button @click="prev()" :disabled="currentIndex === 0" class="rounded-full border-2 border-ink/10 px-10 py-4 font-black transition hover:bg-ink/5 disabled:opacity-30 text-ink">SEBELUMNYA</button>
                <button @click="next()" :disabled="currentIndex === questions.length - 1" class="rounded-full bg-ink px-10 py-4 font-black text-white transition hover:bg-ink/80 shadow-lg shadow-ink/20">BERIKUTNYA</button>
            </div>

            <button 
                @click="showAnswer = !showAnswer" 
                class="rounded-full px-10 py-4 font-black transition-all shadow-lg"
                :class="showAnswer ? 'bg-fern text-white shadow-fern/30 ring-4 ring-fern/10' : 'bg-honey text-white shadow-honey/30 hover:-translate-y-0.5'"
                x-text="showAnswer ? 'SEMBUNYIKAN JAWABAN' : 'TAMPILKAN JAWABAN'"
            ></button>
        </footer>
    </div>

    <script>
        // Simple Countdown Timer
        let duration = {{ $quiz->duration * 60 }};
        const timerDisplay = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            let minutes = Math.floor(duration / 60);
            let seconds = duration % 60;
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            if (duration <= 0) clearInterval(countdown);
            duration--;
        }, 1000);
    </script>
</body>
</html>
