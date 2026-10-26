# Panduan Belajar UTS Pemrograman Web — KampusLMS

Panduan ini disusun dari kode dan tes yang ada di repositori ini. Gunakan untuk
latihan demo serta menjawab pertanyaan dengan memahami **alasan desain, alternatif,
risiko jika mekanisme dihapus, dan tes yang membuktikannya**. Jangan menghafal
jawaban tanpa membuka berkas terkait: penguji dapat meminta Anda menunjuk baris
yang benar-benar berjalan.

## Peta cepat proyek

Alur request secara ringkas:

```text
Browser/API client
  -> routes/web.php atau routes/api.php
  -> middleware autentikasi dan role
  -> Form Request (validasi/authorize)
  -> controller (Policy/Gate, query, aksi)
  -> model/relasi/database
  -> Blade view atau API Resource
```

| Tanggung jawab | Berkas untuk dibuka |
|---|---|
| Daftar rute web dan pembatasan role | [`routes/web.php`](../routes/web.php) |
| Endpoint JSON v1 dan middleware Sanctum | [`routes/api.php`](../routes/api.php) |
| Pendaftaran alias middleware Laravel 12 | [`bootstrap/app.php`](../bootstrap/app.php) |
| Pemeriksaan role pada route | [`EnsureUserHasRole.php`](../app/Http/Middleware/EnsureUserHasRole.php) |
| Relasi dan mass assignment | [`Course.php`](../app/Models/Course.php), [`User.php`](../app/Models/User.php), [`Assignment.php`](../app/Models/Assignment.php) |
| Validasi input | [`StoreCourseRequest.php`](../app/Http/Requests/StoreCourseRequest.php), [`StoreUserRequest.php`](../app/Http/Requests/StoreUserRequest.php), [`StoreMaterialRequest.php`](../app/Http/Requests/StoreMaterialRequest.php), [`StoreAssignmentRequest.php`](../app/Http/Requests/StoreAssignmentRequest.php) |
| Otorisasi resource | [`CoursePolicy.php`](../app/Policies/CoursePolicy.php), [`MaterialPolicy.php`](../app/Policies/MaterialPolicy.php), [`AssignmentPolicy.php`](../app/Policies/AssignmentPolicy.php) |
| Alur demo | [`CourseController.php`](../app/Http/Controllers/CourseController.php), [`EnrollmentController.php`](../app/Http/Controllers/EnrollmentController.php), [`MaterialController.php`](../app/Http/Controllers/MaterialController.php), [`AssignmentController.php`](../app/Http/Controllers/AssignmentController.php) |
| Akun dan data awal | [`DemoAccountSeeder.php`](../database/seeders/DemoAccountSeeder.php), [`DatabaseSeeder.php`](../database/seeders/DatabaseSeeder.php) |
| Pengujian keamanan dan kontrak | [`WeekFiveFeatureTest.php`](../tests/Feature/WeekFiveFeatureTest.php), [`CourseEnrollmentFeatureTest.php`](../tests/Feature/CourseEnrollmentFeatureTest.php), [`WeekSixApiFeatureTest.php`](../tests/Feature/WeekSixApiFeatureTest.php), [`ModelPoliciesTest.php`](../tests/Feature/ModelPoliciesTest.php) |
| Tabel uji IDOR untuk penguji | [`keamanan.md`](./keamanan.md) |

## Persiapan sebelum hari UTS

### Jalankan dari database bersih

Penguji menjalankan perintah yang sama. Pastikan konfigurasi `.env` lokal
menunjuk database yang memang boleh dihapus dan dibuat ulang:

```powershell
php artisan migrate:fresh --seed
php artisan route:list --except-vendor
php artisan test
```

`migrate:fresh` menghapus tabel aplikasi sebelum membangunnya kembali. Jangan
menjalankannya pada database produksi atau database yang datanya ingin disimpan.
Untuk mengecek hanya alur penting saat latihan:

```powershell
php artisan test --filter=WeekFourFeatureTest
php artisan test --filter=WeekFiveFeatureTest
php artisan test --filter=CourseEnrollmentFeatureTest
php artisan test --filter=WeekSixApiFeatureTest
php artisan test --filter=WeekSevenAccessFeatureTest
php artisan test --filter=WeekSevenCrudFeatureTest
```

### Akun demo

| Peran | Email | Kata sandi |
|---|---|---|
| Admin | `admin@kampuslms.test` | `password` |
| Dosen | `dosen@kampuslms.test` | `password` |
| Mahasiswa | `mahasiswa@kampuslms.test` | `password` |

Akun dibuat oleh seeder dan disimpan dengan peran yang ditetapkan secara
eksplisit. Seeder juga membangun data mata kuliah, enrollment, tugas, submission,
dan grade agar dashboard tidak kosong. Mahasiswa demo sengaja didaftarkan pada
course pertama; perilaku ini diuji di `WeekFiveFeatureTest`. Saat demo, cek daftar
course yang benar-benar muncul setelah login, jangan menebak ID course dari
database.

## Rencana demo 20 menit

### Bagian 1 — demo 7 menit

Latih satu alur mulus dengan pergantian pembicara yang sudah disepakati.

1. **Admin:** login; buka daftar mata kuliah; buat course baru dengan kode unik,
   nama, SKS 1–6, dosen ber-role `dosen`, dan status `active` agar dapat
   ditampilkan kepada mahasiswa. Course `draft`/`archived` memang tidak terlihat
   untuk mahasiswa.
2. **Admin:** buka enrollment course itu; pilih mahasiswa; simpan; tunjukkan
   mahasiswa benar-benar muncul sebagai peserta.
3. **Dosen:** logout admin, login dengan akun dosen yang ditugaskan pada course;
   buka course tersebut; buat tugas dan materi.
4. **Mahasiswa:** logout dosen, login mahasiswa yang didaftarkan; buka daftar
   mata kuliah dan tunjukkan course itu. Buka tugas yang berstatus `published`;
   draft seharusnya tidak terlihat untuk mahasiswa.
5. Narasikan bahwa batas akses diperiksa di server. Link yang tidak ditampilkan
   bukan satu-satunya perlindungan.

**Narasi pembuka yang aman:** “Saya mulai dari database seed bersih. Saya
demonstrasikan pembuatan course, pendaftaran mahasiswa, pembuatan materi/tugas
oleh dosen pengampu, lalu tampilan course dari akun mahasiswa. Jika ada fitur
yang belum berjalan pada mesin demo, saya sebutkan sekarang.”

### Bagian 2 — code walkthrough 8 menit

Untuk tiap pertanyaan, buka berkasnya, tunjukkan titik kontrolnya, jelaskan
mengapa dipilih, beri alternatif, lalu jelaskan konsekuensi bila dihapus.
Prioritaskan menjawab dua atau tiga pertanyaan yang dipilih penguji; pertanyaan
12 tetap wajib.

### Bagian 3 — uji keamanan 5 menit

Buka tabel IDOR di [`keamanan.md`](./keamanan.md). Pilih satu baris dan lakukan
uji dengan dua akun/dua resource berbeda. Sebelum menjalankan, sebutkan:

1. identitas/role siapa yang mengirim request;
2. resource siapa yang dicoba diakses;
3. kontrol server-side yang diperkirakan menolak;
4. status atau tampilan yang diharapkan;
5. bukti tes otomatis yang menguji skenario serupa.

Jangan mengubah data penting saat demo keamanan. Jika uji berhasil menembus,
jangan menyangkalnya: tunjukkan sebab, cakupan dampak, dan kontrol yang harus
ditambahkan.

### Simulasi uji IDOR 5 menit — skenario yang direkomendasikan

Pilih **baris 2** tabel `keamanan.md`: mahasiswa mencoba membuka detail mata
kuliah yang tidak diikutinya dengan mengganti ID course di URL. Ini mudah
ditunjukkan dari browser dan tidak membuat atau menghapus data.

#### Sebelum timer mulai (persiapan)

1. Pastikan aplikasi berjalan dan akun demo tersedia. Pada ujian, penguji sudah
   menjalankan `php artisan migrate:fresh --seed`; **jangan jalankan perintah
   itu sendiri saat demo**, karena perintah tersebut menghapus lalu membangun
   ulang database.
2. Siapkan dua URL detail course: satu yang diikuti akun mahasiswa demo dan
   satu yang tidak diikutinya. Cara aman: gunakan admin/daftar course atau
   tautan course yang sudah disiapkan hanya untuk membaca ID-nya; jangan
   mengubah enrollment. Database seeder menjamin akun mahasiswa demo mengikuti
   course pertama dan tidak mengikuti course lain.
3. Jangan membagikan atau menampilkan password di rekaman/presentasi. Isi
   kredensial akun demo sesuai instruksi dosen.
4. Buka tab browser terpisah untuk source/test atau siapkan file berikut:
   [`CoursePolicy.php`](../app/Policies/CoursePolicy.php),
   [`CourseController.php`](../app/Http/Controllers/CourseController.php),
   [`WeekFiveFeatureTest.php`](../tests/Feature/WeekFiveFeatureTest.php).

#### Alur saat diuji

| Waktu | Yang dilakukan | Yang diucapkan / diamati |
|---|---|---|
| 0:00–0:30 | Tunjuk baris 2 tabel `keamanan.md`. | “Aktor uji adalah mahasiswa yang sudah login. Saya akan mencoba membaca course yang tidak diikuti dengan mengganti ID URL. Saya tidak mengubah data.” |
| 0:30–1:15 | Login sebagai mahasiswa demo dan buka course yang memang diikutinya. Catat ID dari URL. | Halaman course sendiri berhasil dibuka. Ini kontrol positif: membuktikan akun dan route berfungsi, bukan sekadar request gagal karena belum login. |
| 1:15–2:15 | Pada URL yang sama, ganti hanya `{course}` dengan ID course lain yang tidak diikuti mahasiswa. Tekan Enter. | Server seharusnya menolak dengan **HTTP 403** / halaman “Akses ditolak”; isi course target tidak boleh tampil. Gunakan tab Network browser untuk menunjukkan status request jika tampilan error tidak memperlihatkannya. |
| 2:15–3:30 | Buka `CoursePolicy::view()` dan `CourseController::show()`. | Policy hanya mengizinkan mahasiswa jika relasi `courses()` miliknya memiliki ID course itu. Controller memanggil `$this->authorize('view', $course)`, jadi URL langsung tetap diperiksa di server. |
| 3:30–4:30 | Buka test seed yang membuktikan course pertama dapat diakses dan course kedua ditolak. | Test memakai akun `mahasiswa@kampuslms.test`, memastikan enrollment-nya, lalu memeriksa respons course pertama `200` dan course kedua `403`. |
| 4:30–5:00 | Ringkas hasil dan dampak bila pemeriksaan dihapus. | “Mengganti ID bukan otorisasi. Tanpa pemeriksaan enrollment, mahasiswa yang login bisa membaca detail course orang lain. Di sini percobaan ditolak; kontrolnya adalah Policy server-side yang benar-benar dipanggil.” |

