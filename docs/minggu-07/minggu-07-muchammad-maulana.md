# Minggu 7 — Autentikasi, Otorisasi, dan Validasi Menyeluruh

## Nama: Muchammad Maulana
## NIM: 10241043
## Proyek: KampusLMS — Laravel 12

---

# READ — Alur Autentikasi dan Otorisasi

## 1. Autentikasi berbeda dari otorisasi

Autentikasi menjawab **siapa pengguna yang sedang mengakses aplikasi**. Pada web KampusLMS, pengguna mengirim email, kata sandi, dan role pilihan ke endpoint login. [`LoginController`](../../app/Http/Controllers/Auth/LoginController.php) memvalidasi input dan memanggil `Auth::attempt()` untuk memeriksa kredensial.

Otorisasi menjawab **apa yang boleh dilakukan pengguna tersebut**. KampusLMS memakai tiga lapisan yang saling melengkapi:

- Middleware [`EnsureUserHasRole`](../../app/Http/Middleware/EnsureUserHasRole.php) membatasi area umum berdasarkan role. Pengguna yang belum login menerima 401, sedangkan pengguna yang sudah login tetapi role-nya tidak sesuai menerima 403.
- Policy memeriksa tindakan pada objek tertentu, misalnya apakah dosen yang sedang login memang mengampu course yang ingin diubah.
- Scope query membatasi daftar data sebelum dikirim ke view atau API, supaya pengguna tidak menerima data yang bukan cakupannya.

## 2. Siklus login, logout, dan kata sandi

Setelah kredensial cocok, login juga memastikan role yang dipilih sama dengan role akun. Aplikasi membatasi percobaan login menggunakan `RateLimiter`; bila berhasil, session ID diregenerasi sebelum pengguna diarahkan ke dashboard. Saat logout, session di-logout, di-invalidate, dan token CSRF diregenerasi.

Model [`User`](../../app/Models/User.php) memakai cast `password => hashed`, sehingga kata sandi di-hash saat disimpan. Hash bukan enkripsi dan tidak bisa dikembalikan ke kata sandi asli. Fitur lupa/reset kata sandi karena itu menggunakan token reset, bukan membaca kata sandi lama. Pendaftaran akun dilakukan admin melalui pengelolaan pengguna, bukan pendaftaran publik.

## 3. Pemeriksaan akses per objek dengan Policy

Policy memeriksa objek yang diminta, bukan hanya role umum. Contohnya:

- [`CoursePolicy`](../../app/Policies/CoursePolicy.php) membatasi detail course kepada admin, dosen pemilik course, atau mahasiswa yang terdaftar pada course aktif. Mutasi course hanya diizinkan untuk admin.
- [`MaterialPolicy`](../../app/Policies/MaterialPolicy.php) dan [`AssignmentPolicy`](../../app/Policies/AssignmentPolicy.php) mewarisi pemeriksaan akses dari course. Dosen hanya mengelola materi dan tugas pada course yang dia ampu; mahasiswa hanya membaca tugas terbit pada course aktif yang diikutinya.
- [`SubmissionPolicy`](../../app/Policies/SubmissionPolicy.php) membatasi submission kepada pemiliknya, dosen pengampu, atau admin untuk melihat; mahasiswa hanya dapat membuat atau mengubah submission miliknya sesuai status tugas dan deadline.
- [`GradePolicy`](../../app/Policies/GradePolicy.php) membolehkan mahasiswa melihat nilai submission miliknya dan dosen pengampu mengelola nilai.

Di proyek ini, controller dapat memanggil `$this->authorize()` karena kelas dasar [`Controller`](../../app/Http/Controllers/Controller.php) secara eksplisit menggunakan trait `AuthorizesRequests`. Form Request seperti [`StoreMaterialRequest`](../../app/Http/Requests/StoreMaterialRequest.php) juga memeriksa izin pada method `authorize()` sebelum validasi dan proses penyimpanan dilanjutkan.

`@can` di Blade berguna untuk menampilkan atau menyembunyikan tombol sesuai izin, tetapi bukan pengganti Policy di server. Request langsung ke URL tetap harus diperiksa oleh controller atau Form Request.

## 4. Pembatasan daftar di query

[`Course::visibleTo()`](../../app/Models/Course.php) menerapkan cakupan daftar di database:

- admin mendapat semua course;
- dosen mendapat course yang `lecturer_id`-nya sesuai dengan akunnya;
- mahasiswa mendapat course aktif yang memiliki enrollment untuk dirinya;
- role yang tidak dikenal mendapat query kosong.

Pola serupa dipakai untuk daftar material dan assignment. Controller [`CourseController`](../../app/Http/Controllers/CourseController.php) menggunakan scope tersebut sebelum pencarian dan pagination. Dashboard juga membatasi query course dan statistik berdasarkan role melalui [`DashboardController`](../../app/Http/Controllers/DashboardController.php). Dengan demikian, data yang tidak boleh dilihat tidak sekadar disembunyikan di view.

Daftar endpoint dan kontrol server-side untuk risiko IDOR dicatat pada [tabel titik rawan IDOR](../keamanan.md).

---

# BREAK — Skenario Kerusakan dan Pengamatan

Skenario berikut dirangkum dari feature test dan aturan akses proyek. Demonstrasi dilakukan pada database test/factory, bukan dengan mengubah data produksi.

