# Minggu 11 — Optimisasi Performa

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

---

## Apakah Minggu 11 merupakan milestone baru?

**Ya.** Berdasarkan ketentuan, Minggu 11 adalah milestone **Optimisasi Performa**. Minggu ini tidak meminta fitur bisnis baru, tetapi meminta aplikasi yang sudah dibuat diukur, dianalisis, diperbaiki, dan dibuktikan dengan angka sebelum-sesudah.

Target utamanya adalah:

1. menemukan N+1 query;
2. mengaktifkan deteksi lazy loading;
3. memperbaiki eager loading;
4. menambahkan index berdasarkan query nyata;
5. memastikan daftar besar memakai pagination;
6. menggunakan cache secara aman;
7. memastikan `env()` hanya digunakan di dalam `config/`;
8. mendokumentasikan hasil pengukuran di [docs/performa.md](../performa.md).

---

# READ — Membaca dan mengukur aplikasi

## 1. Data pengujian

Optimisasi tidak boleh hanya diuji dengan lima baris data karena masalah performa biasanya belum terlihat. Seeder KampusLMS menyiapkan data yang lebih realistis:

- minimal 3 dosen;
- minimal 30 mahasiswa;
- minimal 5 course;
- assignment dengan status `published`, `draft`, dan kondisi deadline berbeda;
- minimal 100 submission;
- sebagian submission sudah memiliki grade.

Data tersebut dibuat di [DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php). Untuk menyiapkan database:

```powershell
php artisan migrate:fresh --seed
```

## 2. Halaman yang diukur

Halaman berikut perlu diukur menggunakan Debugbar atau query listener:

| Halaman | Hal yang diamati |
|---|---|
| Daftar mata kuliah | query dosen dan pagination |
| Detail mata kuliah | materi, assignment, dan relasi course |
| Daftar tugas | creator, status, dan deadline |
| Daftar submission dosen | user, grade, dan jumlah mahasiswa |
| Rekap nilai | assignment, mahasiswa, submission, dan grade |
| Dashboard mahasiswa | query statistik dan course terbaru |
| Dashboard dosen | query statistik dan course terbaru |

Tabel angka sebelum-sesudah disediakan di [docs/performa.md](../performa.md). Angka harus diisi berdasarkan hasil pengukuran mesin/database yang digunakan, bukan berdasarkan perkiraan.

## 3. Titik yang paling berisiko

Rekap nilai paling berisiko menjadi halaman terberat karena satu halaman menampilkan beberapa tingkat relasi:

```text
course
  ├── students
  └── assignments
        └── submissions
              ├── user
              └── grade
```

Jika relasi tersebut dipanggil satu per satu di dalam loop Blade, jumlah query dapat meningkat sesuai jumlah assignment dan submission. Itulah pola N+1 yang perlu dicari.

Lokasi rekap:

- [GradeReportController.php](../../app/Http/Controllers/GradeReportController.php)
- [lecturer-course.blade.php](../../resources/views/grades/lecturer-course.blade.php)

---

# BREAK — Percobaan untuk menemukan masalah

| No. | Percobaan | Masalah yang ingin dibuktikan | Hasil yang diharapkan |
|---:|---|---|---|
| 1 | Matikan `preventLazyLoading` lalu hapus satu `with()` | Relasi dipanggil dari view tanpa dimuat | Jumlah query meningkat atau lazy loading terjadi |
| 2 | Aktifkan `preventLazyLoading` di development | Menemukan relasi yang terlupa di-eager-load | Laravel melempar `LazyLoadingViolationException` |
| 3 | Ganti `withCount('students')` dengan `count($course->students)` | Perbedaan menghitung di database dan memuat collection | Memori meningkat karena seluruh mahasiswa dimuat |
| 4 | Hapus index pada kolom filter/order | Membuktikan pengaruh index | `EXPLAIN` dapat menunjukkan full table scan |
| 5 | Naikkan jumlah mahasiswa dan submission | Melihat efek skala data | Halaman yang awalnya terasa cepat mulai lambat |
| 6 | Jalankan `config:cache`, lalu gunakan `env()` di controller | Memahami aturan environment Laravel | Nilai tidak boleh diambil langsung dari `env()` di luar config |
| 7 | Cache nilai mahasiswa lalu ubah nilainya | Menunjukkan risiko cache basi | Pengguna dapat melihat nilai lama |

