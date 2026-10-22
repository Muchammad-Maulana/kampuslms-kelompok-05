## Nama : Muchammad Maulana
## NIM  : 10241043

---

# READ — Peta Route, Model Binding, dan Akses

Pemeriksaan dilakukan pada `routes/web.php`, middleware `EnsureUserHasRole`, controller Course/Materi/Tugas, model Eloquent, dan test fitur Week 5.

### 1. Route mana yang membatasi akses berdasarkan role?
**Jawaban:**
Route aplikasi yang membutuhkan akun memakai middleware `auth`, lalu middleware `role:<nama-role>` membatasi role yang diperbolehkan. Contohnya, CRUD pengguna dan mata kuliah berada di grup `admin`, route baca mata kuliah/materi/tugas berada di grup `dosen` dan `mahasiswa`. Alias `role` didaftarkan pada `bootstrap/app.php`.

### 2. Di mana middleware role didaftarkan pada Laravel 12?
**Jawaban:**
Alias middleware didaftarkan di `bootstrap/app.php` melalui konfigurasi `withMiddleware()`. Implementasinya ada di `app/Http/Middleware/EnsureUserHasRole.php`. Middleware memberi status `401` jika tidak ada user terautentikasi dan `403` jika user sudah login tetapi role-nya tidak diizinkan.

### 3. Apa perbedaan autentikasi dan otorisasi pada alur ini?
**Jawaban:**
Autentikasi memastikan identitas pengguna sudah masuk melalui middleware `auth`. Otorisasi memeriksa apakah role atau kepemilikan pengguna membolehkan akses. Middleware role menjaga batas area umum, sedangkan controller memeriksa apakah course dimiliki dosen tersebut atau diikuti mahasiswa tersebut.

### 4. Apa yang dijamin route model binding, dan apa yang tidak dijamin?
**Jawaban:**
Parameter `{course}`, `{material}`, dan `{assignment}` dipetakan Laravel ke model terkait sehingga data yang tidak ditemukan menghasilkan 404. Binding hanya memastikan record ditemukan; binding tidak otomatis memastikan pengguna berhak melihatnya. Karena itu controller tetap memeriksa role dan kepemilikan/keanggotaan.

### 5. Bagaimana aplikasi membatasi daftar mata kuliah?
**Jawaban:**
`CourseController@index` membangun query sesuai role sebelum menjalankan pencarian dan pagination: admin mengambil seluruh course, dosen melalui relasi `taughtCourses`, dan mahasiswa melalui relasi `courses`. Penyaringan dilakukan di query database, bukan hanya menyembunyikan hasil di view.

### 6. Bagaimana IDOR pada detail course dicegah?
**Jawaban:**
`CourseController@show` mengizinkan admin, dosen yang `lecturer_id`-nya cocok dengan user aktif, atau mahasiswa yang terdaftar di course itu. Jika tidak memenuhi salah satu aturan, request dihentikan dengan 403.

### 7. Apa fungsi `scopeBindings()` dan `shallow()` di route materi/tugas?
**Jawaban:**
Route nested seperti `/dosen/mata-kuliah/{course}/assignments/{assignment}` mengikat assignment melalui relasi course induk. Dengan `scopeBindings()`, parameter anak harus benar-benar milik parent pada URL tersebut. `shallow()` menyediakan route detail yang lebih pendek, seperti `/dosen/assignments/{assignment}`. Pada route detail nested, controller juga membandingkan `course_id` agar pasangan course-assignment yang berbeda menghasilkan 404.

---

# BREAK — Skenario Uji Keamanan

Skenario berikut dicoba melalui test fitur agar hasil bisa diulang tanpa merusak data aplikasi utama.