**Script lisan ringkas:**

> “Saya memilih baris 2. Aktornya mahasiswa demo yang sudah login; targetnya
> course yang tidak diikuti. Saya buka dulu course yang memang diikuti sebagai
> pembanding, lalu mengganti ID pada URL ke course target tanpa mengubah
> parameter lain. Respons yang diharapkan adalah 403 dan data target tidak
> tampil. Di `CoursePolicy::view()`, mahasiswa harus terdaftar pada course;
> `CourseController::show()` memanggil `$this->authorize('view', $course)`.
> Test `test_demo_student_can_open_first_seeded_course_but_not_second`
> membuktikan hasil 200 untuk course yang diikuti dan 403 untuk yang tidak.
> Kalau pemeriksaan Policy dihapus atau tidak dipanggil, IDOR dapat membuat
> mahasiswa membaca course lain.”

**Jika penguji meminta bukti test dijalankan langsung:** dari terminal proyek,
jalankan test spesifik ini (test mengatur database test sendiri):

```powershell
php artisan test --filter=test_demo_student_can_open_first_seeded_course_but_not_second
```

Test terkait kedua, yang memakai data buatan khusus alih-alih seeder, dapat
dijalankan dengan:

```powershell
php artisan test --filter=test_student_can_only_view_enrolled_courses
```

Test bukan pengganti uji langsung yang diminta: jalankan percobaan URL di
browser, lalu tunjukkan test sebagai bukti bahwa skenario yang sama juga
diperiksa otomatis.

#### Kalau browser tidak menampilkan angka 403 dengan jelas

Jangan menyimpulkan request berhasil hanya dari tampilan. Buka DevTools →
**Network**, ulangi request, pilih request `GET` course yang target, lalu tunjuk
status `403`. Pastikan halaman tidak memuat nama/instruksi target. Jika hasilnya
`200`, konten target terlihat, atau status lain yang tidak sesuai, sebutkan apa
yang benar-benar terjadi dan jangan menyebut pengujian berhasil.

#### Kalau penguji memilih baris lain

- **Baris 1 (daftar course):** sebagai mahasiswa, buka daftar mata kuliah dan
  pastikan hanya course yang diikuti yang muncul. Tunjukkan query
  `Course::visibleTo($user)` di `Course::scopeVisibleTo()` dan test
  `test_student_can_only_view_enrolled_courses` atau
  `test_demo_student_can_open_first_seeded_course_but_not_second`.
- **Baris 7 (materi):** login sebagai dosen yang bukan pengampu, ganti ID materi
  ke materi milik course dosen lain, lalu tunjukkan penolakan Policy. Test
  `test_lecturer_cannot_view_material_from_another_course` menguji kasus ini.
- **Baris 9 (tugas):** gunakan tugas di course dosen lain atau tugas draft untuk
  mahasiswa. Tunjukkan `AssignmentPolicy` dan test terkait pada
  `WeekFiveFeatureTest.php`.
- **Baris 13–14 (submission/grade):** ada ketidaksesuaian pada `keamanan.md`.
  Dokumentasi tabel mengatakan route/controller submission dan grade belum
  aktif, tetapi repository sekarang memiliki route web upload/update/penilaian.
  Jangan mengulang klaim tabel itu tanpa memeriksa source. Katakan dengan jujur
  bahwa dokumentasinya belum diperbarui, lalu buka route, controller, dan
  `SubmissionPolicy`; status pengamanan yang diuji harus mengikuti endpoint
  aktual, bukan kalimat lama di tabel.

**Jangan lakukan:** menghapus Policy/middleware saat demo, mengubah database
produksi/demo, menghapus course/enrollment, mengunggah file sembarang, atau
mengulang request mutasi yang dapat mengubah nilai/pengumpulan. Untuk uji baca
IDOR ini cukup gunakan `GET`.

### Kartu demo untuk seluruh 15 baris IDOR

Dosen memilih **satu** baris untuk diuji langsung dalam lima menit. Tabel di
bawah ini adalah persiapan untuk semua kemungkinan pilihan, bukan urutan untuk
mendemokan kelimanya belas satu per satu. Pada setiap skenario:

1. sebutkan aktor dan resource target;
2. tunjukkan request langsung (jangan hanya menyembunyikan tombol);
3. sebutkan status/hasil yang diharapkan;
4. buka Policy/query dan test sebagai bukti;
5. jelaskan dampak jika kontrol dihapus.

Untuk GET, gunakan browser dan ganti ID pada URL atau `curl.exe`. Untuk operasi
mutasi yang ditandai **test saja**, jangan kirim request ke database demo
persistent; tunjukkan test `RefreshDatabase` yang membuktikan request ditolak.
Jangan gunakan `migrate:fresh` saat demo.

