# Panduan Deployment KampusLMS di Railway

Panduan ini menjelaskan konfigurasi aplikasi dan langkah deploy menggunakan Dockerfile di root repository. Nilai domain, keluaran pemeriksaan, spesifikasi project Railway, dan masalah yang benar-benar dialami harus dicatat setelah deployment; jangan mengisi bukti yang belum dijalankan.

## Spesifikasi aplikasi

- Laravel 12, PHP 8.4, Apache, dan aset Vite hasil build produksi.
- MySQL disediakan sebagai service terpisah di project Railway.
- Queue memakai database dan memerlukan service worker terpisah.
- Berkas submission dan materi menggunakan disk `local` privat di `storage/app/private`.
- `railway.json` memeriksa endpoint Laravel `/up`; public document root container mengarah ke `public/`.

## Persiapan project Railway

1. Buat project Railway dan tambahkan service MySQL.
2. Tambahkan service aplikasi dari repository ini dan pilih branch yang akan dirilis. Railway memakai `Dockerfile` dan `railway.json` yang ada di root.
3. Hubungkan service aplikasi ke database dengan reference Railway berikut (ganti nama service reference bila layanan Anda bukan bernama `MySQL`):

   ```env
   APP_NAME=KampusLMS
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=<hasilkan kunci acak untuk deployment ini; jangan gunakan kunci lokal atau contoh publik>
   APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   SESSION_DRIVER=database
   SESSION_SECURE_COOKIE=true
   QUEUE_CONNECTION=database
   CACHE_STORE=database
   FILESYSTEM_DISK=local
   LOG_CHANNEL=stderr
   ```

   Buat APP_KEY untuk pertama kali dengan `php artisan key:generate --show` pada lingkungan lokal tepercaya, lalu masukkan hasilnya hanya ke Variables Railway. Jangan commit, kirim ke chat, atau gunakan ulang key di lingkungan lain. Setelah tersimpan, jangan membuat ulang key pada setiap deploy karena itu membatalkan sesi terenkripsi.
4. Di pengaturan service aplikasi, atur pre-deploy command menjadi `php artisan migrate --force`. Perintah ini hanya menjalankan migrasi; **jangan** menjalankan `migrate:fresh` atau `db:seed` otomatis saat container start.
5. Generate public domain di Railway. Pastikan domain memakai HTTPS. Atur volume persisten pada service aplikasi ke `/var/www/html/storage/app/private` agar berkas privat tidak hilang saat container diganti. Volume Railway tidak otomatis dibagi dengan service lain.
6. Deploy aplikasi. Health check harus mendapat respons sukses dari `/up`.

## Akun demo

Seeder membuat akun `admin@kampuslms.test`, `dosen@kampuslms.test`, dan `mahasiswa@kampuslms.test`. Untuk menggunakannya di produksi, atur variabel `DEMO_ADMIN_PASSWORD`, `DEMO_LECTURER_PASSWORD`, dan `DEMO_STUDENT_PASSWORD` di Railway. Ketiganya harus berupa password berbeda, masing-masing minimal 16 karakter, dan tidak sama dengan `password`.

Jalankan seeder **satu kali secara manual** setelah migrasi dengan terminal Railway:

```bash
php artisan db:seed --class=DemoAccountSeeder --force
```

Seeder produksi akan gagal secara eksplisit bila tiga password rahasia belum dikonfigurasi dengan aman. Jangan jalankan seluruh `DatabaseSeeder` di produksi: seeder tersebut menambahkan data demonstrasi dan bukan bagian dari tiap deployment. Setelah akun dibuat, hapus password variabel dari Railway bila tidak perlu dirotasi lagi; simpan password akun hanya di secret manager kelompok.

## Queue worker

Tambahkan service Railway kedua dari repository dan image Dockerfile yang sama. Pada service worker, set start command:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

Entrypoint menjalankan worker sebagai user `www-data`. Jangan pasang dua migrasi yang sama sebagai start command kedua service. Railway akan mengganti proses worker saat image baru dideploy; worker memakai koneksi dan database yang sama. Periksa deployment log worker dan tabel `jobs`/`failed_jobs` setelah mengirim notifikasi uji.

## Berkas unggahan privat

Dokumen mahasiswa dan materi tidak boleh diakses melalui URL statis. Controller mengunduhnya setelah autentikasi dan pemeriksaan otorisasi. Volume persisten pada direktori private diperlukan agar berkas bertahan melewati pergantian container. Akses langsung ke `/storage/logs/laravel.log` tetap harus gagal.

Uji unduhan submission/materi dari akun yang berhak dan akun lain. Jalankan uji dari jendela privat tanpa login untuk memastikan unduhan memerlukan autentikasi. Jangan pernah mengarahkan volume unggahan ke `public/`.

## Skrip redeploy non-Railway