| # | Skenario | Hasil yang diharapkan/diamati | Analisis |
|---|---|---|---|
| **1** | Akses `/admin/users` sebagai mahasiswa | Response 403 dan halaman hanya berisi pesan umum “Akses ditolak” | Middleware role mencegah mahasiswa masuk area admin. Halaman penolakan tidak membocorkan nama atau data pengguna yang dituju. |
| **2** | Dosen A membuka daftar course | Hanya course dengan `lecturer_id` milik Dosen A yang tampil | Filter berada di query `taughtCourses()`, sehingga data dosen lain tidak dikirim ke view. |
| **3** | Dosen A membuka detail course milik Dosen B | Response 403 | Route model binding menemukan course, tetapi pemeriksaan kepemilikan di controller menolak akses. |
| **4** | Mahasiswa yang belum terdaftar membuka detail course | Response 403; setelah didaftarkan melalui pivot `course_user`, akses detail berhasil | Keberadaan course saja tidak cukup. Akses diperiksa melalui relasi pendaftaran mahasiswa. |
| **5** | Dosen A membuka materi dari course Dosen B | Response 403 | Controller memeriksa hak baca terhadap course pemilik materi. |
| **6** | Mahasiswa membuka assignment berstatus `draft` | Response 404 dan instruksi tidak ikut terkirim | Assignment draft tidak ditampilkan kepada mahasiswa; 404 juga tidak mengungkap bahwa draft tersebut tersedia. |
| **7** | Minta assignment milik Course B melalui URL nested Course A | Response 404 | Parent dan child tidak cocok. Scoped route/controller menolak pasangan yang salah, mencegah akses lintas course. |
| **8** | Mahasiswa membuka detail course yang diikuti dan course itu memiliki assignment draft | Course tampil, tetapi judul dan instruksi draft tidak terlihat | Penyaringan status diterapkan pada daftar relasi assignment yang dimuat untuk halaman detail course. |

---

# FIX — Perbaikan yang Diterapkan

1. Menyusun route ke dalam grup `admin`, `dosen`, dan `mahasiswa` menggunakan prefix URL serta prefix nama route.
2. Mengganti middleware role dengan `EnsureUserHasRole`, mendaftarkan alias `role` di `bootstrap/app.php`, dan membedakan status 401 dengan 403.
3. Mempertahankan route resource CRUD admin untuk mata kuliah/pengguna dan menyesuaikan link serta redirect agar memakai nama route `admin.*`.
4. Menggunakan route model binding bertipe pada parameter model, termasuk `Course $course`, `Material $material`, dan `Assignment $assignment`; controller tidak mencari record dengan `findOrFail($id)` manual.
5. Menambahkan nested read routes untuk materi dan tugas. Nested route menggunakan `scopeBindings()`, sedangkan detail juga tersedia lewat URL shallow.
6. Menambahkan pemeriksaan bahwa dosen hanya mengakses course yang dia ampu dan mahasiswa hanya mengakses course yang diikuti.
7. Membatasi assignment mahasiswa ke status `published` pada daftar, detail assignment, dan halaman detail course.
8. Membuat halaman 403 generik yang tidak menyebut pemilik/data resource.
9. Menambahkan test fitur untuk akses lintas role, akses course dosen lain, keanggotaan mahasiswa, materi, assignment draft, dan ketidakcocokan parent-child pada nested route.

---

# BUILD — Hasil Implementasi Week 5

Build ini menyediakan fondasi routing dan otorisasi per role untuk KampusLMS:

- Admin mengelola CRUD mata kuliah dan pengguna di route `/admin/...`.
- Dosen dapat melihat course yang dia ampu beserta materi dan tugasnya.
- Mahasiswa hanya dapat melihat course yang diikuti, materi course tersebut, dan tugas yang sudah dipublikasikan.
- Route detail menggunakan implicit route model binding; route nested materi/tugas memakai scoped binding dan shallow URL.
- Pemeriksaan kepemilikan dilakukan di server. Menyembunyikan link di Blade tidak dianggap sebagai perlindungan akses.

Implementasi yang dijelaskan di catatan ini dibatasi pada Build Minggu 5: routing berdasarkan role, model binding, nested route, dan pengamanan akses per resource.

### Verifikasi

Perintah yang dijalankan:

```powershell
php artisan route:list --except-vendor
php artisan test
```

Hasil tes terbaru:

```text
Tests: 18 passed (55 assertions)
```

Tes Week 5 mencakup 10 skenario dan 23 assertions. Route registry menampilkan grup role, route parameter `{course}`, `{material}`, `{assignment}`, nested paths, serta shallow detail paths.

Kalimat inti untuk interview:

> Pada Week 5 saya menyusun route berdasarkan role dan memakai model binding agar data dipetakan langsung ke model. Saya paham bahwa binding hanya membuktikan record ada, bukan bahwa pengguna berhak mengaksesnya. Karena itu daftar difilter di query dan detail diperiksa berdasarkan kepemilikan dosen atau pendaftaran mahasiswa. Untuk materi dan tugas, saya memakai nested route dengan scoped binding agar resource anak tidak dapat diakses lewat course induk yang salah.
