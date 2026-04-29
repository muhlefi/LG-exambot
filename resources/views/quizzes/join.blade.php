<x-layouts.app title="Join Quiz - LG ExamBot">
    <div class="mx-auto max-w-md">
        <div class="paper-panel rounded-[2rem] p-8">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Siswa</p>
            <h1 class="ink-heading mt-2 text-5xl font-black">Join Quiz</h1>
            <form method="POST" action="{{ route('quizzes.join') }}" class="mt-8 space-y-5">
                @csrf
                <label class="block">
                    <span class="text-sm font-black">Kode Room</span>
                    <input name="quiz_code" value="{{ old('quiz_code') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 uppercase tracking-[0.2em] outline-none focus:border-fern">
                </label>
                <label class="block">
                    <span class="text-sm font-black">Nama Siswa</span>
                    <input name="student_name" value="{{ old('student_name') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="block">
                    <span class="text-sm font-black">Password Room</span>
                    <input name="password" type="password" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <button class="w-full rounded-2xl bg-fern px-5 py-3 font-black text-white">Mulai</button>
            </form>
        </div>
    </div>
</x-layouts.app>
