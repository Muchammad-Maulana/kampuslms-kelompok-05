# Dokumentasi Debugging KampusLMS

Dokumen ini mencatat bug nyata yang pernah ditemukan semester ini beserta metode
diagnosis, perbaikan, dan perlindungan regresinya.

## 1. Notifikasi tidak muncul meskipun tugas atau nilai berhasil disimpan

**Gejala**

Permintaan pembuatan tugas atau penyimpanan nilai berhasil dan data tersimpan,
tetapi notifikasi database tidak segera muncul.

**Hipotesis awal**

1. Event tidak dipicu.
2. Listener tidak menyesuaikan penerima.
3. Queue worker tidak berjalan.
4. Worker sedang memakai kode lama setelah deployment.

**Penyempitan**

1. Periksa test listener untuk memastikan event menghasilkan queued listener.
2. Periksa tabel `jobs` untuk melihat apakah pekerjaan tertunda.
3. Jalankan worker sekali dan periksa tabel `notifications`.
4. Periksa log untuk job yang gagal.

**Akar masalah**

Queue menggunakan koneksi `database`, tetapi worker terpisah tidak dijalankan atau
belum dikonfigurasi pada lingkungan deployment.

**Perbaikan**

- Listener menerapkan `ShouldQueue` dan memiliki retry/backoff.
- Job listener dan notification dapat diproses dengan
  `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`.
- Worker gagal dapat diperiksa dengan `php artisan queue:failed`.

**Test pencegah**

`tests/Feature/WeekTenNotificationFeatureTest.php` memeriksa:

- tugas published memicu listener, sedangkan draft tidak;
- penerima hanya mahasiswa terdaftar atau pemilik submission;
- payload tidak menyimpan nilai atau feedback;
- worker memindahkan job ke tabel notification.

## 2. Deployment mendapat 500 karena sesi tidak tersedia

**Gejala**

Tampilan aplikasi pada deployment awal gagal dengan HTTP 500. Request tidak dapat
menyelesaikan sesi meskipun kode aplikasi dapat dibangun.

**Hipotesis awal**

1. Variabel environment database salah.
2. Dependency Composer tidak terpasang.
3. Migrasi belum dijalankan.
4. Dokumen root Apache menunjuk direktori yang salah.

**Penyempitan**

1. Periksa log Laravel dan container.
2. Konfirmasi `/up` dan respons error.
3. Periksa tabel database yang dirujuk konfigurasi session.
4. Jalankan migrasi terbatas pada service database yang aktif.

**Akar masalah**

Konfigurasi session database mengharapkan tabel `railway.sessions`, tetapi tabel
tersebut belum tersedia sebelum aplikasi menerima request.

**Perbaikan**

- Jalankan `php artisan migrate --force` sebagai pre-deploy command.
- Jangan jalankan `migrate:fresh` pada database berisi data.
- Jangan menyetel seeder produksi sebagai pekerjaan restart.

**Test pencegah**

`tests/Feature/ExampleTest.php` memeriksa bahwa URL yang dibuat memakai HTTPS saat
forwarded headers Railway dikenali. CI juga menjalankan `migrate:fresh` dan
`migrate:refresh` sebelum suite test.

## 3. Submission atau ID notifikasi milik mahasiswa lain dapat diakses

**Gejala**

Pengguna dapat mencoba mengganti ID di URL atau route dan berpotensi membuka
submission, nilai, atau notifikasi milik pihak lain.

**Hipotesis awal**

1. Blade menyembunyikan tautan, tetapi server tidak membatasi akses.
2. Route memakai model binding tanpa pemeriksaan Policy.
3. Query menampilkan seluruh tabel submission atau notification.
4. Prefix role mengganti pemeriksaan Policy yang sebenarnya.

**Penyempitan**

1. Buat dua mahasiswa dan satu submission milik masing-masing.
2. Login sebagai mahasiswa lain.
3. Kirim request langsung ke route download atau baca notifikasi.
4. Bandingkan hasil HTTP dan perubahan database.

**Akar masalah**

Berpindah dari pemeriksaan tampilan UI ke request langsung dapat melewati
antisipasi yang hanya ada di Blade.

**Perbaikan**

- `SubmissionPolicy` membatasi pemilik, dosen pengampu, dan admin.
- Endpoint download maupun notifikasi mengambil resource melalui cakupan
  pengguna yang login.
- Query grade dan notification dibatasi oleh pemilik submission atau user.

**Test pencegah**

- `tests/Feature/WeekNineFileHandlingTest.php` menolak mahasiswa lain mengunduh
  submission dan menolak mahasiswa tidak terdaftar mengumpulkan tugas.
- `tests/Feature/WeekTenNotificationFeatureTest.php` memastikan ID notifikasi
  milik pengguna lain mendapat 404 dan tidak berubah status bacanya.
- `tests/Feature/ModelPoliciesTest.php` menguji policy model secara langsung.
