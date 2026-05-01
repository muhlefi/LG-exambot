\documentclass[12pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage{indonesian}
\usepackage{amsmath}
\usepackage{amsfonts}
\usepackage{amssymb}
\usepackage{graphicx}
\usepackage{array}
\usepackage{longtable}
\usepackage{geometry}
\geometry{a4paper, margin=2.5cm}

% Untuk format soal pilihan ganda
\newcounter{question}
\newcommand{\question}[1]{
    \refstepcounter{question}
    \noindent\textbf{\arabic{question}. #1}
}

\newcommand{\option}[2]{
    \par\noindent\hangindent=1cm\hangafter=1\textbf{#1.} #2
}

\newcommand{\answer}[1]{
    \vspace{0.2cm}
    \noindent\textbf{Kunci Jawaban:} #1
}

\newcommand{\explanation}[1]{
    \noindent\textbf{Pembahasan:} #1
    \vspace{0.5cm}
}

\begin{document}

% Header
\begin{center}
    \includegraphics[width=2cm]{{ public_path('img/logo.png') }}
    
    {\large\bfseries BIMBINGAN BELAJAR}\\
    {\Huge\bfseries\color[rgb]{0.96,0.62,0.05} L-G Learning}\\
    Kemiri Pakukerto Sukorejo\\
    WA : 085815222639 \hspace{1cm} website : l-glearning.com\\
    \rule{\linewidth}{0.5pt}
    
    \vspace{0.5cm}
    \LARGE\bfseries @latex($documentTitle)
\end{center}

\vspace{0.5cm}

\noindent
\textbf{Mata Pelajaran:} @latex($session->subject)\\
\textbf{Kelas/Semester:} @latex($session->class_level) / @latex($session->semester)\\
\textbf{Tahun Ajaran:} @latex($session->academic_year ?? '2025/2026')

\vspace{0.5cm}

@foreach($session->questions as $index => $question)
    \question{ @latex($question->question_text) }
    
    @foreach($question->options as $option)
        \option{@latex($option->option_label)}{ @latex($option->option_text) }
    @endforeach
    
    @if($documentType !== 'questions')
        \answer{ @latex($question->answer_key) }
        @if($question->explanation)
            \explanation{ @latex($question->explanation) }
        @endif
    @endif
    
    @if($documentType === 'blueprint' && $question->blueprint)
        \noindent\textbf{Kisi-kisi:} @latex($question->blueprint->indicator)
        \vspace{0.3cm}
    @endif
    
    \vspace{0.3cm}
@endforeach

\end{document}