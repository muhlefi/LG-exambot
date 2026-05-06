# Production Deployment Guide - LG ExamBot

Dokumen ini berisi panduan langkah-demi-langkah untuk memindahkan aplikasi LG ExamBot dari lingkungan development ke production (VPS/Hosting).

## 1. Persiapan Server
Pastikan server Anda memenuhi syarat minimum:
- PHP >= 8.2
- MySQL / MariaDB
- Composer
- Node.js & NPM (untuk compile aset)
- Ekstensi PHP: `bcmath`, `ctype`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `gd`, `zip`.

## 2. Langkah Deployment

### A. Clone & Install Dependensi
```bash
git clone <repository-url>
cd LG-exambot
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### B. Konfigurasi Environment
Salin file `.env.example` menjadi `.env` dan sesuaikan nilainya:
```bash
cp .env.example .env
php artisan key:generate
```

**Penting untuk Production:**
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://domain-anda.com`
- Isi `GEMINI_API_KEY`, `HUGGINGFACE_API_KEY`, dll.

### C. Database & Storage
```bash
php artisan migrate --force
php artisan storage:link
```
Pastikan folder `storage` dan `bootstrap/cache` memiliki izin tulis (writable):
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data .
```

### D. Optimasi Laravel
Jalankan perintah ini setiap kali ada perubahan konfigurasi atau rute:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 3. Queue Worker (PENTING)
Aplikasi ini mungkin akan menggunakan Queue untuk proses generate AI yang berat di masa depan. Gunakan Supervisor untuk menjaga worker tetap jalan:
```bash
php artisan queue:work
```

## 4. Penanganan Error di Production
Aplikasi sudah dilengkapi dengan Global Exception Handler. Jika terjadi error:
- User akan menerima pesan ramah (bukan halaman debug yang menakutkan).
- Error detail akan dicatat di `storage/logs/laravel.log`.
- Pastikan Anda memantau file log tersebut secara berkala.

## 5. Troubleshooting Umum
- **Gambar Tidak Muncul**: Pastikan sudah menjalankan `php artisan storage:link`.
- **Export Gagal**: Pastikan library `dompdf` dan `phpword` sudah terinstall via composer. Jika di Linux, pastikan `libfontconfig1` terinstall untuk PDF.
- **AI Timeout**: Jika server AI lambat, Anda mungkin perlu menaikkan `max_execution_time` di `php.ini` atau timeout di Cloudflare/Nginx.