| Baris | Demonstrasi yang disarankan (aktor → target) | Hasil yang diharapkan dan kontrol yang ditunjukkan | Bukti otomatis |
|---|---|---|---|
| 1 | Mahasiswa membuka daftar course; bandingkan course yang diikuti dengan course milik mahasiswa lain, course yang tidak diikuti, serta course `draft`/`archived`. Untuk dosen, buka daftar dosen dan pastikan hanya course yang diajarnya terlihat. | Daftar mahasiswa hanya berisi course `active` yang terdaftar; daftar dosen dibatasi `lecturer_id`. Tunjukkan `Course::scopeVisibleTo()` di `Course.php`; filter berjalan di query, bukan hanya Blade. | `WeekFiveFeatureTest::test_student_cannot_see_or_open_non_active_courses_even_when_enrolled`, `test_lecturer_course_list_only_contains_courses_they_teach`; API: `WeekSixApiFeatureTest::test_student_course_api_hides_non_active_courses_and_rejects_direct_access`. |
| 2 | Login sebagai mahasiswa A, buka course miliknya, lalu ubah ID course pada URL ke course mahasiswa B atau course `draft`. | Course milik sendiri aktif → 200; course lain aktif tetapi tidak diikuti → 403; course `draft`/`archived` → 403. Tunjukkan `CoursePolicy::view()` dan `$this->authorize('view', $course)` di controller. | `WeekFiveFeatureTest::test_student_can_only_view_enrolled_courses`, `test_demo_student_can_open_first_seeded_course_but_not_second`, `test_student_cannot_see_or_open_non_active_courses_even_when_enrolled`. |
| 3 | Mahasiswa/dosen mencoba membuka `GET /admin/mata-kuliah/create`; untuk bukti API, mahasiswa mengirim `POST /api/v1/courses` dengan token valid dan JSON valid. | Halaman admin ditolak (403); API create ditolak (403), course tidak dibuat. Tunjukkan middleware `auth` + `role:admin` pada web, dan `CoursePolicy::create` yang dipanggil controller API. POST hanya dicoba di test/local terisolasi. | `WeekSixApiFeatureTest::test_student_cannot_create_course`; uji route admin juga tercakup oleh pemeriksaan role di `WeekFiveFeatureTest`. |
| 4 | **Test saja untuk update/delete.** Sebagai mahasiswa, kirim PATCH ke course API; jangan uji DELETE pada database demo karena jika ada bug course bisa terhapus. | PATCH harus 403 dan data tetap sama. Tunjukkan `CoursePolicy::update/delete` dan pemanggil authorize di API controller. Jelaskan bahwa DELETE perlu diuji di database test atau resource sementara yang memang boleh dihapus. | `WeekSixApiFeatureTest::test_admin_can_create_update_and_delete_course_with_rest_status_codes` menguji lifecycle admin; `scripts/test-authz.sh` juga menguji student PATCH 403 tetapi membuat/menghapus course sementara—jalankan hanya di lingkungan lokal khusus. |
| 5 | Login sebagai mahasiswa dan buka dashboard; bandingkan daftar course terbaru dengan daftar course mahasiswa itu. | Dashboard tidak menampilkan course di luar enrollment/nonaktif. Tunjukkan `DashboardController` memakai `Course::visibleTo($user)` sebelum menghitung/mengambil course; tidak mengandalkan penyembunyian di view. | `WeekFiveFeatureTest::test_student_dashboard_only_shows_enrolled_courses`; filter status yang sama diuji pada `test_student_cannot_see_or_open_non_active_courses_even_when_enrolled`. |
| 6 | Mahasiswa yang tidak terdaftar atau dosen lain meminta daftar material melalui URL course target, misalnya `GET /mahasiswa/mata-kuliah/{course}/materials` atau API `GET /api/v1/courses/{course}/materials`. | Ditolak 403 dan material target tidak keluar. Tunjukkan `MaterialPolicy::viewAny`, query relasi course/`visibleTo`, dan bahwa field pemilik dibuat oleh server. Pembuatan POST lintas course jangan dicoba pada demo persistent. | `WeekSixApiFeatureTest::test_material_api_requires_course_access_and_returns_a_resource_collection`; `WeekSevenCrudFeatureTest::test_lecturer_cannot_create_or_modify_materials_in_another_course`. |
| 7 | Login sebagai dosen yang bukan pengampu, lalu buka URL detail material milik dosen lain. Untuk update/delete, gunakan test, bukan mutasi langsung ke data demo. | GET ditolak 403; pasangan parent-child yang salah pada nested URL seharusnya 404. Tunjukkan `MaterialPolicy::view/update/delete`, `showScoped()`, dan `scopeBindings()`. | `WeekFiveFeatureTest::test_lecturer_cannot_view_material_from_another_course`; `WeekSevenCrudFeatureTest::test_lecturer_cannot_create_or_modify_materials_in_another_course`. |
| 8 | Sebagai mahasiswa, minta daftar tugas course yang tidak diikuti; atau sebagai dosen lain, buka daftar tugas course yang bukan miliknya. | 403; mahasiswa hanya mendapat tugas `published` pada course `active` yang diikuti. Tunjukkan `AssignmentPolicy::viewAny`, `Assignment::visibleTo()`, serta Policy create untuk dosen pengampu. | `WeekSixApiFeatureTest::test_student_cannot_list_assignments_for_a_course_they_do_not_take`, `test_lecturer_cannot_list_assignments_for_another_lecturers_course`, `test_student_cannot_create_assignment`. |
| 9 | Mahasiswa coba buka tugas `draft` dengan URL langsung; atau ubah ID pada URL dosen ke tugas milik course dosen lain. | Tugas `draft` untuk mahasiswa disembunyikan/ditolak (detail saat ini 404); dosen yang bukan pengampu ditolak. Untuk PATCH/DELETE, test saja. Tunjukkan `AssignmentPolicy::view/update/delete` dan kecocokan course-assignment pada nested route. | `WeekFiveFeatureTest::test_student_cannot_view_draft_assignment`, `test_scoped_nested_route_rejects_an_assignment_from_another_course`; API: `WeekSixApiFeatureTest::test_api_assignment_detail_rejects_mismatched_course`, `test_student_cannot_update_or_delete_assignment`. |
| 10 | **Test saja untuk POST.** Mahasiswa bertoken mencoba POST tugas pada course aktif yang diikutinya (buat data di test), atau dosen mencoba membuat tugas pada course dosen lain lewat web. | 403; tidak ada assignment baru. Tunjukkan `AssignmentPolicy::create` dan server mengisi `created_by` dari user login, bukan dari input klien. Jangan POST langsung ke database demo. | `WeekSixApiFeatureTest::test_student_cannot_create_assignment`; `WeekSevenCrudFeatureTest::test_lecturer_cannot_create_or_modify_assignments_in_another_course`. |
| 11 | **Test saja untuk mutasi.** Sebagai mahasiswa, PATCH assignment API; untuk DELETE jangan gunakan record penting/demo. | PATCH/DELETE harus 403 dan assignment tetap ada. Tunjukkan Policy ownership dan pemeriksaan pasangan course/assignment. | `WeekSixApiFeatureTest::test_student_cannot_update_or_delete_assignment`; test dosen lintas course ada di `WeekSevenCrudFeatureTest::test_lecturer_cannot_create_or_modify_assignments_in_another_course`. |
| 12 | Login bukan admin lalu buka `/admin/users/{id}`; variasikan ID user lain. Untuk mencoba update role, jangan kirim request langsung ke data demo. | Semua route `/admin/users` harus ditolak middleware `role:admin`, apa pun ID yang dipilih. Tunjukkan grup route admin. Jelaskan bahwa admin memang berwenang mengelola user; pembatas yang diuji di sini adalah aktor non-admin. | `WeekFiveFeatureTest::test_user_without_admin_role_receives_a_safe_forbidden_page`; `WeekSevenCrudFeatureTest::test_student_cannot_change_a_users_role_through_admin_update_route`. |
| 13 | **Dokumentasi tabel ini stale untuk web.** Tidak ada endpoint GET `/submissions/{id}` khusus; ada POST/PUT submission mahasiswa di bawah course-assignment. Untuk akses submission milik mahasiswa lain, jangan PUT saat demo—gunakan test policy. | Pemilik submission saja yang boleh melihat/memperbarui; dosen pengampu dapat melihat sesuai Policy. Tunjukkan `SubmissionPolicy`, pemeriksaan bahwa course-assignment-submission cocok di controller, lalu jelaskan bahwa tabel `keamanan.md` masih mengatakan “belum ada route/controller” dan harus diperbarui. Tidak ada API submission. | `ModelPoliciesTest::test_course_material_assignment_submission_and_grade_policies_enforce_ownership`; `SubmissionUploadFeatureTest` menguji upload dan penilaian dasar, bukan semua bentuk serangan IDOR submission. |
| 14 | **Test saja untuk POST penilaian.** Mahasiswa mencoba URL POST penilaian dosen untuk submission; jangan kirim nilai ke database demo. | Middleware role dosen menolak mahasiswa (403); dosen yang bukan pengampu juga tidak boleh menilai. Tunjukkan route dosen, `SubmissionController::grade()` yang memanggil Policy, dan relasi submission-assignment-course. Tidak ada API grade. | `WeekSevenAccessFeatureTest::test_admin_can_view_grade_but_only_course_lecturer_can_change_it` menguji keputusan Policy; periksa juga `ModelPoliciesTest` untuk kepemilikan grade. Keduanya bukan uji HTTP mahasiswa pada route grade. |
| 15 | Login sebagai dosen B, buka GET halaman enrollment course milik dosen A. Jangan PUT daftar mahasiswa pada database demo. | 403. Tunjukkan `CoursePolicy::manageEnrollment` yang membandingkan `lecturer_id` dengan ID dosen aktif; validasi enrollment membatasi pilihan ke mahasiswa dan ID unik. | `CourseEnrollmentFeatureTest::test_course_lecturer_can_manage_enrollment_but_other_roles_cannot`, `test_enrollment_rejects_non_students_and_duplicate_ids`; `WeekSevenAccessFeatureTest::test_only_admin_and_the_course_lecturer_can_manage_enrollment`. |

#### Memilih cara menjalankan bukti otomatis

“Bukti otomatis” berarti **test PHPUnit/Pest yang mengirim request atau menguji
Policy lalu memeriksa hasil yang diharapkan**. Misalnya, test memastikan course
yang diikuti menghasilkan `200`, course yang tidak diikuti menghasilkan `403`,
dan course draft tidak muncul. Test tidak menggantikan percobaan browser/curl
yang diminta penguji; test menunjukkan bahwa skenario serupa juga tercakup
otomatis dan bisa diulang.

**Cara menjalankannya di Windows/PowerShell:**

1. Buka PowerShell baru.
2. Masuk ke folder root proyek (folder yang berisi `artisan`):

   ```powershell
   cd C:\laragon\www\kampuslms-kelompok-05
   ```

3. Jalankan satu test berdasarkan nama method yang tercantum di kolom “Bukti
   otomatis”. Contoh untuk course draft:

```powershell
php artisan test --filter=test_student_cannot_see_or_open_non_active_courses_even_when_enrolled
```

Untuk menjalankan semua test dalam satu file, berikan path file:

```powershell
php artisan test tests/Feature/WeekFiveFeatureTest.php
```

Contoh command lain sesuai pilihan baris:

```powershell
# Mahasiswa tidak boleh melihat course yang tidak diikutinya
php artisan test --filter=test_student_can_only_view_enrolled_courses

# Dosen tidak boleh mengubah tugas milik dosen lain
php artisan test --filter=test_lecturer_cannot_create_or_modify_assignments_in_another_course

# Hanya admin/dosen pemilik course yang boleh mengelola enrollment
php artisan test --filter=test_course_lecturer_can_manage_enrollment_but_other_roles_cannot
```

**Cara membaca hasil:** `PASS` dan tanda centang berarti assertion test
terpenuhi. Lihat nama test yang lulus dan jumlah assertions; jelaskan apa yang
diperiksa, bukan hanya menunjukkan tulisan PASS. `FAIL` berarti test menemukan
hasil berbeda dari yang diharapkan—jangan menyebutnya bukti bahwa kontrol aman.
Baca baris `Expected`/`Failed asserting` untuk melihat perbedaannya, lalu laporkan
hasil itu dengan jujur.

Test yang tercantum pada panduan memakai `RefreshDatabase` atau data test
terisolasi. Test tersebut bekerja pada database testing, bukan bukti bahwa
request live ke database demo sudah dicoba. Tetap cek `APP_ENV`/konfigurasi
testing project dan jangan mengarahkan pengujian ke database produksi. Jangan
menjalankan `migrate:fresh` saat demonstrasi.

Untuk script `scripts/test-authz.sh`, perhatikan bahwa ia membuat course dan
assignment sementara lalu menghapusnya. Jalankan hanya pada server/database
lokal yang memang disiapkan untuk latihan, bukan server produksi atau database
berisi data penting; script memakai token akun demo dan bukan pengganti penjelasan
kontrol pada baris yang dipilih.

### Runbook latihan: mencoba baris 1–15 satu per satu

Bagian ini untuk **latihan sebelum UTS**. Saat UTS, dosen memilih satu skenario;
jangan menjalankan seluruh langkah berikut di database demo. Beberapa skenario
berupa mutasi dan cukup diperiksa lewat test otomatis.

#### Aturan main setiap skenario

Untuk setiap baris, lakukan urutan yang sama:

1. **Sebut aktor:** role/akun yang mengirim request.
2. **Sebut target:** data milik siapa atau milik course yang mana.
3. **Buat pembanding positif jika aman:** akses data sendiri/hak sendiri harus
   berhasil. Ini membuktikan bahwa penolakan bukan semata-mata karena halaman
   rusak atau akun belum login.
4. **Coba akses target:** untuk baca gunakan `GET` di browser dan ganti ID URL.
   Untuk perubahan gunakan test, kecuali dosen memberi database latihan khusus.
5. **Periksa hasil:** lihat status di browser DevTools → Network, bukan hanya
   tampilan halaman.
6. **Jelaskan kontrol:** tunjuk middleware, Policy, query scope, atau validasi
   yang benar-benar dipanggil server.
7. **Tunjukkan test:** jalankan hanya test yang relevan dan jelaskan assertion
   yang membuktikan hasil tersebut.

Gunakan dua jendela browser/profil berbeda jika perlu membandingkan dua akun.
Jangan login dua role di tab biasa yang sama tanpa logout: session browser
dibagi antartab. Jangan menjalankan `migrate:fresh`, menghapus record, mengganti
enrollment, mengirim submission, memberi nilai, atau menjalankan `DELETE` pada
database yang dipakai demo.

#### Cara mendapatkan target dengan aman

