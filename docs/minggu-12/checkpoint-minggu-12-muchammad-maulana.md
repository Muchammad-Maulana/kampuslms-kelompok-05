# Jawaban Checkpoint Minggu 12 — Deployment

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

> Aplikasi sudah tersedia di Railway. Catatan berikut membedakan hal yang sudah diverifikasi pada domain publik dari pekerjaan yang masih memerlukan layanan tambahan atau tindakan anggota kelompok.

---

## 1. Apa yang terjadi jika `APP_DEBUG=true` di production?

Laravel dapat menampilkan exception lengkap, stack trace, potongan kode, path server, informasi request, dan SQL query. Data request bisa berisi informasi sensitif. Karena itu nilai production harus `APP_DEBUG=false`; diagnosis dilakukan dari log Railway/Laravel, bukan dengan menyalakan debug publik.

Saya tidak menguji paparan debug pada situs publik. Demonstrasi hanya boleh dilakukan pada lokal atau staging tertutup dengan data dan secret throwaway.

## 2. Mengapa document root mengarah ke `public/`?

Hanya folder `public/` yang boleh menjadi root web. Jika root repository dipublikasikan, file aplikasi seperti `.env`, `composer.json`, source, dan log berpotensi dapat diunduh. Docker Apache mengatur document root menjadi `/var/www/html/public`.

Setelah deploy, uji:

```bash
curl -I https://domain-anda.example.com/.env
curl -I https://domain-anda.example.com/storage/logs/laravel.log
curl -I https://domain-anda.example.com/composer.json
```

Pada 12 November 2026, ketiganya merespons **404** di domain [KampusLMS Railway](https://kampuslms-kelompok-05-production.up.railway.app/).

## 3. Apa akibatnya bila queue worker mati?

Request yang membuat tugas atau nilai masih dapat berhasil karena pekerjaan disimpan ke tabel `jobs`. Namun notifikasi tertahan sampai worker kembali aktif. Periksa worker log, tabel `jobs`, `failed_jobs`, dan log aplikasi. Service worker Railway harus menjalankan:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

## 4. Mengapa worker perlu restart setelah deployment?

Worker adalah proses yang berjalan lama dan dapat tetap memegang kode yang dimuat sebelum deployment. Restart membuat worker berhenti dengan aman lalu proses baru memuat versi kode terbaru. Pada VPS, `php artisan queue:restart` memberi sinyal restart. Di Railway, service worker harus ikut menggunakan deployment image terbaru.

## 5. Apa yang terjadi pada `env()` sesudah `config:cache`?

Laravel memakai konfigurasi hasil cache dan aplikasi tidak seharusnya membaca `.env` langsung per request. `env()` dipakai hanya di file `config/*.php`; controller, service, dan view memakai `config()`. Pencarian repository tidak menemukan pemanggilan `env()` di `app/`, `routes/`, atau `resources/`.

## 6. Bagaimana migrasi production dijalankan?

Railway pre-deploy command menjalankan:

```bash
php artisan migrate --force
```

Migrasi harus selesai sebelum release aktif. Jangan gunakan `migrate:fresh` karena dapat menghapus data. Seeder tidak dijalankan otomatis setiap restart. Seeder demo hanya dijalankan manual jika tiga password unik minimal 16 karakter sudah diatur melalui secret Railway.

## 7. Bagaimana file submission dan materi dilindungi?

File menggunakan disk lokal privat `storage/app/private`. Pengunduhan dilakukan melalui controller setelah autentikasi dan otorisasi, bukan melalui URL statis. Railway volume perlu dipasang pada direktori ini agar file tidak hilang saat container diganti. Uji akun tanpa login dan akun lain harus memastikan mereka tidak dapat mengunduh file tersebut.

## 8. Bagaimana memeriksa error 500 tanpa mengaktifkan debug?

Pertahankan `APP_DEBUG=false`; baca deploy log Railway dan log aplikasi. Periksa apakah migrasi berhasil, konfigurasi DB/APP_KEY benar, storage/cache dapat ditulis, health check `/up` lulus, dan worker aktif. Jangan mengirim secret atau isi `.env` pada log/tiket.

## 9. Apa hasil deployment kelompok?

- Domain HTTPS: https://kampuslms-kelompok-05-production.up.railway.app/
- `GET /`, `/up`, dan `/login`: **200**.
- Tanpa login, `/dashboard` dan `/admin/users` mengarahkan ke login (**302**).
- `/.env`, `/composer.json`, dan `/storage/logs/laravel.log`: **404**.
- HTTP dialihkan ke HTTPS (**301**); aset CSS/JavaScript menggunakan HTTPS dan merespons **200**.
- Header yang diverifikasi: `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, dan `Referrer-Policy: strict-origin-when-cross-origin`.
- Migrasi awal dijalankan dengan `php artisan migrate --force`; perintah yang sama kini dikonfigurasi sebagai pre-deploy command untuk rilis berikutnya.
- Worker, volume persistent, akun demo production, skrip mutatif `test-authz.sh`, dan pengujian dari perangkat anggota lain **belum diverifikasi/dilakukan**. Worker dan volume memerlukan resource Railway tambahan; jangan menyatakan fitur antrean atau unggahan persisten siap production sebelum dikonfigurasi dan diuji.

Build aset lokal dan regression test forwarded HTTPS berhasil.

## 10. Hal yang dipelajari dengan bantuan AI

AI membantu menyusun Dockerfile, health check, konfigurasi variable, dan checklist. Saya menyesuaikan contoh deploy agar tidak mengabaikan kegagalan Composer/migrasi, tidak menjalankan seeder otomatis setiap restart, tidak memakai `APP_KEY` publik, serta mewajibkan password demo produksi yang berbeda. Deploy live, akun demo, dan keluaran pengujian harus tetap diverifikasi dan dilengkapi sendiri oleh kelompok.