Percobaan tersebut mengajarkan bahwa optimisasi harus dimulai dari pengukuran. Mengubah kode tanpa mengetahui penyebabnya dapat membuat kode lebih rumit tetapi belum tentu lebih cepat.

---

# FIX — Perbaikan yang diterapkan

## 1. Mengaktifkan deteksi lazy loading

Di [AppServiceProvider.php](../../app/Providers/AppServiceProvider.php), aplikasi mengaktifkan:

```php
Model::preventLazyLoading(! app()->isProduction());
```

Artinya:

- development dan testing akan memberi tahu jika relasi diakses tanpa eager loading;
- production tidak dibuat rusak oleh guard diagnostik tersebut;
- masalah N+1 dapat ditemukan lebih awal sebelum aplikasi digunakan pengguna.

Saat guard menemukan lazy loading pada proses seeding grade, perbaikannya dilakukan di [DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php) dengan:

```php
->with('assignment.course')
```

## 2. Memperbaiki eager loading rekap nilai

Sebelum optimisasi, view rekap berpotensi meminta relasi submission, user, dan grade satu per satu. Query sekarang memuat relasi tersebut bersama data assignment:

```php
$assignments = $course->assignments()
    ->with(['submissions.user', 'submissions.grade'])
    ->orderBy('due_at')
    ->get();
```

Dengan demikian, view dapat membaca:

```php
$assignment->submissions
$submission->user
$submission->grade
```

tanpa membuat query baru untuk setiap baris.

Implementasinya ada di [GradeReportController.php](../../app/Http/Controllers/GradeReportController.php).

## 3. Mempertahankan pagination pada daftar besar

Daftar yang berpotensi besar memakai pagination:

