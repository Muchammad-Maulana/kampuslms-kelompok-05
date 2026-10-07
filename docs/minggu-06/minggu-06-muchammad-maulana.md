## Nama : Muchammad Maulana
## NIM  : 10241043
## Proyek : KampusLMS — Laravel 12

---

# READ — Memahami API dan pemisahan frontend

API memisahkan data dan aturan bisnis dari tampilan. Web Blade memakai session dan cookie; API proyek ini memakai bearer token Sanctum dan mengembalikan JSON. Client mengirim identitas pada setiap request protected.

Alur login hingga membaca course:

```text
POST /api/v1/auth/login
  -> validasi email/password dengan pesan error generik
  -> Sanctum menerbitkan token
  -> client menyimpan token
  -> GET /api/v1/courses dengan Authorization: Bearer <token>
  -> auth:sanctum memverifikasi token
  -> controller membatasi query menurut role
  -> API Resource memilih field yang boleh keluar
  -> response JSON berisi data dan meta pagination
```

### Perbandingan web dan API

| Web Blade | REST API |
|---|---|
| Route di `routes/web.php` | Route di `routes/api.php`, prefix `/api/v1` |
| Session/cookie | Token bearer Sanctum |
| Umumnya mengembalikan HTML/view | Mengembalikan JSON melalui API Resource |
| Form web memakai proteksi CSRF | API token tidak memakai session CSRF |
| Gagal login web kembali ke form | API gagal autentikasi memberikan status JSON, umumnya 401 untuk token protected yang hilang dan 422 untuk kredensial login yang salah |

Untuk request API, client mengirim `Accept: application/json`, termasuk saat menguji error dan validasi.

### Batas Resource

Resource bertindak sebagai whitelist. `CourseResource` memilih field course, lecturer yang sudah dimuat, serta jumlah material dan assignment. `UserResource` tidak mengirim password atau remember token. API tidak mengembalikan `User::all()` atau model mentah.

`whenLoaded('lecturer')` menyertakan relasi hanya setelah controller melakukan eager load. Untuk daftar course, controller memakai `with('lecturer')` dan `withCount(...)`; daftar assignment memakai `with('creator')`. Ini menghindari query tambahan berulang untuk setiap item.

### Keputusan jalur frontend

Jalur frontend kelompok untuk minggu 7–16 ditetapkan **Blade + Livewire**. Ini mempertahankan antarmuka dan pola kerja Blade yang sudah digunakan, sambil menyediakan API v1 untuk integrasi dan kemungkinan client lain.

---

# BREAK — Tujuh kerusakan dan dampaknya

Skenario BREAK adalah latihan lokal yang sengaja membuat satu kerusakan, mengamati gejalanya, lalu mengembalikan kode ke kondisi aman. Jangan melakukan demonstrasi kebocoran menggunakan database produksi atau akun pengguna nyata. Branch `kampuslms-broken` tidak tersedia di workspace ini; tabel berikut memetakan tujuh kerusakan spesifikasi ke contoh pada LMS aktif.

| # | Kerusakan yang dicoba | Dampak yang harus diamati | FIX pada LMS |
|---|---|---|---|
| 1 | Serialisasi model mentah, misalnya `User::all()`, lalu buat `password` terlihat pada model demo sementara | Response mentah membawa field yang tidak dirancang untuk client. Jika field tersembunyi dibuka, hash password dapat ikut terlihat. Jangan jalankan terhadap data nyata. | Gunakan `UserResource`, `CourseResource`, dan resource per model. Resource adalah whitelist; test login/resource memeriksa password tidak dikirim. |
| 2 | Hapus sementara `auth:sanctum` dari group route dan panggil `GET /api/v1/courses` tanpa token | Data menjadi dapat diminta tanpa identitas, jika endpoint tidak memiliki pemeriksaan lain. | Semua route data berada dalam group `auth:sanctum`; test tanpa token mengharapkan 401. Kembalikan middleware segera setelah demonstrasi. |
| 3 | Kirim bearer token yang sudah direvoke/dihapus ke endpoint protected | Token tidak valid harus ditolak dengan 401. | Sanctum memvalidasi personal access token pada setiap request; endpoint `/auth/logout` menghapus token aktif. |
| 4 | Login sebagai mahasiswa, lalu `POST /api/v1/courses/{course}/assignments` | Mahasiswa terautentikasi tetapi tidak punya hak membuat assignment; hasil yang benar 403, bukan 401. | `authorizeCourseManage()` hanya mengizinkan admin atau dosen pemilik course. Test `test_student_cannot_create_assignment()` membuktikannya. |
| 5 | Hapus eager loading pada daftar course/assignment dan amati query serta isi relasi | Relasi yang tidak di-eager-load tidak boleh dipaksa query satu per item. Resource `whenLoaded()` akan menghilangkan relasi yang tidak dimuat; jika resource mengakses relasi secara langsung, jumlah query dapat tumbuh mengikuti jumlah item (N+1). | Pertahankan `with('lecturer')`, `with('creator')`, dan `withCount(...)`; gunakan `whenLoaded()`/`whenCounted()` pada resource. |
| 6 | Hilangkan `throttle:5,1` dari login dan lakukan enam percobaan dalam satu menit | Request keenam tidak lagi mendapat 429; endpoint lebih mudah diserang dengan percobaan password berulang. | Login dibatasi lima request/menit. Test `test_login_is_limited_to_five_attempts_per_minute()` memastikan request keenam mendapat 429. |
| 7 | Buat pesan berbeda untuk email yang tidak terdaftar dan password yang salah | Penyerang dapat membedakan akun yang terdaftar; ini disebut user enumeration. | Login memakai pesan `Email atau kata sandi salah.` untuk kedua kondisi. Test membandingkan error keduanya. |

