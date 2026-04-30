<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="LG ExamBot - Platform bertenaga AI untuk memudahkan guru menyusun soal, kisi-kisi, kunci jawaban, dan melaksanakan quiz interaktif dalam satu studio.">
    <title>{{ $title ?? config('app.name', 'LG ExamBot') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .swal2-styled.swal2-confirm { background-color: #f59e0b !important; border-radius: 1rem !important; font-weight: 800 !important; }
        .swal2-popup { border-radius: 2rem !important; font-family: 'Inter', sans-serif !important; }
    </style>
    <script>
        window.confirmDelete = function(formId, text = 'Data ini akan dihapus permanen!') {
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#111827',
                confirmButtonText: 'Ya, hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
    </script>
</head>
<body class="font-sans antialiased text-ink">
    @auth
        <div x-data="{ sidebarOpen: false }" class="flex h-screen overflow-hidden">
            <!-- Sidebar -->
            <aside 
                class="fixed inset-y-0 left-0 z-50 w-72 transform bg-white/90 backdrop-blur-xl border-r border-ink/10 transition-transform duration-300 lg:static lg:translate-x-0 flex flex-col"
                :class="{'translate-x-0': sidebarOpen, '-translate-x-full': !sidebarOpen}"
            >
                <div class="flex h-20 items-center px-6 border-b border-ink/10">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-fern text-xl font-black text-white shadow-lg shadow-fern/20">LG</span>
                        <span>
                            <span class="block ink-heading text-2xl font-black leading-none text-ink">ExamBot</span>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-fern">Assessment Studio</span>
                        </span>
                    </a>
                </div>

                @php
                    $nav = [
                        ['label' => 'Dashboard', 'route' => 'dashboard', 'match' => 'dashboard'],
                        ['label' => 'Sesi Soal', 'route' => 'sessions.index', 'match' => 'sessions.*'],
                        ['label' => 'Bank Soal', 'route' => 'bank.index', 'match' => 'bank.*'],
                        ['label' => 'Quiz', 'route' => 'quizzes.index', 'match' => 'quizzes.*'],
                    ];
                @endphp
                
                <div class="p-6">
                    <div class="rounded-[2rem] bg-limewash/50 backdrop-blur-sm p-6 border border-fern/10 shadow-inner group transition-all hover:bg-limewash">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 rounded-full bg-fern grid place-items-center text-white font-black shadow-md shadow-fern/20">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                            <div class="flex-1 overflow-hidden">
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-fern/60 leading-none">Guru Aktif</p>
                                <p class="mt-1.5 text-base font-black truncate text-ink">{{ auth()->user()->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <nav class="flex-1 space-y-2 px-6 pb-6 overflow-y-auto">
                    @foreach ($nav as $item)
                        <a href="{{ route($item['route']) }}" 
                           class="flex items-center justify-between rounded-xl px-4 py-3 text-sm font-bold transition-all {{ request()->routeIs($item['match']) ? 'bg-fern text-white shadow-md shadow-fern/20' : 'text-ink/70 hover:bg-limewash hover:text-fern' }}">
                            <span>{{ $item['label'] }}</span>
                            @if(request()->routeIs($item['match']))
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M9 5l7 7-7 7"></path></svg>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>

            <!-- Main Content -->
            <div class="flex flex-1 flex-col overflow-hidden relative">
                <!-- Header -->
                <header class="flex h-20 items-center justify-between border-b border-ink/10 bg-white/50 backdrop-blur-md px-6 lg:px-10 z-10">
                    <div class="flex items-center">
                        <button type="button" class="rounded-xl border border-ink/10 p-2 text-ink/70 hover:bg-limewash hover:text-fern lg:hidden mr-4 transition" @click="sidebarOpen = !sidebarOpen">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <h1 class="text-xl font-black text-ink hidden sm:block">{{ $title ?? 'Dashboard' }}</h1>
                    </div>

                    <div class="flex items-center gap-4">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-full bg-ink px-6 py-2.5 text-sm font-bold text-white transition hover:bg-clay shadow-lg shadow-ink/10">Logout</button>
                        </form>
                    </div>
                </header>

                <!-- Scrollable Content -->
                <main class="flex-1 overflow-y-auto p-6 lg:p-10">
                    <div class="max-w-7xl mx-auto">
                        @if (session('status'))
                            <script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil!',
                                    text: "{{ session('status') }}",
                                    timer: 3000,
                                    showConfirmButton: false
                                });
                            </script>
                        @endif

                        @if ($errors->any())
                            <script>
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Oops...',
                                    text: "{{ $errors->first() }}",
                                    confirmButtonColor: '#f59e0b'
                                });
                            </script>
                        @endif

                        {{ $slot ?? '' }}
                        @yield('content')
                    </div>
                </main>
            </div>

            <!-- Overlay -->
            <div x-show="sidebarOpen" 
                 class="fixed inset-0 z-40 bg-ink/60 backdrop-blur-sm lg:hidden transition-opacity"
                 @click="sidebarOpen = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
            </div>
        </div>
    @else
        <div class="min-h-screen flex flex-col">
            <header class="sticky top-0 z-40 border-b border-ink/10 bg-white/80 backdrop-blur-xl">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <a href="{{ url('/') }}" class="flex items-center gap-3">
                        <span class="grid h-11 w-11 place-items-center rounded-2xl bg-fern text-lg font-black text-white shadow-lg shadow-fern/20">LG</span>
                        <span>
                            <span class="block ink-heading text-2xl font-black leading-none">ExamBot</span>
                            <span class="text-[10px] font-bold uppercase tracking-[0.2em] text-fern">Assessment Studio</span>
                        </span>
                    </a>
                    <div class="flex items-center gap-3">
                        @if(!request()->routeIs('login'))
                            <a href="{{ route('login') }}" class="rounded-full bg-ink px-6 py-2 text-sm font-bold text-white transition hover:bg-fern shadow-lg shadow-ink/20">Login</a>
                        @endif
                    </div>
                </div>
            </header>
            
            <main class="flex-1 mx-auto max-w-7xl w-full px-4 py-8 sm:px-6 lg:px-8">
                @if (session('status'))
                    <div class="mb-8 rounded-2xl border border-fern/20 bg-limewash px-6 py-4 text-sm font-bold text-fern flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-8 rounded-2xl border border-clay/30 bg-[#fff4ec] px-6 py-5 text-sm text-clay shadow-sm">
                        <div class="flex items-center gap-3 mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            <p class="font-black">Ada input yang perlu diperbaiki.</p>
                        </div>
                        <ul class="list-disc pl-8 space-y-1">
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
    @endauth
    @livewireScripts
</body>
</html>
