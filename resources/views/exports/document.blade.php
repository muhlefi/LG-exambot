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
        table.blueprint { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.blueprint th, table.blueprint td { border: 1px solid #444; padding: 5px; vertical-align: top; }
    </style>
</head>
<body>
    <h2>{{ strtoupper($session->school_name) }}</h2>
    <h1>
        @if ($documentType === 'answers')
            KUNCI JAWABAN
        @elseif ($documentType === 'blueprint')
            KISI-KISI SOAL
        @else
            NASKAH SOAL
        @endif
    </h1>

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
                <strong>{{ $loop->iteration }}. {{ $question->question_text }}</strong>
                @if ($question->options->isNotEmpty())
                    <div class="options">
                        @foreach ($question->options as $option)
                            <div>{{ $option->option_label }}. {{ $option->option_text }}</div>
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
