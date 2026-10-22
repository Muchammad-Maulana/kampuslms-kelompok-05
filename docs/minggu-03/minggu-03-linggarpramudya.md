# Catatan Minggu 3
Nama : Linggar Pramudya\
Nim : 10241039\
Kelas : A

## 3.3 Read → Break → Fix → Build

### READ — Baca skema sebelum menulisnya (30 menit)

Sebelum menyentuh kode, kerjakan bersama kelompok:

1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.
2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan **tuliskan alasannya**.
3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?
4. Jawab: kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?

### Jawaban
1. ERD berdasarkan migration dan model:

	 ```text
	 users (id PK)
			| 1                         | 1
			|--< courses.lecturer_id   |--< materials.uploaded_by
			|                          |--< assignments.created_by
			|                          |--< submissions.user_id
			|                          |--< grades.graded_by
			|
			| >--< courses melalui course_user
			|
	 courses (id PK)
			|--< materials (course_id FK)
			|--< assignments (course_id FK)
			|
	 assignments (id PK) --< submissions (assignment_id FK)
															|
															`-- 1 grade (submission_id FK, UNIQUE)
	 ```

	 Setiap tabel memakai `id` sebagai primary key. `courses.lecturer_id` menunjuk ke
	 `users.id`. Relasi user dan course untuk mahasiswa adalah many-to-many melalui
	 `course_user`, dengan `course_id` dan `user_id` sebagai foreign key. Satu course
	 memiliki banyak material dan assignment. Satu assignment memiliki banyak
	 submission, sedangkan satu submission paling banyak memiliki satu grade karena
	 `grades.submission_id` unique. Kolom `uploaded_by`, `created_by`, `user_id`, dan
	 `graded_by` juga menunjuk ke `users.id`.

2. Perilaku foreign key yang benar-benar dipakai:

	 - `courses.lecturer_id`: `restrictOnDelete()`. Dosen yang masih dipakai course
		 tidak bisa dihapus begitu saja agar course dan data pengampunya tetap valid.
	 - `course_user.course_id` dan `course_user.user_id`: `cascadeOnDelete()`. Baris
		 pendaftaran tidak diperlukan lagi jika course atau user induknya dihapus.
	 - `materials.course_id`: `cascadeOnDelete()`. Material ikut dihapus karena
		 hanya dimiliki oleh course tersebut.
	 - `materials.uploaded_by`: `restrictOnDelete()`. User pengunggah tidak boleh
		 dihapus jika masih menjadi pencatat material.
	 - `assignments.course_id`: `cascadeOnDelete()`. Assignment adalah bagian dari
		 course, jadi ikut dihapus saat course dihapus.
	 - `assignments.created_by`: `restrictOnDelete()`. Data tugas tetap membutuhkan
		 user pembuat yang valid.
	 - `submissions.assignment_id`: `cascadeOnDelete()`. Submission tidak berguna
		 tanpa assignment-nya.
	 - `submissions.user_id`: `restrictOnDelete()`. Riwayat pengumpulan tidak ikut
		 hilang ketika user masih memiliki submission.
	 - `grades.submission_id`: `cascadeOnDelete()`. Grade tidak berguna tanpa
		 submission yang dinilai.
	 - `grades.graded_by`: `restrictOnDelete()`. Identitas pemberi nilai harus tetap
		 dapat ditelusuri.

	 `sessions.user_id` hanya dibuat sebagai kolom yang nullable dan ber-index pada
	 migration users; itu bukan foreign key karena tidak memakai `constrained()`.

3. Jika dosen masih menjadi `lecturer_id` pada suatu course, penghapusan user
	 ditolak oleh database karena memakai `restrictOnDelete()`. Mata kuliahnya tidak
	 ikut terhapus. Tujuannya supaya course tidak kehilangan dosen secara diam-diam
	 dan data tidak hilang secara berantai.

4. `grades.submission_id` memakai `unique` karena satu submission hanya boleh
	 mempunyai satu grade. Index biasa hanya membantu pencarian, tetapi masih
	 mengizinkan grade kedua. Unique sekaligus membuat database menolak data nilai
	 ganda.

### BREAK

| # | Yang dirusak | Prediksi | Yang Sebenarnya Terjadi |
|---|---|---|---|
| 1 | Menghapus `unique(['course_id', 'user_id'])` dari `course_user`, lalu mendaftarkan mahasiswa yang sama dua kali. | Sistem akan menolak pendaftaran mahasiswa yang sama pada mata kuliah yang sama. | Data pendaftaran ganda berhasil disimpan karena database tidak lagi memiliki batasan untuk mencegah duplikasi. |
| 2 | Menambahkan `role` ke `$fillable` model `User`, lalu mengirim request pembuatan user dengan `role=admin` melalui form yang tidak memiliki field `role`. | Role pengguna tetap menjadi mahasiswa karena form tidak menyediakan pilihan role. | Request langsung tetap dapat membawa nilai `role`, sehingga akun pengguna berpotensi mendapatkan hak akses admin. |
| 3 | Mengganti seluruh `$fillable` dengan `protected $guarded = [];`, lalu mengulangi pengujian nomor 2. | Kolom sensitif tetap terlindungi dari pengisian data yang tidak diizinkan. | Semua atribut model dapat diisi melalui mass assignment tanpa pembatasan, termasuk kolom penting seperti `role`. |
| 4 | Mengosongkan isi `down()` pada salah satu migration, lalu menjalankan `php artisan migrate:refresh`. | Migration dapat dibatalkan dan dijalankan ulang tanpa masalah. | Migration tidak dapat membatalkan perubahan database dengan semestinya, sehingga proses refresh bisa gagal atau menyisakan struktur tabel sebelumnya. |
| 5 | Mengubah `restrictOnDelete()` pada `courses.lecturer_id` menjadi `cascadeOnDelete()`, lalu menghapus seorang dosen. | Dosen tidak dapat dihapus jika masih memiliki mata kuliah. | Data mata kuliah yang terkait dengan dosen tersebut dapat ikut terhapus secara otomatis, sehingga menimbulkan risiko kehilangan data. |

### FIX

1. Menambahkan kembali unique composite constraint pada migration `course_user` untuk mencegah mahasiswa terdaftar lebih dari satu kali pada mata kuliah yang sama.

   ```php
   $table->unique(['course_id', 'user_id']);
   ```

2. Mengeluarkan kolom `role` dari `$fillable` pada model `User` agar pengguna tidak bisa menentukan hak aksesnya sendiri. Saat membuat akun, hanya data yang diperlukan yang diambil dari request, sedangkan role ditetapkan langsung oleh server.

   ```php
   $user = User::create($request->only([
       'name',
       'email',
       'password',
   ]));

   $user->role = 'mahasiswa';
   $user->save();
   ```

3. Mengganti penggunaan `protected $guarded = [];` dengan `$fillable` yang hanya mengizinkan kolom tertentu. Cara ini membantu melindungi kolom penting agar tidak dapat diubah sembarangan melalui mass assignment.

4. Melengkapi method `down()` pada setiap migration dengan `Schema::dropIfExists(...)` sesuai tabel yang dibuat pada method `up()`. Perbaikan ini memungkinkan migration dibatalkan atau dijalankan ulang dengan benar melalui perintah berikut.

   ```bash
   php artisan migrate:refresh --seed
   ```

5. Mengatur kembali foreign key `courses.lecturer_id` menggunakan `restrictOnDelete()` supaya dosen tidak dapat dihapus selama masih memiliki mata kuliah terkait. Sementara itu, relasi turunan seperti materials dan assignments tetap menggunakan `cascadeOnDelete()` agar data terkait ikut terhapus ketika data induknya dihapus.