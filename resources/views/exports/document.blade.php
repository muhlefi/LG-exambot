<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1, h2 { text-align: center; margin: 0; }
        .meta { margin: 18px 0; width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; }
        .question { margin-bottom: 14px; }
        .options { margin-left: 18px; }
        .options p { display: inline; margin: 0; }
        
        /* Table Styles for Markdown Content */
        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 11px; }
        table th, table td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        table th { background-color: #f3f4f6; font-weight: bold; }
        
        table.blueprint { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.blueprint th, table.blueprint td { border: 1px solid #444; padding: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <table style="width: 100%; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 20px;">
        <tr>
            <td style="width: 80px; vertical-align: middle;">
                @php
                    $logoPath = public_path('img/logo.jpeg');
                    $logoBase64 = '';
                    if (file_exists($logoPath)) {
                        $logoData = base64_encode(file_get_contents($logoPath));
                        $logoBase64 = 'data:image/jpeg;base64,' . $logoData;
                    }
                @endphp
                @if($logoBase64)
                    <img src="{{ $logoBase64 }}" style="width: 80px; height: 80px;" />
                @endif
            </td>
            <td style="text-align: center; vertical-align: middle; padding-right: 80px;">
                <h3 style="margin: 0; font-size: 14px; font-weight: bold; text-transform: uppercase;">BIMBINGAN BELAJAR</h3>
                <h1 style="margin: 0; font-size: 28px; font-weight: black; color: #f59e0b;">L-G Learning</h1>
                <p style="margin: 2px 0; font-size: 11px;">Kemiri Pakukerto sukorejo</p>
                <p style="margin: 0; font-size: 11px; font-weight: bold;">WA : 085815222639 II website : l-glearning.com</p>
            </td>
        </tr>
    </table>

    <h2 style="text-align: center; margin-bottom: 10px; font-size: 16px;">
        @if ($documentType === 'answers')
            KUNCI JAWABAN
        @elseif ($documentType === 'blueprint')
            KISI-KISI SOAL
        @else
            NASKAH SOAL
        @endif
    </h2>

    <table class="meta">
        <tr><td>Mata Pelajaran</td><td>: {{ $session->subject }}</td></tr>
        <tr><td>Kelas/Semester</td><td>: {{ $session->class_level }} / {{ $session->semester }}</td></tr>
        <tr><td>Tahun Ajaran</td><td>: {{ $session->academic_year }}</td></tr>
        <tr><td>Materi</td><td>: {{ $session->topic }}</td></tr>
    </table>

    @if ($documentType === 'blueprint')
        <table class="blueprint">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kompetensi</th>
                    <th>Indikator</th>
                    <th>Materi</th>
                    <th>Level</th>
                    <th>Bentuk</th>
                    <th>Jawaban</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($session->questions as $question)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $question->blueprint?->competency }}</td>
                        <td>{{ $question->blueprint?->indicator }}</td>
                        <td>{{ $question->blueprint?->material }}</td>
                        <td>{{ $question->cognitive_level }}</td>
                        <td>{{ $question->question_type }}</td>
                        <td>{{ $question->answer_key }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        @foreach ($session->questions as $question)
            <div class="question">
                <div style="font-weight: bold; margin-bottom: 5px;">{{ $loop->iteration }}. {!! $question->pdf_formatted_text !!}</div>
                
                @if ($question->question_image)
                    <div style="margin: 10px 0; text-align: center;">
                        <img src="{{ public_path('storage/' . $question->question_image) }}" style="max-width: 100%; max-height: 250px; border: 1px solid #eee;" />
                    </div>
                @endif

                @if ($question->options->isNotEmpty())
                    <div class="options">
                        @foreach ($question->options as $option)
                            <div>{{ $option->option_label }}. {!! $option->pdf_formatted_text !!}</div>
                        @endforeach
                    </div>
                @endif
                @if ($documentType === 'answers')
                    <p><strong>Kunci:</strong> {{ $question->answer_key }}</p>
                    <p><strong>Pembahasan:</strong> {{ $question->explanation }}</p>
                @endif
            </div>
        @endforeach
    @endif
</body>
</html>
