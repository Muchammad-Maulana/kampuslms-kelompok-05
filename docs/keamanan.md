# Tabel Titik Rawan IDOR

IDOR (Insecure Direct Object Reference) terjadi ketika pengguna dapat mengganti ID
resource pada URL/request dan membaca atau mengubah resource milik pihak lain.
Tabel ini mencatat endpoint yang menerima ID model dan kontrol server-side yang
melindunginya. Pembatasan tampilan di Blade bukan kontrol keamanan; keputusan
akses dilakukan oleh middleware, Policy, query, dan pemeriksaan pasangan parent-child.

| # | Endpoint / referensi | Titik rawan IDOR | Policy atau query yang menutupnya |
|---|---|---|---|
| 1 | `GET /admin/mata-kuliah`, `GET /dosen/mata-kuliah`, `GET /mahasiswa/mata-kuliah`, `GET /api/v1/courses` | Daftar course mengungkap course milik dosen lain atau course yang tidak diikuti mahasiswa. | `CoursePolicy::viewAny` menolak role selain admin/dosen/mahasiswa. Query memakai `Course::visibleTo($user)`: admin mendapat semua course, dosen dibatasi `lecturer_id`, mahasiswa dibatasi relasi `students`. |
| 2 | `GET /admin/mata-kuliah/{course}`, `GET /dosen/mata-kuliah/{course}`, `GET /mahasiswa/mata-kuliah/{course}`, `GET /api/v1/courses/{course}` | Mengganti `{course}` untuk membuka detail course lain. | `CoursePolicy::view` hanya mengizinkan admin, dosen pemilik course, atau mahasiswa yang terdaftar pada course. Controller web dan API memanggil `authorize('view', $course)`. |
| 3 | `POST /admin/mata-kuliah`, `GET /admin/mata-kuliah/create` | Membuat course melalui endpoint admin oleh role lain. | Grup route admin memakai middleware `auth` dan `role:admin`; controller juga memanggil `CoursePolicy::create`. |
| 4 | `PUT/PATCH/DELETE /admin/mata-kuliah/{course}`, `PUT/PATCH/DELETE /api/v1/courses/{course}` | Mengganti atau menghapus course dengan ID milik pihak lain. | `CoursePolicy::update` dan `CoursePolicy::delete` hanya mengizinkan admin; controller web/API memanggil Policy sebelum mutasi. |
| 5 | `GET /dashboard` dan daftar course terbaru | Dashboard memperlihatkan ringkasan course di luar cakupan pengguna. | Query dashboard memakai `Course::visibleTo($user)` sebelum menghitung total atau mengambil course terbaru. |
| 6 | `GET /admin/mata-kuliah/{course}/materials`, `GET /dosen/mata-kuliah/{course}/materials`, `GET /mahasiswa/mata-kuliah/{course}/materials` (tidak ada index material API) | Daftar material course lain dapat diminta dengan mengganti ID course. | `MaterialPolicy::viewAny` memeriksa hak melihat course; controller memakai relasi `$course->materials()` dan `Material::visibleTo($user)` sebelum mengambil daftar. |
| 7 | `GET /admin/materials/{material}`, `GET /dosen/materials/{material}`, `GET /mahasiswa/materials/{material}` dan route nested `/mata-kuliah/{course}/materials/{material}` | Mengganti ID material untuk membuka materi dari course yang tidak boleh diakses. | `MaterialPolicy::view` mewarisi pemeriksaan `CoursePolicy::view` pada course material. Route nested memakai `scopeBindings()` dan `showScoped()` memeriksa `material.course_id` cocok dengan `{course}`; route shallow tetap memanggil Policy. |
| 8 | `GET /{role}/mata-kuliah/{course}/assignments`, `GET /api/v1/courses/{course}/assignments` | Daftar tugas dari course lain atau tugas draft bocor ke mahasiswa. | `AssignmentPolicy::viewAny` memeriksa akses course; query relasi course ditambah `Assignment::visibleTo($user)`. Query membatasi dosen ke course yang diajar dan mahasiswa ke course terdaftar serta status `published`. |
| 9 | `GET /{role}/assignments/{assignment}`, `GET /{role}/mata-kuliah/{course}/assignments/{assignment}`, `GET /api/v1/courses/{course}/assignments/{assignment}` | Mengganti ID tugas untuk membaca tugas course lain atau tugas draft. | `AssignmentPolicy::view` memeriksa pemilik/enrollment course dan status `published` untuk mahasiswa; controller web/API mengembalikan 404 untuk tugas draft mahasiswa. Nested web memakai `scopeBindings()` dan `showScoped()` mencocokkan `assignment.course_id`; API memeriksa pasangan `{course}` dan `{assignment}` sebelum akses. |
| 10 | `POST /api/v1/courses/{course}/assignments` | Membuat tugas pada course yang tidak boleh dikelola. | `AssignmentPolicy::create` hanya mengizinkan admin atau dosen pengampu course; API memanggil `authorize('create', [Assignment::class, $course])`. |
| 11 | `PUT/PATCH/DELETE /api/v1/courses/{course}/assignments/{assignment}` | Mengubah atau menghapus tugas milik course lain. | API memeriksa `assignment.course_id` cocok dengan `{course}` dan memanggil `AssignmentPolicy::update` atau `AssignmentPolicy::delete`, yang hanya mengizinkan admin atau dosen pengampu. |
| 12 | `/admin/users`, `/admin/users/{user}`, `/admin/users/{user}/password` (index/show/create/edit/update/delete/reset) | Mengakses atau mengubah akun dengan mengganti `{user}`. | Semua route berada dalam grup `auth` + `role:admin`; hanya admin dapat mencapai controller. Tidak ada Policy User terpisah; otorisasi endpoint saat ini dilakukan oleh middleware grup route. |
| 13 | Submission, misalnya `/assignments/{assignment}/submissions` atau `/submissions/{submission}` | ID submission dapat digunakan untuk membaca file/catatan mahasiswa lain, mengubah, atau menghapus submission. | Belum ada route/controller Submission aktif, sehingga belum ada endpoint yang dapat diakses. `SubmissionPolicy` sudah mendefinisikan akses pemilik submission dan dosen pengampu, tetapi harus dipanggil oleh controller ketika endpoint dibuat; daftar juga harus di-query berdasarkan user/assignment yang berwenang. |
| 14 | Grade, misalnya `/submissions/{submission}/grade` atau `/grades/{grade}` | ID grade dapat digunakan untuk membaca nilai mahasiswa lain atau mengubah nilai tanpa hak. | Belum ada route/controller Grade aktif. `GradePolicy` sudah mendefinisikan akses mahasiswa ke grade miliknya serta hak dosen pengampu/admin untuk menilai, tetapi harus dipanggil controller dan query daftar harus dibatasi saat fitur dibuat. |

Placeholder `{role}` pada baris 8–9 berarti salah satu prefix route Web yang
terdaftar: `admin`, `dosen`, atau `mahasiswa`.

## Batas cakupan

- Route Web untuk course, material, dan assignment memakai autentikasi. Route di
  prefix admin juga dibatasi middleware role admin; route dosen dan mahasiswa
  dibatasi role masing-masing.
- Endpoint API berada di middleware `auth:sanctum`. Otorisasi resource tetap
  dilakukan Policy/query setelah autentikasi.
- Tidak ada endpoint API material, submission, atau grade saat tabel ini dibuat.
- Perlindungan setiap fitur baru harus diterapkan di server-side controller/query;
  menyembunyikan tombol dengan Blade saja tidak mencegah request langsung.
