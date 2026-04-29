<x-layouts.app title="Pengaturan - LG ExamBot">
    <div class="mb-6">
        <p class="text-sm font-black uppercase tracking-[0.24em] text-fern">Pengaturan</p>
        <h1 class="ink-heading text-5xl font-black">Profile, template, dan AI</h1>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="paper-panel rounded-[2rem] p-6">
            <h2 class="ink-heading text-3xl font-black">Profile</h2>
            <div class="mt-5 space-y-3 text-sm">
                <p><span class="font-black">Nama:</span> {{ auth()->user()->name }}</p>
                <p><span class="font-black">Email:</span> {{ auth()->user()->email }}</p>
                <p><span class="font-black">Role:</span> {{ auth()->user()->role }}</p>
            </div>
        </section>

        <section class="paper-panel rounded-[2rem] p-6">
            <h2 class="ink-heading text-3xl font-black">API AI</h2>
            <div class="mt-5 space-y-3 text-sm text-ink/70">
                <p>Provider aktif: <span class="font-black text-ink">{{ config('services.ai.default_provider') }}</span></p>
                <p>OpenAI model: <span class="font-black text-ink">{{ config('services.ai.openai_model') }}</span></p>
                <p>Gemini model: <span class="font-black text-ink">{{ config('services.ai.gemini_model') }}</span></p>
                <p>Jika API key belum diisi, generator memakai mode draft lokal untuk demo.</p>
            </div>
        </section>

        <section class="paper-panel rounded-[2rem] p-6 lg:col-span-2">
            <h2 class="ink-heading text-3xl font-black">Template Dokumen</h2>
            <div class="mt-5 grid gap-3 md:grid-cols-2">
                @forelse ($templates as $template)
                    <div class="rounded-2xl bg-white/70 p-4">
                        <p class="font-black">{{ $template->name }}</p>
                        <p class="mt-1 text-sm text-ink/60">{{ $template->school_name ?: 'Template umum' }}</p>
                    </div>
                @empty
                    <p class="text-sm text-ink/60">Belum ada template dokumen.</p>
                @endforelse
            </div>
        </section>
    </div>
</x-layouts.app>
