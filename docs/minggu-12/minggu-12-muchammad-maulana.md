# Minggu 12 — Deployment

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12
- **Sub-CPMK:** Mahasiswa mampu menerapkan deployment aplikasi web, mengelola aplikasi produksi secara bertanggung jawab, dan menjalankannya di server produksi.

> Minggu 12 adalah Milestone M4 — Tugas 4 dan interview kelompok. Target akhirnya adalah aplikasi yang dapat diakses melalui HTTPS, dengan konfigurasi produksi yang aman.

---

# READ — Perbedaan Lokal dan Produksi

| Aspek | Lokal | Produksi |
|---|---|---|
| `APP_ENV` | `local` | `production` |
| `APP_DEBUG` | `true` untuk development | **`false`** |
| Aset | Vite dev server | `npm run build` |
| Cache Laravel | dapat dibersihkan untuk development | config, route, view, dan event cache |
| Composer | termasuk dependency development | `--no-dev --optimize-autoloader` |
| Queue worker | dijalankan manual | proses terpisah yang selalu dijaga aktif |
| HTTPS | tidak wajib di localhost | wajib untuk situs publik |
| Error | tampil untuk developer | log produksi; jangan membuka debug |

## `APP_DEBUG=false` dan document root

`APP_DEBUG=true` pada aplikasi publik dapat menampilkan exception, trace, potongan source, path server, detail request, dan query. Informasi tersebut membantu penyerang memahami aplikasi. Produksi harus memakai `APP_DEBUG=false` sejak awal.

Document root web server harus menuju `public/`, bukan root repository. Akar repository memuat `.env`, `composer.json`, source code, dan log. Repository ini mengatur Apache container ke `/var/www/html/public`; Docker build juga mengecualikan `.env` dan data unggahan.

Sesudah deploy, jalankan pemeriksaan ini. Ketiganya tidak boleh menghasilkan HTTP 200:

```bash
curl -I https://domain-anda.example.com/.env
curl -I https://domain-anda.example.com/storage/logs/laravel.log
curl -I https://domain-anda.example.com/composer.json
```

Status 403/404 dapat diterima. Pemeriksaan dilakukan dari browser/perangkat lain juga.

## Railway

Kelompok memilih Railway sesuai arahan asisten dosen. Aplikasi menggunakan Dockerfile PHP 8.4 + Apache, membangun aset Vite di tahap build, membaca port dinamis Railway, dan mengekspos health endpoint Laravel `/up`.

- `railway.json` menetapkan Dockerfile, health check, dan restart policy.
- MySQL menjadi service terpisah dan kredensialnya direferensikan melalui Railway Variables.
- `APP_KEY` harus unik untuk deployment ini dan disimpan sebagai secret; jangan commit atau membagikannya.
- `SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`, dan `APP_ENV=production`.
- Pre-deploy command hanya menjalankan `php artisan migrate --force`; seeder tidak dijalankan otomatis setiap container start.
- Queue worker dijalankan sebagai service Railway terpisah.
- Volume persisten dipasang untuk `storage/app/private`; file submission dan materi tidak dipublikasikan sebagai URL statis.

Langkah konfigurasi rinci dan variabel Railway ada di [panduan deployment](../deployment.md).

## `env()` dan cache

Pemeriksaan repository tidak menemukan `env()` di `app/`, `routes/`, atau `resources/`. Nilai environment dibaca melalui file `config/`, lalu kode aplikasi menggunakan `config()`. Sesudah config cache dibuat, pemanggilan `env()` di luar konfigurasi dapat menghasilkan nilai yang tidak diharapkan.

---

# BREAK — Percobaan dan Risiko

Percobaan yang dapat membuka source atau rahasia **tidak dilakukan pada domain publik**. Gunakan staging terpisah dengan IP restriction/basic auth, database throwaway, dan `APP_KEY` non-produksi. Segera kembalikan konfigurasi setelah observasi. Jangan pernah menggunakan kredensial atau data produksi untuk percobaan paparan.

