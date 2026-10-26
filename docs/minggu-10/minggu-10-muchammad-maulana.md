# Minggu 10 — Realtime, Event, Queue, dan Notifikasi

## Nama: Muchammad Maulana
## NIM: 10241043
## Proyek: KampusLMS — Laravel 12

---

# READ — Alur Event, Queue, dan Notifikasi

## 1. Queue database sudah tersedia

Koneksi queue default proyek menggunakan `database`, dan `.env.example` juga menetapkan `QUEUE_CONNECTION=database`. Migrasi bawaan Laravel [`create_jobs_table`](../../database/migrations/0001_01_01_000002_create_jobs_table.php) sudah membuat tabel `jobs`, `job_batches`, serta `failed_jobs`; jadi proyek ini tidak memerlukan migrasi queue tambahan. Week 10 menambahkan migrasi tabel [notifications](../../database/migrations/2026_10_26_000001_create_notifications_table.php).

Dengan koneksi database, request web dapat menyimpan tugas atau nilai, kemudian mengantrekan pekerjaan. Worker terpisah memproses job. Jika worker tidak berjalan, pekerjaan tetap berada di tabel `jobs`; aplikasi web tidak otomatis menampilkan error hanya karena queue belum diproses.

## 2. Dua peristiwa yang memicu notifikasi

Alurnya dipisah agar controller cukup menyimpan perubahan dan mengumumkan event:

```text
Dosen memublikasikan tugas
  -> AssignmentPublished
  -> NotifyStudentsOfPublishedAssignment (queued)
  -> satu queued notification untuk setiap mahasiswa terdaftar
  -> tabel notifications

Dosen menyimpan nilai
  -> GradeAwarded
  -> NotifyStudentOfGradeAwarded (queued)
  -> queued notification untuk pemilik submission
  -> tabel notifications
```

Event [`AssignmentPublished`](../../app/Events/AssignmentPublished.php) dipicu saat tugas langsung dibuat dengan status `published` atau saat tugas berubah dari `draft` menjadi `published`. Event [`GradeAwarded`](../../app/Events/GradeAwarded.php) dipicu setelah nilai tersimpan.

Listener [`NotifyStudentsOfPublishedAssignment`](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php) mengambil mahasiswa terdaftar melalui satu query relasi, lalu mengirim notifikasi. Listener [`NotifyStudentOfGradeAwarded`](../../app/Listeners/NotifyStudentOfGradeAwarded.php) mengirim notifikasi hanya kepada mahasiswa pemilik submission. Keduanya menerapkan `ShouldQueue`, tiga percobaan, jeda 10 detik, dan method `failed()` untuk mencatat kegagalan di log.

Notification juga menerapkan `ShouldQueue`. Karena itu, event tugas yang diterima satu listener akan membuat satu job listener dan, saat listener berjalan, satu job notifikasi untuk setiap penerima. Notifikasi yang gagal diproses dapat dicoba ulang tanpa mengulang notifikasi ke seluruh kelas.

## 3. Isi notifikasi dan perlindungan data

[`NewAssignmentNotification`](../../app/Notifications/NewAssignmentNotification.php) dan [`GradeReleasedNotification`](../../app/Notifications/GradeReleasedNotification.php) hanya menggunakan channel `database`. Data yang disimpan berisi jenis peristiwa, ID tugas/submission/course, judul tugas, nama course, dan URL tujuan. Payload **tidak menyimpan nilai atau feedback**; nilai dan feedback hanya dapat dibaca melalui halaman aplikasi yang menerapkan cakupan pengguna.

Trait `SerializesModels` digunakan pada notification dan event agar job queue menyimpan identitas model, bukan menyalin seluruh data nilai ke payload job.

## 4. Lonceng, inbox, dan rekap nilai

[`NotificationController`](../../app/Http/Controllers/NotificationController.php) hanya membaca notifikasi melalui relasi milik akun yang sedang login. Endpoint jumlah belum dibaca juga menghitung relasi pengguna aktif, bukan seluruh tabel. Route `notifications/{notification}/read` mencari ID notifikasi melalui relasi tersebut; ID milik pengguna lain menghasilkan 404. Halaman inbox menyediakan tandai satu dan tandai semua dibaca.

Lonceng di [`layout.blade.php`](../../resources/views/components/layout.blade.php) memperbarui jumlah belum dibaca dengan polling setiap 30 detik. Ini memenuhi kebutuhan realtime minimum tanpa server WebSocket tambahan.

Rekap dosen tersedia per course melalui [`GradeReportController`](../../app/Http/Controllers/GradeReportController.php) dan dibatasi oleh `CoursePolicy`. Halaman menampilkan mahasiswa terdaftar, status pengumpulan, nilai, dan feedback. Rekap mahasiswa membatasi query berdasarkan pemilik submission sehingga mahasiswa hanya melihat nilai dan feedback miliknya sendiri.

---

# BREAK — Skenario Kerusakan dan Cara Mendeteksinya

Skenario pemeriksaan dilakukan dengan feature test/database test agar tidak mengubah data nyata.

