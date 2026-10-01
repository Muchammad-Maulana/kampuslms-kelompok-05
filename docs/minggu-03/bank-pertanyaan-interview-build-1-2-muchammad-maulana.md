# Bank Pertanyaan Interview Build 1 dan 2

Nama: Muchammad Maulana  
NIM: 10241043  
Kelas: A

> Gunakan format ini saat belajar: baca pertanyaan, jawab dengan kalimat sendiri, lalu buka file yang disebutkan untuk membuktikan jawaban.

## A. Pertanyaan Umum

### 1. Apa jobdesk kamu pada Week 3?

> Saya mengerjakan Build nomor 1 dan 2. Build 1 adalah migration database, sedangkan Build 2 adalah model Eloquent dan relasinya. Saya memastikan struktur tabel, foreign key, constraint, relasi, `$fillable`, casting, dan migration reversal sesuai kebutuhan LMS.

**Tunjukkan:** folder `database/migrations/` dan `app/Models/`.

### 2. Apa perbedaan migration dan model?

> Migration menentukan bentuk database, seperti tabel, kolom, foreign key, dan constraint. Model adalah representasi tabel di Laravel yang menyediakan relasi dan akses data dari kode PHP.

### 3. Mengapa memakai migration, bukan phpMyAdmin?

> Migration dapat disimpan di Git, di-review, dijalankan ulang, dan menghasilkan struktur database yang sama pada setiap komputer anggota kelompok.

### 4. Bagaimana Build 1 dan 2 dipakai oleh CRUD?

> Migration menyediakan tabel dan aturan database. Model dipakai controller untuk mengambil, membuat, mengubah, dan menghapus data. Jadi CRUD bergantung pada migration dan model.

### 5. Bagaimana kamu memverifikasi kode dari AI?

> Saya mencocokkannya dengan Laravel 12, membaca setiap migration dan model, menjalankan `migrate:fresh --seed`, `migrate:refresh --seed`, test, dan memeriksa hasil data. Saya tidak langsung menerima kode tanpa memahami dan mengujinya.

## B. Pertanyaan Urutan Migration

### 6. Mengapa `users` dibuat lebih dahulu?

> Karena users adalah parent yang dirujuk oleh `courses.lecturer_id`, `course_user.user_id`, dan `submissions.user_id`. Tabel parent harus ada sebelum foreign key child dibuat.

**Tunjukkan:** `database/migrations/0001_01_01_000000_create_users_table.php`.

### 7. Mengapa `grades` dibuat paling akhir?

> Karena grades memiliki foreign key `submission_id`. Tabel submissions harus tersedia terlebih dahulu.

### 8. Apa yang terjadi jika urutan migration salah?

> Migration dapat gagal ketika membuat foreign key karena tabel atau kolom parent belum tersedia.

### 9. Apa fungsi `$table->id()`?

> Membuat primary key auto-increment yang menjadi identitas unik setiap record.

### 10. Apa fungsi `foreignId()`?

> Membuat kolom foreign key dengan tipe yang sesuai dengan primary key Laravel.

### 11. Apa fungsi `constrained()`?

> Membuat foreign key ke tabel parent berdasarkan konvensi nama. Contohnya `course_id` mengarah ke tabel `courses`.

### 12. Apa fungsi `nullable()`?

> Mengizinkan nilai NULL pada kolom yang memang tidak selalu tersedia, seperti deskripsi, file, score, atau waktu submission.

## C. Pertanyaan Setiap Migration

### 13. Apa fungsi tabel `users`?

> Menyimpan akun admin, dosen, dan mahasiswa. Email dibuat unique, role memiliki default student, dan tabel juga memiliki kebutuhan bawaan Laravel seperti sessions.

**Tunjukkan:** `create_users_table.php`, bagian `email`, `role`, dan `down()`.

### 14. Mengapa email dibuat unique?

> Agar satu email tidak dapat dipakai oleh lebih dari satu akun.

### 15. Mengapa role memiliki default `student`?

> Agar user baru tidak otomatis menjadi admin atau dosen jika server belum menetapkan role secara eksplisit.

### 16. Apa fungsi tabel `courses`?

