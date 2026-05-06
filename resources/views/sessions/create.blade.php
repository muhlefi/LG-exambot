<x-layouts.app title="Buat Sesi - LG ExamBot">
    <div class="paper-panel rounded-[2rem] p-6">
        <nav class="mb-6 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.2em] text-ink/30">
        <a href="{{ route('dashboard') }}" class="hover:text-fern">Dashboard</a>
        <span>/</span>
        <a href="{{ route('sessions.index') }}" class="hover:text-fern">Sesi</a>
        <span>/</span>
        <span class="text-fern">Buat Sesi</span>
    </nav>

    <div class="mb-8">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Identitas Penyusun</p>
            <h1 class="ink-heading text-5xl font-black">Buat Sesi Baru</h1>
        </div>

        <form method="POST" action="{{ route('sessions.store') }}" class="grid gap-5 md:grid-cols-2">
            @csrf
            <label class="block md:col-span-2">
                <span class="text-sm font-black">Judul Paket Soal</span>
                <input name="title" value="{{ old('title') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern" placeholder="Contoh: PTS Matematika Ganjil">
            </label>
            
            <label class="block">
                <span class="text-sm font-black">Nama Guru / Penyusun</span>
                <input name="teacher_name" value="{{ old('teacher_name', auth()->user()->name) }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>

            <label class="block">
                <span class="text-sm font-black">Jenjang Pendidikan</span>
                <select name="education_level" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    @foreach (['SD','SMP','SMA'] as $level)
                        <option value="{{ $level }}" @selected(old('education_level') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block">
                <span class="text-sm font-black">Kelas</span>
                <input name="class_level" value="{{ old('class_level') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern" placeholder="7 / 8 / 9">
            </label>
            <label class="block">
                <span class="text-sm font-black">Semester</span>
                <select name="semester" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    @foreach (['Ganjil', 'Genap'] as $sem)
                        <option value="{{ $sem }}" @selected(old('semester') === $sem)>{{ $sem }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-black">Tahun Ajaran</span>
                <select name="academic_year" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    @php
                        $currentYear = date('Y');
                        $startYear = $currentYear - 2;
                    @endphp
                    @for ($i = 0; $i < 6; $i++)
                        @php $year = ($startYear + $i) . '/' . ($startYear + $i + 1); @endphp
                        <option value="{{ $year }}" @selected(old('academic_year', '2024/2025') === $year)>{{ $year }}</option>
                    @endfor
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-black">Mata Pelajaran</span>
                <input name="subject" value="{{ old('subject') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm font-black">Lingkup Materi / Topik</span>
                <input name="topic" value="{{ old('topic') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm font-black">Batasan Teori / Cakupan Materi (Opsional)</span>
                <textarea name="subtopic" rows="4" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern text-sm" placeholder="Contoh: Fokus pada hukum Newton 1 & 2 saja, jangan masukkan perhitungan gesekan.">{{ old('subtopic') }}</textarea>
                <p class="mt-1 text-[10px] text-ink/40 font-bold italic">* Semakin detail batasan yang Anda berikan, hasil soal AI akan semakin presisi.</p>
            </label>
            
            <div class="mt-4 md:col-span-2">
                <button class="rounded-full bg-fern px-10 py-4 text-sm font-black text-white shadow-xl shadow-fern/20 transition hover:-translate-y-1">Buat Sesi & Lanjutkan</button>
            </div>
        </form>
    </div>
</x-layouts.app>