| # | Skenario | Hasil yang diharapkan/diamati | Perlindungan |
|---|---|---|---|
| 1 | Menghapus `session()->regenerate()` setelah login | Login mungkin tetap berhasil, tetapi ID session tidak diperbarui setelah autentikasi | [`LoginController`](../../app/Http/Controllers/Auth/LoginController.php) meregenerasi session setelah kredensial dan role tervalidasi. |
| 2 | Login berulang dengan kata sandi salah | Setelah ambang percobaan tercapai, request berikutnya ditolak sementara | `RateLimiter` membatasi percobaan berdasarkan email dan IP; skenario diuji di [LoginThrottleTest](../../tests/Feature/LoginThrottleTest.php). |
| 3 | Mahasiswa mengakses area admin atau dosen | Request ditolak dengan 403, bukan hanya tombol admin yang disembunyikan | Route memakai middleware `auth` dan `role`; role diperiksa di server oleh [`EnsureUserHasRole`](../../app/Http/Middleware/EnsureUserHasRole.php). |
| 4 | Dosen A membuka atau mengubah course milik Dosen B | Akses ditolak | [`CoursePolicy`](../../app/Policies/CoursePolicy.php) membandingkan pemilik course dengan user aktif; test CRUD memverifikasi dosen tidak dapat mengubah course dosen lain. |
| 5 | Mahasiswa membuka submission milik mahasiswa lain | Mahasiswa lain tidak dapat melihat submission tersebut | [`SubmissionPolicy`](../../app/Policies/SubmissionPolicy.php) membandingkan `user_id` submission dengan ID user aktif; kombinasi akses Policy diuji di [ModelPoliciesTest](../../tests/Feature/ModelPoliciesTest.php). |
| 6 | Mengganti daftar menjadi `Course::query()` tanpa scope | Dosen atau mahasiswa berisiko menerima course di luar cakupannya | `visibleTo()` menyaring di query; test memastikan course yang terlihat berbeda sesuai role. |
| 7 | Mengirim `role=admin` melalui form profil pengguna | Pengguna tidak boleh menaikkan hak aksesnya sendiri | Route pengelolaan pengguna dibatasi untuk admin dan field `role` tidak termasuk `$fillable` pada model [`User`](../../app/Models/User.php). |
| 8 | Menghapus pemeriksaan Policy dari controller tetapi mempertahankan `@can` di Blade | Tombol dapat tersembunyi, tetapi request langsung masih berpotensi mencapai endpoint | Otorisasi wajib ditegakkan di controller/Form Request; Blade hanya mengatur tampilan. |
| 9 | Mengubah password reset menggunakan token tidak valid | Password lama harus tetap berlaku | [PasswordRecoveryFeatureTest](../../tests/Feature/PasswordRecoveryFeatureTest.php) memastikan token tidak valid tidak mengubah password. |

---

# FIX — Perlindungan yang Diterapkan

1. Menggunakan autentikasi session web melalui `Auth::attempt()` dan memastikan role akun sesuai dengan role yang dipilih pada form login.
2. Meregenerasi session setelah login berhasil; saat logout, mengakhiri session dan meregenerasi token CSRF.
3. Membatasi percobaan login dan menggunakan pesan gagal login umum agar respons tidak mengungkap apakah email tertentu terdaftar.
4. Menyimpan password melalui cast `hashed`, menyembunyikan atribut password dan remember token dari serialisasi model, serta menyediakan alur reset password menggunakan token.
5. Membatasi pembuatan dan pengelolaan akun melalui route admin. `role` tidak mass assignable, sehingga tidak dapat diubah dengan mengirim field tambahan pada request biasa.
6. Memakai middleware `auth` dan `role:<role>` untuk area admin, dosen, dan mahasiswa; status 401 dan 403 dibedakan.
7. Menambahkan Policy untuk Course, Material, Assignment, Submission, dan Grade, lalu memanggil otorisasi di server sebelum aksi yang dilindungi.
8. Menggunakan `authorize()` pada Form Request terkait agar pemeriksaan izin dilakukan sebelum data tervalidasi diproses.
9. Membatasi query daftar melalui scope `visibleTo()` dan relasi user/course, bukan mengambil semua record lalu menyaringnya di view.
10. Menguji aturan akses per role, kepemilikan objek, dashboard, pembatasan daftar, pembuatan akun admin, logout, throttle login, dan reset password dengan feature test.

---

# BUILD — Hasil Minggu 7

Implementasi autentikasi dan otorisasi KampusLMS menyediakan:

- login dan logout berbasis session;
- akun admin, dosen, dan mahasiswa dengan akses yang dibatasi;
- pengelolaan akun oleh admin serta pemulihan kata sandi dengan token;
- dashboard dan daftar data yang disesuaikan dengan role;
- pemeriksaan Policy untuk akses objek dan perubahan data;
- pembatasan query daftar untuk mengurangi kebocoran data dan IDOR;
- feature test untuk memastikan akses yang diizinkan maupun yang harus ditolak.

### Verifikasi

Jalankan test yang mencakup Policy, akses minggu 7, CRUD materi/tugas, autentikasi admin, reset password, dan throttle login:

```powershell
php artisan test tests\Feature\ModelPoliciesTest.php tests\Feature\WeekSevenAccessFeatureTest.php tests\Feature\WeekSevenCrudFeatureTest.php tests\Feature\AdminAuthenticationManagementTest.php tests\Feature\PasswordRecoveryFeatureTest.php tests\Feature\LoginThrottleTest.php
```

Kalimat inti untuk interview:

> “Autentikasi memastikan siapa yang login, sedangkan otorisasi menentukan tindakan dan data yang boleh diakses. Di KampusLMS saya membatasi area dengan middleware role, objek dengan Policy, dan daftar dengan query scope. Pemeriksaan dilakukan di server, bukan hanya dengan menyembunyikan tombol di Blade. Session diregenerasi setelah login, password di-hash, dan aturan akses diuji dengan feature test.”
