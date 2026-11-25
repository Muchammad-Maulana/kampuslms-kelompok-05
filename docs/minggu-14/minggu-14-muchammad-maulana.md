# Minggu 14 — Debugging dan Testing

## Identitas

- Nama: Muchammad Maulana
- NIM: 10241043
- Kelompok: A05
- Proyek: KampusLMS — Laravel 12

## Status hasil

Baseline awal berhasil dijalankan pada 25 November 2026:

```text
php artisan test
90 tests passed (458 assertions)
```

Pengujian khusus ditambahkan untuk mahasiswa yang tidak terdaftar mencoba mengumpulkan tugas pada `tests/Feature/WeekNineFileHandlingTest.php`. Setelah test ditambahkan, hasil suite final adalah:

```text
php artisan test
91 tests passed (460 assertions)
```

## READ — Metode debugging

Alur diagnosis yang digunakan:

1. Reproduksi gejala dan catat langkah serta data yang dipakai.
2. Pisahkan lapisan autentikasi, route, controller, Policy/query, dan database.
3. Uji satu hipotesis dengan pemeriksaan murah.
4. Perbaiki sumber masalah.
5. Tambahkan test bermakna agar regresi terdeteksi.

Diagnostik yang dipakai:

- `php artisan test` untuk reproduksi dan regresi.
- Stack trace Laravel dengan fokus pada berkas pertama di `app/`.
- `Log::` untuk data diagnostik yang tidak boleh dibawa ke layar pengguna.
- `Storage::fake()` dan `Notification::fake()` agar pengujian tidak menulis berkas atau mengirim notifikasi nyata.
- `DB::listen()` untuk membaca query ketika alur rekap diuji.

## Test suite

Suite mencakup feature test utama pada jalur autentikasi, CRUD, enrollment, unggah materi, pengumpulan tugas, unduhan, penilaian, notifikasi, API, dan pembatasan role.

Contoh test jalur ditolak yang bermakna:

- dosen lain tidak dapat mengubah course atau assignment milik dosen lain;
- mahasiswa tidak terdaftar tidak dapat mengumpulkan tugas;
- mahasiswa tidak dapat mengunduh submission milik mahasiswa lain;
- dosen lain tidak dapat mengelola rekap atau nilai course yang bukan miliknya;
- mahasiswa tidak dapat membaca nilai atau notifikasi milik pengguna lain;
- mahasiswa tidak dapat melewati deadline jika `allow_late=false`.

Test bermakna memeriksa status dan efek tambahan, misalnya:

- `assertForbidden()` untuk otorisasi;
- `assertDatabaseMissing()` atau `assertDatabaseHas()` untuk mutasi;
- `assertExists()` atau `assertMissing()` pada storage fake;
- `Notification::assertSentTo()` atau `assertNotSentTo()` untuk penerima.

Test tidak hanya memeriksa `200`: setiap test bertujuan membuktikan data yang benar terbaca atau mutasi yang benar diterima/ditolak.

## BREAK — Pemeriksaan kualitas test

Pemeriksaan yang dilakukan terhadap suite:

| Risiko test palsu | Pembuktian/proteksi |
|---|---|
| Hanya memeriksa status | Test otorisasi memakai `assertForbidden()` lalu memeriksa database atau hasil aksi |
| Bergantung pada seeder | Test membuat user, course, assignment, dan submission sendiri dengan factory/model |
| Data antar-test tercampur | Setiap test memakai `RefreshDatabase` |
| Berkas sungguhan tertulis | Test unggah dan penghapusan memakai `Storage::fake('local')` |
| Notifikasi mengirim data nyata | Test notifikasi memakai `Notification::fake()` atau queue fake |
| Authorization tidak membuat test merah | Test mengandung role dan resource milik dua pihak, lalu membandingkan hasil |
| Migrasi tidak dapat dibatalkan | CI menjalankan `migrate:fresh`, `migrate:refresh`, dan test |

Demonstrasi degradasi fitur yang dapat dilakukan pada checkpoint:

1. Pilih test unduhan submission milik pihak lain.
2. Hapus pemeriksaan Policy pada endpoint download.
3. Jalankan test tersebut.
4. Pastikan test menjadi merah karena respons berubah dari `403` menjadi `200/redirect`.
5. Pulihkan Policy dan pastikan test hijau kembali.

## FIX — Perbaikan dan bukti

- Menambah test eksplisit untuk mahasiswa yang tidak terdaftar mengumpulkan tugas.
- Mempertahankan `RefreshDatabase` pada test feature.
- Memakai fake untuk storage dan notification.
- Membatasi test otorisasi pada 403 dan efek database.
- Menambah CI untuk menjalankan suite penuh dengan PHP 8.3 dan SQLite.
- Menambahkan [kolom test pada dokumentasi keamanan](../keamanan.md).

Konfigurasi [GitHub Actions](../../.github/workflows/ci.yml) menjalankan:

- install PHP dan dependency;
- build aset frontend;
- generate application key;
- `migrate:fresh --seed`;
- `migrate:refresh --seed`;
- `php artisan test`.

## BUILD — Dokumentasi debugging

Bug nyata dan diagnosis terdokumentasi pada [docs/debugging.md](../debugging.md).

Checkpoint Minggu 14:

- [x] Suite baseline dijalankan dan hasil dicatat.
- [x] Test jalur sukses, ditolak, dan validasi tersedia.
- [x] `RefreshDatabase` dipakai pada test feature.
- [x] Storage dan notification fake dipakai pada test relevan.
- [x] CI menjalankan `php artisan test`.
- [ ] Demonstrasi deletion fitur dan perubahan test merah masih perlu diperagakan saat checkpoint.

