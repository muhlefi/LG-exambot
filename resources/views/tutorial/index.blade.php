<x-layouts.app title="Tutorial - LG ExamBot">
    <div class="mb-6">
        <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Tutorial</p>
        <h1 class="ink-heading text-5xl font-black">Panduan penggunaan aplikasi</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @forelse ($lessons as $category => $items)
            <section class="paper-panel rounded-[2rem] p-6">
                <h2 class="ink-heading text-3xl font-black">{{ $category }}</h2>
                <div class="mt-5 space-y-4">
                    @foreach ($items as $lesson)
                        <article class="rounded-xl bg-white/70 p-5">
                            <h3 class="font-black">{{ $lesson->title }}</h3>
                            <p class="mt-2 text-sm leading-6 text-ink/70">{{ $lesson->body }}</p>
                            @if ($lesson->video_url)
                                <a href="{{ $lesson->video_url }}" target="_blank" class="mt-3 inline-flex text-sm font-black text-fern">Buka video</a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="paper-panel rounded-[2rem] p-10 text-center text-ink/60 lg:col-span-2">
                Tutorial belum tersedia. Jalankan seeder untuk mengisi tutorial awal.
            </div>
        @endforelse
    </div>
</x-layouts.app>
