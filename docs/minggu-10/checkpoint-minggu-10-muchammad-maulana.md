# Jawaban Checkpoint Minggu 10 — Realtime, Event, Queue, dan Notifikasi

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12

---

## 1. Apa yang terjadi kalau queue worker mati saat notifikasi dikirim?

### Jawaban inti

Koneksi queue proyek menggunakan database. Saat tugas dipublikasikan atau nilai diberikan, event tetap berjalan dan job listener dimasukkan ke tabel `jobs`. Tugas atau nilai sudah disimpan oleh request web, tetapi notifikasi belum muncul sampai worker hidup dan memproses job tersebut.

Jika worker mati sebelum mengambil job, job tetap menunggu di `jobs`. Jika proses worker mati ketika job sedang dikerjakan, job yang sudah di-reserve dapat dicoba kembali setelah masa `retry_after` sesuai konfigurasi queue. Setelah semua percobaan gagal, job dicatat di `failed_jobs`.

### Penjelasan di KampusLMS

- Event [`AssignmentPublished`](../../app/Events/AssignmentPublished.php) dipicu setelah tugas dibuat langsung sebagai `published` atau diubah dari `draft` menjadi `published`.
- Event [`GradeAwarded`](../../app/Events/GradeAwarded.php) dipicu setelah nilai tersimpan.
- Listener [`NotifyStudentsOfPublishedAssignment`](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php) dan [`NotifyStudentOfGradeAwarded`](../../app/Listeners/NotifyStudentOfGradeAwarded.php) memakai `ShouldQueue`.
- Kedua notifikasi juga memakai `ShouldQueue`, jadi setiap penerima akan mendapat job notifikasi tersendiri setelah listener memproses event.

### Cara memperagakan dengan aman

Gunakan database development atau test, bukan data production:

1. Pastikan `.env` memakai `QUEUE_CONNECTION=database`.
2. Jangan jalankan worker. Terbitkan tugas untuk course yang memiliki mahasiswa terdaftar.
3. Periksa apakah job menunggu:

   ```sql
   SELECT id, queue, attempts, created_at FROM jobs;
   ```

4. Jalankan worker:

   ```bash
   php artisan queue:work
   ```

5. Periksa tabel `jobs` dan `notifications` lagi. Job listener diproses lebih dahulu, lalu job notifikasi untuk penerimanya.

**Poin penting:** request yang berhasil tidak berarti notifikasi sudah terkirim. Request dan pekerjaan background memiliki siklus hidup berbeda.

---

## 2. Kenapa kegagalan itu tidak menghasilkan pesan error? Bagaimana mengetahuinya di server?

### Jawaban inti

Dengan queue database, request hanya mencatat pekerjaan ke queue lalu mengembalikan response. Pengiriman notifikasi dikerjakan proses worker terpisah. Kalau worker tidak berjalan, aplikasi web dapat terlihat normal karena tidak ada proses yang mencoba mengirim notifikasi pada saat itu.

Ini bukan berarti semua kegagalan tidak tercatat. Job yang gagal diproses berulang kali dapat masuk ke `failed_jobs`; tetapi tidak adanya worker berarti job umumnya hanya menunggu di `jobs`.

### Langkah diagnosis

1. Pastikan tindakan pemicu terjadi: tugas benar-benar menjadi `published` atau nilai sudah tersimpan.
2. Periksa jumlah job yang menunggu di tabel `jobs`.
3. Pastikan worker berjalan dan memakai konfigurasi environment yang sama dengan aplikasi.
4. Periksa job gagal:

   ```bash
   php artisan queue:failed
   ```

5. Baca log aplikasi untuk pesan yang dicatat method `failed()` pada listener.
6. Periksa tabel `notifications` untuk memastikan notification sudah disimpan bagi penerima yang benar.
7. Jika penyebabnya sudah diperbaiki, jalankan ulang job yang gagal:

   ```bash
   php artisan queue:retry all
   ```

