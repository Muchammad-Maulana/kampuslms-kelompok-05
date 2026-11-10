# Jawaban Checkpoint Minggu 12 — Deployment

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

> Domain Railway dan hasil pemeriksaan produksi belum tersedia dari workspace ini. Bagian yang membutuhkan bukti live harus dilengkapi dengan keluaran asli setelah deployment; jangan menganggap konfigurasi lokal sebagai bukti production.

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

Semua harus menghasilkan status selain 200. Domain aktual dan kode respons aktual: **belum tersedia; isi setelah deploy**.

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

- Domain HTTPS: **belum tersedia pada workspace ini**.
- `curl` file sensitif, redirect HTTP, dan header: **belum dijalankan pada domain live**.
- Status worker dan hasil test otorisasi pada server: **belum tersedia**.
- Perangkat anggota lain yang dipakai serta kendala nyata: **catat setelah pengujian**.

Build aset lokal berhasil. Seluruh bukti yang masih kosong di atas merupakan pekerjaan operasional kelompok di akun Railway, bukan hasil yang dapat dibuktikan dari build lokal.

## 10. Hal yang dipelajari dengan bantuan AI

AI membantu menyusun Dockerfile, health check, konfigurasi variable, dan checklist. Saya menyesuaikan contoh deploy agar tidak mengabaikan kegagalan Composer/migrasi, tidak menjalankan seeder otomatis setiap restart, tidak memakai `APP_KEY` publik, serta mewajibkan password demo produksi yang berbeda. Deploy live, akun demo, dan keluaran pengujian harus tetap diverifikasi dan dilengkapi sendiri oleh kelompok.
