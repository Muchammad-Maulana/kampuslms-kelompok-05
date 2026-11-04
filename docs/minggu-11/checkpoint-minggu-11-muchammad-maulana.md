# Jawaban Checkpoint Minggu 11 — Optimisasi Performa

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

> **Catatan:** Ketentuan checkpoint meminta angka hasil pengukuran. Isi kolom angka pada [docs/performa.md](../performa.md) dari Debugbar/query listener setelah menjalankan halaman pada mesin Anda. Jangan mengarang angka karena waktu, database, dan cache tiap lingkungan berbeda.

---

## 1. Halaman mana yang terburuk sebelum optimisasi? Kenapa?

### Jawaban

Halaman yang paling berisiko menjadi terburuk adalah **rekap nilai dosen**, karena satu halaman menggabungkan assignment, seluruh mahasiswa course, submission, user, dan grade. Jika relasi tersebut diakses satu per satu dari loop, jumlah query dapat meningkat berdasarkan jumlah assignment atau submission.

Pada implementasi sekarang, controller memuat relasi yang dibutuhkan:

```php
$assignments = $course->assignments()
    ->with(['submissions.user', 'submissions.grade'])
    ->orderBy('due_at')
    ->get();
```

Dengan begitu view tidak perlu menjalankan query baru ketika membaca `$assignment->submissions`, `$submission->user`, atau `$submission->grade`.

### Lokasi kode

