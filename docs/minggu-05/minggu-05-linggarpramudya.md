# Catatan Minggu 5
Nama : Linggar Pramudya\
Nim : 10241039\
Kelas : A

## 5.3 Read → Break → Fix → Build

### READ

1. Jalankan `php artisan route:list --except-vendor`. Salin keluarannya ke catatan.
2. Tandai setiap route yang menerima parameter model (`{course}`, `{assignment}`, dst).
3. Untuk setiap route bertanda, jawab: **siapa saja yang seharusnya boleh mengaksesnya, dan apa yang saat ini mencegah orang lain?** Kemungkinan besar jawabannya "belum ada apa-apa" — itu wajar, dan itulah pekerjaan minggu ini dan minggu 7.
4. Buat tabel di `docs/minggu-05-<nama>.md` berjudul "Daftar Titik Rawan IDOR". Tabel ini akan Anda pakai lagi di minggu 7 dan saat interview.
   
## Jawaban
1. Perintah yang dijalankan:

	```text
	php artisan route:list --except-vendor
	```

	Hasilnya menampilkan `58 routes`. Route yang memiliki parameter model adalah:

	| Method | URI | Nama route | Pengaman yang terlihat |
	|---|---|---|---|
	| GET | `admin/assignments/{assignment}` | `admin.assignments.show` | `auth`, `role:admin`, lalu controller mengecek course dari assignment |
	| GET/PUT/PATCH/DELETE | `admin/mata-kuliah/{course}` | `admin.mata-kuliah.*` | `auth`, `role:admin` |
	| GET | `admin/mata-kuliah/{course}/materials/{material}` | `admin.mata-kuliah.materials.scoped-show` | `auth`, `role:admin`, `scopeBindings()` |
	| GET | `admin/mata-kuliah/{course}/assignments/{assignment}` | `admin.mata-kuliah.assignments.scoped-show` | `auth`, `role:admin`, `scopeBindings()` |
	| GET | `admin/mata-kuliah/{course}/edit` | `admin.mata-kuliah.edit` | `auth`, `role:admin` |
	| GET | `admin/mata-kuliah/{course}/materials` | `admin.mata-kuliah.materials.index` | `auth`, `role:admin`, `scopeBindings()` |
	| GET | `admin/mata-kuliah/{course}/assignments` | `admin.mata-kuliah.assignments.index` | `auth`, `role:admin`, `scopeBindings()` |
	| GET | `admin/materials/{material}` | `admin.materials.show` | `auth`, `role:admin`, controller mengecek course dari material |
	| GET/PUT/PATCH/DELETE | `admin/users/{user}` | `admin.users.*` | `auth`, `role:admin` |
	| GET | `dosen/assignments/{assignment}` | `dosen.assignments.show` | `auth`, `role:dosen`, controller mengecek dosen pemilik course |
	| GET | `dosen/mata-kuliah/{course}` | `dosen.mata-kuliah.show` | `auth`, `role:dosen`, controller mengecek `lecturer_id` |
	| GET | `dosen/mata-kuliah/{course}/materials/{material}` | `dosen.mata-kuliah.materials.scoped-show` | `auth`, `role:dosen`, `scopeBindings()` dan pengecekan course |
	| GET | `dosen/mata-kuliah/{course}/assignments/{assignment}` | `dosen.mata-kuliah.assignments.scoped-show` | `auth`, `role:dosen`, `scopeBindings()` dan pengecekan course |
	| GET | `dosen/mata-kuliah/{course}/materials` | `dosen.mata-kuliah.materials.index` | `auth`, `role:dosen`, `scopeBindings()` dan pengecekan course |
	| GET | `dosen/mata-kuliah/{course}/assignments` | `dosen.mata-kuliah.assignments.index` | `auth`, `role:dosen`, `scopeBindings()` dan pengecekan course |
	| GET | `dosen/materials/{material}` | `dosen.materials.show` | `auth`, `role:dosen`, controller mengecek course pemilik |
	| GET | `mahasiswa/assignments/{assignment}` | `mahasiswa.assignments.show` | `auth`, `role:mahasiswa`, controller mengecek enrollment dan status published |
	| GET | `mahasiswa/mata-kuliah/{course}` | `mahasiswa.mata-kuliah.show` | `auth`, `role:mahasiswa`, controller mengecek enrollment |
	| GET | `mahasiswa/mata-kuliah/{course}/materials/{material}` | `mahasiswa.mata-kuliah.materials.scoped-show` | `auth`, `role:mahasiswa`, `scopeBindings()` dan pengecekan enrollment |
	| GET | `mahasiswa/mata-kuliah/{course}/assignments/{assignment}` | `mahasiswa.mata-kuliah.assignments.scoped-show` | `auth`, `role:mahasiswa`, `scopeBindings()` dan pengecekan enrollment/status |
	| GET | `mahasiswa/mata-kuliah/{course}/materials` | `mahasiswa.mata-kuliah.materials.index` | `auth`, `role:mahasiswa`, `scopeBindings()` dan pengecekan enrollment |
	| GET | `mahasiswa/mata-kuliah/{course}/assignments` | `mahasiswa.mata-kuliah.assignments.index` | `auth`, `role:mahasiswa`, `scopeBindings()` dan pengecekan enrollment |
	| GET | `mahasiswa/materials/{material}` | `mahasiswa.materials.show` | `auth`, `role:mahasiswa`, controller mengecek enrollment course |
	| GET/PUT/PATCH/DELETE | `api/v1/courses/{course}` | `api.courses.*` | `auth:sanctum`; controller membatasi berdasarkan role dan pemilik course |
	| GET/POST | `api/v1/courses/{course}/assignments` | `api.assignments.index/store` | `auth:sanctum`; controller mengecek akses course |
	| GET/PUT/PATCH/DELETE | `api/v1/courses/{course}/assignments/{assignment}` | `api.assignments.show/update/destroy` | `auth:sanctum`; controller memastikan `assignment.course_id` sama dengan course URL |

