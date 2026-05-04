<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        @if($type === 'answers') Kunci Jawaban: 
        @elseif($type === 'blueprint') Kisi-kisi: 
        @else Naskah Soal: @endif
        {{ $examSession->title }}
    </title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
    
    <style>
        @page { size: A4; margin: 1cm 1.5cm; }
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.25; color: #000; margin: 0; padding: 0; background: white; font-size: 10.5pt; }
        
        .no-print { position: fixed; top: 15px; right: 15px; background: #111827; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); z-index: 1000; font-family: sans-serif; font-size: 12px; }
        .no-print:hover { background: #f59e0b; }
        
        /* Kop Surat */
        .kop { border-bottom: 3px double #000; padding-bottom: 8px; margin-bottom: 12px; display: flex; align-items: center; }
        .kop-logo { width: 70px; height: 70px; margin-right: 15px; }
        .kop-text { text-align: center; flex: 1; margin-right: 85px; }
        .kop-text h2 { margin: 0; font-size: 12pt; text-transform: uppercase; letter-spacing: 0.5px; }
        .kop-text h1 { margin: 0; font-size: 20pt; color: #000; font-weight: bold; line-height: 1; }
        .kop-text p { margin: 1px 0; font-size: 9pt; font-style: italic; }
        .kop-text .contact { font-size: 8.5pt; font-style: normal; font-weight: bold; }

        /* Detail Ujian */
        .exam-info { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        .exam-info td { vertical-align: top; padding: 1px 0; font-size: 9.5pt; }
        
        .title-block { text-align: center; font-weight: bold; font-size: 11pt; text-transform: uppercase; margin: 8px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; }

        /* Soal */
        .question-list { margin-top: 5px; }
        .question-item { margin-bottom: 12px; position: relative; padding-left: 22px; page-break-inside: avoid; }
        .question-number { position: absolute; left: 0; top: 0; font-weight: bold; }
        .question-body { margin-bottom: 4px; }
        
        /* Options */
        .options-container { display: flex; flex-wrap: wrap; margin-top: 3px; }
        .option-item { width: 24%; /* 4 columns for efficiency */ min-width: 140px; margin-bottom: 2px; display: flex; gap: 6px; }
        .option-label { font-weight: bold; min-width: 12px; }

        /* Utilities */
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        table.markdown-table { border-collapse: collapse; margin: 10px 0; font-size: 10pt; width: auto; }
        table.markdown-table th, table.markdown-table td { border: 1px solid #000; padding: 4px 8px; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
            .kop-text h1 { color: #000 !important; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">
        CETAK 
        @if($type === 'answers') KUNCI JAWABAN
        @elseif($type === 'blueprint') KISI-KISI
        @else NASKAH SOAL @endif
    </button>

    @if($type === 'questions')
    <!-- Kop Surat -->
    <div class="kop">
        <img src="{{ asset('img/logo.jpeg') }}" class="kop-logo">
        <div class="kop-text">
            <h2>Bimbingan Belajar</h2>
            <h1>L-G LEARNING</h1>
            <p>Kemiri Pakukerto sukorejo</p>
            <p class="contact">WA : 085815222639 II Website : l-glearning.com</p>
        </div>
    </div>

    <!-- Identitas Siswa & Ujian -->
    <table class="exam-info">
        <tr>
            <td width="15%">Nama</td>
            <td width="35%">: ...........................................</td>
            <td width="15%">Mata Pelajaran</td>
            <td width="35%">: <strong>{{ $examSession->subject }}</strong></td>
        </tr>
        <tr>
            <td>No. Absen</td>
            <td>: ...........................................</td>
            <td>Kelas / Smt</td>
            <td>: {{ $examSession->class_level }} / {{ $examSession->semester }}</td>
        </tr>
        <tr>
            <td>Hari / Tgl</td>
            <td>: ...........................................</td>
            <td>Materi</td>
            <td>: {{ $examSession->topic }}</td>
        </tr>
    </table>

    <div class="title-block">
        {{ $examSession->title }}
    </div>

    <div class="question-list">
        @foreach($examSession->questions as $index => $question)
            <div class="question-item">
                <span class="question-number">{{ $index + 1 }}.</span>
                <div class="question-body">
                    {!! Str::of($question->question_text)->markdown(['html_input' => 'allow'])->replace('<table>', '<table class="markdown-table">') !!}
                    
                    @if($question->question_image)
                        <div style="margin: 10px 0;">
                            <img src="{{ Storage::url($question->question_image) }}" style="max-height: 200px; max-width: 100%; border: 1px solid #eee;">
                        </div>
                    @endif
                </div>

                @if($question->options->isNotEmpty())
                    <div class="options-container">
                        @foreach($question->options as $option)
                            <div class="option-item">
                                <span class="option-label">{{ $option->option_label }}.</span>
                                <span class="option-text">{{ $option->option_text }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    @endif

    @if($type === 'answers')
    <!-- Kunci Jawaban -->
    <div>
        <h2 class="text-center font-bold" style="text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 20px;">Kunci Jawaban & Pembahasan: {{ $examSession->title }}</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
            <thead>
                <tr style="background: #f0f0f0;">
                    <th style="border: 1px solid #000; padding: 8px; width: 50px;">No</th>
                    <th style="border: 1px solid #000; padding: 8px; width: 80px;">Jawaban</th>
                    <th style="border: 1px solid #000; padding: 8px;">Pembahasan / Penjelasan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examSession->questions as $index => $question)
                    <tr>
                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $index + 1 }}</td>
                        <td style="border: 1px solid #000; padding: 8px; text-align: center; font-weight: bold;">{{ $question->answer_key }}</td>
                        <td style="border: 1px solid #000; padding: 8px;">{{ $question->explanation }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($type === 'blueprint')
    <!-- Kisi-Kisi -->
    <div>
        <h2 class="text-center font-bold" style="text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 5px; margin-bottom: 20px;">Kisi-Kisi Instrumen Soal: {{ $examSession->title }}</h2>
        <table style="width: 100%; border-collapse: collapse; font-size: 9pt;">
            <thead>
                <tr style="background: #f0f0f0;">
                    <th style="border: 1px solid #000; padding: 8px; width: 30px;">No</th>
                    <th style="border: 1px solid #000; padding: 8px;">Kompetensi Dasar / Materi</th>
                    <th style="border: 1px solid #000; padding: 8px;">Indikator Soal</th>
                    <th style="border: 1px solid #000; padding: 8px; width: 60px;">Level</th>
                    <th style="border: 1px solid #000; padding: 8px; width: 80px;">Bentuk</th>
                </tr>
            </thead>
            <tbody>
                @foreach($examSession->questions as $index => $question)
                    <tr>
                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $index + 1 }}</td>
                        <td style="border: 1px solid #000; padding: 8px;">
                            <strong>{{ $question->blueprint?->material }}</strong><br>
                            <small>{{ $question->blueprint?->competency }}</small>
                        </td>
                        <td style="border: 1px solid #000; padding: 8px;">{{ $question->blueprint?->indicator }}</td>
                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $question->cognitive_level }}</td>
                        <td style="border: 1px solid #000; padding: 8px; text-align: center;">{{ $question->question_type }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            renderMathInElement(document.body, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false}
                ],
                throwOnError : false
            });
        });
    </script>
</body>
</html>
