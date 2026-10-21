# Perintah Test Otomatis untuk 15 Skenario IDOR

Dokumen ini berisi command untuk menjalankan test yang berkaitan dengan 15
skenario pada [keamanan.md](./keamanan.md). Jalankan command dari folder utama
proyek KampusLMS di PowerShell:

```powershell
cd C:\laragon\www\kampuslms-kelompok-05
```

## Cara membaca command dan hasilnya

Contoh:

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_student_can_only_view_enrolled_courses"
```

- `php artisan test` menjalankan test Laravel.
- `tests\Feature\WeekFiveFeatureTest.php` memilih file test.
- `--filter="..."` memilih fungsi test berdasarkan namanya.
- Test PHP menjalankan request/Policy dengan data pengujian, memeriksa hasil
  melalui assertion, lalu Laravel membersihkan database testing. Ini berbeda
  dari `scripts\test-authz.sh`, yaitu skrip shell yang benar-benar mengirim
  request API ke server.
- `PASS` berarti assertion di test yang dipilih berhasil. `FAIL` berarti
  hasilnya tidak sesuai harapan test; baca pesan kegagalan sebelum menarik
  kesimpulan.

Test feature menggunakan database testing dan data yang dibuat untuk
pengujian. Pastikan konfigurasi testing proyek tidak menunjuk ke database
demo atau produksi. Jangan menjalankan operasi mutasi secara manual pada data
demo untuk membuktikan skenario.

## Command per skenario

### 1. Daftar course dibatasi sesuai role

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_lecturer_course_list_only_contains_courses_they_teach"
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_student_cannot_see_or_open_non_active_courses_even_when_enrolled"
```

Test pertama memeriksa dosen hanya melihat course yang diajarnya. Test kedua
memeriksa mahasiswa tidak melihat atau membuka course nonaktif meskipun sudah
terdaftar.

**Rujukan kode aplikasi:** [CourseController.php](../app/Http/Controllers/CourseController.php)
menerapkan `visibleTo()` pada daftar; [Course.php](../app/Models/Course.php)
mendefinisikan scope tersebut; [CoursePolicy.php](../app/Policies/CoursePolicy.php)
menentukan hak melihat daftar course.

### 2. Mahasiswa hanya membuka course yang diikuti

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_student_can_only_view_enrolled_courses"
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_demo_student_can_open_first_seeded_course_but_not_second"
```

Test memeriksa mahasiswa dapat membuka course yang diikuti, tetapi tidak dapat
membuka course yang tidak diikuti.

**Rujukan kode aplikasi:** [CoursePolicy.php](../app/Policies/CoursePolicy.php)
membatasi akses detail; [CourseController.php](../app/Http/Controllers/CourseController.php)
memanggil otorisasi sebelum menampilkan course.

### 3. Hanya admin yang boleh mengakses fungsi admin atau membuat course

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php) dan
[WeekSixApiFeatureTest.php](../tests/Feature/WeekSixApiFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_user_without_admin_role_receives_a_safe_forbidden_page"
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_student_cannot_create_course"
```

Test pertama memeriksa role non-admin ditolak dari halaman admin. Test kedua
memeriksa mahasiswa tidak dapat membuat course melalui API.

**Rujukan kode aplikasi:** [web.php](../routes/web.php) membatasi grup route
admin dengan middleware role; [CoursePolicy.php](../app/Policies/CoursePolicy.php)
membatasi `create`; [Api/CourseController.php](../app/Http/Controllers/Api/CourseController.php)
memeriksa Policy saat membuat course API.

### 4. Update dan delete course