2. Route web memakai parameter `{course}`, `{assignment}`, `{material}`, dan
	`{user}`. Route API memakai `{course}` dan `{assignment}`. Tidak ada route
	submission pada hasil `route:list`, walaupun model `Submission` ada di project.

3. Admin boleh mengakses data course, assignment, material, dan user melalui
	middleware `auth` dan `role:admin`. Dosen hanya boleh melihat course yang
	`lecturer_id`-nya sama dengan user yang login; mahasiswa hanya boleh melihat
	course yang diikutinya. Pemeriksaan ini ada di `CourseController`,
	`AssignmentController`, dan `MaterialController`.

	Route nested web berada di dalam `Route::scopeBindings()`, sehingga model
	child harus berada di bawah parent yang ada di URL. `showScoped()` juga
	memeriksa pasangan `course_id` dan `course`. Route shallow tetap dilindungi
	controller berdasarkan course terkait. API tidak memakai `scopeBindings()`,
	tetapi `Api\\AssignmentController` melakukan pemeriksaan pasangan ID secara
	langsung sebelum menampilkan atau mengubah assignment.

4. **Daftar Titik Rawan IDOR**

	| Route/parameter | Risiko yang diperiksa | Kondisi saat ini |
	|---|---|---|
	| `/.../mata-kuliah/{course}` | User melihat course milik user lain | Sudah dibatasi berdasarkan role dan ownership/enrollment di controller. |
	| `/.../assignments/{assignment}` | Assignment dibuka tanpa hak akses course | Sudah dicek melalui course terkait; mahasiswa juga harus enrolled dan assignment published. |
	| `/.../mata-kuliah/{course}/assignments/{assignment}` | Assignment milik course lain dipasang pada URL course ini | Web memakai `scopeBindings()` dan `showScoped()`; test menghasilkan 404. API memakai pengecekan `assignment.course_id === course.id`. |
	| `/.../materials/{material}` | Material course lain dibuka dengan mengganti ID | Controller memeriksa akses ke course material; nested web juga memakai `scopeBindings()`. |
	| `/admin/users/{user}` | User lain diakses oleh bukan admin | Grup route memakai `auth` dan `role:admin`; akses mahasiswa diuji menghasilkan 403. |
	| `/api/v1/courses/{course}` | Course milik role/user lain diakses lewat API | `auth:sanctum` dan pengecekan role/ownership di `Api\\CourseController`; belum diuji dengan request token nyata. |
	| `/api/v1/courses/{course}/assignments/{assignment}` | Assignment dari course lain dipasang pada URL API | Ada pemeriksaan ID course di `Api\\AssignmentController`; belum diuji dengan request token nyata. |
	| Submission | Mengakses submission mahasiswa lain | Belum ada route submission pada project, jadi skenario URL submission belum bisa diuji. |

