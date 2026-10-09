# Catatan Minggu 6
Nama : Linggar Pramudya/
Nim : 10241039/
Kelas : A

## 6.3 Read → Break → Fix → Build

## READ

1. Jalankan `php artisan install:api`. Baca perubahan yang terjadi di `bootstrap/app.php`.
2. Buat satu endpoint `GET /api/v1/courses` sederhana.
3. Bandingkan dengan `CourseController` versi web yang sudah ada. Tulis di catatan: apa yang **sama** dan apa yang **berbeda** di antara keduanya?
4. Panggil endpoint API tanpa header `Accept: application/json`. Lalu dengan header itu. Catat bedanya.
5. Jalankan `php artisan route:list --path=api`. Cocokkan dengan kontrak di spesifikasi.

## Jawaban

### 1. Hasil menjalankan `php artisan install:api`

Sanctum sudah terpasang melalui `laravel/sanctum` versi `v4.3.3`. File `routes/api.php` sudah tersedia dan `bootstrap/app.php` sudah mendaftarkannya pada konfigurasi routing API.

Perintah `php artisan install:api` sebelumnya dijalankan dari project dan berakhir dengan exit code 1. Perintah tersebut tidak dijalankan ulang karena Sanctum, route API, dan migration `personal_access_tokens` sudah tersedia.

Pemeriksaan `git diff` tidak menunjukkan perubahan pada `bootstrap/app.php`. Perubahan lain sudah ada di worktree sebelum dokumentasi ini dibuat. Tidak ada perubahan kode atau konfigurasi untuk dokumentasi ini.

Bukti: `composer.json`, `bootstrap/app.php`, `config/sanctum.php`, dan migration `database/migrations/2026_10_06_014722_create_personal_access_tokens_table.php`.

### 2. Hasil pemeriksaan endpoint `GET /api/v1/courses`

Endpoint `GET /api/v1/courses` sudah tersedia sehingga tidak dibuat endpoint baru.

- Nama route: `api.courses.index`
- Controller: `App\Http\Controllers\Api\CourseController@index`
- Middleware: `api`, `auth:sanctum`, `throttle:60,1`

`Api\CourseController@index` memilih query berdasarkan role, memakai `with('lecturer')`, memakai `withCount(['materials', 'assignments'])`, mendukung query `q` dan `status`, menggunakan pagination 15, lalu mengembalikan `CourseResource` collection.

Model `Course` memiliki relasi `lecturer()`, `students()`, `materials()`, dan `assignments()`.

### 3. Perbandingan `CourseController` API dan web

| Bagian | API | Web |
|---|---|---|
| File dan method | `app/Http/Controllers/Api/CourseController.php`, `index()` | `app/Http/Controllers/CourseController.php`, `index()` |
| Sumber data | Model `Course`, query berdasarkan role | Model `Course`, query berdasarkan role |
| Role | Admin semua course, dosen memakai `taughtCourses()`, mahasiswa memakai `courses()` | Admin semua course, dosen memakai `taughtCourses()`, mahasiswa memakai `courses()` |
| Relasi | Memakai `with('lecturer')` | Memakai `with('lecturer')` |
| Pencarian dan filter | Mendukung `q` berdasarkan nama/kode dan `status` | Mendukung `q` berdasarkan nama/kode/deskripsi dan `status` |
| Eager loading dan jumlah relasi | Memakai `withCount(['materials', 'assignments'])` | Daftar hanya memakai `with('lecturer')`; detail memuat `materials` dan `assignments` |
| Pagination | 15 data | 10 data dan `withQueryString()` |
| Perhitungan tambahan | Tidak menghitung total SKS | Menghitung total SKS dengan `sum('courses.sks')` |
| Respons | `CourseResource` collection dalam JSON | View `courses.index` |
| Middleware | Route memakai `auth:sanctum` dan `throttle:60,1` | Route memakai `auth` dan `role` sesuai grup web |

Resource API yang diperiksa adalah `app/Http/Resources/CourseResource.php` dan `app/Http/Resources/UserResource.php`.

### 4. Hasil pengujian endpoint dengan dan tanpa header `Accept: application/json`