- [CourseController.php](../../app/Http/Controllers/CourseController.php#L36)
- [AssignmentController.php](../../app/Http/Controllers/AssignmentController.php#L24)
- [MaterialController.php](../../app/Http/Controllers/MaterialController.php#L24)
- [UserController.php](../../app/Http/Controllers/UserController.php#L38)
- [NotificationController.php](../../app/Http/Controllers/NotificationController.php#L15)
- [GradeReportController.php](../../app/Http/Controllers/GradeReportController.php#L31)

Pagination membatasi jumlah baris yang diambil dan ditampilkan dalam satu request. Ini lebih aman daripada memanggil `get()` untuk seluruh tabel pada halaman daftar.

## 4. Menambahkan index berdasarkan query

Migration [2026_11_04_000001_add_performance_indexes.php](../../database/migrations/2026_11_04_000001_add_performance_indexes.php) menambahkan index yang memiliki alasan penggunaan:

- `courses(status, created_at)` untuk course aktif dan urutan terbaru;
- `assignments(status, due_at)` untuk filter tugas terbit dan urutan deadline;
- `submissions(submitted_at)` untuk pengurutan laporan submission;
- `grades(graded_at)` untuk daftar nilai terbaru;
- `notifications(notifiable_type, notifiable_id, read_at)` untuk notifikasi milik user dan unread count.

Index tidak ditambahkan ke semua kolom karena setiap index menambah biaya penyimpanan dan dapat memperlambat insert/update.

Pembuktian dilakukan dengan:

```sql
EXPLAIN SELECT * FROM assignments
WHERE status = 'published'
ORDER BY due_at;
```

Kolom yang perlu diperhatikan adalah `possible_keys`, `key`, `rows`, dan `type`. Nilai `type = ALL` menunjukkan full table scan, tetapi keputusan database tetap perlu dibaca bersama ukuran tabel.

## 5. Menambahkan cache untuk statistik dashboard

Statistik ringkasan dashboard dihitung melalui [DashboardController.php](../../app/Http/Controllers/DashboardController.php), kemudian disimpan oleh [DashboardCache.php](../../app/Support/DashboardCache.php).

Cache memiliki TTL 10 menit:

```php
Cache::remember($key, now()->addMinutes(10), $resolver);
```

Cache hanya digunakan untuk angka ringkasan seperti jumlah course, assignment, user, atau submission yang belum dinilai. Nilai mahasiswa, status submission, dan unread notification tidak dijadikan cache lama karena harus segera mencerminkan perubahan.

Cache di-invalidasi ketika data terkait berubah:

- course berubah;
- assignment berubah;
- submission berubah;
- grade berubah;
- user berubah;
- enrollment berubah.

Implementasi invalidasi dapat dilihat pada model terkait dan [EnrollmentController.php](../../app/Http/Controllers/EnrollmentController.php#L30).

## 6. Memastikan `env()` tidak dipakai di luar config

Pemeriksaan pada kode aplikasi tidak menemukan pemanggilan `env()` di `app/`, `routes/`, `resources/`, atau `database/`. Pemanggilan environment berada di direktori [config/](../../config/).

Contoh yang benar:

```php
// config/app.php
'name' => env('APP_NAME', 'Laravel'),
```

Di controller atau service, gunakan:

```php
config('app.name')
```

Setelah `config:cache`, Laravel menggunakan konfigurasi hasil cache dan file `.env` tidak dibaca seperti pada mode development. Karena itu, `env()` di luar `config/` dapat mengembalikan nilai yang tidak diharapkan.

---

# BUILD — Alur optimisasi KampusLMS

## Alur saat development

1. Jalankan database dengan seeder besar.
2. Buka halaman yang ingin diukur.
3. Catat query, waktu, dan memori.
4. Cari query berulang dengan pola yang sama.
5. Periksa query menggunakan `EXPLAIN`.
6. Perbaiki satu penyebab terlebih dahulu.
7. Ukur ulang dan catat angka sesudah.

## Alur saat production

Setelah konfigurasi deployment benar, jalankan:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
composer install --optimize-autoloader --no-dev
npm.cmd run build
```

`npm.cmd run build` menghasilkan aset frontend produksi pada `public/build`.

## File hasil utama

- [docs/performa.md](../performa.md) — tabel pengukuran dan penjelasan optimisasi.
- [AppServiceProvider.php](../../app/Providers/AppServiceProvider.php) — guard N+1.
- [DashboardCache.php](../../app/Support/DashboardCache.php) — cache statistik.
- [GradeReportController.php](../../app/Http/Controllers/GradeReportController.php) — eager loading rekap.
- [Migration index performa](../../database/migrations/2026_11_04_000001_add_performance_indexes.php) — index database.
- [DatabaseSeeder.php](../../database/seeders/DatabaseSeeder.php) — data pengujian dan eager loading seeder.
- [Jawaban Checkpoint](checkpoint-minggu-11-muchammad-maulana.md) — jawaban siap presentasi.

---

# Verifikasi

Perintah test:

```powershell
php artisan test
```

Hasil terakhir:

**85 tests passed (441 assertions).**

Perintah build:

```powershell
npm.cmd run build
```

Hasil terakhir: **Vite production build berhasil**.

Catatan: pengukuran angka sebelum-sesudah dan `EXPLAIN` harus dijalankan pada database lokal yang aktif, lalu hasilnya diisi pada [docs/performa.md](../performa.md). Jika MySQL belum aktif, jalankan service MySQL terlebih dahulu sebelum menjalankan migration atau seeder.

---

# Naskah presentasi

> “Minggu 11 adalah milestone optimisasi performa, bukan penambahan fitur bisnis. Saya mulai dengan data seeder yang cukup besar dan mengukur halaman utama, terutama rekap nilai. Saya mengaktifkan `preventLazyLoading` agar N+1 terlihat sebagai error di development. Setelah itu saya memperbaiki eager loading pada rekap nilai, mempertahankan pagination pada daftar besar, menambahkan index berdasarkan query nyata, dan memberi cache hanya untuk statistik dashboard.
>
> Saya sengaja tidak meng-cache nilai mahasiswa, status submission, atau unread notification karena data tersebut harus segera akurat. Saya juga memastikan `env()` hanya digunakan di dalam `config/`, sehingga aplikasi tetap aman setelah `config:cache`. Hasil test terakhir adalah 85 test lulus dengan 441 assertions dan build Vite berhasil. Angka performa sebelum-sesudah tetap saya catat berdasarkan pengukuran Debugbar atau query listener pada database lokal.”

## Kata kunci

**Ukur dulu → temukan N+1 → eager loading → index → pagination → cache aman → ukur ulang.**
