# Catatan Minggu 3
Nama: Muchammad Maulana
NIM: 10241043
Kelas: A

## 3.3 Read -> Break -> Fix -> Build

### READ - Baca skema sebelum menulisnya

1. ERD yang saya pahami:

   ```text
   User (dosen) 1 ---- banyak Course
   Course 1 ---- banyak Material
   Course 1 ---- banyak Assignment
   User (mahasiswa) banyak ---- banyak Course
   Assignment 1 ---- banyak Submission
   User 1 ---- banyak Submission
   Submission 1 ---- 1 Grade
   ```

2. Perilaku foreign key dan alasannya:

   - `courses.lecturer_id` memakai `restrictOnDelete()` supaya dosen yang masih mengampu course tidak dapat dihapus sembarangan.
   - `course_user.course_id` dan `course_user.user_id` memakai `cascadeOnDelete()` karena data pendaftaran tidak berguna jika course atau user induknya dihapus.
   - `materials.course_id` dan `assignments.course_id` memakai `cascadeOnDelete()` karena materi dan tugas hanya bermakna dalam course tertentu.
   - `submissions.assignment_id` memakai `cascadeOnDelete()` karena submission tidak berguna tanpa tugasnya.
   - `submissions.user_id` memakai `restrictOnDelete()` supaya bukti pengumpulan mahasiswa tidak hilang saat user dihapus.
   - `grades.submission_id` memakai `cascadeOnDelete()` karena nilai tidak berguna tanpa submission.

3. Jika dosen masih dipakai oleh course, penghapusannya ditolak oleh database. Desain ini mencegah course memiliki `lecturer_id` yang tidak lagi menunjuk ke user yang valid dan mencegah kehilangan data secara berantai.

4. `grades.submission_id` dibuat unique karena satu submission hanya boleh mempunyai satu grade. Index biasa hanya mempercepat pencarian, sedangkan unique juga menegakkan aturan bisnis dan menolak grade kedua.

### BREAK - Lima kerusakan

Analisis berikut menjelaskan dampak setiap kerusakan dan perbaikannya pada kode yang benar.

| # | Kerusakan yang dicoba | Hasil yang diamati |
|---|------------------------|--------------------|
| 1 | Menghapus `unique(['course_id', 'user_id'])` dari `course_user`, lalu memasukkan mahasiswa yang sama dua kali | Duplikasi pendaftaran dapat tersimpan karena tidak ada lagi constraint database. |
| 2 | Menambahkan `role` ke `$fillable` model `User`, lalu mengirim `role=admin` melalui input tambahan | Field yang tidak ada di form tetap dapat diproses melalui request langsung. User dapat berubah menjadi admin. |
| 3 | Mengganti `$fillable` dengan `protected $guarded = [];` | Semua atribut model terbuka untuk mass assignment, termasuk field sensitif seperti `role`. |
| 4 | Mengosongkan `down()` salah satu migration, lalu menjalankan `php artisan migrate:refresh` | Migration tidak dapat mengembalikan struktur tabel secara lengkap sehingga proses refresh gagal atau meninggalkan struktur lama. |
| 5 | Mengganti `restrictOnDelete()` pada `courses.lecturer_id` menjadi `cascadeOnDelete()`, lalu menghapus dosen | Course yang diajar dosen dapat ikut terhapus. Data hilang berantai tanpa persetujuan tambahan. |

Contoh payload untuk membuktikan mass assignment:

```php
User::create([
    'name' => 'Penyusup',
    'email' => 'x@x.test',
    'password' => 'rahasia123',
    'role' => 'admin',
]);

### FIX - Perbaikan

1. Mengembalikan unique composite pada migration `course_user`:

   ```php
   $table->unique(['course_id', 'user_id']);
   ```

2. Menghapus `role` dari `$fillable` model `User`. Data role ditentukan oleh server, bukan dari input pengguna. Pembuatan user hanya mengambil field yang diizinkan:

   ```php
   $user = User::create($request->only([
       'name',
       'email',
       'password',
   ]));

   $user->role = 'student';
   $user->save();
   ```

3. Tidak menggunakan `protected $guarded = [];`. Model memakai `$fillable` yang terbatas agar kolom sensitif tidak dapat diisi melalui mass assignment.

4. Mengisi setiap `down()` dengan `Schema::dropIfExists(...)` yang sesuai dengan tabel pada `up()`. Dengan begitu migration dapat dibalik saat menjalankan:

   ```bash
   php artisan migrate:refresh --seed
   ```

5. Mengembalikan `restrictOnDelete()` pada `courses.lecturer_id`, sedangkan relasi anak seperti materials dan assignments tetap memakai `cascadeOnDelete()` sesuai aturan bisnis.

### BUILD - Kontribusi saya

Saya mengerjakan fondasi database dan model Eloquent:

- migration seluruh tabel dan urutan foreign key;
- unique composite pada `course_user` dan `submissions`;
- unique `grades.submission_id`;
- relasi `belongsTo`, `hasMany`, dan `belongsToMany`;
- `$fillable` yang terbatas pada setiap model;
- casting Laravel 12 melalui method `casts()`;
- `down()` yang dapat membalikkan setiap migration.

Bagian factory-seeder, CRUD mata kuliah, CRUD pengguna, dan CI dikerjakan atau dilanjutkan anggota lain. Saya tetap memahami alurnya karena semuanya memakai migration, model, dan relasi yang sama.

## 3.4 Checkpoint Minggu 3 - Gerbang Interview Tugas 1

- [x] Menjelaskan urutan pembuatan tabel berdasarkan foreign key.
- [x] Menjelaskan unique composite pada `course_user`.
- [x] Menjelaskan unique composite pada `submissions`.
- [x] Menjelaskan unique `grades.submission_id`.
- [x] Menjelaskan perbedaan `restrictOnDelete()` dan `cascadeOnDelete()`.
- [x] Menjelaskan mass assignment dan alasan `role` tidak ada di `$fillable`.
- [x] Menjelaskan mengapa `$guarded = []` berbahaya.
- [x] Menjelaskan method `casts()` pada Laravel 12.
- [ ] Menjalankan `php artisan migrate:refresh --seed` di depan penguji.
- [x] Menjelaskan kontribusi saya pada migration, model, dan relasi.

Perintah verifikasi yang perlu saya kuasai:

```bash
php artisan migrate:fresh --seed
php artisan migrate:refresh --seed
php artisan test
php artisan route:list
```