- Gunakan admin untuk **membaca** daftar course, dosen, tugas, materi, mahasiswa,
  dan ID dari tautan halaman. Jangan menekan tombol Simpan/Hapus saat mencari ID.
- Untuk mahasiswa demo, seeder memastikan course pertama dapat diakses dan
  course berikutnya tidak diikuti. Untuk course lain, pastikan dahulu status
  dan enrollment-nya sebelum mencoba.
- Untuk skenario dosen A vs dosen B, perlu dua akun dosen yang berbeda dan
  course yang pengampunya jelas. Jika seeder lokal tidak menyediakan kredensial
  login kedua dosen, jangan menebak password; buktikan dengan test yang membuat
  dua dosen terpisah.
- Untuk materi/tugas/submission/grade, catat ID dari URL/tautan atau database
  latihan. Jangan mengarang ID karena 404 akibat ID tidak ada bukan bukti
  otorisasi berhasil.

#### Langkah per baris

##### 1. Daftar course: scope mahasiswa dan dosen

1. Login sebagai mahasiswa demo dan buka **Mata Kuliah**.
2. Catat course yang muncul. Course tersebut harus merupakan course aktif yang
   diikuti mahasiswa.
3. Sebagai pembanding, logout lalu login sebagai dosen yang mengampu course
   tertentu; buka daftar dosen dan catat daftar course-nya.
4. Buka course yang tidak diikuti mahasiswa atau milik dosen lain lewat akun
   masing-masing. Jangan menganggap nama yang tidak muncul saja sudah cukup;
   gunakan baris 2 untuk mencoba detail langsung.
5. Tunjukkan `Course::scopeVisibleTo()` yang membatasi query. Mahasiswa dibatasi
   enrollment + `active`; dosen dibatasi `lecturer_id`.
6. Test yang relevan:

   ```powershell
   php artisan test --filter=test_student_cannot_see_or_open_non_active_courses_even_when_enrolled
   php artisan test --filter=test_lecturer_course_list_only_contains_courses_they_teach
   ```

##### 2. Detail course: ganti ID untuk mencoba IDOR

1. Login sebagai mahasiswa A, buka course yang memang diikutinya, dan catat ID
   dari URL `/mahasiswa/mata-kuliah/{course}`.
2. Cari ID course aktif yang tidak diikuti mahasiswa tersebut menggunakan akun
   admin (hanya lihat, jangan edit).
3. Kembali ke sesi mahasiswa; ganti hanya angka `{course}` pada URL ke ID target.
4. Periksa Network. Course sendiri diharapkan `200`; course aktif yang tidak
   diikuti dan course draft/arsip diharapkan ditolak `403`. Jangan hanya melihat
   apakah link-nya disembunyikan.
5. Tunjukkan `CoursePolicy::view()` dan `$this->authorize('view', $course)` di
   `CourseController::show()`.
6. Test:

   ```powershell
   php artisan test --filter=test_student_can_only_view_enrolled_courses
   php artisan test --filter=test_demo_student_can_open_first_seeded_course_but_not_second
   ```

##### 3. Route admin-only untuk membuat course

1. Login sebagai mahasiswa atau dosen.
2. Ketik langsung URL `http://<host>/admin/mata-kuliah/create`.
3. Harapannya server menolak dengan `403`; jangan mencoba kirim POST course
   lewat browser demo.
4. Tunjukkan grup `auth` + `role:admin` di `routes/web.php`, lalu
   `CoursePolicy::create()`.
5. API-nya juga menolak mahasiswa bertoken valid yang mengirim POST, tetapi
   percobaan POST cukup dibuktikan dengan:

   ```powershell
   php artisan test --filter=test_student_cannot_create_course
   ```

##### 4. Update/delete course oleh role bukan admin

1. Ini operasi mutasi. **Jangan PATCH/DELETE course demo**, sebab bila ada
   celah request dapat benar-benar mengubah atau menghapus course.
2. Sebutkan request yang diuji: mahasiswa bertoken valid mencoba PATCH
   `/api/v1/courses/{course}`. Kontrol yang diharapkan menolak adalah Policy
   update; harapan status `403` dan data tidak berubah.
3. Tunjukkan `CoursePolicy::update()`/`delete()` serta pemanggilan authorize
   pada API CourseController.
4. Jalankan test yang membuat data di database test dan menguji lifecycle admin:

   ```powershell
   php artisan test --filter=test_admin_can_create_update_and_delete_course_with_rest_status_codes
   ```

5. Untuk penolakan mahasiswa PATCH, `scripts/test-authz.sh` mencakup skenario
   itu tetapi membuat course sementara lewat API dan menghapusnya saat selesai.
   Jalankan hanya pada server/database lokal khusus yang boleh diubah. Jangan
   jalankan script itu pada database demo/produksi.

##### 5. Course yang ditampilkan dashboard

1. Login sebagai mahasiswa A lalu buka Dashboard.
2. Catat course pada bagian course terbaru/ringkasan.
3. Bandingkan dengan course yang memang diikuti mahasiswa; jangan gunakan
   dashboard admin sebagai pembanding karena admin memang dapat melihat semua.
4. Tunjukkan `DashboardController`: query dimulai dari
   `Course::query()->visibleTo($user)` sebelum hitung jumlah atau mengambil
   course terbaru.
5. Test:

   ```powershell
   php artisan test --filter=test_student_dashboard_only_shows_enrolled_courses
   ```

##### 6. Daftar material pada course

1. Login sebagai mahasiswa yang tidak terdaftar di course target, atau dosen
   yang bukan pengampu.
2. Dari admin, catat ID course target yang memang ada. Ketik URL
   `http://<host>/mahasiswa/mata-kuliah/{course}/materials` dengan ID itu.
3. Harapannya `403`; bila route web untuk material mengembalikan tampilan lain,
   cek request GET di Network. Jangan mencoba POST pembuatan material pada
   database demo.
4. Tunjukkan `MaterialPolicy::viewAny()` yang meminta izin melihat course,
   serta `MaterialController::index()` yang mengotorisasi dan menyaring query.
5. Test API list dan akses:

   ```powershell
   php artisan test --filter=test_material_api_requires_course_access_and_returns_a_resource_collection
   ```

##### 7. Detail/update/delete material dari course lain

1. Perlu dua dosen: dosen A pemilik material dan dosen B yang bukan pemilik.
   Catat ID material milik A.
2. Login sebagai B dan buka tautan detail material A (route shallow umumnya
   `/dosen/materials/{material}`; pastikan path lokal dengan
   `php artisan route:list --path=materials`).
3. Request GET diharapkan `403`. Jika mencoba nested URL dengan `{course}` yang
   tidak cocok dengan material, hasil yang diharapkan adalah `404` karena
   pasangan parent-child tidak cocok.
4. Tunjukkan `MaterialPolicy::view/update/delete`, `showScoped()`, dan
   `scopeBindings()` di route.
5. Jangan melakukan PUT/DELETE di data demo. Bukti:

   ```powershell
   php artisan test --filter=test_lecturer_cannot_view_material_from_another_course
   php artisan test --filter=test_lecturer_cannot_create_or_modify_materials_in_another_course
   ```

##### 8. Daftar tugas course

1. Login sebagai mahasiswa yang tidak terdaftar di course target, atau dosen
   B yang bukan pengampu course target.
2. Ketik URL daftar tugas, misalnya
   `/mahasiswa/mata-kuliah/{course}/assignments`, dengan ID course target.
3. Harapannya `403`; mahasiswa pada course miliknya hanya melihat tugas
   `published` di course `active`.
4. Tunjukkan `AssignmentPolicy::viewAny()` dan
   `Assignment::scopeVisibleTo()`.
5. Test:

   ```powershell
   php artisan test --filter=test_student_cannot_list_assignments_for_a_course_they_do_not_take
   php artisan test --filter=test_lecturer_cannot_list_assignments_for_another_lecturers_course
   ```

##### 9. Detail tugas: ID tugas lain atau tugas draft

1. Untuk skenario draft, login sebagai mahasiswa yang terdaftar pada course
   aktif yang memiliki tugas `draft`. Catat ID tugas melalui akun dosen/admin.
2. Ketik URL langsung `/mahasiswa/assignments/{assignment}` dengan ID draft.
   Harapannya `404` dan judul/instruksi draft tidak tampil.
3. Alternatif pemilik: login sebagai dosen B, lalu GET
   `/dosen/assignments/{assignment}` milik dosen A. Harapannya ditolak `403`.
4. Tunjukkan `AssignmentPolicy::view()` serta pemeriksaan status/enrollment.
   Untuk URL nested yang course dan assignment-nya tidak cocok, tunjukkan
   `showScoped()`/`scopeBindings()`; hasilnya `404`.
5. Test:

   ```powershell
   php artisan test --filter=test_student_cannot_view_draft_assignment
   php artisan test --filter=test_scoped_nested_route_rejects_an_assignment_from_another_course
   ```

##### 10. Membuat tugas pada course yang bukan haknya

1. **Jangan mengirim POST ke server demo.**
2. Sebut skenario: mahasiswa mencoba membuat assignment atau dosen B mencoba
   membuatnya di course dosen A. Harapannya `403` dan tidak ada assignment
   baru.
3. Tunjukkan `AssignmentPolicy::create()` dan
   `AssignmentController::store()`. `created_by` seharusnya berasal dari user
   yang login, bukan input bebas.
4. Jalankan test terisolasi:

   ```powershell
   php artisan test --filter=test_student_cannot_create_assignment
   php artisan test --filter=test_lecturer_cannot_create_or_modify_assignments_in_another_course
   ```

##### 11. Mengubah/menghapus tugas milik course lain

1. **Jangan PATCH/DELETE assignment penting/demo.**
2. Sebut request yang dicoba: mahasiswa atau dosen yang bukan pengampu
   mengirim update/delete untuk ID assignment tersebut.
3. Tunjukkan Policy update/delete dan pemeriksaan kecocokan course-assignment.
   Harapannya `403` (atau `404` untuk pasangan nested tidak cocok) dan data
   tetap ada.
4. Jalankan test yang memeriksa penolakan serta bahwa assignment tidak berubah:

   ```powershell
   php artisan test --filter=test_student_cannot_update_or_delete_assignment
   php artisan test --filter=test_lecturer_cannot_create_or_modify_assignments_in_another_course
   ```

