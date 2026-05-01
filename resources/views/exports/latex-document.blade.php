\documentclass[12pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage{amsmath}
\usepackage{amssymb}
\usepackage{graphicx}
\usepackage{array}
\usepackage{geometry}
\usepackage{enumitem}
\geometry{a4paper, margin=1.5cm}

\title{\textbf{BIMBINGAN BELAJAR L-G Learning}}
\author{}
\date{}

\begin{document}

% Header
\begin{center}
    \textbf{\LARGE L-G Learning} \\
    \large Kemiri Pakukerto Sukorejo \\
    WA: 085815222639 \quad Website: l-glearning.com \\
    \rule{\linewidth}{0.5pt} \\[0.5cm]
    \textbf{\Large {{ $documentTitle }}} \\[0.5cm]
\end{center}

% Informasi Sesi
\begin{tabular}{|p{4cm}|p{10cm}|}
\hline
\textbf{Mata Pelajaran} & : {{ $session->subject }} \\
\textbf{Kelas/Semester} & : {{ $session->class_level }} / {{ $session->semester }} \\
\textbf{Tahun Ajaran} & : {{ $session->academic_year }} \\
\textbf{Materi} & : {{ $session->topic }} \\
\hline
\end{tabular}
\\[1cm]

@php
$questions = $session->questions()->with('options')->orderBy('sort_order')->get();
@endphp

% Soal
@foreach($questions as $index => $question)
\textbf{{{ $index + 1 }}.} {!! preg_replace('/\$([^\$]+)\$/U', '$$$1$$', $question->question_text) !!}

@if($question->options->count() > 0)
\begin{enumerate}[label=\Alph*.]
@foreach($question->options as $option)
    \item {{ $option->option_text }}
@endforeach
\end{enumerate}
@endif

@if($documentType !== 'questions')
\textbf{Kunci Jawaban:} {{ $question->answer_key }}

\textbf{Pembahasan:} {!! preg_replace('/\$([^\$]+)\$/U', '$$$1$$', $question->explanation) !!}
@endif

@if($documentType === 'blueprint' && $question->blueprint)
\textbf{Indikator:} {{ $question->blueprint->indicator }}
@endif

\vspace{0.5cm}
@endforeach

\end{document}