Listener mempunyai tiga percobaan dan jeda 10 detik. Method `failed()` pada [listener tugas baru](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php) dan [listener nilai](../../app/Listeners/NotifyStudentOfGradeAwarded.php) mencatat kegagalan ke log.

### Operasional server

Di production, worker harus dijalankan sebagai proses yang dikelola service manager/supervisor agar dimulai kembali bila berhenti. Pantau proses worker, jumlah job tertunda, jumlah failed job, log, dan umur job tertua. Worker juga perlu dimulai ulang setelah deployment ketika kode berubah, misalnya dengan perintah restart queue worker yang sesuai prosedur deployment.

---

## 3. Apa beda `QUEUE_CONNECTION=sync` dan `QUEUE_CONNECTION=database`?

| Koneksi | Perilaku | Kegunaan dan konsekuensi |
|---|---|---|
| `sync` | Job dijalankan langsung dalam request yang memicunya. | Praktis untuk debugging sederhana, tetapi pekerjaan lambat menambah waktu response dan bukan pemrosesan asynchronous. |
| `database` | Job disimpan sebagai row pada tabel `jobs`, lalu worker terpisah mengambil dan menjalankannya. | Request dapat selesai lebih cepat, tetapi worker harus aktif dan tabel queue harus tersedia. Job yang menunggu tidak diproses sampai worker berjalan. |

Proyek menetapkan `QUEUE_CONNECTION=database` pada [`.env.example`](../../.env.example). Migrasi bawaan Laravel sudah membuat tabel `jobs` dan `failed_jobs`, sehingga tidak perlu membuat migrasi queue kedua. Untuk development, `composer run dev` menjalankan aplikasi bersama queue listener; worker juga dapat dijalankan sendiri dengan `php artisan queue:work`.

---

## 4. Tunjukkan Listener yang dibuat. Apa akibatnya kalau `ShouldQueue` dihapus?

### Jawaban inti

Listener tugas baru adalah [`NotifyStudentsOfPublishedAssignment`](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php). Listener tersebut menerima `AssignmentPublished`, mengambil mahasiswa ber-role `mahasiswa` yang terdaftar di course, lalu mengirim [`NewAssignmentNotification`](../../app/Notifications/NewAssignmentNotification.php).

```php
class NotifyStudentsOfPublishedAssignment implements ShouldQueue
{
    public int $tries = 3;
    public int $backoff = 10;

    public function handle(AssignmentPublished $event): void
    {
        $assignment = $event->assignment->loadMissing('course');
        $students = $assignment->course->students()
            ->where('users.role', 'mahasiswa')
            ->get(['users.id']);

        Notification::send($students, new NewAssignmentNotification($assignment));
    }
}
```

Jika `ShouldQueue` dihapus, listener dijalankan sinkron saat event dipicu. Mengambil penerima dan membuat notification kemudian ikut memperlambat request yang menerbitkan tugas atau menyimpan nilai. Retry dan penanganan failed job sebagai pekerjaan queue juga tidak lagi berlaku pada listener tersebut.

Pada implementasi ini notification itu sendiri juga queued. Karena itu, untuk satu tugas dengan 40 penerima, ada satu job listener dan kemudian satu job notification per penerima—total 41 job jika semua penerima diproses. Test worker menggunakan dua mahasiswa untuk membuktikan urutan tersebut: satu job listener menjadi dua job notification.

Listener nilai [`NotifyStudentOfGradeAwarded`](../../app/Listeners/NotifyStudentOfGradeAwarded.php) mengikuti pola serupa, tetapi penerimanya hanya mahasiswa pemilik submission.

---

## 5. Kenapa nilai mahasiswa tidak boleh masuk payload notifikasi?

Payload notification disimpan di kolom `data` pada tabel `notifications`. Data tersebut tidak terenkripsi secara otomatis oleh notification database Laravel. Karena itu, nilai dan feedback tidak perlu disalin ke payload yang tersimpan lebih lama.