Pengujian `curl` langsung ke server HTTP belum dilakukan karena server belum dijalankan. Karena endpoint courses memakai `auth:sanctum`, request harus diamati dengan kondisi autentikasi yang sesuai. Perbedaan format respons tanpa dan dengan header belum dinyatakan karena belum diuji.

Perintah tanpa header:

```bash
curl.exe -i http://127.0.0.1:8000/api/v1/courses
```

Perintah dengan header:

```bash
curl.exe -i http://127.0.0.1:8000/api/v1/courses -H "Accept: application/json"
```

### 5. Hasil pemeriksaan route API dan kontrak spesifikasi

Perintah `php artisan route:list --path=api -v` berhasil dijalankan dan menampilkan 15 route.

| Method | URI | Nama route | Controller | Middleware |
|---|---|---|---|---|
| POST | `/api/v1/auth/login` | `api.auth.login` | `Api\AuthController@login` | `api`, `throttle:login` |
| POST | `/api/v1/auth/logout` | `api.auth.logout` | `Api\AuthController@logout` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/courses` | `api.courses.index` | `Api\CourseController@index` | `api`, `auth:sanctum`, `throttle:60,1` |
| POST | `/api/v1/courses` | `api.courses.store` | `Api\CourseController@store` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/courses/{course}` | `api.courses.show` | `Api\CourseController@show` | `api`, `auth:sanctum`, `throttle:60,1` |
| PUT\|PATCH | `/api/v1/courses/{course}` | `api.courses.update` | `Api\CourseController@update` | `api`, `auth:sanctum`, `throttle:60,1` |
| DELETE | `/api/v1/courses/{course}` | `api.courses.destroy` | `Api\CourseController@destroy` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/courses/{course}/assignments` | `api.assignments.index` | `Api\AssignmentController@index` | `api`, `auth:sanctum`, `throttle:60,1` |
| POST | `/api/v1/courses/{course}/assignments` | `api.assignments.store` | `Api\AssignmentController@store` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/courses/{course}/assignments/{assignment}` | `api.assignments.show` | `Api\AssignmentController@show` | `api`, `auth:sanctum`, `throttle:60,1` |
| PUT\|PATCH | `/api/v1/courses/{course}/assignments/{assignment}` | `api.assignments.update` | `Api\AssignmentController@update` | `api`, `auth:sanctum`, `throttle:60,1` |
| DELETE | `/api/v1/courses/{course}/assignments/{assignment}` | `api.assignments.destroy` | `Api\AssignmentController@destroy` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/demo/raw-users` | `api.demo.raw-users` | `Api\AuthController@modelSerializationDemo` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/me` | `api.auth.me` | `Api\AuthController@me` | `api`, `auth:sanctum`, `throttle:60,1` |
| GET\|HEAD | `/api/v1/security/model-serialization-demo` | `api.security.model-serialization-demo` | `Api\AuthController@modelSerializationDemo` | `api`, `auth:sanctum`, `throttle:60,1` |

Spesifikasi API tersedia di `docs/api.md` dan menggunakan path utama `/api/v1`. Route `POST /api/v1/assignments` tidak ditemukan. Route yang tersedia adalah `POST /api/v1/courses/{course}/assignments`.