- [GradeReportController.php — eager loading rekap](../../app/Http/Controllers/GradeReportController.php#L14)
- [lecturer-course.blade.php — loop rekap nilai](../../resources/views/grades/lecturer-course.blade.php#L14)
- [performa.md — tabel angka sebelum-sesudah](../performa.md)

### Kalimat presentasi

> “Rekap nilai paling berat karena menggabungkan beberapa tingkat relasi. Risiko N+1 muncul ketika relasi dibaca di dalam loop. Saya memperbaikinya dengan eager loading submissions, user, dan grade sejak query utama.”

---

## 2. Peragakan N+1 dan jelaskan perbaikannya.

### Jawaban

N+1 terjadi ketika aplikasi menjalankan satu query untuk mengambil daftar utama, lalu satu query tambahan untuk setiap baris ketika relasi dipanggil. Misalnya 15 course yang membaca `$course->lecturer->name` tanpa `with('lecturer')` dapat menghasilkan query dosen berulang.

Perbaikan daftar course menggunakan:

```php
$query->with('lecturer');
$courses = $query->latest('courses.created_at')->paginate(10);
```

Guard otomatis juga dipasang:

```php
Model::preventLazyLoading(! app()->isProduction());
```

Saat `with()` sengaja dihapus pada development/testing, Laravel akan memberi `LazyLoadingViolationException` ketika view mengakses relasi yang belum dimuat. Ini membuat N+1 dapat diperagakan dan dibuktikan, bukan hanya diasumsikan.

### Lokasi kode

- [CourseController.php — `with('lecturer')` dan pagination](../../app/Http/Controllers/CourseController.php#L19)
- [AppServiceProvider.php — guard lazy loading](../../app/Providers/AppServiceProvider.php#L22)
- [courses/index.blade.php — pemakaian data lecturer](../../resources/views/courses/index.blade.php#L1)

### Kalimat presentasi

> “Saya dapat menunjukkan N+1 dengan menghapus `with('lecturer')` sementara, lalu membuka halaman daftar course. Guard akan menandai lazy loading. Setelah `with()` dikembalikan, relasi dimuat terkelompok dan jumlah query tidak bertambah linear terhadap jumlah baris.”

---

## 3. Apa beda `withCount('students')` dan `count($course->students)`?

### Jawaban

`withCount('students')` meminta database menghitung jumlah mahasiswa melalui subquery dan menyimpan hasilnya pada atribut seperti `students_count`. Aplikasi tidak perlu memuat semua baris mahasiswa.

`count($course->students)` terlebih dahulu mengakses relasi `students`. Jika relasi belum dimuat, Eloquent mengambil semua data mahasiswa ke memori, lalu PHP menghitung jumlahnya. Ini lebih boros memori dan dapat menambah query.

Bila hanya memerlukan angka, gunakan `withCount()` atau query relasi langsung seperti `$course->students()->count()`. Bila memerlukan detail mahasiswa untuk ditampilkan, barulah gunakan eager loading.

### Lokasi kode

- [API CourseController.php — contoh `withCount`](../../app/Http/Controllers/Api/CourseController.php#L21)
- [Course.php — relasi students](../../app/Models/Course.php#L52)
- [performa.md — penjelasan pagination dan eager loading](../performa.md)

### Kalimat presentasi

> “`withCount` menghitung di database tanpa memuat seluruh mahasiswa. `count($course->students)` dapat memuat collection lengkap ke memori terlebih dahulu, sehingga lebih mahal untuk data besar.”

---

## 4. Tunjukkan index dan buktikan dengan `EXPLAIN`.

### Jawaban

Migration performa menambahkan index berdasarkan filter dan pengurutan yang sering digunakan:

- `courses(status, created_at)`;
- `assignments(status, due_at)`;
- `submissions(submitted_at)`;
- `grades(graded_at)`;
- `notifications(notifiable_type, notifiable_id, read_at)`.

Contoh pembuktian:

```sql
EXPLAIN SELECT * FROM assignments
WHERE status = 'published'
ORDER BY due_at;
```

Periksa kolom `possible_keys`, `key`, dan `rows`. `key` menunjukkan index yang dipilih. Jika `type` bernilai `ALL`, database masih melakukan full table scan; pada tabel kecil itu bisa dipilih secara wajar, jadi `EXPLAIN` harus dibaca bersama ukuran tabel dan query sebenarnya.

### Lokasi kode

- [Migration index performa](../../database/migrations/2026_11_04_000001_add_performance_indexes.php)
- [AssignmentController.php — query daftar assignment](../../app/Http/Controllers/AssignmentController.php#L16)
- [performa.md — alasan tiap index](../performa.md)

### Kalimat presentasi

> “Saya tidak menambahkan index ke semua kolom. Saya memilih kombinasi yang sesuai dengan filter dan order query, kemudian membuktikan pemakaiannya melalui `EXPLAIN`, terutama pada kolom `key`, `possible_keys`, dan `rows`.”

---

## 5. Sebutkan satu data yang tidak boleh di-cache.

### Jawaban

**Nilai mahasiswa** tidak boleh dijadikan cache tampilan yang lama karena dosen dapat mengubah nilai dan mahasiswa harus segera melihat hasil terbaru. Status submission dan unread notification juga sebaiknya tidak dibuat stale.

Yang saya cache hanya angka ringkasan dashboard selama 10 menit. Cache tersebut diberi invalidasi versi ketika course, assignment, submission, grade, user, atau enrollment berubah.

### Lokasi kode

- [DashboardCache.php — TTL dan invalidasi versi](../../app/Support/DashboardCache.php)
- [DashboardController.php — cache statistik dashboard](../../app/Http/Controllers/DashboardController.php#L20)
- [Grade.php — invalidasi ketika grade berubah](../../app/Models/Grade.php#L10)
- [EnrollmentController.php — invalidasi setelah enrollment berubah](../../app/Http/Controllers/EnrollmentController.php#L30)
- [performa.md — data yang sengaja tidak di-cache](../performa.md)

### Kalimat presentasi

> “Saya tidak meng-cache nilai mahasiswa karena data itu harus segera akurat. Cache hanya dipakai untuk statistik ringkasan dashboard, dengan TTL sepuluh menit dan invalidasi saat data sumber berubah.”

---

## 6. Apa yang terjadi pada `env()` setelah `config:cache`?

### Jawaban

Setelah `config:cache`, Laravel menggunakan konfigurasi hasil cache dan tidak membaca file `.env` pada setiap request. Karena itu, `env()` di luar file `config/*.php` dapat mengembalikan `null` atau tidak memberikan nilai yang diharapkan.

Aturannya:

- gunakan `env()` hanya di file `config/*.php`;
- di controller, model, view, dan service gunakan `config('nama.kunci')`;
- setelah perubahan konfigurasi jalankan ulang `php artisan config:cache`.

Repository ini tidak memiliki pemanggilan `env()` di `app/`, `routes/`, `resources/`, atau `database/`; pemakaian environment berada di konfigurasi Laravel.

### Lokasi kode/file

- [config/app.php — contoh `env()` yang benar di dalam konfigurasi](../../config/app.php#L15)
- [config/cache.php — konfigurasi cache dari environment](../../config/cache.php#L17)
- [performa.md — perintah optimisasi produksi](../performa.md)

### Kalimat presentasi

> “`env()` hanya untuk membaca environment saat membangun konfigurasi. Setelah `config:cache`, kode aplikasi harus mengambil nilai melalui `config()`, karena file `.env` tidak lagi dibaca seperti pada mode development.”

---

## Checklist sebelum presentasi

- [ ] Jalankan `php artisan migrate:fresh --seed`.
- [ ] Isi tabel sebelum-sesudah di [docs/performa.md](../performa.md).
- [ ] Tunjukkan N+1 dengan mematikan `with()` sementara pada branch lokal, bukan pada hasil final.
- [ ] Jalankan `EXPLAIN` untuk minimal satu query ber-index.
- [ ] Jelaskan kenapa nilai mahasiswa tidak di-cache.
- [ ] Jelaskan perbedaan `env()` dan `config()` setelah `config:cache`.
- [ ] Jalankan `php artisan test`.
- [ ] Jalankan `npm.cmd run build`.