> Menyimpan mata kuliah, kode, nama, deskripsi, jumlah SKS, dosen pengampu, dan status.

**Tunjukkan:** `create_courses_table.php`.

### 17. Mengapa `code` course unique?

> Kode mata kuliah harus menjadi identitas yang tidak boleh sama dengan course lain.

### 18. Mengapa status course memakai enum?

> Agar status hanya bernilai `draft`, `active`, atau `archived`, bukan teks sembarangan.

### 19. Apa fungsi tabel `course_user`?

> Sebagai tabel pivot yang menghubungkan mahasiswa dan course dalam hubungan many-to-many.

### 20. Apa fungsi tabel `materials` dan `assignments`?

> Materials menyimpan materi pembelajaran, sedangkan assignments menyimpan tugas yang dimiliki oleh course.

### 21. Apa fungsi tabel `submissions`?

> Menyimpan jawaban atau file pengumpulan mahasiswa untuk suatu assignment.

### 22. Apa fungsi tabel `grades`?

> Menyimpan score dan feedback untuk submission.

## D. Foreign Key dan Delete Behavior

### 23. Mengapa `lecturer_id` memakai `restrictOnDelete()`?

> Karena dosen yang masih mengampu course tidak boleh dihapus. Restrict mencegah course kehilangan dosen secara diam-diam.

**Tunjukkan:**

```php
$table->foreignId('lecturer_id')
    ->constrained('users')
    ->restrictOnDelete();
```

### 24. Mengapa materials dan assignments memakai `cascadeOnDelete()`?

> Karena keduanya hanya memiliki konteks di dalam course. Jika course dihapus, data anak tersebut tidak lagi berguna.

### 25. Mengapa `submissions.assignment_id` memakai cascade?

> Submission tidak berguna jika assignment induknya sudah dihapus.

### 26. Mengapa `submissions.user_id` memakai restrict?

> Submission adalah bukti pengumpulan mahasiswa. Restrict mencegah bukti tersebut hilang otomatis ketika user dihapus.

### 27. Mengapa `grades.submission_id` memakai cascade?

> Grade tidak berguna jika submission sudah dihapus.

### 28. Apa perbedaan restrict, cascade, dan nullOnDelete?

> Restrict menolak penghapusan parent yang masih memiliki child. Cascade menghapus child secara otomatis. NullOnDelete mengubah foreign key child menjadi NULL. Project ini menggunakan restrict dan cascade sesuai kebutuhan bisnis.

### 29. Apa risiko cascade?

> Data child dapat terhapus secara berantai. Karena itu cascade hanya dipakai pada child yang memang tidak berguna tanpa parent.

## E. Unique Constraint

### 30. Mengapa `course_user` memiliki unique composite?

> Agar kombinasi `course_id` dan `user_id` tidak dapat disimpan dua kali. Mahasiswa tidak dapat terdaftar dua kali pada course yang sama.

**Tunjukkan:**

```php
$table->unique(['course_id', 'user_id']);
```

### 31. Mengapa validasi controller saja tidak cukup?

> Dua request yang datang bersamaan dapat sama-sama lolos pengecekan controller. Unique constraint database tetap menolak duplikasi dan melindungi dari race condition.

### 32. Mengapa submissions memiliki unique composite?

> Agar satu mahasiswa hanya memiliki satu submission untuk satu assignment.

```php
$table->unique(['assignment_id', 'user_id']);
```

### 33. Mengapa `grades.submission_id` unique?

> Karena satu submission hanya boleh memiliki satu grade. Unique juga menolak grade kedua, sedangkan index biasa hanya membantu pencarian.

### 34. Apa yang terjadi jika unique constraint dihapus?

> Data pendaftaran, submission, atau grade ganda dapat masuk ke database dan merusak aturan bisnis.

## F. `up()`, `down()`, dan Command

### 35. Apa fungsi `up()`?

> `up()` menerapkan perubahan migration, misalnya membuat tabel dan constraint.

### 36. Apa fungsi `down()`?

> `down()` membalikkan perubahan `up()`. Jika `up()` membuat tabel courses, `down()` menghapus tabel courses.

**Tunjukkan:** bagian bawah salah satu migration.