### BREAK

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Kembalikan `response()->json(User::all())` di satu endpoint uji | **Email dan seluruh kolom pengguna tampil di layar.** Lalu hapus sementara `$hidden` dari model `User` dan ulangi: hash password ikut tampil<br><br>**Hasil aktual:** Model `User` memiliki `$hidden` berisi `password` dan `remember_token`. `UserResource` hanya mengembalikan field yang dipilih. Test model serialization demo berhasil dan password serta `remember_token` tidak muncul pada `safe_resource`.<br><br>**Analisis:** Penghapusan sementara `$hidden` tidak dilakukan karena belum disetujui dan dapat membuka data sensitif. Endpoint demo yang tersedia tidak mengembalikan model mentah. |
| 2 | Hapus `auth:sanctum` dari grup route, panggil tanpa token | Data terbuka untuk publik<br><br>**Hasil aktual:** Semua route API v1 selain login berada dalam group `auth:sanctum` dan `throttle:60,1`. Test request daftar course tanpa autentikasi menghasilkan 401.<br><br>**Analisis:** Middleware tidak dihapus. Risiko data terbuka untuk publik tidak diuji dengan mengubah route. |
| 3 | Panggil endpoint terlindungi dengan token yang sudah dihapus | 401<br><br>**Hasil aktual:** Test logout berhasil menghapus token yang sedang digunakan dari tabel `personal_access_tokens`. Pengujian ulang memakai token yang sudah dicabut melalui HTTP curl belum dilakukan.<br><br>**Analisis:** Kode `AuthController@logout` menghapus `currentAccessToken()` dengan `delete()`, tetapi status HTTP saat token dicabut melalui HTTP belum diamati langsung. |
| 4 | Login sebagai mahasiswa, panggil `POST /api/v1/assignments` | Harus 403, bukan 401 — periksa punya Anda<br><br>**Hasil aktual:** Route `POST /api/v1/assignments` tidak ditemukan. Route yang tersedia adalah `POST /api/v1/courses/{course}/assignments`. Pada route yang tersedia, test mahasiswa terautentikasi yang mencoba membuat assignment menghasilkan 403. Request tanpa autentikasi pada endpoint terlindungi menghasilkan 401 pada test terkait.<br><br>**Analisis:** `authorizeCourseManage()` mengizinkan admin atau dosen pemilik course. Mahasiswa tidak diperbolehkan. |
| 5 | Hapus eager loading, panggil daftar mata kuliah, lihat Telescope/Debugbar | N+1 di API<br><br>**Hasil aktual:** API `CourseController@index` memakai `with('lecturer')` dan `withCount(['materials', 'assignments'])`. Web `CourseController@index` memakai `with('lecturer')`. Telescope dan Debugbar tidak tercantum di `composer.json`. Jumlah query sebelum dan sesudah eager loading tidak diukur.<br><br>**Analisis:** N+1 belum dapat dinyatakan terjadi karena belum ada pengukuran jumlah query. Eager loading tidak dihapus. |
| 6 | Hapus `throttle` dari login, jalankan 50 percobaan berturut-turut | Brute force tanpa hambatan<br><br>**Hasil aktual:** Route login API memakai `throttle:login`. `AppServiceProvider` menetapkan batas 5 request per menit berdasarkan email dan IP. Test rate limiting berhasil: lima percobaan gagal menghasilkan 422, lalu percobaan berikutnya menghasilkan 429.<br><br>**Analisis:** Perlindungan throttle tidak dihapus. Pengujian 50 percobaan tidak dilakukan. |
| 7 | Buat pesan login berbeda untuk email salah vs password salah | *User enumeration* — kenapa ini berbahaya<br><br>**Hasil aktual:** Test membandingkan email yang terdaftar dan email yang tidak terdaftar. Keduanya menghasilkan status 422 dan pesan `Email atau password salah.` yang sama.<br><br>**Analisis:** Pesan yang sama membantu mencegah pihak luar membedakan email terdaftar dari email yang tidak terdaftar. Pesan autentikasi tidak diubah. |

Pengujian feature yang dijalankan:

```text
php artisan test tests/Feature/WeekSixApiFeatureTest.php

20 test passed
72 assertions
```

PENGUJIAN YANG MASIH BELUM DILAKUKAN

- Pengujian `curl` langsung tanpa header `Accept: application/json`.
- Pengujian `curl` langsung dengan header `Accept: application/json`.
- Pengujian token Sanctum yang sudah dicabut melalui server HTTP.
- Penghitungan query sebelum dan sesudah eager loading untuk membuktikan N+1.
- Penghapusan sementara `$hidden` pada model `User`.
- Penghapusan middleware `auth:sanctum`.
- Pengujian 50 percobaan login.

FILE BUKTI

- `composer.json`
- `bootstrap/app.php`
- `routes/web.php`
- `routes/api.php`
- `config/sanctum.php`
- `config/auth.php`
- `app/Models/User.php`
- `app/Models/Course.php`
- `app/Http/Controllers/Api/CourseController.php`
- `app/Http/Controllers/CourseController.php`
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Auth/LoginController.php`
- `app/Http/Controllers/Api/AssignmentController.php`
- `app/Http/Middleware/EnsureUserHasRole.php`
- `app/Providers/AppServiceProvider.php`
- `app/Http/Resources/UserResource.php`
- `app/Http/Resources/CourseResource.php`
- `docs/api.md`
- `tests/Feature/WeekSixApiFeatureTest.php`