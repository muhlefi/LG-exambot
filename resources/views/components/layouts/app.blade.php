<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'LG ExamBot') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen">
        <header class="sticky top-0 z-40 border-b border-ink/10 bg-[#fff9eb]/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ auth()->check() ? route('dashboard') : url('/') }}" class="flex items-center gap-3">
                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-fern text-lg font-black text-white shadow-lg shadow-fern/20">LG</span>
                    <span>
                        <span class="block ink-heading text-2xl font-black leading-none">ExamBot</span>
                        <span class="text-xs font-semibold uppercase tracking-[0.28em] text-fern">AI Assessment Studio</span>
                    </span>
                </a>

                <div class="flex items-center gap-3">
                    <a href="{{ route('quizzes.join.form') }}" class="hidden rounded-full border border-fern/20 px-4 py-2 text-sm font-bold text-fern transition hover:bg-fern hover:text-white sm:inline-flex">Join Quiz</a>
                    @auth
                        <button type="button" class="rounded-full border border-ink/10 px-4 py-2 text-sm font-bold lg:hidden" @click="sidebarOpen = !sidebarOpen">Menu</button>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-full bg-ink px-4 py-2 text-sm font-bold text-white transition hover:bg-fern">Logout</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="rounded-full bg-ink px-4 py-2 text-sm font-bold text-white transition hover:bg-fern">Login</a>
                    @endauth
                </div>
            </div>
        </header>

        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-6 sm:px-6 lg:grid-cols-[280px_1fr] lg:px-8">
            @auth
                @php
                    $nav = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                        ['label' => 'Sesi Soal', 'route' => 'sessions.index', 'match' => 'sessions.*'],
                        ['label' => 'Bank Soal', 'route' => 'bank.index', 'match' => 'bank.*'],
                        ['label' => 'Quiz', 'route' => 'quizzes.index', 'match' => 'quizzes.index'],
                        ['label' => 'Tutorial', 'route' => 'tutorial', 'match' => 'tutorial'],
                        ['label' => 'Pengaturan', 'route' => 'settings', 'match' => 'settings'],
                    ];
                @endphp
                <aside class="lg:block" :class="sidebarOpen ? 'block' : 'hidden'">
                    <div class="paper-panel sticky top-24 rounded-[2rem] p-4">
                        <div class="mb-5 rounded-[1.5rem] bg-fern p-5 text-white">
                            <p class="text-sm text-white/70">Masuk sebagai</p>
                            <p class="text-lg font-black">{{ auth()->user()->name }}</p>
                            <p class="text-xs uppercase tracking-[0.24em] text-limewash">{{ auth()->user()->role }}</p>
                        </div>
                        <nav class="space-y-2">
                            @foreach ($nav as $item)
                                <a href="{{ route($item['route']) }}" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold transition {{ request()->routeIs($item['match']) ? 'bg-honey text-ink shadow-md shadow-honey/20' : 'text-ink/70 hover:bg-white hover:text-ink' }}">
                                    <span>{{ $item['label'] }}</span>
                                    <span>{{ request()->routeIs($item['match']) ? '>' : '' }}</span>
                                </a>
                            @endforeach
                        </nav>
                    </div>
                </aside>
            @endauth

            <main class="{{ auth()->check() ? '' : 'lg:col-span-2' }}">
                @if (session('status'))
                    <div class="mb-5 rounded-2xl border border-fern/20 bg-limewash px-5 py-4 text-sm font-bold text-fern">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-5 rounded-2xl border border-clay/30 bg-[#fff4ec] px-5 py-4 text-sm text-clay">
                        <p class="font-black">Ada input yang perlu diperbaiki.</p>
                        <ul class="mt-2 list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