### 37. Mengapa `down()` harus benar?

> Supaya rollback dan `migrate:refresh` dapat mengembalikan database ke kondisi sebelumnya.

### 38. Apa perbedaan `migrate:fresh` dan `migrate:refresh`?

> `migrate:fresh` langsung menghapus semua tabel lalu menjalankan migration dari awal. `migrate:refresh` menjalankan `down()` secara terbalik, lalu menjalankan kembali `up()`.

### 39. Apa fungsi `--seed`?

> Menjalankan `DatabaseSeeder` setelah migration selesai sehingga database diisi data demo.

### 40. Apakah command tersebut boleh dijalankan di production?

> Tidak, karena dapat menghapus data. Command tersebut hanya untuk development, testing, atau CI.

## G. Model dan Relasi

### 41. Apa itu Eloquent?

> Eloquent adalah ORM Laravel yang memetakan model PHP ke tabel database dan menyediakan query serta relasi.

### 42. Mengapa Course `belongsTo` User sebagai lecturer?

> Karena foreign key `lecturer_id` berada di tabel courses dan menunjuk ke satu user sebagai dosen.

```php
public function lecturer(): BelongsTo
{
    return $this->belongsTo(User::class, 'lecturer_id');
}
```

### 43. Mengapa User `hasMany` Course?

> Satu dosen dapat mengampu banyak course. Foreign key-nya bernama `lecturer_id`, sehingga nama kolom ditulis eksplisit.

### 44. Mengapa Course dan User `belongsToMany`?

> Karena satu course memiliki banyak mahasiswa dan satu mahasiswa dapat mengikuti banyak course melalui pivot `course_user`.

### 45. Apa fungsi `withTimestamps()`?

> Menyimpan waktu pembuatan dan perubahan hubungan pada tabel pivot.

### 46. Mengapa Course `hasMany` assignments?

> Satu course dapat memiliki banyak assignment, dan foreign key `course_id` berada di tabel assignments.

### 47. Mengapa Submission `hasOne` Grade?

> Karena `grades.submission_id` unique, sehingga satu submission memiliki paling banyak satu grade.

### 48. Apa yang terjadi jika relasi salah?

> Query dapat gagal atau mengambil data yang tidak sesuai. Nama model, foreign key, dan jenis relasi harus cocok dengan migration.

## H. `$fillable` dan Mass Assignment

### 49. Apa itu mass assignment?

> Mass assignment adalah pengisian banyak atribut model dari array, misalnya `User::create($data)`.

### 50. Apa bahaya `$request->all()`?

> `$request->all()` mengambil semua field yang dikirim user, termasuk field tambahan yang tidak ada di form. Field sensitif seperti role dapat dimanipulasi jika perlindungannya lemah.

### 51. Apa perlindungan pada model User?

> User hanya memiliki `name`, `email`, dan `password` di `$fillable`. Field role tidak dimasukkan karena menentukan hak akses.

**Tunjukkan:** `app/Models/User.php`.

```php
protected $fillable = [
    'name',
    'email',
    'password',
];
```

### 52. Di mana role diisi?

> Di UserController, role dikeluarkan dari array mass assignment lalu diisi eksplisit oleh server.

```php
$role = $validated['role'];
unset($validated['role']);
$user = User::create($validated);
$user->role = $role;
$user->save();
```

### 53. Mengapa role tetap divalidasi?

> Karena validasi memastikan nilainya hanya admin, dosen, atau student. `$fillable` membatasi mass assignment, sedangkan validasi membatasi nilai input. Keduanya memiliki fungsi berbeda.

### 54. Mengapa tidak memakai `$guarded = []`?

> Karena `$guarded = []` membuka seluruh atribut untuk mass assignment dan dapat mengekspos field sensitif.

### 55. Apakah menyembunyikan field di frontend sudah aman?

> Tidak. User dapat mengirim request manual lewat Tinker, Postman, curl, atau DevTools. Keamanan harus ditegakkan di server dan model.

### 56. Apakah CSRF mencegah mass assignment?

> Tidak. CSRF memeriksa asal request, sedangkan `$fillable` membatasi atribut model. Request yang valid tetap dapat membawa field tambahan.

