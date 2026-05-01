<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Naskah Soal: {{ $examSession->title }}</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>
    
    <style>
        @page { size: A4; margin: 1.5cm 2cm; }
        body { font-family: 'Times New Roman', Times, serif; line-height: 1.4; color: #000; margin: 0; padding: 0; background: white; font-size: 11pt; }
        
        .no-print { position: fixed; top: 20px; right: 20px; background: #111827; color: white; border: none; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.1); z-index: 1000; font-family: sans-serif; }
        .no-print:hover { background: #f59e0b; }
        
        /* Kop Surat */
        .kop { border-bottom: 4px double #000; padding-bottom: 10px; margin-bottom: 15px; display: flex; align-items: center; }
        .kop-logo { width: 85px; height: 85px; margin-right: 20px; }
        .kop-text { text-align: center; flex: 1; margin-right: 105px; /* Offset to center text relative to page */ }
        .kop-text h2 { margin: 0; font-size: 14pt; text-transform: uppercase; letter-spacing: 1px; }
        .kop-text h1 { margin: 0; font-size: 24pt; color: #000; font-weight: bold; }
        .kop-text p { margin: 2px 0; font-size: 10pt; font-style: italic; }
        .kop-text .contact { font-size: 9pt; font-style: normal; font-weight: bold; }

        /* Detail Ujian */
        .exam-info { width: 100%; border-collapse: collapse; margin-bottom: 15px; table-layout: fixed; }
        .exam-info td { vertical-align: top; padding: 2px 0; font-size: 10pt; }
        
        .title-block { text-align: center; font-weight: bold; font-size: 12pt; text-transform: uppercase; margin: 10px 0; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 5px 0; }

        /* Petunjuk */
        .instructions { border: 1px solid #000; padding: 8px 12px; margin-bottom: 20px; font-size: 9pt; }
        .instructions strong { display: block; margin-bottom: 3px; text-transform: uppercase; }

        /* Soal */
        .question-list { margin-top: 10px; }
        .question-item { margin-bottom: 15px; position: relative; padding-left: 25px; page-break-inside: avoid; }
        .question-number { position: absolute; left: 0; top: 0; font-weight: bold; }
        .question-body { margin-bottom: 8px; }
        
        /* Options */
        .options-container { display: flex; flex-wrap: wrap; margin-top: 5px; }
        .option-item { width: 48%; /* Default 2 columns */ margin-bottom: 3px; display: flex; gap: 8px; }
        .option-label { font-weight: bold; min-width: 15px; }

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
    <button class="no-print" onclick="window.print()">CETAK NASKAH SOAL</button>

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

    <div class="instructions">
        <strong>Petunjuk Umum:</strong>
        1. Tulislah nama dan nomor absen pada lembar jawaban yang tersedia.<br>
        2. Bacalah setiap soal dengan teliti sebelum menjawab.<br>
        3. Pilihlah satu jawaban yang paling tepat dengan memberi tanda silang (X) pada huruf A, B, C, atau D.
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
