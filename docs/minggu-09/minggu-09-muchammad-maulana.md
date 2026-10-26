# Minggu 9 — Upload File dan Data Dinamis

## Nama: Muchammad Maulana
## NIM: 10241043
## Proyek: KampusLMS — Laravel 12

---

# READ — Telusuri satu berkas

## Disk penyimpanan

Disk `local` pada `config/filesystems.php` menggunakan root `storage/app/private`. Disk `public` menggunakan root `storage/app/public` dan dipetakan oleh `storage:link` ke `public/storage`. Materi dan submission menggunakan disk `local`; file privat tidak diletakkan di bawah path yang dapat diakses langsung melalui web server.

File fisik memakai path acak hasil `$file->store(...)`. Database menyimpan path privat pada `file_path`, nama asli untuk tampilan pada `original_name`, dan ukuran pada `file_size`. Nama asli tidak digunakan sebagai nama file fisik.

## Siklus submission

1. Route submission berada di bawah middleware autentikasi dan role mahasiswa.
2. `SubmissionPolicy::create` memeriksa enrollment, status course/tugas, deadline, serta `allow_late`.
3. Controller memvalidasi file PDF/DOC/DOCX dengan batas 5 MB.
4. File disimpan ke disk privat, lalu `updateOrCreate` memperbarui submission untuk pasangan assignment dan mahasiswa yang sama.
5. Nilai `is_late` ditentukan server dari waktu saat request diproses dan deadline assignment.
6. Route download memanggil `Gate::authorize('view', $submission)` sebelum mengirim file.

## Siklus materi

Dosen pemilik course membuat materi berupa file PDF/PPT/PPTX/DOCX (maksimal 20 MB) atau tautan eksternal. File materi disimpan pada disk privat. Pengunduhan file melewati `MaterialController::download` dan `MaterialPolicy::view`, yang menggunakan hak akses course.

`storage:link` hanya membuat jalur publik untuk `storage/app/public`; tautan tersebut tidak membuka `storage/app/private`. Menaruh materi atau submission di disk publik akan melewati login dan Policy saat URL file diketahui.

---

# BREAK — Delapan kerusakan

| # | Skenario | Hasil yang diamati/diuji | Perlindungan |
|---|---|---|---|
| 1 | Simpan submission di disk `public` lalu akses URL tanpa login | File di bawah direktori publik dapat disajikan oleh web server tanpa melewati controller | File submission dan materi disimpan pada disk `local` yang root-nya `storage/app/private`. |
| 2 | Hilangkan otorisasi pada unduh, lalu ganti ID submission | Mahasiswa lain dapat mencoba mengambil file dengan ID target | Download memanggil Policy; submission hanya dapat dibaca pemilik, dosen pengampu, atau admin. |
| 3 | Gunakan nama asli sebagai nama fisik dan unggah `tugas.pdf` oleh dua mahasiswa | Nama yang sama dapat menimpa file sebelumnya atau membuat penamaan mudah ditebak | `$file->store()` menghasilkan nama acak; nama asli hanya metadata. |
| 4 | Validasi ekstensi saja dan unggah PHP yang diberi nama `.pdf` | Ekstensi tidak membuktikan isi berkas aman atau bertipe PDF | Rule `mimes` memvalidasi tipe konten yang dikenali Laravel; upload tetap terbatas pada tipe yang diizinkan. |
| 5 | Ubah batas dari `max:5120` menjadi `max:5` | File 1 MB ditolak karena `max` menggunakan kilobyte | Submission memakai `max:5120` (5 MB); materi memakai `max:20480` (20 MB). |
| 6 | Atur `upload_max_filesize` lebih kecil daripada batas Laravel | PHP/web server dapat menolak request sebelum Laravel menjalankan validasi | Batas PHP `upload_max_filesize` dan `post_max_size` harus disesuaikan saat deployment. |
| 7 | Ganti `updateOrCreate` menjadi `create`, lalu kumpulkan dua kali | Unique composite `(assignment_id, user_id)` menolak baris duplikat | Controller menggunakan `updateOrCreate` agar pengumpulan ulang memperbarui baris lama. |
| 8 | Hapus row parent lewat cascade SQL dan periksa storage | Event `deleting` pada model anak tidak berjalan pada cascade di level database | Controller menghapus model anak satu per satu sebelum menghapus course, agar event cleanup file berjalan. |

---

# FIX — Implementasi

1. Menyimpan materi dan submission pada disk privat `local` (`storage/app/private`), bukan disk `public`.
2. Menggunakan nama file acak melalui `store()` dan mempertahankan nama asli hanya pada metadata.
3. Menambahkan validasi submission PDF/DOC/DOCX, wajib saat mengumpulkan, maksimal 5 MB; validasi file materi PDF/PPT/PPTX/DOCX maksimal 20 MB.
4. Memakai `updateOrCreate` untuk submission mahasiswa per assignment dan menghitung `is_late` di server.
5. Memakai Policy untuk menolak pengumpulan terlambat ketika `allow_late` false.
6. Menambahkan endpoint unduh materi dan submission. Kedua endpoint memeriksa Policy sebelum mengecek dan mengirim file.
7. Menambahkan event model untuk menghapus file saat record materi atau submission dihapus. Penghapusan course dan assignment menghapus record anak satu per satu agar event file terpicu sebelum cascade database.
8. Menampilkan nama dan ukuran file dalam UI; halaman tugas dosen juga membedakan mahasiswa yang sudah dan belum mengumpulkan.
9. Menampilkan ulang form submission mahasiswa agar pengumpulan dapat diperbarui sebelum deadline atau ketika tugas mengizinkan keterlambatan.
10. Mengatur disk privat agar kegagalan operasi filesystem dilaporkan sebagai exception, bukan hasil sukses semu.

---

# BUILD — Alur berkas KampusLMS

- **Unggah materi:** dosen pemilik course mengunggah PDF/PPT/PPTX/DOCX maksimal 20 MB ke disk privat, atau menyimpan URL eksternal.
- **Unduh materi:** hanya pengguna yang lolos `MaterialPolicy` dapat mengunduh melalui controller.
- **Pengumpulan tugas:** mahasiswa terdaftar mengunggah PDF/DOC/DOCX maksimal 5 MB; pengumpulan ulang memperbarui row lama dan mengganti file privat lama.
- **Deadline:** `is_late` dihitung server. Jika deadline lewat dan `allow_late` false, Policy menolak pengumpulan/perubahan.
- **Unduh submission:** pemilik, dosen pengampu, dan admin diotorisasi melalui `SubmissionPolicy`.
- **Daftar submission:** dosen/admin melihat daftar mahasiswa terdaftar, termasuk status belum mengumpulkan, file, ukuran, waktu, keterlambatan, dan nilai.
- **Cleanup:** record file dihapus melalui event model; penghapusan course/assignment menangani cascade dengan menghapus model anak terlebih dahulu.

### Verifikasi

Perintah:

```powershell
php artisan test tests\Feature\WeekNineFileHandlingTest.php tests\Feature\SubmissionUploadFeatureTest.php tests\Feature\WeekSevenCrudFeatureTest.php
```

Hasil terakhir: **15 tests passed (82 assertions)**.