### 57. Bagaimana memperagakan mass assignment?

> Saya buka User.php untuk menunjukkan `$fillable`, lalu menjalankan Tinker dan mengirim role admin melalui `User::create()`. Setelah membaca kembali role, hasilnya tetap student karena role tidak ada di `$fillable` dan migration memiliki default student.

Command:

```bash
php artisan tinker
```

```php
$user = App\Models\User::create([
    'name' => 'Demo',
    'email' => 'demo-mass@test.local',
    'password' => 'password123',
    'role' => 'admin',
]);

$user->refresh()->role;
$user->delete();
```

### 58. Bagaimana menunjukkan kondisi rentan dengan aman?

> Saya memakai branch atau salinan sementara, menambahkan role ke `$fillable`, menjalankan payload yang sama, lalu mengembalikan perubahan. Branch utama tidak boleh dibiarkan rentan.

## I. Casting

### 59. Apa fungsi `casts()`?

> `casts()` mengubah atribut menjadi tipe yang sesuai. `due_at` dan `submitted_at` menjadi datetime, sedangkan password diproses sebagai hashed.

### 60. Mengapa memakai method `casts()`?

> Project menggunakan Laravel 12 dan mengikuti konvensi method `casts()`, bukan pola properti `$casts` yang sering ditemukan pada tutorial lama.

### 61. Apa manfaat cast datetime?

> Nilai waktu dapat diformat, dibandingkan, dan dipakai untuk mengecek deadline sebagai objek datetime.

## J. Factory dan Seeder

### 62. Apa perbedaan factory dan seeder?

> Factory mendefinisikan bentuk data palsu. Seeder mengatur jumlah, urutan, dan hubungan data yang dibuat.

### 63. Berapa data yang dibuat seeder project?

> Seeder membuat 1 admin, 3 dosen, 30 mahasiswa, 5 course, 15 assignment, minimal 100 submission, dan grade untuk sebagian submission. Hasil yang diverifikasi adalah 34 user, 5 course, 15 assignment, 100 submission, dan 60 grade.

### 64. Bagaimana seeder menjaga submission tetap valid?

> Seeder mengambil mahasiswa dari relasi students pada course, lalu menggunakannya sebagai user pemilik submission. Jadi submission hanya berasal dari mahasiswa yang terdaftar.

**Tunjukkan:** `database/seeders/DatabaseSeeder.php`.

### 65. Apa yang terjadi jika seeder dijalankan tanpa migration?

> Seeder akan gagal jika tabel atau kolom yang dibutuhkan belum tersedia. Karena itu migration dijalankan lebih dahulu.

## K. Pertanyaan CRUD dan Pembuktian Project

### 66. Bagaimana alur halaman course dari URL sampai database?

> Request masuk ke resource route, diarahkan ke `CourseController@index`, lalu controller menjalankan `Course::with('lecturer')->latest()->get()`. Model mengambil data dari database dan hasilnya dikirim ke view.

**Tunjukkan:** `routes/web.php` dan `CourseController.php`.

### 67. Mengapa `lecturer_id` divalidasi berdasarkan role dosen?

> Foreign key hanya memastikan user tersebut ada. Rule tambahan memastikan user tersebut benar-benar dosen, bukan admin atau mahasiswa.

### 68. Mengapa controller tetap melakukan validasi jika ada constraint database?

> Validasi memberikan pesan yang ramah sebelum query berjalan. Constraint database menjadi perlindungan terakhir dan menjaga konsistensi walaupun request datang bersamaan.

### 69. Mengapa memakai route model binding?

> Parameter route seperti `{course}` otomatis diubah menjadi model Course yang sesuai. Ini mengurangi query manual dan menghasilkan 404 jika data tidak ditemukan.

### 70. Mengapa user yang masih punya submission tidak boleh dihapus?

> Submission adalah bukti akademik dan foreign key-nya memakai restrict. Controller mencegah penghapusan serta memberikan pesan yang lebih mudah dipahami.

### 71. Tunjukkan route CRUD course.

> Saya membuka `routes/web.php` dan menunjukkan `Route::resource('mata-kuliah', CourseController::class)`. Ini menghasilkan index, create, store, show, edit, update, dan destroy.