`scripts/deploy.sh` disediakan sebagai prosedur untuk host Linux/VPS tradisional, sesuai kebutuhan demonstrasi mode maintenance, build, migrasi, cache, restart worker, dan health check. Skrip mengasumsikan checkout Git dengan remote `origin`, Composer, Node/npm, PHP, dan cURL terpasang.

```bash
DEPLOY_BRANCH=main HEALTHCHECK_URL=https://domain-anda.example.com bash scripts/deploy.sh
```

Railway melakukan build dan mengganti container dari commit yang didorong, sehingga skrip ini **bukan** perintah deploy Railway. Di Railway gunakan pre-deploy migration, auto-deploy service, dan log release; jangan menjalankan `artisan down` dari skrip VPS pada service live.

## Pemeriksaan pasca-deployment

Ganti domain di bawah dengan domain publik setelah deployment dan simpan status HTTP aktual beserta tanggalnya di checkpoint minggu 12:

```bash
curl -I https://domain-anda.example.com/
curl -I https://domain-anda.example.com/up
curl -I https://domain-anda.example.com/.env
curl -I https://domain-anda.example.com/storage/logs/laravel.log
curl -I https://domain-anda.example.com/composer.json
curl -I http://domain-anda.example.com/
```

Hasil yang diharapkan: halaman utama dan `/up` merespons sukses; `/.env`, `storage/logs/laravel.log`, dan `/composer.json` **tidak** mengembalikan 200; HTTP dialihkan ke HTTPS. Domain Railway melayani HTTPS otomatis. Periksa header `X-Content-Type-Options: nosniff`, `X-Frame-Options`, `Referrer-Policy`, serta `Permissions-Policy`; image menambahkan header tersebut melalui Apache.

Periksa juga:

- `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, dan nilai `APP_URL` menunjuk domain HTTPS.
- Login, role admin/dosen/mahasiswa, notifikasi/queue, unggah, dan unduh file privat berfungsi.
- Worker aktif melalui deployment log Railway; tidak ada job gagal yang belum ditangani.
- Skrip otorisasi dijalankan terhadap lingkungan demo yang aman dan tidak berisi data pengguna sungguhan. Skrip membuat dan menghapus course/tugas sementara.

Skrip uji menerima password demo melalui environment variable dan tetap menggunakan `password` sebagai default hanya untuk development:

```bash
BASE_URL=https://domain-anda.example.com \
AUTHZ_ADMIN_PASSWORD='...' \
AUTHZ_LECTURER_PASSWORD='...' \
AUTHZ_STUDENT_PASSWORD='...' \
bash scripts/test-authz.sh
```

Hindari menaruh password produksi dalam shell history. Jalankan pengujian setelah cadangan database tersedia dan catat keluaran aslinya; jika pengujian membuat data sementara yang tidak terhapus, hapus secara manual melalui akun admin setelah memastikan identitas datanya.

## Troubleshooting

| Gejala | Pemeriksaan dan tindakan |
|---|---|
| Container gagal build pada Composer | Periksa log build, versi PHP pada Dockerfile, lockfile, dan ekstensi PHP. Jangan memakai `--ignore-platform-reqs`; itu menyembunyikan ketidakcocokan runtime. |
| Railway menampilkan 502 | Pastikan deployment aktif, `PORT` diteruskan ke Apache, konfigurasi health check `/up`, dan APP_KEY tersedia. Baca deploy log tanpa menyalakan `APP_DEBUG`. |
| Error database saat pre-deploy | Periksa koneksi internal MySQL dan referensi Variables; jangan mengabaikan kegagalan `migrate --force`. |
| Request selain `/` menjadi 404 | Pastikan `mod_rewrite` aktif, `AllowOverride All` berlaku, dan DocumentRoot Apache adalah `/var/www/html/public`. |
| Upload hilang setelah deploy | Pastikan volume persisten terpasang tepat di `/var/www/html/storage/app/private`. File container tanpa volume bersifat sementara. |
| Notifikasi tertahan | Pastikan service worker aktif, konfigurasi `QUEUE_CONNECTION=database`, lalu periksa tabel jobs, failed jobs, dan deploy log worker. |
| Error 500 | Pertahankan `APP_DEBUG=false`; baca log Railway dan log Laravel (`LOG_CHANNEL=stderr`). Periksa migrasi, izin storage, environment, dan konfigurasi cache. |

## Bukti deployment yang perlu dilengkapi kelompok

- Domain publik HTTPS: **belum tersedia pada workspace ini**.
- Tanggal/waktu deployment dan spesifikasi/region Railway: **isi setelah deploy**.
- Status tiga pemeriksaan file rahasia dan redirect HTTP: **isi dengan keluaran curl aktual**.
- Hasil test-authz terhadap server produksi: **belum dijalankan**.
- Worker Railway dan bukti status: **isi dari deployment log/screenshot aktual**.
- Perangkat lain yang dipakai untuk pengujian serta masalah nyata dan penyelesaiannya: **isi setelah pengujian kelompok**.
