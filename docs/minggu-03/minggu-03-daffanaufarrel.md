# Catatan Minggu 3
Nama : Muhammad Daffa Naufarrel Putranada\
NIM : 10241045\
Kelas : A

## 3.3 Read → Break → Fix → Build

### READ — Baca skema sebelum menulisnya (30 menit)

Sebelum menyentuh kode, kerjakan bersama kelompok:

1. Gambar ulang ERD dari spesifikasi di papan/kertas, tanpa melihat dokumen.
2. Untuk setiap foreign key, tentukan perilaku `onDelete`-nya dan **tuliskan alasannya**.
3. Jawab: kalau seorang dosen dihapus, apa yang terjadi pada mata kuliahnya? Kenapa dirancang begitu?
4. Jawab: kenapa `grades.submission_id` bersifat unique, bukan sekadar index biasa?

### Jawaban

1. ~
2. 
| Foreign Key | `onDelete` | Alasan |
|---|---|---|
| `courses.lecturer_id` | `restrictOnDelete` | Dosen adalah entitas independen. Course + seluruh material/assignment/submission/grade di dalamnya terlalu berharga untuk hilang konteks pengampu begitu saja — harus dipindah tangan dulu secara sadar oleh admin |
| `course_user.course_id` | `cascadeOnDelete` | Baris pivot ini cuma "catatan keanggotaan", tidak berarti apa-apa kalau course-nya sudah tidak ada |
| `course_user.user_id` | `cascadeOnDelete` | Sama — catatan keanggotaan tidak berarti apa-apa kalau usernya sudah tidak ada |
| `materials.course_id` | `cascadeOnDelete` | Materi adalah bagian dari course, tidak berdiri sendiri. Course hilang → materinya memang seharusnya ikut hilang |
| `assignments.course_id` | `cascadeOnDelete` | Sama seperti materi — tugas adalah bagian dari course |
| `submissions.assignment_id` | `cascadeOnDelete` | Submission adalah jawaban atas assignment tertentu; tanpa assignment-nya, submission itu tidak bermakna |
| `submissions.user_id` | `restrictOnDelete` | Mahasiswa entitas independen. Riwayat pengumpulan tugas adalah data akademik yang harus sengaja ditangani (dihapus/diarsipkan) kalau memang mau hapus user, bukan otomatis ikut lenyap begitu saja |
| `grades.submission_id` | `cascadeOnDelete` | Nilai tidak berarti apa-apa tanpa submission yang dinilai |

3. Yang terjadi yaitu penghapusan gagal. Database melempar `QueryException` (`FOREIGN KEY constraint failed`), karena `lecturer_id` memakai `restrictOnDelete()`. Mata kuliahnya tidak ikut terhapus, dan dosennya juga tidak jadi terhapus.

Kenapa dirancang begitu:

Kalau dosen boleh dihapus begitu saja, ada dua pilihan buruk:

1. Course-nya ikut terhapus (cascade) → semua materi, tugas, submission, dan nilai mahasiswa yang sudah dikerjakan ikut lenyap, padahal tidak ada hubungannya dengan siapa dosennya.
2. Course-nya dibiarkan tanpa dosen (`lecturer_id` jadi null) → butuh kolom nullable dan logika tambahan untuk menangani "course tanpa pengampu", rawan terlewat di banyak tempat (laporan, tampilan, dsb).

Pilihan desainnya: paksa admin mengambil keputusan eksplisit — pindahkan dulu pengampu ke dosen lain (update `lecturer_id`), baru boleh hapus dosen lama. Database berperan sebagai penjaga yang mencegah kehilangan data secara tidak sengaja.

4. Relasi antara `Submission` dan `Grade` bersifat **one-to-one**: satu submission (satu kali pengumpulan tugas) seharusnya hanya punya satu nilai.

- Kalau cuma index biasa (non-unique): tidak ada yang mencegah dua baris `grades` merujuk ke `submission_id` yang sama. Satu submission bisa saja tercatat punya dua nilai berbeda, menjadi ambigu, nilai mana yang dipakai.
- Dengan `unique('submission_id')`: database menjamin maksimal satu baris grade per submission. Percobaan insert nilai kedua untuk submission yang sama akan langsung ditolak database, bukan tersimpan sebagai data ganda yang membingungkan.

Constraint ini juga yang membuat relasi Eloquent `Submission::grade()` bisa didefinisikan sebagai `hasOne()` (bukan `hasMany()`) dengan percaya diri, karena dijamin oleh database, bukan sekadar asumsi di level aplikasi