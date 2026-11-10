# Laporan Performa KampusLMS — Minggu 11

## Tujuan dan metode

Optimisasi dilakukan setelah pengukuran, bukan berdasarkan tebakan. Seeder menyediakan minimal 30 mahasiswa, 5 course, dan setidaknya 100 submission agar pola query terlihat pada data yang lebih realistis.

Saat mengukur, gunakan:

```powershell
php artisan migrate:fresh --seed
php artisan serve
```

Pasang Debugbar hanya di lingkungan pengembangan bila diperlukan:

```powershell
composer require barryvdh/laravel-debugbar --dev
```

Catat jumlah query, waktu, dan memori dari halaman yang sama setelah cache aplikasi dibersihkan. Jangan mengukur halaman pertama setelah migrasi sebagai satu-satunya hasil karena proses bootstrap dapat memengaruhi angka.

## Tabel pengukuran

Angka di bawah adalah format pencatatan hasil Debugbar lokal. Isi kolom **Sebelum** saat eksperimen dengan eager loading/cache dimatikan, kemudian isi **Sesudah** dari implementasi branch ini. Angka dapat berbeda berdasarkan mesin, database, dan ukuran data.

| Halaman | Sebelum: query | Sebelum: waktu | Sebelum: memori | Sesudah: query | Sesudah: waktu | Sesudah: memori | Perubahan utama |
|---|---:|---:|---:|---:|---:|---:|---|
| Daftar mata kuliah | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | `with('lecturer')`, pagination |
| Detail mata kuliah | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | eager loading materials/assignments |
| Daftar tugas | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | `with('creator')`, pagination |
| Daftar pengumpulan dosen | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | `with(['user', 'grade'])` |
| **Rekap nilai** | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | `with(['submissions.user', 'submissions.grade'])` |
| Dashboard mahasiswa | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | cache statistik 10 menit |
| Dashboard dosen | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | isi hasil ukur | cache statistik 10 menit |

Target checkpoint adalah rekap nilai berada di bawah 15 query. Angka tersebut harus dibuktikan melalui Debugbar atau query listener, bukan ditulis sebagai klaim tanpa pengukuran.

## Perubahan yang diterapkan

### 1. Guard N+1

[AppServiceProvider.php](../app/Providers/AppServiceProvider.php#L22) mengaktifkan `Model::preventLazyLoading(! app()->isProduction())`. Di development dan testing, relasi yang belum di-load akan menghasilkan exception sehingga N+1 segera terlihat. Di production, aplikasi tidak dibuat rusak hanya karena guard diagnostik.

Contoh perbaikan pada rekap:

```php
$assignments = $course->assignments()
    ->with(['submissions.user', 'submissions.grade'])
    ->orderBy('due_at')
    ->get();
```

Lihat [GradeReportController.php](../app/Http/Controllers/GradeReportController.php#L14).

### 2. Index yang beralasan

[Migration index performa](../database/migrations/2026_11_04_000001_add_performance_indexes.php) menambahkan index hanya pada pola query yang berulang:

- `courses(status, created_at)` untuk course aktif dan urutan terbaru;
- `assignments(status, due_at)` untuk filter tugas terbit dan urutan deadline;
- `submissions(submitted_at)` untuk pengurutan laporan submission;
- `grades(graded_at)` untuk daftar nilai terbaru;
- `notifications(notifiable_type, notifiable_id, read_at)` untuk notifikasi milik user dan unread count.

Foreign key yang sudah memiliki index atau unique constraint tidak diduplikasi tanpa alasan.

Buktikan penggunaan index dengan:

```sql
EXPLAIN SELECT * FROM assignments
WHERE status = 'published'
ORDER BY due_at;
```

`type = ALL` berarti full table scan. Setelah migration, periksa `possible_keys`, `key`, dan `rows`; database tetap boleh memilih scan jika tabel sangat kecil.

### 3. Pagination dan eager loading

Daftar course, assignment, material, user, notifikasi, API course, API assignment, dan API material menggunakan pagination. Detail implementasi dapat dilihat pada controller-controller di [app/Http/Controllers/](../app/Http/Controllers/).

`withCount()` dipakai untuk jumlah relasi pada resource/API, bukan memuat seluruh collection hanya untuk menghitungnya.

### 4. Cache dashboard

Statistik angka pada dashboard di-cache selama 10 menit melalui [DashboardCache.php](../app/Support/DashboardCache.php). Nilai statistik boleh sedikit basi; nilai grade, submission, dan notifikasi tidak di-cache sebagai data tampilan utama.

Cache di-invalidasi ketika course, assignment, submission, grade, atau user berubah. Perubahan enrollment juga memanggil invalidasi secara eksplisit di [EnrollmentController.php](../app/Http/Controllers/EnrollmentController.php#L30). Mekanisme versi membuat cache lama tidak digunakan lagi tanpa harus menebak semua key user.

### 5. Konfigurasi produksi

Jalankan setelah deployment dan setelah memastikan perubahan konfigurasi sudah benar:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer install --optimize-autoloader --no-dev
npm.cmd run build
```

Semua pemanggilan `env()` aplikasi berada di bawah `config/`. Kode aplikasi menggunakan `config()` sehingga tetap bekerja setelah `config:cache`.

## Data yang sengaja tidak di-cache

Nilai mahasiswa, status submission, dan notifikasi unread tidak dijadikan cache dashboard yang berdiri sendiri karena perubahan harus terlihat segera dan data basi dapat menyesatkan pengguna. Statistik jumlah di dashboard boleh memiliki TTL 10 menit karena sifatnya ringkasan, dan invalidasi event memperpendek masa basi saat data berubah.

## Verifikasi

```powershell
php artisan test
npm.cmd run build
```

Test fitur minggu 11 harus memastikan guard N+1 tidak mematahkan halaman dan semua query relasi penting sudah eager-loaded.
