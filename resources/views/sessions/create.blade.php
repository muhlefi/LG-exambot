<x-layouts.app title="Buat Sesi - LG ExamBot">
    <div class="paper-panel rounded-[2rem] p-6">
        <div class="mb-8">
            <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Identitas Penyusun</p>
            <h1 class="ink-heading text-5xl font-black">Buat Sesi Baru</h1>
        </div>

        <form method="POST" action="{{ route('sessions.store') }}" enctype="multipart/form-data" class="grid gap-5 md:grid-cols-2">
            @csrf
            <label class="block md:col-span-2">
                <span class="text-sm font-black">Judul Paket</span>
                <input name="title" value="{{ old('title') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern" placeholder="PTS Matematika Kelas VIII">
            </label>
            <label class="block">
                <span class="text-sm font-black">Nama Guru / Penyusun</span>
                <input name="teacher_name" value="{{ old('teacher_name', auth()->user()->name) }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block">
                <span class="text-sm font-black">Nama Satuan Pendidikan</span>
                <input name="school_name" value="{{ old('school_name') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block">
                <span class="text-sm font-black">Logo Sekolah</span>
                <input name="logo" type="file" accept="image/*" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 text-sm">
            </label>
            <label class="block">
                <span class="text-sm font-black">Jenjang Pendidikan</span>
                <select name="education_level" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                    @foreach (['TK','SD','SMP','SMA','SMK','Perguruan Tinggi','Lainnya'] as $level)
                        <option value="{{ $level }}" @selected(old('education_level') === $level)>{{ $level }}</option>
                    @endforeach
                </select>
            </label>
            <label class="block">
                <span class="text-sm font-black">Fase Pembelajaran</span>
                <input name="learning_phase" value="{{ old('learning_phase') }}" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern" placeholder="Fase D">
            </label>
            <label class="block">
                <span class="text-sm font-black">Kelas</span>
                <input name="class_level" value="{{ old('class_level') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block">
                <span class="text-sm font-black">Semester</span>
                <input name="semester" value="{{ old('semester') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block">
                <span class="text-sm font-black">Tahun Ajaran</span>
                <input name="academic_year" value="{{ old('academic_year', '2026/2027') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block">
                <span class="text-sm font-black">Mata Pelajaran</span>
                <input name="subject" value="{{ old('subject') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block">
                <span class="text-sm font-black">Lingkup Materi / Topik</span>
                <input name="topic" value="{{ old('topic') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <label class="block md:col-span-2">
                <span class="text-sm font-black">Sub Materi</span>
                <input name="subtopic" value="{{ old('subtopic') }}" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
            </label>
            <div class="mt-4 md:col-span-2">
                <button class="rounded-full bg-fern px-10 py-4 text-sm font-black text-white shadow-xl shadow-fern/20 transition hover:-translate-y-1">Buat Sesi & Lanjutkan</button>
            </div>
        </form>
    </div>
</x-layouts.app>