##### 12. Akses direktori/akun admin dengan role bukan admin

1. Login sebagai mahasiswa atau dosen.
2. Ketik `/admin/users`; jika perlu ganti ID dengan `/admin/users/{id}`.
3. Harapannya `403`/halaman akses ditolak dan informasi akun tidak tampil.
   Karena middleware menolak sebelum controller, mengganti ID tidak seharusnya
   membantu.
4. Tunjukkan grup `auth` + `role:admin` di `routes/web.php`. Jelaskan bahwa
   otorisasi ini memakai middleware grup route, bukan UserPolicy.
5. Test:

   ```powershell
   php artisan test --filter=test_user_without_admin_role_receives_a_safe_forbidden_page
   ```

   Update role juga mutasi; jangan lakukan pada demo. Bukti test:

   ```powershell
   php artisan test --filter=test_student_cannot_change_a_users_role_through_admin_update_route
   ```

##### 13. Submission milik mahasiswa lain — berhati-hati, tidak ada GET detail

1. Pertama, jelaskan bahwa `keamanan.md` sudah stale: repository memiliki
   route web POST/PUT submission, jadi klaim “belum ada route/controller” tidak
   benar lagi. Saat ini tidak ditemukan API submission atau endpoint GET
   submission individual.
2. Jangan membuat POST upload atau PUT pada submission mahasiswa lain di demo;
   update berpotensi mengubah/menghapus file.
3. Buka `routes/web.php` untuk route
   `/mahasiswa/mata-kuliah/{course}/assignments/{assignment}/submissions/{submission}`
   (PUT), lalu `SubmissionController::update()`: ia mencocokkan course,
   assignment, submission, kemudian memanggil `authorize('update', $submission)`.
4. Buka `SubmissionPolicy::update()`: pemilik harus mahasiswa yang sama dan
   masih memenuhi aturan create/deadline.
5. Bukti yang tersedia sekarang adalah pemeriksaan Policy pada
   `ModelPoliciesTest`; test tersebut membuktikan mahasiswa dapat melihat
   submission sendiri dan tidak dapat melihat submission mahasiswa lain, tetapi
   tidak sama dengan HTTP PUT ke route. Nyatakan batas cakupan test itu secara
   jujur. Jalankan:

   ```powershell
   php artisan test --filter=test_course_material_assignment_submission_and_grade_policies_enforce_ownership
   ```

##### 14. Memberi nilai submission

1. **Jangan POST nilai ke data demo.**
2. Sebut skenario: mahasiswa mencoba memakai endpoint prefix dosen, atau dosen B
   mencoba menilai submission course milik dosen A.
3. Untuk mahasiswa, route dosen dilindungi middleware `role:dosen` (hasil
   seharusnya `403`). Untuk dosen yang salah, Policy grade membandingkan
   `lecturer_id` course dengan ID dosen aktif.
4. Tunjukkan route POST grade di `routes/web.php`,
   `SubmissionController::grade()` yang memanggil `authorize('create', Grade)`,
   lalu `GradePolicy::create()`.
5. Test saat ini memeriksa Policy bahwa dosen pengampu boleh mengubah grade,
   dosen lain/admin tidak boleh. Test tersebut bukan request HTTP mahasiswa ke
   route grade, jadi jelaskan perbedaannya:

   ```powershell
   php artisan test --filter=test_admin_can_view_grade_but_only_course_lecturer_can_change_it
   ```

##### 15. Mengelola enrollment course dosen lain

1. Perlu dosen A pemilik course dan dosen B bukan pemilik. Catat ID course A.
2. Login sebagai dosen B dan buka halaman
   `/dosen/mata-kuliah/{course}/enrollment` dengan ID course A.
3. Harapannya `403`. Cukup buka halaman GET; jangan submit perubahan enrollment.
4. Tunjukkan `EnrollmentController::edit()` yang memanggil
   `authorize('manageEnrollment', $course)` dan `CoursePolicy::manageEnrollment()`
   yang membandingkan role serta `lecturer_id`.
5. Test:

   ```powershell
   php artisan test --filter=test_course_lecturer_can_manage_enrollment_but_other_roles_cannot
   ```

#### Cara menyampaikan hasil yang benar

Untuk baris mana pun, tutup dengan format ini:

> “Saya memakai akun **[role/aktor]** dan mencoba mengakses **[target milik siapa]**.
> Akses yang benar untuk akun saya adalah **[izin/penolakan]**. Hasil aktualnya
> **[HTTP/status dan apakah data terlihat]**. Server memutuskan lewat
> **[middleware/Policy/query dan file]**. Test **[nama test]** membuktikan
> **[assertion spesifik]**. Jika hasil aktual berbeda, saya tidak akan
> mengklaim aman; saya akan jelaskan temuan dan dampaknya.”

Jangan mengatakan “aman karena tombolnya tidak ada”. Bukti yang dicari adalah
request yang benar-benar ditolak oleh server dan penjelasan kontrolnya. Jika
penguji memilih baris 13 atau 14, akui dengan terbuka bahwa tabel keamanan dan
cakupan test belum sepenuhnya sinkron dengan route web aktual.

## Bank pertanyaan interview — contoh jawaban dan kode

### 1. Tunjukkan migrasi yang ditulis. Jelaskan setiap constraint

**Buka dan tunjuk bagian ini (anotasi belajar, bukan komentar yang ditambahkan ke source):**

```php
// database/migrations/2026_09_14_000002_create_course_user_table.php
$table->foreignId('course_id')->constrained()->cascadeOnDelete();
// FK ke courses; enrollment pivot ikut terhapus saat course dihapus.
$table->foreignId('user_id')->constrained()->cascadeOnDelete();
// FK ke users; baris pivot ikut terhapus saat user dihapus.
$table->timestamp('enrolled_at')->useCurrent();
// Waktu enrollment otomatis diisi waktu sekarang.
$table->timestamps();
// created_at dan updated_at untuk catatan perubahan.
$table->unique(['course_id', 'user_id']);
// Pasangan course dan mahasiswa tidak boleh tercatat dua kali.
```

```php
// database/migrations/2026_09_14_000001_create_courses_table.php
$table->string('code')->unique();
// Kode course harus unik.
$table->text('description')->nullable();
// Deskripsi boleh kosong (NULL).
$table->foreignId('lecturer_id')->constrained('users')->restrictOnDelete();
// FK ke users; cegah dosen dihapus jika masih direferensikan course.
$table->enum('status', ['draft', 'active', 'archived'])->default('draft');
// Database membatasi status dan memberi nilai awal draft.
```

Contoh terbaik untuk dibedah adalah
[`create_course_user_table`](../database/migrations/2026_09_14_000002_create_course_user_table.php)
dan [`create_courses_table`](../database/migrations/2026_09_14_000001_create_courses_table.php).

Pada pivot `course_user`:

- `id()` memberi primary key untuk baris enrollment.
- `course_id` dan `user_id` adalah foreign key ke tabel induk.
- `cascadeOnDelete()` membersihkan baris pivot ketika course atau user terkait
  dihapus; baris pivot bukan enrollment yang berdiri sendiri.
- `enrolled_at` mencatat waktu pendaftaran.
- `timestamps()` menyimpan waktu dibuat/diubah.
- `unique(['course_id', 'user_id'])` menjamin satu mahasiswa hanya punya satu
  baris untuk course yang sama.

Pada `courses`, `code` unik mencegah dua course dengan kode sama;
`description` nullable karena deskripsi boleh kosong; `lecturer_id` adalah
foreign key dengan `restrictOnDelete()` agar dosen yang masih mengampu course
tidak terhapus begitu saja; `status` dibatasi menjadi `draft`, `active`, atau
`archived`; SKS disimpan sebagai bilangan bulat kecil.

**Contoh jawaban lisan:**
> Saya meletakkan constraint penting di database, bukan hanya di form, karena
> data bisa datang dari request lain atau dua request bersamaan. Di pivot,
> unique gabungan `(course_id, user_id)` melarang enrollment ganda untuk
> pasangan yang sama tetapi tetap mengizinkan satu mahasiswa ikut banyak
> course. Foreign key menjaga referensi tetap valid. Untuk course yang masih
> punya dosen, `restrictOnDelete` mencegah penghapusan dosen menghasilkan
> relasi yatim.

**Alternatif/konsekuensi:** Tanpa unique gabungan, bug atau request paralel dapat
menambahkan pasangan enrollment yang sama lebih dari sekali. Validasi aplikasi
saja rentan race condition. Constraint database menolak duplikasi pada sumber
kebenaran terakhir. Uji enrollment ada di
[`CourseEnrollmentFeatureTest.php`](../tests/Feature/CourseEnrollmentFeatureTest.php).

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira aturan database cukup berupa kolom dan validasi form.
> Dengan bantuan AI, migration final memakai foreign key, aturan penghapusan,
> unique, dan timestamp yang sesuai kebutuhan. Saya memahami hasilnya sekarang:
> foreign key menjaga referensi, cascade membersihkan pivot saat parent
> dihapus, dan unique gabungan mencegah enrollment ganda. Alasannya, constraint
> database tetap melindungi data meskipun request melewati form atau terjadi
> bersamaan. Kode ini dibantu AI; saya tidak mengklaim menulis seluruh migration
> sendiri.”

### 2. Mengapa `course_user` memiliki unique composite?

**Buka file migration dan sorot constraint ini:**

```php
$table->unique(['course_id', 'user_id']);
// Unik berdasarkan pasangan, bukan tiap kolom secara terpisah.
// Mahasiswa boleh ikut banyak course dan course boleh punya banyak mahasiswa,
// tetapi pasangan mahasiswa-course yang sama hanya boleh satu baris.
```

Unique composite berarti keunikan dinilai atas **pasangan kolom**, bukan atas
masing-masing kolom terpisah. `user_id` boleh berulang untuk course berbeda;
`course_id` boleh berulang untuk mahasiswa berbeda; pasangan identik tidak boleh
berulang.