### BREAK

| # | Yang dirusak | Prediksi | Yang Sebenarnya Terjadi |
|---|---|---|---|
| 1 | Login sebagai mahasiswa A, lalu membuka submission milik mahasiswa B dengan mengubah angka pada URL. | Jika route submission ada tetapi hanya mengambil ID tanpa memeriksa pemiliknya, mahasiswa A dapat membaca data mahasiswa B. | Belum dapat diuji karena `php artisan route:list --except-vendor` tidak menemukan route submission. Model `Submission` ada, tetapi tidak ada endpoint/view route untuk skenario ini. |
| 2 | Membuka `/courses/1/assignments/99`, padahal tugas 99 milik mata kuliah lain. | Assignment dari course lain seharusnya ditolak, biasanya dengan 404, bukan ditampilkan. | URL persis `/courses/1/assignments/99` tidak terdaftar. Pada route nested project yang setara, test `test_scoped_nested_route_rejects_an_assignment_from_another_course` lulus dan response yang diharapkan adalah 404. |
| 3 | Mengaktifkan `Route::scopeBindings()`, lalu mengulangi pengujian nomor 2. | Binding assignment harus dibatasi pada course di URL, sehingga assignment dari course lain tidak ditemukan dan menghasilkan 404. | `routes/web.php` memang memakai `Route::scopeBindings()` pada grup nested admin, dosen, dan mahasiswa. Test nested assignment lulus dengan 404 dan isi assignment tidak tampil. |
| 4 | Mendaftarkan middleware di `app/Http/Kernel.php` mengikuti tutorial Laravel versi lama. | Pada Laravel 12, cara lama itu tidak mendaftarkan alias middleware yang dipakai route dan dapat menyebabkan alias `role` tidak ditemukan. | File `app/Http/Kernel.php` tidak dipakai/tersedia pada project ini. Alias `role` didaftarkan di `bootstrap/app.php` dengan `$middleware->alias(...)`. Ini diverifikasi dari kode, bukan dari percobaan mengubah file aplikasi. |
| 5 | Memasang `role:admin` pada grup route, lalu mengaksesnya menggunakan akun dosen. | Middleware harus menolak akun dosen dengan response 403. | `EnsureUserHasRole` membandingkan role secara ketat dan meng-abort 403 jika tidak cocok. Test akses role yang tidak sesuai pada `WeekFiveFeatureTest` lulus untuk user non-admin; pengujian khusus akun dosen ke route admin belum dijalankan. |
| 6 | Sebagai dosen A, mengedit mata kuliah milik dosen B meskipun keduanya lolos middleware `role:dosen`. | Dosen A seharusnya tidak dapat mengedit course dosen B; request harus ditolak. | Grup `dosen` hanya mendaftarkan route GET index/show. Route edit/update course hanya ada di grup `admin`, sehingga tidak ada endpoint edit course yang dapat dipakai dosen. Test dosen melihat course dosen lain juga lulus dengan 403. |