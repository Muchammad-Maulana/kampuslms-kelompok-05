# Minggu 11 — Optimisasi Performa

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

## Apakah ini milestone baru?

Ya. Ketentuan Minggu 11 menyebut target baru **Optimisasi Performa**. Minggu ini bukan penambahan fitur bisnis baru, melainkan mengukur dan memperbaiki aplikasi yang sudah dibuat pada minggu sebelumnya. Bukti akhirnya adalah angka sebelum-sesudah, bukan hanya perubahan kode.

## READ — Temuan awal

Titik yang perlu diawasi pada KampusLMS:

1. Rekap nilai menampilkan assignment, mahasiswa, submission, dan grade sekaligus sehingga paling berisiko mengalami N+1.
2. Statistik dashboard menjalankan beberapa query count yang cocok untuk cache singkat.
3. Filter status dan pengurutan deadline membutuhkan index yang sesuai.
4. Seeder harus cukup besar agar masalah query terlihat.

Seeder saat ini menyiapkan minimal 3 dosen, 30 mahasiswa, 5 course, assignment published/draft, dan setidaknya 100 submission. Lihat [DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php).

## FIX — Perubahan yang dibuat

### Guard lazy loading

[AppServiceProvider.php](../../app/Providers/AppServiceProvider.php) mengaktifkan:

```php
Model::preventLazyLoading(! app()->isProduction());
```

Dengan demikian, relasi yang dipanggil dari view tanpa eager loading akan terlihat sebagai error pada development/testing. Seeder juga diperbaiki agar grade dibuat dari submission yang sudah memuat `assignment.course`.

### Eager loading rekap nilai

[GradeReportController.php](../../app/Http/Controllers/GradeReportController.php) memuat:

```php
->with(['submissions.user', 'submissions.grade'])
```

Tanpa ini, loop rekap dapat memicu query per assignment atau per submission. Dengan eager loading, relasi disiapkan dalam query terkelompok.

### Index database

[2026_11_04_000001_add_performance_indexes.php](../../database/migrations/2026_11_04_000001_add_performance_indexes.php) menambahkan index berdasarkan query nyata, bukan memberi index ke semua kolom. Index yang ditambahkan dijelaskan di [performa.md](../performa.md).

### Cache dashboard

[DashboardCache.php](../../app/Support/DashboardCache.php) menyimpan ringkasan dashboard selama 10 menit. [DashboardController.php](../../app/Http/Controllers/DashboardController.php) memakai cache tersebut, sedangkan perubahan model dan enrollment melakukan invalidasi versi.

### Pagination

Daftar utama sudah menggunakan pagination, misalnya:

- [CourseController.php](../../app/Http/Controllers/CourseController.php#L36)
- [AssignmentController.php](../../app/Http/Controllers/AssignmentController.php#L24)
- [MaterialController.php](../../app/Http/Controllers/MaterialController.php#L24)
- [UserController.php](../../app/Http/Controllers/UserController.php#L38)
- [GradeReportController.php](../../app/Http/Controllers/GradeReportController.php#L31)

## BUILD — Bukti dan cara menjalankan

1. Jalankan `php artisan migrate:fresh --seed`.
2. Ukur halaman pada [tabel performa](../performa.md).
3. Gunakan `EXPLAIN` untuk query filter/ordering yang diuji.
4. Bandingkan angka sebelum dan sesudah.
5. Jalankan test dan build:

```powershell
php artisan test
npm.cmd run build
```

## Kesimpulan presentasi

> “Minggu 11 memang milestone baru tentang optimisasi performa. Saya tidak menebak-nebak perbaikannya: saya menyiapkan data seeder yang lebih besar, mengaktifkan guard lazy loading, memperbaiki eager loading pada rekap nilai, menambahkan index berdasarkan query, menggunakan pagination, dan memberi cache hanya untuk statistik dashboard. Nilai grade dan notifikasi tidak saya cache karena harus segera mencerminkan perubahan. Semua hasil sebelum-sesudah dicatat di `docs/performa.md`.”