[`GradeReleasedNotification`](../../app/Notifications/GradeReleasedNotification.php) hanya menyimpan jenis notifikasi, ID submission/assignment/course, judul tugas, nama course, serta URL. Nilai dan feedback diambil dari database pada halaman yang memeriksa identitas dan hak akses pengguna. Hal ini mengikuti prinsip data minimum: simpan informasi yang diperlukan untuk mengenali dan membuka pemberitahuan, bukan isi sensitifnya.

Hal serupa berlaku untuk [`NewAssignmentNotification`](../../app/Notifications/NewAssignmentNotification.php): payload tidak menyimpan instruksi tugas, nilai, atau feedback. Feature test memastikan key `score` dan `feedback` tidak ada pada payload database.

---

## 6. Apa yang mencegah mahasiswa menandai notifikasi orang lain sebagai sudah dibaca?

Semua route notifikasi dilindungi middleware `auth`. Pada aksi tandai dibaca, [`NotificationController::markRead`](../../app/Http/Controllers/NotificationController.php) mencari notifikasi melalui relasi:

```php
$request->user()
    ->notifications()
    ->whereKey($notification)
    ->firstOrFail();
```

Query tersebut dibatasi oleh pemilik notifikasi. Jika mahasiswa mengganti ID dengan ID milik pengguna lain, record itu tidak ditemukan pada relasinya dan endpoint mengembalikan 404. Aksi tandai semua dibaca juga dijalankan melalui relasi `unreadNotifications()` milik pengguna aktif, bukan update seluruh tabel.

Hitungan lonceng dan daftar inbox dibatasi dengan cara yang sama. [Feature test Week 10](../../tests/Feature/WeekTenNotificationFeatureTest.php) memeriksa bahwa ID notifikasi pengguna lain menghasilkan 404 dan `read_at` milik pengguna tersebut tetap kosong.

---

## 7. Tunjukkan bagian yang dibuat dengan bantuan AI. Apa yang diubah dan kenapa?

### Contoh jawaban saat interview

> “Saya menggunakan bantuan AI untuk menyusun kerangka Event, queued Listener, Notification database, halaman inbox, dan rekap nilai. Saya tidak langsung menerima hasilnya begitu saja. Saya mencocokkan implementasinya dengan route, Policy, relasi, dan struktur queue yang sudah ada di Laravel 12. Saya juga memastikan notifikasi tidak menyimpan nilai atau feedback, endpoint baca notifikasi membatasi query ke pengguna yang login, dan tugas draft tidak memicu notifikasi. Saya menambahkan feature test untuk memastikan alur worker dan pembatasan penerima benar-benar berjalan. Saat pemeriksaan, relasi pemilik submission perlu memakai foreign key `user_id`; saya memperbaikinya supaya notifikasi nilai diarahkan ke mahasiswa yang tepat.”

Pilih dan buka file yang memang dipahami ketika menjawab, misalnya [listener publikasi tugas](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php), [controller notifikasi](../../app/Http/Controllers/NotificationController.php), atau [feature test Week 10](../../tests/Feature/WeekTenNotificationFeatureTest.php). Jelaskan alurnya dengan kata-kata sendiri, bukan hanya membaca nama class.

---

## Ringkasan untuk diingat

**Event menyatakan sesuatu terjadi → queued Listener menentukan penerima → queued Notification membuat pekerjaan per penerima → worker menyimpan notifikasi → inbox dan aksi baca dibatasi pada pemiliknya.**

Perintah yang paling penting saat demonstrasi:

```bash
php artisan queue:work
php artisan queue:failed
php artisan queue:retry all
php artisan test tests\Feature\WeekTenNotificationFeatureTest.php
```

Untuk menjalankan test dan worker, gunakan database development/test. Jangan menjalankan eksperimen yang memodifikasi queue atau notifikasi pada data production.