**Demonstrasi yang bisa dijelaskan:** daftar mahasiswa yang sama dua kali di
course yang sama. Aplikasi juga memvalidasi enrollment, tetapi unique database
tetap penting untuk melindungi data ketika validasi dilewati atau dua request
bersamaan. Tunjukkan test `test_enrollment_rejects_non_students_and_duplicate_ids`
dan constraint migration.

**Jika constraint dicabut:** baris duplikat bisa menggandakan jumlah peserta,
menghasilkan tampilan/penghitungan yang salah, dan merusak perilaku sync.
Jangan benar-benar menghapus constraint di database demo saat ujian; cukup
tunjukkan migration/test atau gunakan database latihan yang dapat dibuat ulang.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira cukup mencegah mahasiswa memilih course yang sama dua
> kali di form. Dengan bantuan AI, implementasi final menambahkan
> `unique(['course_id', 'user_id'])` di database. Alasannya, validasi form saja
> bisa dilewati atau terkena race condition; constraint pasangan menjadi
> perlindungan terakhir. Ini tetap mengizinkan satu mahasiswa ikut banyak
> course dan satu course punya banyak mahasiswa. Kode dibantu AI, dan saya
> menjelaskan keputusan finalnya tanpa mengaku pernah mengganti constraint
> lama yang sebenarnya tidak ada.”

### 3. Apa itu mass assignment? Di mana kode mencegahnya?

**Buka controller dan model berikut, lalu jelaskan kedua lapis allowlist:**

```php
// app/Http/Controllers/CourseController.php, method store()
$validated = $request->validated();
// Ambil hanya field yang lolos rules Form Request.
Course::create($validated);
// Tidak meneruskan seluruh request ke model.
```

```php
// app/Models/Course.php
protected $fillable = [
    'code', 'name', 'description', 'sks', 'lecturer_id', 'status',
];
// Hanya field ini yang boleh diisi lewat mass assignment.
```

Mass assignment adalah mengisi banyak atribut model sekaligus dari array,
misalnya `Course::create($data)`. Jika seluruh request dimasukkan, pengguna
dapat menyisipkan field tambahan yang tidak disediakan form, seperti foreign
key atau field privilege.

Di project, `CourseController::store` dan controller API menggunakan
`$request->validated()`. Model `Course` juga membatasi `$fillable`. `User` punya
perlindungan tambahan: `role` tidak ada dalam `$fillable`; controller akun
mengelola nilainya secara terpisah.

**Contoh jawaban lisan:**
> Saya tidak meneruskan seluruh request ke model. Form Request menentukan
> field dan aturan yang boleh diterima, lalu controller memakai
> `validated()`. `$fillable` menjadi allowlist kedua di level model. Jika
> memakai `$request->all()`, field liar bisa ikut masuk ke alur penyimpanan.

**Penting:** validasi bentuk data bukan otorisasi. Field yang valid masih bisa
diubah oleh aktor yang tidak berhak kalau Policy/middleware dilepas.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira input yang dikirim form bisa langsung diberikan ke
> `Model::create()`. Dengan bantuan AI, alur final memakai Form Request,
> `$request->validated()`, dan `$fillable`. Alasannya, pengguna dapat
> memanipulasi request dan menambah field yang tidak ada di form. `validated()`
> membatasi data pada field yang lolos rules, sedangkan `$fillable` membatasi
> field mass assignment di model. Implementasinya dibantu AI; saya bisa
> menjelaskan lapisan perlindungannya dan tidak mengklaim menulis semua kode
> tersebut sendiri.”

### 4. Mengapa `role` tidak ada di `$fillable`? Di mana diisi?

**Buka dua file ini dan sorot pemisahan role dari mass assignment:**

```php
// app/Models/User.php
protected $fillable = ['name', 'email', 'password', 'nim_nip'];
// role sengaja tidak dimasukkan ke allowlist.
```

```php
// app/Http/Controllers/UserController.php, method store()
$role = $validated['role'];
unset($validated['role']);
// Pisahkan role sebelum create() agar tidak ikut mass-assignment.
$user = User::create($validated);
$user->role = $role;
$user->save();
// Tetapkan role secara eksplisit pada alur pembuatan admin.
```

`role` menentukan hak akses, sehingga tidak boleh berubah hanya karena klien
mengirim `role=admin`. Di
[`User.php`](../app/Models/User.php), `$fillable` hanya berisi atribut profil
yang diizinkan. Di
[`UserController.php`](../app/Http/Controllers/UserController.php), `store()`
mengambil `role` dari nilai yang tervalidasi, menghapusnya dari array sebelum
`User::create($validated)`, lalu menetapkan `$user->role = $role` dan menyimpan.
Route pembuatan akun juga berada di grup `auth` + `role:admin`.

Seeder menetapkan role dengan assignment eksplisit dan `save()`. Ini berbeda
dari membolehkan mass assignment bebas; seeder adalah kode server yang memang
menentukan akun demo.

**Jika dimasukkan ke `$fillable`:** setiap tempat yang memanggil `create()` atau
`update()` dengan input mentah berisiko memungkinkan peningkatan privilege.
Jelaskan lapisan-lapisannya: validasi nilai role, admin-only route, dan
assignment eksplisit.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya menganggap `role` seperti nama atau email, jadi bisa ikut
> diisi mass assignment. Dengan bantuan AI, versi final tidak memasukkan `role`
> ke `$fillable`: role divalidasi, dipisahkan sebelum `User::create()`, lalu
> ditetapkan eksplisit pada alur admin. Alasannya, jika role bebas diambil dari
> input, orang bisa mencoba menaikkan hak akses menjadi admin. Kode dibantu AI;
> yang saya pastikan adalah memahami dan dapat menunjukkan pembatasan final
> serta risikonya.”

### 5. Tunjukkan satu Policy dan jelaskan logikanya

**Buka Policy lalu pemanggilnya di controller:**

```php
// app/Policies/CoursePolicy.php, method view()
return $user->role === 'admin'
    || ($user->role === 'dosen' && $course->lecturer_id === $user->id)
    || ($user->role === 'mahasiswa'
        && $user->courses()->whereKey($course->getKey())->exists());
// Admin boleh; dosen harus pengampu; mahasiswa harus terdaftar.
```

```php
// app/Http/Controllers/CourseController.php, method show()
$this->authorize('view', $course);
// Menjalankan pemeriksaan Policy pada request di server.
```

Gunakan [`CoursePolicy.php`](../app/Policies/CoursePolicy.php):

- `viewAny` hanya mengizinkan tiga role aplikasi.
- `view` memberi akses ke admin; dosen hanya bila `lecturer_id` cocok dengan ID
  user aktif; mahasiswa hanya bila relasi course mereka memiliki course itu.
- `create`, `update`, dan `delete` pada course dibatasi admin.
- `manageEnrollment` mengizinkan admin atau dosen pemilik course.

**Contoh jawaban lisan:**
> Saya pisahkan aturan akses dari tampilan supaya aturan bisa dipakai pada
> berbagai controller dan diuji langsung. Pada `view`, role saja tidak cukup:
> dosen harus pemilik course dan mahasiswa harus terdaftar. Kalau pemeriksaan
> ownership dihapus, user yang sudah login dapat mengganti ID pada URL dan
> mengakses course orang lain.

Controller tetap harus memanggil `$this->authorize(...)` atau
`Gate::authorize(...)`. Policy yang ada tetapi tidak pernah dipanggil tidak
melindungi endpoint.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira cukup memeriksa role—misalnya semua dosen boleh membuka
> semua course. Dengan bantuan AI, Policy final memeriksa juga kepemilikan
> course untuk dosen dan enrollment untuk mahasiswa; controller memanggil
> authorize. Alasannya, akses harus dibatasi pada resource yang memang terkait
> dengan user, bukan hanya pada jenis rolenya. Jika ownership dihilangkan,
> mengganti ID dapat membuka course pihak lain. Implementasi dibantu AI, dan
> saya bisa menjelaskan aturan final serta cara kerjanya.”

### 6. Mengapa `@can` di Blade tidak cukup untuk otorisasi?

**Tunjukkan perbedaan tampilan dan keamanan server:**

```blade
{{-- resources/views/courses/index.blade.php --}}
@can('create', \App\Models\Course::class)
    {{-- Tombol Tambah Mata Kuliah ditampilkan hanya jika diizinkan. --}}
    <a href="{{ route('admin.mata-kuliah.create') }}">Tambah Mata Kuliah</a>
@endcan
```

```php
// app/Http/Controllers/CourseController.php, method store()
$this->authorize('create', Course::class);
// Ini yang memeriksa izin pada server walau request dikirim langsung.
```

`@can` hanya mengatur apakah tombol/link dirender. Pengguna dapat mengetik URL
sendiri, memakai DevTools, atau mengirim request dengan `curl`; request itu
tidak perlu melalui tombol Blade.

Di view course/materi/tugas memang ada `@can` untuk menyembunyikan tindakan yang
tidak sesuai. Perlindungan server ada di middleware route, Policy/Form Request,
dan controller yang memanggil `authorize`/`Gate::authorize`.

**Demo yang aman:** login sebagai mahasiswa dan tunjukkan bahwa tombol mutasi
tidak terlihat; kemudian kirim request mutasi langsung atau buka URL yang
dilindungi. Server harus memberi 403/redirect penolakan, bukan menyimpan data.
Untuk API, tes mahasiswa membuat course mengharapkan `403`:
[`WeekSixApiFeatureTest.php`](../tests/Feature/WeekSixApiFeatureTest.php).

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira menyembunyikan tombol dengan `@can` sudah cukup.
> Dengan bantuan AI, tampilan tetap memakai `@can`, tetapi server juga
> memeriksa Policy/Gate dan middleware pada request. Alasannya, pengguna dapat
> mengetik URL atau mengirim request sendiri tanpa menekan tombol. Jadi
> `@can` membantu UX, sedangkan otorisasi server menegakkan keamanan. Kode
> dibantu AI; saya tidak akan mengaku pernah menambahkan pemeriksaan server
> sendiri kalau bukan perubahan saya.”

### 7. Apa beda HTTP 401 dan 403? Tunjukkan satu contoh tiap status pada API

