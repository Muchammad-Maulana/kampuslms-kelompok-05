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
1. s
2. s
3. s
4. s

### BREAK — Lima kerusakan (45 menit)

| # | Yang dicoba | Yang harus Anda amati |
|---|-------------|------------------------|
| 1 | Hapus `unique(['course_id','user_id'])` dari `course_user`, lalu daftarkan mahasiswa yang sama dua kali | Data ganda lolos tanpa keluhan |
| 2 | Tambahkan `role` ke `$fillable` model `User`, lalu kirim request pembuatan user dengan `role=admin` lewat form yang **tidak punya field role** | **Mass assignment nyata** — Anda baru saja jadi admin |
| 3 | Ganti seluruh `$fillable` dengan `protected $guarded = [];` lalu ulangi nomor 2 | Kenapa `$guarded` kosong dilarang |
| 4 | Kosongkan isi `down()` di satu migrasi, lalu jalankan `php artisan migrate:refresh` | Migrasi tidak reversible = CI merah |
| 5 | Ubah `restrictOnDelete` pada `lecturer_id` menjadi `cascadeOnDelete`, lalu hapus satu dosen | Kehilangan data berantai |

Nomor 2 wajib benar-benar dilakukan, bukan sekadar dibayangkan. Anda perlu melihat sendiri bahwa **formulir di frontend bukan pembatas apa pun**.

## 3.4 Checkpoint Minggu 3 — Gerbang Interview Tugas 1

Setiap anggota akan ditanya secara individu, dengan kode kelompok terbuka di layar.

- [ ] Tunjukkan migrasi yang **Anda** tulis. Jelaskan setiap constraint di dalamnya.
- [ ] Kenapa `course_user` punya unique composite? Peragakan apa yang terjadi kalau dihapus.
- [ ] Apa itu mass assignment? Tunjukkan di kode Anda apa yang mencegahnya, lalu peragakan serangannya dengan `curl`.
- [ ] Kenapa `role` tidak boleh ada di `$fillable`? Di mana ia diisi sebagai gantinya?
- [ ] Kenapa `lecturer_id` memakai `restrictOnDelete` sementara `materials.course_id` memakai `cascadeOnDelete`?
- [ ] Jalankan `php artisan migrate:refresh` di depan penguji. Harus berhasil tanpa error.
- [ ] Tunjukkan satu bagian kode yang Anda tulis dengan bantuan AI. Apa yang Anda ubah dari keluaran aslinya, dan kenapa?