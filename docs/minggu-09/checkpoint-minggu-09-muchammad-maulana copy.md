# Jawaban Checkpoint Minggu 9 — Upload File dan Data Dinamis

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

---

## 1. Kenapa submission tidak boleh disimpan di `storage/app/public`?

### Jawaban inti

Submission berisi tugas pribadi mahasiswa. Karena itu, file tidak boleh dapat diakses langsung hanya dengan mengetahui URL-nya. Di KampusLMS, submission disimpan pada disk `local`, yang root-nya `storage/app/private`; [konfigurasi disk `local` dan symbolic link publik](../../config/filesystems.php#L33) menunjukkan pemisahan lokasi penyimpanannya. File kemudian diunduh melalui controller setelah hak akses pengguna diperiksa.

### Penjelasan lebih lanjut

Folder `storage/app/public` dipetakan oleh perintah `php artisan storage:link` ke `public/storage`. Folder di bawah `public` dapat disajikan langsung oleh web server. Jika submission disimpan di sana, seseorang yang memperoleh atau menebak alamat file berpotensi membukanya tanpa melewati login maupun pemeriksaan Policy.

Sebaliknya, file pada `storage/app/private` tidak memiliki tautan publik langsung melalui `storage:link`. Ketika pengguna meminta unduhan, [`SubmissionController::download`](../../app/Http/Controllers/SubmissionController.php#L86) terlebih dahulu menjalankan `Gate::authorize('view', $submission)`. Pola pemeriksaan dan pengiriman file materi juga terlihat pada [`MaterialController`](../../app/Http/Controllers/MaterialController.php#L98). Hanya setelah izin diberikan, aplikasi mengirim file.

**Contoh sederhana:** menyimpan file di folder publik seperti meletakkan tugas di papan pengumuman yang bisa dibuka siapa saja. Menyimpannya di folder privat dan melewati controller seperti menyimpan tugas di ruang terkunci; petugas memeriksa izin sebelum menyerahkannya.

**Catatan penting:** lokasi privat mengurangi risiko akses langsung, tetapi tetap harus disertai pemeriksaan otorisasi pada setiap endpoint unduh. Lokasi privat saja tidak cukup.

Direktori yang digunakan adalah [`storage/app/private/`](../../storage/app/private/) untuk file privat dan [`storage/app/public/`](../../storage/app/public/) untuk file yang dipetakan ke web publik. Perilaku penyimpanan privat dan unduh dengan otorisasi dibuktikan oleh [feature test pengelolaan file minggu ke-9](../../tests/Feature/WeekNineFileHandlingTest.php).

### Kalimat siap presentasi

> “Saya menyimpan submission di disk `local` yang mengarah ke `storage/app/private`, bukan di `storage/app/public`. Dengan begitu file tidak bisa diakses langsung lewat URL publik. Saat file diminta, controller memeriksa Policy terlebih dahulu, baru mengirimkan file jika pengguna memang berhak.”

---

## 2. Kenapa nama berkas fisik dibuat acak? Serangan atau masalah apa yang dicegah?

### Jawaban inti

Nama file fisik dibuat acak agar tidak mudah ditebak dan agar file dengan nama asli yang sama tidak saling menimpa. Nama asli tetap disimpan sebagai metadata untuk ditampilkan kepada pengguna.

### Penjelasan lebih lanjut

KampusLMS menggunakan `$file->store(...)` di [`SubmissionController::store()`](../../app/Http/Controllers/SubmissionController.php#L16) untuk menyimpan file. Laravel membuat nama penyimpanan baru, sedangkan nama asli seperti `tugas.pdf` disimpan pada kolom `original_name`. Model [`Submission`](../../app/Models/Submission.php#L13) menyimpan `file_path`, `original_name`, dan `file_size`.

Cara ini membantu mencegah:

1. **Tabrakan nama:** dua mahasiswa dapat sama-sama mengunggah `tugas.pdf` tanpa file mereka memakai path yang sama.
2. **Penebakan alamat file:** nama acak lebih sulit ditebak daripada path yang memakai nama mahasiswa atau nama asli file.
3. **Penggunaan nama pengguna sebagai path:** nama file dari pengguna tidak langsung dijadikan bagian nama file fisik.

Nama acak bukan pengganti otorisasi. Jika endpoint unduh tidak memeriksa izin, file masih dapat terekspos melalui kelemahan lain. Karena itu, endpoint tetap memeriksa `SubmissionPolicy`.

Hal ini juga diperiksa oleh [test nama path acak dan pengumpulan ulang](../../tests/Feature/WeekNineFileHandlingTest.php#L16).

### Kalimat siap presentasi

> “Nama asli, misalnya `tugas.pdf`, hanya saya simpan untuk ditampilkan. File fisiknya menggunakan nama acak dari Laravel supaya unggahan dengan nama yang sama tidak bertabrakan dan alamat file tidak mudah ditebak. Namun, keamanan utamanya tetap berasal dari penyimpanan privat dan Policy, bukan dari nama acak.”

---

## 3. Apa beda validasi `mimes` dan `extensions`? Mana yang lebih aman dan kenapa?

### Jawaban inti

- `extensions` memeriksa ekstensi atau akhiran nama yang diberikan pada file, misalnya `.pdf`.
- `mimes` memeriksa tipe MIME yang dikenali Laravel dari isi file dan mencocokkannya dengan tipe yang diizinkan.

Pemeriksaan `mimes` lebih kuat daripada hanya mengandalkan ekstensi, karena mengganti nama file tidak otomatis mengubah isi atau tipe file yang terdeteksi.

### Contoh

Misalnya, file program bernama `script.php` diganti namanya menjadi `script.pdf`. Pemeriksaan ekstensi saja dapat melihat akhiran `.pdf`, tetapi isi file belum tentu merupakan dokumen PDF. Pemeriksaan `mimes` menambahkan pemeriksaan berdasarkan tipe file yang dikenali dari kontennya.

Pada submission, [`SubmissionController`](../../app/Http/Controllers/SubmissionController.php#L22) memakai:

```php
'file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120']
```

Artinya file wajib diunggah, harus berupa file, tipe yang dikenali harus termasuk PDF/DOC/DOCX, dan ukurannya tidak boleh melewati batas.

Upload materi menggunakan aturan serupa pada [`StoreMaterialRequest`](../../app/Http/Requests/StoreMaterialRequest.php#L23). Pengujian [penolakan tipe file dan ukuran yang tidak valid](../../tests/Feature/WeekNineFileHandlingTest.php#L126) memastikan batasan tersebut diterapkan.

### Batasan yang perlu dijelaskan

`mimes` bukan jaminan bahwa dokumen bebas virus atau sepenuhnya aman. Validasi tipe file merupakan salah satu lapisan perlindungan. Aplikasi tetap membatasi ukuran, menyimpan file secara privat, dan memeriksa izin akses sebelum file diunduh.

### Kalimat siap presentasi

> “`extensions` hanya melihat akhiran nama file, sedangkan `mimes` memeriksa tipe file yang dikenali dari kontennya. Karena nama file bisa diganti tanpa mengubah isinya, `mimes` lebih dapat diandalkan daripada ekstensi saja. Tetapi validasi ini bukan pemindai virus, sehingga tetap perlu digabung dengan batas ukuran, storage privat, dan otorisasi.”

---

## 4. `max:5120` berarti berapa MB? Apa yang bisa membatalkan batas ini di level server?

### Jawaban inti

Pada validasi file Laravel, nilai `max` dihitung dalam kilobyte. Jadi `max:5120` berarti **5120 KB**, atau sekitar **5 MiB**—umumnya disebut 5 MB.

### Penjelasan lebih lanjut

Batas Laravel hanya berlaku jika request berhasil sampai ke aplikasi. Sebelum itu, PHP atau web server dapat menolak request yang terlalu besar. Beberapa konfigurasi yang perlu diperiksa saat deployment adalah:

- PHP `upload_max_filesize`, yaitu batas ukuran satu file unggahan.
- PHP `post_max_size`, yaitu batas total ukuran isi request POST.
- Batas ukuran request pada web server atau reverse proxy, jika digunakan.

Karena itu, batas di server harus disetel agar mendukung batas aplikasi. Bila batas server lebih kecil, Laravel mungkin tidak sempat menjalankan validasi file karena request sudah ditolak lebih dahulu.

**Contoh:** jika batas Laravel 5 MB tetapi `upload_max_filesize` di PHP hanya 2 MB, file berukuran 3 MB ditolak di tingkat PHP. Mengubah validasi Laravel saja tidak akan menyelesaikan masalah tersebut.

Batas aplikasi submission `max:5120` terlihat di [`SubmissionController`](../../app/Http/Controllers/SubmissionController.php#L22), sedangkan contoh batas materi `max:20480` ada di [`StoreMaterialRequest`](../../app/Http/Requests/StoreMaterialRequest.php#L23). Penjelasan keamanan dan konfigurasi upload juga tersedia di [dokumentasi keamanan](../keamanan.md).

> Catatan: `upload_max_filesize` dan `post_max_size` merupakan konfigurasi PHP di lingkungan server, bukan file konfigurasi Laravel pada repository ini. Sesuaikan nilainya pada `php.ini` atau konfigurasi hosting/server yang digunakan.

### Kalimat siap presentasi

> “`max:5120` berarti 5120 KB, kira-kira 5 MiB atau biasa disebut 5 MB. Namun, upload juga dibatasi PHP dan web server. Jika `upload_max_filesize`, `post_max_size`, atau batas proxy lebih kecil, request bisa ditolak sebelum mencapai validasi Laravel.”

---

## 5. Tunjukkan apa yang mencegah mahasiswa mengunduh submission mahasiswa lain.

### Jawaban inti

Sebelum file dikirim, `SubmissionController::download` menjalankan:

```php
Gate::authorize('view', $submission);
```

Pemeriksaan tersebut memanggil [`SubmissionPolicy::view`](../../app/Policies/SubmissionPolicy.php#L16). Mahasiswa hanya boleh melihat submission miliknya sendiri. Dosen pengampu course dan admin juga boleh melihatnya sesuai aturan Policy.

### Urutan proses unduh

1. Pengguna membuka endpoint unduh untuk suatu submission.
2. Laravel menemukan record submission yang diminta.
3. Controller meminta Gate mengotorisasi aksi `view`.
4. `SubmissionPolicy` membandingkan pemilik submission dengan pengguna yang sedang login. Untuk dosen, Policy memeriksa apakah dosen tersebut mengampu course terkait; admin juga diizinkan.
5. Jika akses ditolak, file tidak dikirim. Jika diizinkan, controller memastikan file ada lalu mengirimkannya.

Mengganti ID pada URL tidak mengubah pemilik submission dan tidak melewati pemeriksaan Policy. Pengguna yang tidak berhak akan ditolak. Route untuk endpoint ini didefinisikan di [`routes/web.php`](../../routes/web.php#L53), dan perilaku penolakan mahasiswa lain dibuktikan oleh [feature test unduh submission](../../tests/Feature/WeekNineFileHandlingTest.php#L41).

### Kalimat siap presentasi

> “Perlindungannya ada di `SubmissionController::download`, yang memanggil `Gate::authorize('view', $submission)`. Policy kemudian hanya mengizinkan pemilik submission, dosen pengampu course, atau admin. Jadi, kalau mahasiswa mengganti ID pada URL menjadi ID milik mahasiswa lain, pemeriksaan tetap menggunakan identitas pengguna yang sedang login dan aksesnya ditolak.”

---

## 6. Kenapa `is_late` dihitung di server, bukan dikirim dari browser?

### Jawaban inti

Browser berada di sisi pengguna sehingga nilai yang dikirim dari sana dapat diubah. Karena itu, status terlambat harus dihitung dari waktu server dan deadline yang tersimpan pada assignment.

### Penjelasan lebih lanjut

Saat submission dibuat atau file submission diperbarui, [`SubmissionController`](../../app/Http/Controllers/SubmissionController.php#L36) mengambil waktu server dengan `now()`, lalu membandingkannya dengan `due_at`:

```php
'is_late' => $submittedAt->greaterThan($assignment->due_at)
```

Jika waktu pengumpulan melewati deadline, `is_late` bernilai `true`; jika tidak, nilainya `false`. Dengan cara ini, pengguna tidak dapat sekadar mengirim `is_late=false` dari browser untuk menyamarkan keterlambatan.

Izin mengumpulkan juga diperiksa di server melalui [`SubmissionPolicy`](../../app/Policies/SubmissionPolicy.php#L22). Jika deadline sudah lewat dan `allow_late` bernilai `false`, pengumpulan ditolak. Jika keterlambatan diizinkan, submission dapat diterima dan status terlambat tetap dicatat sesuai waktu pengumpulan. Field dan cast `due_at` serta `allow_late` didefinisikan pada model [`Assignment`](../../app/Models/Assignment.php#L33), sementara [feature test deadline dan status terlambat](../../tests/Feature/WeekNineFileHandlingTest.php#L83) menguji perilakunya.

### Kalimat siap presentasi

> “Saya tidak mempercayai nilai `is_late` dari browser, karena pengguna bisa mengubah data request. Controller menghitungnya memakai waktu server dan `due_at` dari assignment. Policy juga memutuskan apakah submission setelah deadline boleh diterima berdasarkan `allow_late`.”

---

## Ringkasan bukti uji

Feature test menggunakan database dan storage palsu untuk memeriksa perilaku berikut:

- submission dan materi disimpan pada disk privat dengan nama fisik acak;
- pengumpulan ulang memperbarui submission yang sama dan mengganti file sebelumnya;
- hanya pemilik submission, dosen pengampu, atau admin yang dapat mengunduh;
- mahasiswa lain ditolak saat mencoba mengunduh submission;
- pengumpulan setelah deadline ditolak ketika keterlambatan tidak diizinkan;
- file dibersihkan ketika record atau course induk dihapus;
- daftar dosen menampilkan mahasiswa yang belum mengumpulkan.

Untuk menjalankan test utama pengelolaan file:

```powershell
php artisan test tests\Feature\WeekNineFileHandlingTest.php
```

Untuk menjalankan rangkaian test fitur terkait yang juga tercatat pada dokumentasi minggu ini:

```powershell
php artisan test tests\Feature\WeekNineFileHandlingTest.php tests\Feature\SubmissionUploadFeatureTest.php tests\Feature\WeekSevenCrudFeatureTest.php
```

Hasil terakhir yang tercatat pada dokumentasi implementasi minggu ini: **15 tests passed (82 assertions)**.

---

## Naskah presentasi singkat

> “Pada fitur upload KampusLMS, submission disimpan di disk privat `local`, bukan di folder publik, supaya file tidak dapat dibuka langsung tanpa pemeriksaan akses. Nama file fisik dibuat acak oleh Laravel untuk menghindari tabrakan nama dan agar path tidak mudah ditebak, sedangkan nama asli tetap disimpan sebagai metadata.
>
> Untuk validasi, aplikasi memakai `mimes` agar tipe file yang dikenali dari konten ikut diperiksa, bukan hanya akhiran namanya. Batas `max:5120` setara dengan sekitar 5 MiB. Batas ini harus selaras dengan konfigurasi PHP dan web server karena keduanya dapat menolak upload lebih dulu.
>
> Saat download, controller memanggil Gate dan `SubmissionPolicy`. Policy membatasi akses kepada pemilik submission, dosen pengampu, atau admin. Terakhir, status terlambat dihitung di server dengan membandingkan waktu pengumpulan dan deadline, sehingga tidak bisa dimanipulasi dari browser. Semua ini didukung oleh feature test untuk akses, pengumpulan ulang, deadline, penyimpanan, dan penghapusan file.”

## Kata kunci untuk mengingat

**Storage privat → nama fisik acak → validasi isi dan ukuran → otorisasi Policy → waktu dan status dihitung server.**