| # | Skenario | Gejala atau dampak | Pemeriksaan/perlindungan |
|---|---|---|---|
| 1 | `QUEUE_CONNECTION=database` tetapi worker tidak berjalan | Tugas/nilai berhasil disimpan, tetapi pemberitahuan belum muncul | Periksa jumlah job tertunda pada tabel `jobs`, lalu jalankan `php artisan queue:work`. |
| 2 | Listener tidak menerapkan `ShouldQueue` | Pengiriman notifikasi berlangsung dalam request yang membuat tugas/nilai | Feature test memastikan event memasukkan listener ke queue; implementasi listener berada pada folder `app/Listeners`. |
| 3 | Tugas berstatus draft dianggap sebagai tugas baru | Mahasiswa mendapat pemberitahuan sebelum tugas diterbitkan | Event hanya dipicu saat tugas dibuat published atau bertransisi dari draft ke published. |
| 4 | Penerima tugas diambil satu per satu melalui query dalam loop | Beban query bertambah seiring jumlah mahasiswa | Listener mengambil koleksi mahasiswa terdaftar sekali melalui relasi course sebelum mengirim. |
| 5 | Nilai atau feedback dimasukkan ke payload notifikasi | Data pribadi tersimpan permanen di kolom `notifications.data` | Test membaca payload database dan memastikan key `score` dan `feedback` tidak ada. |
| 6 | Pengguna mengganti ID saat menandai notifikasi dibaca | Dapat mengubah status baca milik orang lain bila pencarian tidak dibatasi | Controller mencari notifikasi dari `$request->user()->notifications()`; test memastikan ID pengguna lain mendapat 404. |
| 7 | Worker mencoba ulang job tetapi tidak pernah berhasil | Job akhirnya masuk daftar failed dan proses perlu ditangani | Listener memiliki `tries`, `backoff`, dan `failed()`; periksa dengan `php artisan queue:failed`. |
| 8 | Mahasiswa mengganti ID course atau meminta rekap nilai mahasiswa lain | Informasi course/nilai berpotensi bocor | Policy course membatasi dosen, sedangkan rekap mahasiswa meng-query grade melalui `submission.user_id` pengguna aktif. |

Urutan diagnosis saat notifikasi belum terlihat:

1. Pastikan event memang terpicu setelah tugas diterbitkan atau nilai tersimpan.
2. Periksa job tertunda di tabel `jobs`.
3. Pastikan worker aktif dengan `php artisan queue:work`.
4. Periksa job yang gagal melalui `php artisan queue:failed` dan log aplikasi.
5. Periksa tabel `notifications` serta ID `notifiable_id` penerima.

---

# FIX — Implementasi Minggu 10

1. Menambahkan migrasi tabel notifications; memakai tabel `jobs` dan `failed_jobs` bawaan Laravel tanpa membuat migrasi duplikat.
2. Menambahkan event `AssignmentPublished` dan `GradeAwarded`, kemudian memicunya setelah data tugas/nilai tersimpan.
3. Menambahkan queued listener dengan tiga percobaan, jeda 10 detik, dan log ketika job berakhir gagal.
4. Mengirim notification database secara asinkron untuk mahasiswa terdaftar atau mahasiswa pemilik submission.
5. Menjaga payload database notifikasi tetap non-sensitif—tanpa nilai maupun feedback.
6. Membatasi inbox, hitungan belum dibaca, dan aksi tandai-dibaca pada notifikasi akun aktif; notifikasi asing tidak ditemukan melalui endpoint.
7. Menambahkan lonceng di navigasi dan polling 30 detik, serta halaman inbox dengan pagination dan aksi baca satu/semua.
8. Menyediakan rekap nilai per course untuk dosen dan rekap nilai pribadi untuk mahasiswa; nilai dan feedback juga ditampilkan pada detail tugas mahasiswa setelah dinilai.
9. Memperbaiki relasi `Submission::student()` agar memakai foreign key skema `user_id`, sehingga listener dapat menemukan pemilik submission secara konsisten.
10. Menambahkan feature test untuk pemicu event, queue listener, pembatasan penerima, isi payload, keamanan endpoint baca, dan rekap nilai.

---

# BUILD — Hasil Minggu 10

- Tugas yang diterbitkan memicu notifikasi kepada mahasiswa terdaftar; tugas draft tidak memicu pemberitahuan.
- Nilai yang disimpan memicu notifikasi bagi mahasiswa pemilik submission.
- Queue database memproses listener dan notifikasi di luar request web.
- Worker dapat dijalankan menggunakan `php artisan queue:work`; failed job dapat diperiksa dengan `php artisan queue:failed` dan dicoba ulang dengan `php artisan queue:retry all`.
- Lonceng memperbarui hitungan notifikasi belum dibaca melalui polling, dan pengguna dapat membuka inbox serta menandai notifikasi sudah dibaca.
- Rekap nilai tersedia bagi dosen pengampu per course dan bagi mahasiswa secara pribadi.
- Isi notifikasi tidak menyimpan nilai atau feedback.

### Verifikasi

Test Week 10:

```powershell
php artisan test tests\Feature\WeekTenNotificationFeatureTest.php
```

Hasil test Week 10: **10 tests passed (59 assertions)**. Seluruh suite proyek: **85 tests passed (441 assertions)**.

Kalimat inti untuk interview:

> “Saat tugas dipublikasikan atau dosen memberi nilai, aplikasi mengirim event. Listener yang menerapkan `ShouldQueue` memproses penerima di background, lalu notification database disimpan oleh queue. Jika worker mati, job tetap menunggu di tabel jobs; saya memeriksa worker, failed jobs, dan log. Payload hanya berisi metadata, tidak berisi nilai atau feedback. Inbox dan aksi tandai dibaca selalu dibatasi pada notifikasi milik pengguna yang login.”