**Tunjukkan middleware autentikasi route dan izin operasi:**

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    // Tanpa token valid, request berhenti di autentikasi (401).
    Route::post('/courses', [CourseController::class, 'store']);
    // Token valid diteruskan; user tanpa izin ditolak oleh authorize (403).
});
```

- **401 Unauthorized:** request belum terautentikasi—token Sanctum tidak ada,
  tidak valid, atau tidak diterima. Contoh: `GET /api/v1/courses` tanpa header
  bearer token.
- **403 Forbidden:** identitas sudah terautentikasi tetapi tidak punya hak atas
  operasi/resource. Contoh: mahasiswa dengan token valid mencoba
  `POST /api/v1/courses`, atau membuka course yang tidak diikutinya.

**Jawaban singkat yang tepat:** 401 menjawab “siapa Anda?”; 403 menjawab
“identitas Anda dikenali, tetapi Anda tidak boleh melakukan ini.”

Uji otomatisnya ada pada test `test_api_course_list_requires_authentication`,
`test_student_cannot_create_course`, dan
`test_student_cannot_access_course_for_another_student` di
[`WeekSixApiFeatureTest.php`](../tests/Feature/WeekSixApiFeatureTest.php).
API route memakai `auth:sanctum`; aturan izin kemudian diperiksa oleh Policy.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira semua penolakan akses bisa diberi status yang sama.
> Dengan bantuan AI, implementasi API membedakan 401 untuk request tanpa
> autentikasi yang valid dan 403 untuk user yang sudah terautentikasi tetapi
> tidak berhak. Alasannya, client perlu tahu apakah harus login atau memang
> tidak memiliki izin. Kode dibantu AI; saya memahami perbedaan itu dan bisa
> menunjukkan contoh endpoint serta test yang sesuai.”

### 8. Mengapa `$request->validated()` lebih aman daripada `$request->all()`?

**Tunjukkan rules dan pemakaian hasil validasinya:**

```php
// app/Http/Requests/StoreCourseRequest.php
'code' => ['required', 'string', 'max:255', 'unique:courses,code'],
'sks' => ['required', 'integer', 'between:1,6'],
// Server membatasi bentuk dan nilai input.
```

```php
// app/Http/Controllers/CourseController.php, method store()
$validated = $request->validated();
Course::create($validated);
// Simpan hanya field yang lolos rules, bukan seluruh input klien.
```

`validated()` hanya mengembalikan field yang didefinisikan dan lolos rules pada
Form Request. `all()` mengembalikan seluruh input yang dikirim, termasuk field
tak dikenal atau field tersembunyi yang sengaja ditambahkan klien.

Alternatif yang lebih rapuh adalah mempercayai HTML/JavaScript atau memeriksa
field hanya di browser. Browser dikendalikan pengguna; validasi harus di server.
Tetap kombinasikan `validated()` dengan `$fillable` dan otorisasi. `validated()`
sendiri tidak membuktikan bahwa user punya hak mengubah resource.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira validasi di browser sudah cukup dan aturan hanya
> diperlukan agar form mudah diisi. Dengan bantuan AI, versi final memvalidasi
> di server melalui Form Request; contohnya kode harus unik, SKS 1–6, lecturer
> harus user ber-role dosen, dan status harus nilai yang diizinkan. Controller
> menyimpan hasil `validated()`. Alasannya, request dapat dibuat atau
> dimanipulasi di luar browser. Kode dibantu AI; saya dapat menjelaskan rules
> dan konsekuensi jika validasi server dihilangkan.”

### 9. Mengapa filter pencarian disimpan di query string, bukan session?

**Buka `CourseController::index()` dan ikuti query dari input hingga pagination:**

```php
if ($request->filled('q')) {
    $keyword = trim($request->input('q'));
    // Ambil kata kunci pencarian dari request/URL.
}
if ($request->filled('status')) {
    $query->where('status', $request->input('status'));
    // Terapkan filter status langsung pada query database.
}
$courses = $query->latest('courses.created_at')
    ->paginate(10)
    ->withQueryString();
// Pertahankan parameter pencarian pada tautan halaman berikutnya.
```

Course index membaca `q` dan `status` dari URL dan memakai
`paginate(10)->withQueryString()`. Query string membuat state pencarian terlihat
dan dapat dibagikan, di-bookmark, dipakai tombol Back/Forward, serta memiliki
state berbeda pada tab berbeda. Session cenderung menyembunyikan filter dan
membuat tab saling mengubah state.

`withQueryString()` menjaga filter ikut ke link halaman berikutnya. Tanpanya,
berpindah ke halaman 2 dapat kehilangan filter dan menampilkan hasil yang
berbeda. Implementasi filter course ada di
[`CourseController.php`](../app/Http/Controllers/CourseController.php);
filter-nya diuji pada
[`WeekFourFeatureTest.php`](../tests/Feature/WeekFourFeatureTest.php). Pola
pagination dengan query tetap dipertahankan juga diuji pada daftar pengguna di
[`WeekSevenAccessFeatureTest.php`](../tests/Feature/WeekSevenAccessFeatureTest.php).

Alternatif untuk filter yang rumit adalah menyimpannya di database/user
preferences; untuk pencarian daftar yang sementara, URL lebih sederhana dan
transparan. Filter query tetap harus divalidasi bila memiliki domain terbatas.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira filter pencarian lebih mudah disimpan di session.
> Dengan bantuan AI, course index memakai `q` dan `status` pada query string
> dan pagination web memakai `withQueryString()`. Alasannya, URL membuat
> pencarian mudah dibagikan, di-bookmark, dan dipertahankan saat pindah halaman;
> session juga dapat membuat state antar-tab saling mengganggu. Kode dibantu
> AI, dan saya memahami alasan pilihan final tersebut.”

### 10. Mengapa `session()->regenerate()` dipanggil setelah login?

**Buka `LoginController::login()` dan tunjukkan titik setelah autentikasi berhasil:**

```php
if (! $user || ! Auth::attempt([...])) {
    // Jika autentikasi gagal, alur berhenti dengan pesan error.
}
if ($user->role !== $credentials['role']) {
    Auth::logout();
    // Role pilihan harus cocok sebelum login diteruskan.
}
$request->session()->regenerate();
// Ganti session ID hanya setelah autentikasi dan pemeriksaan role berhasil.
return redirect()->intended('/dashboard');
```

Setelah kredensial benar, session ID diganti agar ID anonim sebelum login tidak
terus dipakai setelah autentikasi. Ini mengurangi risiko session fixation.
Session baru tetap membawa state yang diperlukan untuk meneruskan pengalaman
login.

Di `LoginController::login`, regeneration dilakukan setelah `Auth::attempt`
berhasil dan role yang dipilih cocok. Saat logout, kode melakukan
`invalidate()` dan `regenerateToken()` agar session lama dan token CSRF lama
tidak dapat terus dipakai.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mengira setelah `Auth::attempt()` berhasil, aplikasi cukup
> mengarahkan user ke dashboard. Dengan bantuan AI, alur final meregenerasi
> session ID setelah kredensial dan role cocok; saat logout session
> di-invalidate dan CSRF token diperbarui. Alasannya, session ID anonim
> sebaiknya tidak terus dipakai setelah login karena risiko session fixation.
> Kode dibantu AI, dan saya bisa menjelaskan kapan regenerasi dilakukan serta
> apa yang dibersihkan saat logout.”

### 11. Di mana middleware didaftarkan pada Laravel 12, dan mengapa berbeda dari tutorial lama?

**Buka `bootstrap/app.php`, lalu cari pemakaian aliasnya di `routes/web.php`:**

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role' => EnsureUserHasRole::class,
        // Daftarkan alias role ke class middleware aplikasi.
    ]);
})
```

```php
// routes/web.php (cari role:admin, role:dosen, atau role:mahasiswa)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Route memakai alias yang sudah didaftarkan di bootstrap/app.php.
});
```

Pada project ini, alias middleware `role` didaftarkan di
[`bootstrap/app.php`](../bootstrap/app.php) lewat `withMiddleware()`, yang
mengarahkan alias ke `EnsureUserHasRole`. File route kemudian memakai
`role:admin`, `role:dosen`, atau `role:mahasiswa`.

Tutorial Laravel versi lama sering menunjukkan `app/Http/Kernel.php` sebagai
tempat pendaftaran alias. Struktur bootstrap aplikasi Laravel 11/12 yang baru
menggunakan konfigurasi fluent di `bootstrap/app.php`; karena itu ikuti struktur
versi framework yang benar-benar dipakai proyek, bukan menyalin tutorial lama.

Middleware ini menjawab pemeriksaan role umum. Ia tidak menggantikan Policy
untuk pemeriksaan kepemilikan per-course.

**Script nomor 12 — awal pemahaman → implementasi dibantu AI → alasan:**

> “Awalnya saya mencari middleware di `app/Http/Kernel.php` karena itu yang
> sering terlihat di tutorial lama. Dengan bantuan AI dan pemeriksaan struktur
> project Laravel 12, saya memahami bahwa alias `role` didaftarkan di
> `bootstrap/app.php` lewat `withMiddleware()`, lalu dipakai di route. Alasannya,
> konfigurasi harus mengikuti struktur versi framework yang digunakan.
> Middleware membatasi role umum, sedangkan Policy memeriksa kepemilikan
> course. Kode dibantu AI; saya dapat menunjukkan cara pendaftaran finalnya.”

### 12. Wajib: bagian yang dibuat dengan bantuan AI, apa yang diubah dan mengapa?

Jawaban harus jujur dan spesifik pada kontribusi Anda sendiri. Jangan
mengatakan kode tertentu “dibuat AI” atau “ditulis sendiri” jika Anda tidak
mengetahuinya. Sebelum ujian, pilih satu contoh nyata, buka diff/commit-nya,
dan siapkan tiga hal:

1. bagian atau keputusan yang dibantu AI;
2. pemeriksaan yang Anda lakukan (membaca Laravel docs, menjalankan test,
   mencoba alur, atau membandingkan kontrak);
3. perubahan yang Anda putuskan dan alasannya.

