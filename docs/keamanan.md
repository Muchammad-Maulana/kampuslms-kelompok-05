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
| 6 | `GET/POST /admin/mata-kuliah/{course}/materials`, `GET/POST /dosen/mata-kuliah/{course}/materials`, `GET /mahasiswa/mata-kuliah/{course}/materials`, `GET /api/v1/courses/{course}/materials` | Daftar material dapat dibaca atau dibuat pada course yang tidak boleh diakses/dikelola. | `MaterialPolicy::viewAny` memeriksa hak melihat course dan `MaterialPolicy::create` hanya mengizinkan admin atau dosen pengampu. Controller memakai relasi `$course->materials()` dan `Material::visibleTo($user)` untuk daftar; Form Request dan Policy mengamankan pembuatan. Field `course_id` dan `uploaded_by` ditentukan server. |
| 7 | `GET/PUT/PATCH/DELETE /admin/materials/{material}`, `GET/PUT/PATCH/DELETE /dosen/materials/{material}`, `GET /mahasiswa/materials/{material}`, serta route nested `/mata-kuliah/{course}/materials/{material}` | Mengganti ID material untuk membaca, mengubah, atau menghapus materi dari course yang tidak boleh diakses. | `MaterialPolicy::view`, `update`, dan `delete` mewarisi pemeriksaan akses/pengelolaan `CoursePolicy` pada course material. `UpdateMaterialRequest` dan controller memanggil Policy; route nested memakai `scopeBindings()` dan `showScoped()` mencocokkan `material.course_id` dengan `{course}`. |
| 8 | `GET/POST /admin/mata-kuliah/{course}/assignments`, `GET/POST /dosen/mata-kuliah/{course}/assignments`, `GET /mahasiswa/mata-kuliah/{course}/assignments`, `GET /api/v1/courses/{course}/assignments`, `POST /api/v1/assignments` | Daftar tugas dari course lain atau tugas draft bocor; pembuatan tugas dilakukan pada course yang tidak dikelola. | `AssignmentPolicy::viewAny` memeriksa akses course dan `create` hanya mengizinkan admin/pengampu. Query memakai relasi course dan `Assignment::visibleTo($user)`; input `course_id` diverifikasi oleh Policy dan `created_by` berasal dari user terautentikasi. |
| 9 | `GET/PUT/PATCH/DELETE /admin/assignments/{assignment}`, `GET/PUT/PATCH/DELETE /dosen/assignments/{assignment}`, `GET/mahasiswa/assignments/{assignment}`, `GET/{role}/mata-kuliah/{course}/assignments/{assignment}`, `GET/api/v1/courses/{course}/assignments/{assignment}` | Mengganti ID tugas untuk membaca, mengubah, atau menghapus tugas milik course lain atau membaca tugas draft. | `AssignmentPolicy::view`, `update`, dan `delete` memeriksa akses/ownership course; mahasiswa hanya melihat tugas `published`. Update Form Request dan controller memanggil Policy; nested web/API memeriksa pasangan `{course}` dan `{assignment}` sebelum akses. |
| 10 | `POST /api/v1/assignments`, `POST /api/v1/courses/{course}/assignments` | Membuat tugas pada course yang tidak boleh dikelola atau memalsukan pembuat. | `AssignmentPolicy::create` hanya mengizinkan admin atau dosen pengampu; kedua path memeriksa Policy dan mengisi `created_by` dari user terautentikasi. |
| 11 | `PUT/PATCH/DELETE /api/v1/assignments/{assignment}`, `PUT/PATCH/DELETE /api/v1/courses/{course}/assignments/{assignment}` | Mengubah atau menghapus tugas milik course lain. | Policy hanya mengizinkan admin atau dosen pengampu; nested path juga mencocokkan `assignment.course_id` dengan `{course}`. |
| 12 | `/admin/users`, `/admin/users/{user}`, `/admin/users/{user}/password` (index/show/create/edit/update/delete/reset) | Mengakses atau mengubah akun dengan mengganti `{user}`. | Semua route berada dalam grup `auth` + `role:admin`; hanya admin dapat mencapai controller. Tidak ada Policy User terpisah; otorisasi endpoint saat ini dilakukan oleh middleware grup route. |
| 13 | Submission, misalnya `/assignments/{assignment}/submissions` atau `/submissions/{submission}` | ID submission dapat digunakan untuk membaca file/catatan mahasiswa lain, mengubah, atau menghapus submission. | Belum ada route/controller Submission aktif, sehingga belum ada endpoint yang dapat diakses. `SubmissionPolicy` sudah mendefinisikan akses pemilik submission dan dosen pengampu, tetapi harus dipanggil oleh controller ketika endpoint dibuat; daftar juga harus di-query berdasarkan user/assignment yang berwenang. |
| 14 | Grade, misalnya `/submissions/{submission}/grade` atau `/grades/{grade}` | ID grade dapat digunakan untuk membaca nilai mahasiswa lain atau mengubah nilai tanpa hak. | Belum ada route/controller Grade aktif. `GradePolicy` mengizinkan mahasiswa melihat nilai miliknya, admin melihat nilai, dan hanya dosen pengampu yang dapat membuat/mengubah/menghapus grade; policy harus dipanggil controller saat fitur dibuat. |
| 15 | `GET/PUT /admin/mata-kuliah/{course}/enrollment`, `GET/PUT /dosen/mata-kuliah/{course}/enrollment` | Mengganti ID course untuk mengelola pendaftaran di course dosen lain atau mengirim ID user non-mahasiswa. | `CoursePolicy::manageEnrollment` mengizinkan admin atau dosen pemilik course. Form Request memastikan setiap `student_id` adalah mahasiswa unik; update hanya menyinkronkan ID yang tervalidasi. |

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

## Menjalankan pengujian otorisasi

Jalankan aplikasi dan pastikan akun demo serta data course sudah di-seed, lalu dari
Git Bash jalankan `scripts/test-authz.sh`. Skrip login dengan tiga akun demo,
menguji status HTTP untuk course dan assignment, lalu menghapus course sementara
yang dibuatnya. Default URL adalah `http://127.0.0.1:8000`; atur `BASE_URL` jika
server berjalan di alamat lain. Pastikan `curl` dan PHP CLI tersedia di PATH atau
atur `PHP_BIN` ke executable PHP Laragon.

CRUD Web materi/tugas dan penolakan perubahan role diuji oleh
`php artisan test --filter=WeekSevenCrudFeatureTest`.
