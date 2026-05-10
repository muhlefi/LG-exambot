<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ikuti Quiz - LG ExamBot</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-fern via-fern-dark to-honey flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-3xl bg-white/20 backdrop-blur-sm shadow-2xl mb-6">
                <span class="text-3xl font-black text-white">LG</span>
            </div>
            <h1 class="text-4xl font-black text-white drop-shadow-lg">LG ExamBot</h1>
            <p class="mt-2 text-white/70 font-bold">Masukkan kode quiz untuk memulai</p>
        </div>

        <div class="bg-white rounded-[2.5rem] p-8 shadow-2xl shadow-fern/30">
            @if(session('error'))
                <div class="mb-4 rounded-xl bg-clay/10 border border-clay/20 px-4 py-3 text-sm font-black text-clay">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('quiz.show', ['code' => '__CODE__']) }}" method="GET" id="codeForm" class="space-y-6">
                <div>
                    <label class="block text-xs font-black uppercase tracking-[0.2em] text-ink/50 mb-2">Kode Quiz</label>
                    <input type="text" name="code" id="codeInput" placeholder="例: ABC123"
                        class="w-full rounded-xl border-2 border-ink/10 bg-ink/5 px-6 py-5 text-center text-2xl font-black uppercase tracking-[0.3em] outline-none focus:border-fern focus:bg-fern/5 transition-all"
                        maxlength="6" required autofocus>
                </div>
                <button type="submit" class="w-full rounded-xl bg-fern px-6 py-5 font-black text-white shadow-xl shadow-fern/30 transition hover:-translate-y-1 hover:shadow-2xl hover:shadow-fern/40">
                    Lanjutkan
                </button>
            </form>

            <div class="mt-6 flex items-center gap-3">
                <div class="flex-1 h-px bg-ink/10"></div>
                <span class="text-xs font-black text-ink/30 uppercase tracking-widest">atau</span>
                <div class="flex-1 h-px bg-ink/10"></div>
            </div>

            <button id="scanQrBtn" type="button" class="mt-6 w-full rounded-xl border-2 border-fern/30 bg-fern/5 px-6 py-4 font-black text-fern transition hover:bg-fern hover:text-white flex items-center justify-center gap-3">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
                Scan QR Code
            </button>
        </div>

        <p class="text-center text-white/40 text-xs font-bold mt-8 uppercase tracking-widest">
            Powered by LG ExamBot
        </p>
    </div>

    <div id="qrScanner" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden items-center justify-center p-4">
        <div class="bg-white rounded-[2rem] p-6 w-full max-w-sm shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-black">Scan QR Code</h2>
                <button id="closeScanner" class="text-ink/40 hover:text-ink">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div id="reader" class="w-full aspect-square rounded-xl overflow-hidden"></div>
            <p class="text-center text-xs font-bold text-ink/40 mt-4">Arahkan kamera ke QR code</p>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        const codeInput = document.getElementById('codeInput');
        const codeForm = document.getElementById('codeForm');
        const scanQrBtn = document.getElementById('scanQrBtn');
        const qrScanner = document.getElementById('qrScanner');
        const closeScanner = document.getElementById('closeScanner');
        const reader = document.getElementById('reader');
        let html5QrCode;

        codeInput.addEventListener('input', () => {
            codeInput.value = codeInput.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        codeInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const code = codeInput.value.trim();
                if (code.length === 6) {
                    window.location.href = '/quiz/' + code;
                }
            }
        });

        function toggleScanner(show) {
            qrScanner.classList.toggle('hidden', !show);
            qrScanner.classList.toggle('flex', show);
        }

        scanQrBtn.addEventListener('click', async () => {
            toggleScanner(true);
            try {
                html5QrCode = new Html5Qrcode("reader");
                await html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: { width: 250, height: 250 } },
                    (decodedText) => {
                        html5QrCode.stop().then(() => {
                            toggleScanner(false);
                            window.location.href = decodedText;
                        });
                    },
                    () => {}
                );
            } catch (err) {
                toggleScanner(false);
                Swal.fire({
                    icon: 'error',
                    title: 'Kamera tidak tersedia',
                    text: 'Tidak dapat mengakses kamera. Coba masukkan kode secara manual.',
                    confirmButtonColor: '#4a7c59'
                });
            }
        });

        closeScanner.addEventListener('click', () => {
            if (html5QrCode) {
                html5QrCode.stop().then(() => {});
            }
            toggleScanner(false);
        });

        qrScanner.addEventListener('click', (e) => {
            if (e.target === qrScanner) {
                if (html5QrCode) {
                    html5QrCode.stop().then(() => {});
                }
                toggleScanner(false);
            }
        });
    </script>
</body>
</html>
