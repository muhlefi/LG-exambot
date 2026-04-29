<x-layouts.app title="Register - LG ExamBot">
    <div class="mx-auto max-w-xl">
        <div class="paper-panel rounded-[2rem] p-8">
            <h1 class="ink-heading text-4xl font-black">Buat Akun</h1>
            <p class="mt-2 text-sm text-ink/60">Akun guru dapat langsung membuat sesi soal dan quiz.</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-8 grid gap-5 sm:grid-cols-2">
                @csrf
                <label class="block sm:col-span-2">
                    <span class="text-sm font-black text-ink">Nama</span>
                    <input name="name" value="{{ old('name') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-sm font-black text-ink">Email</span>
                    <input name="email" type="email" value="{{ old('email') }}" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="block">
                    <span class="text-sm font-black text-ink">Password</span>
                    <input name="password" type="password" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="block">
                    <span class="text-sm font-black text-ink">Konfirmasi Password</span>
                    <input name="password_confirmation" type="password" required class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                </label>
                <label class="block sm:col-span-2">
                    <span class="text-sm font-black text-ink">Role</span>
                    <select name="role" class="mt-2 w-full rounded-2xl border border-ink/10 bg-white px-4 py-3 outline-none focus:border-fern">
                        <option value="teacher">Guru / Penyusun</option>
                        <option value="admin">Admin</option>
                        <option value="student">Siswa</option>
                    </select>
                </label>
                <button class="rounded-2xl bg-fern px-5 py-3 font-black text-white sm:col-span-2">Daftar</button>
            </form>
        </div>
    </div>
</x-layouts.app>
