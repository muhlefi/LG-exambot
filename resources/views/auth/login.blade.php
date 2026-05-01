<x-layouts.app title="Login - LG ExamBot">
    <div class="mx-auto max-w-md">
        <div class="paper-panel rounded-[2rem] p-8">
            <h1 class="ink-heading text-4xl font-black">Masuk</h1>
            <p class="mt-2 text-sm text-ink/60">Gunakan akun guru/admin untuk mengelola sesi soal.</p>

            <form method="POST" action="{{ route('login.store') }}" class="mt-8 space-y-5">
                @csrf
                <label class="block">
                    <span class="text-sm font-black text-ink">Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="block">
                    <span class="text-sm font-black text-ink">Password</span>
                    <input name="password" type="password" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="flex items-center gap-2 text-sm font-bold text-ink/70">
                    <input type="checkbox" name="remember" value="1" class="rounded border-ink/20 text-fern">
                    Ingat saya
                </label>
                <button class="w-full rounded-2xl bg-fern px-5 py-3 font-black text-white">Login</button>
            </form>

            <!-- <p class="mt-6 text-center text-sm text-ink/60">
                Belum punya akun? <a href="#" class="font-black text-fern">Daftar</a>
            </p> -->
        </div>
    </div>
</x-layouts.app>