File: [WeekSixApiFeatureTest.php](../tests/Feature/WeekSixApiFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_admin_can_create_update_and_delete_course_with_rest_status_codes"
```

Test ini memeriksa CRUD course oleh admin dan status HTTP API yang diharapkan.
Test tersebut bukan bukti langsung bahwa mahasiswa ditolak saat mencoba
PATCH/DELETE.

**Rujukan kode aplikasi:** [Api/CourseController.php](../app/Http/Controllers/Api/CourseController.php)
memanggil Policy untuk update/delete; [CoursePolicy.php](../app/Policies/CoursePolicy.php)
menentukan role yang diizinkan.

Penolakan mutasi juga diuji oleh `scripts\test-authz.sh`, tetapi script itu
mengirim request mutasi dan membuat lalu menghapus course sementara. Jalankan
hanya pada server/database lokal khusus yang memang boleh diubah—jangan pada
database demo atau produksi.

### 5. Course pada dashboard sesuai role

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_student_dashboard_only_shows_enrolled_courses"
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_lecturer_dashboard_only_shows_their_courses_and_role_navigation"
```

Test memastikan dashboard mahasiswa dan dosen hanya menampilkan course yang
sesuai dengan cakupan masing-masing.

**Rujukan kode aplikasi:** [DashboardController.php](../app/Http/Controllers/DashboardController.php)
menggunakan `Course::visibleTo($user)` sebelum menampilkan ringkasan course.

### 6. Daftar material dibatasi akses course

File: [WeekSixApiFeatureTest.php](../tests/Feature/WeekSixApiFeatureTest.php) dan
[WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_material_api_requires_course_access_and_returns_a_resource_collection"
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_enrolled_student_can_view_course_materials"
```

Test pertama memeriksa daftar material API dengan aturan akses course. Test
kedua memeriksa mahasiswa terdaftar dapat melihat material course-nya.

**Rujukan kode aplikasi:** [Api/MaterialController.php](../app/Http/Controllers/Api/MaterialController.php)
dan [MaterialController.php](../app/Http/Controllers/MaterialController.php)
membatasi daftar material dengan otorisasi dan scope; [MaterialPolicy.php](../app/Policies/MaterialPolicy.php)
memeriksa izin melihat material/course.

### 7. Dosen tidak boleh melihat atau mengubah material course dosen lain

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php) dan
[WeekSevenCrudFeatureTest.php](../tests/Feature/WeekSevenCrudFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_lecturer_cannot_view_material_from_another_course"
php artisan test tests\Feature\WeekSevenCrudFeatureTest.php --filter="test_lecturer_cannot_create_or_modify_materials_in_another_course"
```

Test pertama memeriksa penolakan saat dosen membuka material course lain. Test
kedua memeriksa dosen tidak dapat membuat atau mengubah material pada course
yang bukan haknya.

**Rujukan kode aplikasi:** [MaterialController.php](../app/Http/Controllers/MaterialController.php)
memanggil Policy saat membaca/mengubah; [MaterialPolicy.php](../app/Policies/MaterialPolicy.php)
memeriksa akses lewat course pemilik; [web.php](../routes/web.php) mendaftarkan
route nested dengan `scopeBindings()`.

### 8. Daftar tugas dibatasi akses course dan status tugas

File: [WeekSixApiFeatureTest.php](../tests/Feature/WeekSixApiFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_student_cannot_list_assignments_for_a_course_they_do_not_take"
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_lecturer_cannot_list_assignments_for_another_lecturers_course"
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_nested_assignment_list_filters_draft_assignments_for_student"
```

Test memeriksa mahasiswa/dosen tidak dapat melihat daftar tugas di luar
cakupannya dan mahasiswa tidak menerima tugas draft dalam daftar.

**Rujukan kode aplikasi:** [Api/AssignmentController.php](../app/Http/Controllers/Api/AssignmentController.php)
dan [AssignmentController.php](../app/Http/Controllers/AssignmentController.php)
membatasi daftar; [Assignment.php](../app/Models/Assignment.php) menyediakan
scope visibilitas; [AssignmentPolicy.php](../app/Policies/AssignmentPolicy.php)
menentukan akses ke course/tugas.

### 9. Detail tugas: draft atau tugas pada course lain

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_student_cannot_view_draft_assignment"
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_scoped_nested_route_rejects_an_assignment_from_another_course"
```

Test memastikan mahasiswa tidak dapat membuka tugas draft dan route nested
menolak pasangan course dan tugas yang tidak cocok.

**Rujukan kode aplikasi:** [AssignmentController.php](../app/Http/Controllers/AssignmentController.php)
mengotorisasi detail; [AssignmentPolicy.php](../app/Policies/AssignmentPolicy.php)
memeriksa status dan hak akses; [web.php](../routes/web.php) memakai
`scopeBindings()` untuk route nested.

### 10. Tidak boleh membuat tugas pada course yang bukan haknya

File: [WeekSixApiFeatureTest.php](../tests/Feature/WeekSixApiFeatureTest.php) dan
[WeekSevenCrudFeatureTest.php](../tests/Feature/WeekSevenCrudFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_student_cannot_create_assignment"
php artisan test tests\Feature\WeekSevenCrudFeatureTest.php --filter="test_lecturer_cannot_create_or_modify_assignments_in_another_course"
```

Test memastikan mahasiswa tidak dapat membuat tugas dan dosen tidak dapat
mengelola tugas pada course dosen lain.

**Rujukan kode aplikasi:** [Api/AssignmentController.php](../app/Http/Controllers/Api/AssignmentController.php)
dan [AssignmentController.php](../app/Http/Controllers/AssignmentController.php)
memeriksa izin pembuatan; [AssignmentPolicy.php](../app/Policies/AssignmentPolicy.php)
membatasi pembuatan pada admin/pengampu course.

### 11. Tidak boleh mengubah atau menghapus tugas course lain

File: [WeekSixApiFeatureTest.php](../tests/Feature/WeekSixApiFeatureTest.php) dan
[WeekSevenCrudFeatureTest.php](../tests/Feature/WeekSevenCrudFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekSixApiFeatureTest.php --filter="test_student_cannot_update_or_delete_assignment"
php artisan test tests\Feature\WeekSevenCrudFeatureTest.php --filter="test_lecturer_cannot_create_or_modify_assignments_in_another_course"
```

Test pertama memeriksa mahasiswa ditolak ketika mencoba mengubah atau
menghapus tugas. Test kedua memeriksa dosen tidak dapat mengelola tugas pada
course dosen lain.

**Rujukan kode aplikasi:** [Api/AssignmentController.php](../app/Http/Controllers/Api/AssignmentController.php)
memanggil Policy sebelum update/delete; [AssignmentPolicy.php](../app/Policies/AssignmentPolicy.php)
memeriksa hak pengelolaan; route nested di [web.php](../routes/web.php)
memastikan pasangan course dan tugas konsisten.

### 12. Bukan admin tidak boleh mengakses atau mengubah akun

File: [WeekFiveFeatureTest.php](../tests/Feature/WeekFiveFeatureTest.php) dan
[WeekSevenCrudFeatureTest.php](../tests/Feature/WeekSevenCrudFeatureTest.php)

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php --filter="test_user_without_admin_role_receives_a_safe_forbidden_page"
php artisan test tests\Feature\WeekSevenCrudFeatureTest.php --filter="test_student_cannot_change_a_users_role_through_admin_update_route"
```

Test memeriksa role non-admin ditolak dari halaman admin dan mahasiswa tidak
dapat mengubah role pengguna lewat route admin.

**Rujukan kode aplikasi:** [web.php](../routes/web.php) menempatkan route
pengguna dalam grup `auth` + `role:admin`; [UserController.php](../app/Http/Controllers/UserController.php)
mengelola pengguna dan mencegah field role diubah melalui input biasa.

### 13. Submission milik mahasiswa lain

File: [ModelPoliciesTest.php](../tests/Feature/ModelPoliciesTest.php)

```powershell
php artisan test tests\Feature\ModelPoliciesTest.php --filter="test_course_material_assignment_submission_and_grade_policies_enforce_ownership"
```

Test memeriksa Policy kepemilikan submission, termasuk mahasiswa tidak
berwenang melihat submission mahasiswa lain. Ini bukan test request HTTP GET
ke halaman detail submission. Saat ini route web submission yang aktif
melakukan POST upload dan PUT update; tidak ditemukan route GET detail
submission.

**Rujukan kode aplikasi:** [SubmissionPolicy.php](../app/Policies/SubmissionPolicy.php)
mendefinisikan aturan pemilik; [SubmissionController.php](../app/Http/Controllers/SubmissionController.php)
memanggil Policy pada create/update; route aktifnya ada di [web.php](../routes/web.php).
Catatan: baris 13 di [keamanan.md](./keamanan.md) masih perlu diselaraskan
dengan route aktual.

### 14. Hanya dosen pengampu yang boleh memberi nilai

File: [ModelPoliciesTest.php](../tests/Feature/ModelPoliciesTest.php),
[WeekSevenAccessFeatureTest.php](../tests/Feature/WeekSevenAccessFeatureTest.php),
dan [SubmissionUploadFeatureTest.php](../tests/Feature/SubmissionUploadFeatureTest.php)

```powershell
php artisan test tests\Feature\ModelPoliciesTest.php --filter="test_course_material_assignment_submission_and_grade_policies_enforce_ownership"
php artisan test tests\Feature\WeekSevenAccessFeatureTest.php --filter="test_admin_can_view_grade_but_only_course_lecturer_can_change_it"
php artisan test tests\Feature\SubmissionUploadFeatureTest.php --filter="test_student_can_upload_submission_and_dosen_can_grade_it"
```

Test memeriksa aturan Policy terkait grade dan alur penilaian yang sah oleh
dosen. Belum ada test HTTP khusus yang mengirim request penilaian sebagai
mahasiswa atau dosen yang bukan pengampu.

**Rujukan kode aplikasi:** [SubmissionController.php](../app/Http/Controllers/SubmissionController.php)
memeriksa izin sebelum memberi nilai; [GradePolicy.php](../app/Policies/GradePolicy.php)
menentukan siapa yang boleh mengelola grade; route POST penilaian ada di
[web.php](../routes/web.php). Catatan: baris 14 di [keamanan.md](./keamanan.md)
masih perlu diselaraskan karena route penilaian web sudah aktif.

### 15. Dosen tidak boleh mengelola enrollment course dosen lain

File: [CourseEnrollmentFeatureTest.php](../tests/Feature/CourseEnrollmentFeatureTest.php)

```powershell
php artisan test tests\Feature\CourseEnrollmentFeatureTest.php --filter="test_course_lecturer_can_manage_enrollment_but_other_roles_cannot"
```

Test memastikan dosen pengampu dapat mengelola enrollment, sedangkan dosen
lain dan mahasiswa ditolak.

**Rujukan kode aplikasi:** [EnrollmentController.php](../app/Http/Controllers/EnrollmentController.php)
memanggil `manageEnrollment`; [CoursePolicy.php](../app/Policies/CoursePolicy.php)
memeriksa apakah pengguna admin atau dosen pemilik course; [web.php](../routes/web.php)
mendaftarkan route GET/PUT enrollment.

## Menjalankan semua test terkait sekaligus

Untuk menjalankan seluruh test yang relevan dari beberapa file:

```powershell
php artisan test tests\Feature\WeekFiveFeatureTest.php tests\Feature\WeekSixApiFeatureTest.php tests\Feature\WeekSevenCrudFeatureTest.php tests\Feature\ModelPoliciesTest.php tests\Feature\WeekSevenAccessFeatureTest.php tests\Feature\SubmissionUploadFeatureTest.php tests\Feature\CourseEnrollmentFeatureTest.php
```

Untuk demo UTS, jalankan hanya test yang terkait dengan skenario yang dipilih
penguji. Test otomatis adalah bukti pendukung; tetap jelaskan aktor, resource,
kontrol server-side, dan hasil yang diharapkan.