Untuk menunjukkan kodenya, pilih salah satu blok anotasi di soal 1–11 di atas,
buka file sumber yang disebut di komentar blok, lalu cocokkan dengan implementasi
sekarang. Tunjukkan commit/diff dengan `git show` hanya jika memang menjadi
bukti bantuan atau perubahan yang kamu ceritakan. Anotasi di panduan ini adalah
alat belajar; komentar tersebut tidak berarti source pernah diubah olehmu.

**Pola jawaban jika AI membantu menulis sebagian besar kode:**

> “Awalnya kebutuhan/pemahaman saya adalah **[kebutuhan atau pemahaman awal
> yang benar]**. Saya memakai AI untuk membantu membuat implementasi. Hasil
> finalnya di project adalah **[kode yang benar-benar ada]**, karena
> **[alasan desain]**. Setelah itu saya **[hal yang benar-benar dilakukan:
> memeriksa, menjalankan test, mencoba demo, atau membuat perubahan spesifik]**.
> Bagian yang saya ubah sendiri adalah **[perubahan yang benar-benar saya
> buat]**; kalau tidak ada perubahan manual, saya tidak mengklaim ada. Saya
> dapat menjelaskan dampak jika kontrol ini dihapus: **[dampak]**.”

Jika AI hanya dipakai untuk menjelaskan konsep atau mencari alternatif,
katakan demikian. Bila Anda tidak dapat menjelaskan sebuah bagian, pelajari
alur request dan test-nya sebelum menyebutnya sebagai kontribusi yang dikuasai.
AI bukan alasan untuk mengaku memahami kode yang belum pernah diverifikasi.

#### Cara memakai pola “awalnya → sekarang” dengan jujur

Untuk tiap pertanyaan, **“awalnya” di dalam script berarti pemahaman awal
sebelum mempelajari solusi**, bukan klaim bahwa pernah ada commit atau versi
kode lama seperti itu. Contoh-contoh di nomor 1–11 adalah kemungkinan cara
menggambarkan pemahaman awal; pakai hanya jika memang menggambarkan dirimu.
Jika tidak, ganti dengan kebutuhan atau pertanyaan yang benar-benar kamu miliki.
Jangan membuat seolah-olah ada kode lama atau perubahan pribadi yang tidak
pernah terjadi.

Pola lisan:

> “Awalnya saya mengira **[pemahaman awal saya]**. Saya memakai AI untuk
> membantu mengimplementasikan solusi; hasil final di project
> **[jelaskan kode yang benar-benar ada]**. Alasannya **[tujuan desain/risiko
> yang dikurangi]**. Saya kemudian **[sebutkan pemeriksaan yang benar-benar
> dilakukan]** dan bisa menunjukkan **[file/test/demo]**. Perubahan yang saya
> buat sendiri adalah **[perubahan yang benar-benar dibuat]**; jika tidak ada,
> saya katakan tidak ada perubahan manual. Saya tidak mengklaim implementasi
> yang dibuat AI sebagai tulisan mandiri.”

Jika memang ada perubahan yang kamu putuskan sendiri setelah AI memberi hasil,
tambahkan perubahan itu hanya jika benar dan dapat dijelaskan. Hindari kalimat
“AI awalnya menulis X, lalu saya memperbaiki menjadi Y” kecuali kamu memiliki
prompt/diff/riwayat nyata yang membuktikan urutan tersebut.

## Kenali kode per anggota kelompok

Daftar anggota di README:

| Anggota | Petunjuk dari riwayat Git | Area yang perlu dipastikan dan dilatih |
|---|---|---|
| Linggar Pramudya (10241039) | Ada commit atas alias `Ling9ar`/`Linggar Pramudya`, misalnya `52bf811` (factory/seeder), `5f1d84b` (akun demo), dan `9c172b1` (penanganan login). | Telusuri seeder/factory dan autentikasi; bila commit Anda menyentuhnya, jelaskan role demo, data seed, validasi login, throttle, dan session regeneration. |
| Melodiva Rosananda (10241041) | Ada commit dengan alias `hanyouji-san`, misalnya `9fe49a9` (CRUD course), `ecc6b44` (route/controller materi dan tugas), dan `26381a1` (perubahan Week 5/halaman 403). | Telusuri course/material/assignment routes dan controller; jelaskan model binding, validasi, operasi CRUD, serta mengapa dosen hanya mengelola course miliknya. |
| Muhammad Daffa Naufarrel Putranada (10241045) | Sejumlah commit memakai email Daffa tetapi nama author `unknown`, misalnya `989dbd9` (CRUD pengguna), `f1ded0d` (model binding), `a49ff3f` (autentikasi), dan `730a2f0` (Policy/query scope). | Telusuri `CoursePolicy`, `AssignmentPolicy`, scope `visibleTo`, middleware/otorisasi, dan tabel IDOR. Cocokkan commit memakai email serta `git blame`; jangan hanya mengandalkan nama author. |
| Muchammad Maulana (10241043) | Ada commit bernama sendiri, misalnya `29e86db` (struktur proyek) dan `6b65e29` (perapian/standardisasi route); banyak milestone berikutnya tercatat sebagai integrasi/commit `Kelompok 5`. | Telusuri perubahan struktur, route naming, test, serta commit yang benar-benar tercatat atas akun Anda. Jangan mengklaim migration atau fitur yang hanya di-commit atas nama kelompok sebelum memverifikasi `git blame`. |

**Catatan penting:** tabel ini petunjuk awal dari sejarah Git, bukan klaim bahwa
seluruh kode fitur tersebut pasti ditulis satu orang. Ada commit bernama
`Kelompok 5`, merge commit, dan commit Daffa dengan nama `unknown`. Yang diuji
adalah kepemilikan yang tercatat dan kemampuan menjelaskan kode. Setiap anggota
sebaiknya membuka source dan tes di area yang muncul atas namanya, termasuk
memahami bagian yang dibuat anggota lain karena alur aplikasi saling terhubung.

Perintah untuk melihat bukti sebelum ujian:

```powershell
git log --all --format="%h %an <%ae> %s" -- app/Policies/CoursePolicy.php
git log --all --format="%h %an <%ae> %s" -- app/Http/Controllers/CourseController.php
git blame app/Policies/CoursePolicy.php
git show --stat <hash-commit>
git show <hash-commit> -- app/Policies/CoursePolicy.php
```

Ganti file dan hash dengan area yang benar-benar menjadi tanggung jawab Anda.
Pahami pula perubahan lanjutan: commit awal mungkin berbeda dari kode sekarang.

## Pertanyaan lanjutan yang mungkin muncul

### Kenapa memakai route model binding? Apakah itu mencegah IDOR?

Binding memetakan `{course}` menjadi model dan mengembalikan 404 jika record
tidak ditemukan. Binding **tidak** membuktikan pemanggil berhak atas model itu.
Policy tetap harus memeriksa akses. Untuk resource nested, `scopeBindings()` atau
pemeriksaan pasangan `course_id` dan child ID mencegah assignment/material dari
course lain dipakai dengan parent URL yang salah. Tunjukkan
`routes/web.php`, `showScoped()` controller, dan skenario pada
[`keamanan.md`](./keamanan.md).

### Kenapa menyaring daftar di query, bukan hanya menyembunyikan baris di Blade?

Jika semua record diambil lalu disembunyikan di view, datanya sudah berada di
server-side response/controller dan mudah bocor lewat bug render, hitungan,
pagination, atau endpoint alternatif. Scope `visibleTo($user)` menyaring baris
di database sebelum dikirim ke view. Periksa scope di `Course`, `Material`, dan
`Assignment`, lalu test query di
[`ModelPoliciesTest.php`](../tests/Feature/ModelPoliciesTest.php).

### Kenapa student hanya melihat assignment `published`?

Draft belum ditujukan untuk mahasiswa. `Assignment::visibleTo()` menyertakan
filter `status=published` dan `AssignmentPolicy::view()` memeriksa status serta
enrollment. Controller detail juga mengembalikan 404 untuk draft agar tidak
membocorkan keberadaan atau instruksi draft. Ada test di
[`WeekFiveFeatureTest.php`](../tests/Feature/WeekFiveFeatureTest.php).

### Apa bedanya validasi dan constraint database?

Validasi memberi penolakan yang jelas sebelum penyimpanan—misalnya SKS 1–6 dan
`lecturer_id` harus menunjuk user role dosen. Constraint database menjamin
integritas sekalipun ada jalur kode lain, kesalahan aplikasi, atau request
bersamaan. Keduanya saling melengkapi, bukan saling menggantikan.

## Checklist latihan akhir

- [ ] Jalankan `migrate:fresh --seed` pada database lokal yang aman; login ke
      tiga akun demo.
- [ ] Demo dari admin membuat course dan enrollment, dosen membuat tugas serta
      materi, mahasiswa melihat course yang diikuti.
- [ ] Tunjuk migration course dan pivot; jelaskan setiap foreign key, unique,
      nullable/default, dan efek penghapusan.
- [ ] Jelaskan satu request dari route sampai validasi, otorisasi, model, lalu
      response/view.
- [ ] Tunjukkan contoh 401 dan 403; jelaskan mengapa keduanya berbeda.
- [ ] Uji satu IDOR sesuai tabel `keamanan.md`, sebutkan aktor, resource,
      kontrol, dan hasilnya.
- [ ] Buka `git log`/`git blame` untuk file yang tercatat atas nama Anda.
- [ ] Siapkan contoh bantuan AI yang benar-benar terjadi dan perubahan/verifikasi
      yang benar-benar Anda lakukan.
- [ ] Sebutkan kendala yang belum berjalan dengan jujur sebelum mulai demo.

## Format jawaban yang kuat

Saat menjawab pertanyaan apa pun, gunakan empat bagian berikut:

1. **Tujuan:** risiko atau kebutuhan apa yang sedang ditangani?
2. **Implementasi:** berkas/method/aturan mana yang melakukannya?
3. **Pilihan desain:** mengapa pendekatan ini dipilih dibanding alternatif?
4. **Bukti dan konsekuensi:** test/demo apa yang membuktikan, dan apa yang
   rusak atau berisiko bila kontrol tersebut dihapus?

Contoh kalimat pembuka: “Tujuan kontrol ini adalah … Implementasinya ada di …
Saya memilih cara ini karena … Alternatifnya … Kalau kontrol ini dihapus …
Test yang membuktikannya adalah …”