### 72. Tunjukkan route CRUD user.

> Saya menunjukkan `Route::resource('users', UserController::class)`. Resource ini juga menghasilkan tujuh endpoint CRUD.

### 73. Bagaimana membuktikan route tersedia?

```bash
php artisan route:list --path=mata-kuliah
php artisan route:list --path=users
```

> Saya memeriksa bahwa semua route terdaftar dan menunjuk controller yang benar.

### 74. Bagaimana membuktikan migration reversible?

```bash
php artisan migrate:refresh --seed
```

> Jika rollback dan pembuatan ulang berhasil, method `down()` dapat membalikkan `up()`.

### 75. Apa yang dilakukan jika migration gagal?

> Saya membaca error pertama, memeriksa urutan migration, nama tabel, foreign key, koneksi database, dan method `down()`. Setelah penyebab diperbaiki, saya menjalankan kembali migration dari kondisi bersih.

## L. Pertanyaan Jebakan

### 76. Apakah semua kode ini kamu tulis sendiri?

> Saya mengerjakan migration dan model sesuai jobdesk. Untuk beberapa draft saya memakai bantuan AI, tetapi saya membaca, mencocokkan dengan Laravel 12, menjalankan migration, seeder, dan test, lalu memahami hasil akhirnya. Saya tidak mengklaim pekerjaan anggota lain.

### 77. Apakah `$fillable` menggantikan validasi?

> Tidak. `$fillable` menentukan atribut yang boleh diisi mass assignment. Validasi menentukan apakah nilai input benar secara format dan aturan bisnis.

### 78. Apakah foreign key menggantikan validasi `exists`?

> Tidak. Foreign key menjamin database tidak memiliki referensi parent yang tidak ada. Validasi `exists` memberikan pesan lebih ramah sebelum database menolak data.

### 79. Apakah cascade selalu lebih baik?

> Tidak. Cascade hanya dipakai ketika child tidak berguna tanpa parent. Restrict dipakai ketika data parent atau bukti child harus dipertahankan.

### 80. Apakah unique constraint sama dengan validasi unique?

> Tidak. Validasi membantu user memahami kesalahan, sedangkan unique constraint menjamin database tetap menolak duplikasi, termasuk pada race condition.

### 81. Apa kelemahan demo Tinker?

> Tinker menguji perilaku model secara langsung, bukan seluruh alur HTTP seperti route, CSRF, dan middleware. Namun Tinker tepat untuk membuktikan mass assignment karena yang diuji adalah `Model::create()`.

### 82. Mengapa tidak menghapus user secara cascade?

> Karena user dapat memiliki submission yang menjadi bukti akademik. Cascade dapat menghilangkan bukti tersebut, sehingga project memakai restrict pada submissions.user_id.

## M. Urutan Demo Interview

Ikuti urutan ini saat interview:

1. Jelaskan jobdesk Build 1 dan 2.
2. Tunjukkan urutan file migration.
3. Jelaskan parent-child dan foreign key.
4. Jelaskan `restrictOnDelete()` pada lecturer.
5. Jelaskan `cascadeOnDelete()` pada material dan assignment.
6. Jelaskan unique composite dan unique submission.
7. Tunjukkan `up()` dan `down()`.
8. Tunjukkan model Course dan User.
9. Jelaskan semua relasi utama.
10. Tunjukkan `$fillable` dan perlindungan role.
11. Peragakan mass assignment di Tinker.
12. Tunjukkan seeder dan konsistensi data.
13. Tunjukkan route dan CRUD.
14. Jalankan `migrate:refresh --seed` dan test.
15. Jelaskan bagian yang dibuat dengan bantuan AI dan cara verifikasinya.

## N. Kalimat Penutup

> Kontribusi saya adalah membangun fondasi database dan model. Saya dapat menjelaskan apa fungsi setiap tabel, mengapa constraint dan delete behavior dipilih, bagaimana relasi Eloquent bekerja, dan bagaimana `$fillable` melindungi role. Saya juga dapat membuktikannya melalui kode, Tinker, migration, seeder, route:list, dan test.