| No. | Percobaan aman | Hal yang dipelajari |
|---:|---|---|
| 1 | Aktifkan `APP_DEBUG=true` pada lokal/staging tertutup, lalu picu exception | Halaman debug dapat menampilkan detail internal; konfigurasi tersebut dilarang pada production. |
| 2 | Periksa document root dan coba akses file sensitif dari staging | Root proyek dapat membocorkan file; web server harus diarahkan ke `public/`. |
| 3 | Jalankan `config:cache` lalu periksa code path yang membaca environment | `env()` hanya digunakan di `config/`; gunakan `config()` di kode aplikasi. |
| 4 | Hentikan worker staging lalu picu notifikasi | Request dapat berhasil sementara pekerjaan tetap menunggu di tabel `jobs`. |
| 5 | Deploy kode staging dan amati worker setelah restart | Worker lama harus dihentikan agar membaca kode deployment terbaru. |
| 6 | Ubah izin storage staging | Aplikasi harus dapat menulis log, cache, dan berkas privat. |
| 7 | Kecilkan batas unggahan PHP di staging lalu unggah file uji | Batas PHP harus konsisten dengan validasi aplikasi. |
| 8 | Coba HTTP setelah secure cookie diaktifkan | Session cookie aman hanya dikirim melalui HTTPS. |

Tidak mengarahkan document root ke root repository dan tidak mematikan aturan perlindungan file pada situs publik.

---

# FIX — Dukungan Deployment di Repository

1. Menambahkan [Dockerfile](../../Dockerfile) multi-stage: build aset Vite, install dependency Composer produksi, konfigurasi Apache `public/`, `mod_rewrite`, header keamanan, dan port Railway dinamis.
2. Menambahkan [.dockerignore](../../.dockerignore) agar `.env`, vendor lokal, node_modules, log, dan file private tidak masuk ke image build.
3. Menambahkan [railway.json](../../railway.json) untuk Docker build, health check `/up`, dan restart policy.
4. Menambahkan [container entrypoint](../../scripts/container-entrypoint.sh) yang memvalidasi port, menyiapkan izin runtime, menjalankan Apache, atau menjalankan worker sebagai user non-root.
5. Mengamankan seeder demo: password development tetap dapat menggunakan nilai lokal, tetapi produksi memerlukan tiga password unik berisi setidaknya 16 karakter melalui config environment.
6. Memperbarui [skrip test otorisasi](../../scripts/test-authz.sh) supaya kredensial akun demo dapat diberikan melalui environment variable tanpa mengganti nilai password di source.
7. Menambahkan [scripts/deploy.sh](../../scripts/deploy.sh) untuk host Linux/VPS tradisional. Railway memakai auto-deploy serta pre-deploy migration dari dashboard, bukan skrip maintenance VPS ini.
8. Menuliskan langkah Railway, MySQL, akun demo, worker, volume, pemeriksaan keamanan, dan troubleshooting pada [docs/deployment.md](../deployment.md).

## Hal yang tidak boleh diotomatisasi

- Jangan meletakkan `APP_KEY`, password database, atau password akun produksi di Git.
- Jangan menambahkan `--ignore-platform-reqs` pada Composer.
- Jangan mengabaikan error migrasi atau menjalankan `db:seed` pada setiap restart.
- Jangan menjalankan `migrate:fresh` pada database berisi data.
- Jangan menyalakan `APP_DEBUG` untuk diagnosis produksi.
- Jangan menyimpan submission atau materi di folder publik.

---

# BUILD — Hasil dan Tugas 4

Build aset lokal dijalankan sebelum konfigurasi minggu ini:

```text
npm.cmd run build
vite v7.3.6
58 modules transformed
✓ built in 15.93s
```

Container image, migration MySQL, dan deployment Railway memerlukan Docker/Railway serta akses project dan tidak dapat dibuktikan dari workspace lokal ini. Statusnya harus dilengkapi setelah kelompok menghubungkan repository ke Railway.

Daftar deliverable:

- [ ] Aplikasi tersedia di domain publik dengan HTTPS dan tautan ditambahkan ke README.
- [ ] Checklist keamanan produksi lulus dengan keluaran `curl` aktual.
- [x] `deploy.sh` tersedia dengan maintenance, first-error-stop, migrasi, cache, queue restart, dan health check untuk host VPS.
- [ ] Service worker aktif dan bukti statusnya disimpan.
- [x] `docs/deployment.md` menjelaskan arsitektur, konfigurasi, redeploy, log, dan masalah umum; masalah nyata kelompok harus ditambahkan setelah deploy.
- [ ] `scripts/test-authz.sh` lulus terhadap deployment staging/produksi yang aman.
- [ ] Akun demo produksi dibuat dengan password berbeda dan tidak memakai password contoh.
- [ ] Aplikasi diuji dari perangkat anggota lain dan hasilnya dicatat.

Untuk layanan Railway, proses build/redeploy menggunakan commit dari GitHub. Worker harus menjalankan `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`; setelah deployment baru, periksa log service worker dan job yang tertunda/gagal.