### Demo yang aman tanpa merusak source

Untuk melihat perbedaan Resource dengan model, gunakan test feature sebagai tempat demonstrasi: test login dan course memeriksa struktur JSON serta memastikan `password` tidak ada. Jangan menghapus `$hidden` permanen dan jangan mengembalikan hasil query pengguna nyata. Jika ingin memperagakan serialisasi sensitif, gunakan objek factory sementara dalam database test saja, lalu buang hasil test.

Contoh request tanpa token:

```bash
curl -i -H 'Accept: application/json' \
  http://127.0.0.1:8000/api/v1/courses
```

Hasil yang diharapkan: `401 Unauthorized`.

Contoh mencoba membuat assignment sebagai mahasiswa:

```bash
curl -i -X POST \
  http://127.0.0.1:8000/api/v1/courses/COURSE_ID/assignments \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer TOKEN_MAHASISWA' \
  -H 'Content-Type: application/json' \
  -d '{"title":"Tes","instructions":"Tes otorisasi","due_at":"2030-01-01T00:00:00Z"}'
```

Hasil yang diharapkan: `403 Forbidden`.

---

# FIX — Perbaikan dan bukti di repository

1. Mengaktifkan route API Laravel 12 di `bootstrap/app.php`, menambahkan `routes/api.php`, dependency Sanctum, konfigurasi token, dan migration `personal_access_tokens`.
2. Menambahkan `HasApiTokens` ke `User`, endpoint login, profil `/me`, dan logout token aktif.
3. Menyamakan pesan gagal login untuk email yang tidak ada dan password salah; login memakai `Hash::check()` tanpa bergantung pada session web.
4. Melindungi endpoint data menggunakan `auth:sanctum`; membatasi login dengan `throttle:5,1` serta request protected dengan `throttle:60,1`.
5. Menambahkan resource whitelist untuk User, Course, Material, Assignment, Submission, dan Grade. Path file internal submission/material tidak dimasukkan ke response resource.
6. Membatasi course menurut role: admin melihat semua, dosen hanya course ajarannya, mahasiswa hanya course yang diikuti. Mutasi course hanya untuk admin.
7. Membatasi assignment menurut hak atas course. Dosen hanya dapat mengelola assignment pada course yang dia ampu; mahasiswa hanya membaca assignment published pada course yang diikuti.
8. Menggunakan eager loading dan count eager loading pada koleksi course/assignment serta metadata pagination Laravel.
9. Menggunakan status `201` untuk create, `204` untuk delete tanpa body, `401` untuk token tidak valid/hilang, `403` untuk user valid yang tidak berhak, `404` untuk resource/parent tidak cocok atau draft tersembunyi, `422` untuk validasi, dan `429` ketika rate limit tercapai.
10. Menambahkan feature test API pada `tests/Feature/WeekSixApiFeatureTest.php` serta script otorisasi pada `scripts/test-api.sh`.

---

# BUILD — Hasil Week 6

API v1 menyediakan autentikasi token dan endpoint berikut:

- `POST /api/v1/auth/login`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`
- `GET|POST /api/v1/courses`
- `GET|PUT|PATCH|DELETE /api/v1/courses/{course}`
- `GET|POST /api/v1/courses/{course}/assignments`
- `GET|PUT|PATCH|DELETE /api/v1/courses/{course}/assignments/{assignment}`

Dokumentasi endpoint dan kontrak response v1 ada di `docs/api.md`. Keputusan jalur frontend: **Blade + Livewire**.

### Verifikasi

Perintah:

```powershell
php artisan test
php artisan route:list --path=api
```

Hasil verifikasi terakhir: **37 tests passed (121 assertions)** dan **13 route API terdaftar**. Script `scripts/test-api.sh` lulus pemeriksaan sintaks Bash; untuk menjalankannya dibutuhkan Bash, curl, jq, token demo, serta ID course/assignment yang sesuai.

Kalimat inti interview:

> Pada Week 6 saya memisahkan akses API dari route web dan memakai Sanctum bearer token. Controller membatasi data berdasarkan role dan kepemilikan, sedangkan API Resource menjadi whitelist agar model mentah tidak membocorkan kolom. Saya menguji 401, 403, 201, 204, 422, dan 429, memakai eager loading untuk relasi, membatasi percobaan login, lalu mendokumentasikan endpoint v1. Jalur frontend kelompok untuk minggu berikutnya adalah Blade + Livewire.
