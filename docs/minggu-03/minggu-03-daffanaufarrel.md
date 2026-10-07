# Catatan Minggu 3
Nama : Muhammad Daffa Naufarrel Putranada\
Nim : 10241039

## 3.3 Read → Break → Fix → Build

### READ — Baca skema sebelum menulisnya (30 menit)

Sebelum menyentuh kode, kerjakan bersama kelompok:

1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.
2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan **tuliskan alasannya**.
3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?
4. Jawab: kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?

### Jawaban

1. ERD:

```text
   User (dosen) 1 ---- banyak Course
   Course 1 ---- banyak Material
   Course 1 ---- banyak Assignment
   User (mahasiswa) banyak ---- banyak Course
   Assignment 1 ---- banyak Submission
   User 1 ---- banyak Submission
   Submission 1 ---- 1 Grade
   ```

2. - `courses.lecturer_id` memakai `restrictOnDelete()` supaya dosen yang masih mengampu course tidak dapat dihapus sembarangan.
   - `course_user.course_id` dan `course_user.user_id` memakai `cascadeOnDelete()` karena data pendaftaran tidak berguna jika course atau user induknya dihapus.
   - `materials.course_id` dan `assignments.course_id` memakai `cascadeOnDelete()` karena materi dan tugas hanya bermakna dalam course tertentu.
   - `submissions.assignment_id` memakai `cascadeOnDelete()` karena submission tidak berguna tanpa tugasnya.
   - `submissions.user_id` memakai `restrictOnDelete()` supaya bukti pengumpulan mahasiswa tidak hilang saat user dihapus.
   - `grades.submission_id` memakai `cascadeOnDelete()` karena nilai tidak berguna tanpa submission.

3. Jika dosen masih digunakan course, penghapusan ditolak agar `lecturer_id` tetap valid dan data tidak ikut terhapus.

4. `grades.submission_id` dibuat unique agar satu submission hanya memiliki satu grade dan mencegah grade ganda.