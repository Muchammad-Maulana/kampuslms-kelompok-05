# Script Video Checkpoint 5–7

## Identitas

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Proyek:** KampusLMS — Laravel 12
- **Durasi:** sekitar 4–5 menit

---

## 1. Persiapan Sebelum Merekam

### File yang dibuka di VS Code

- [`GradeReleasedNotification.php`](../../app/Notifications/GradeReleasedNotification.php)
- [`NewAssignmentNotification.php`](../../app/Notifications/NewAssignmentNotification.php)
- [`NotificationController.php`](../../app/Http/Controllers/NotificationController.php)
- [`index.blade.php`](../../resources/views/notifications/index.blade.php)
- [`NotifyStudentsOfPublishedAssignment.php`](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php)
- [`NotifyStudentOfGradeAwarded.php`](../../app/Listeners/NotifyStudentOfGradeAwarded.php)
- [`WeekTenNotificationFeatureTest.php`](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [`.env.example`](../../.env.example)

### Halaman dan perintah yang disiapkan

- Browser: halaman aplikasi dan `/notifications`.
- Terminal:

  ```powershell
  php artisan test tests\Feature\WeekTenNotificationFeatureTest.php
  ```

- Konfigurasi queue yang dapat ditunjukkan:

  ```env
  QUEUE_CONNECTION=database
  ```

> Gunakan database development atau test. Jangan melakukan eksperimen pada data production.

---

# 2. Pembukaan

**Tampilan:** halaman utama KampusLMS atau slide judul.

**Narasi:**

> Halo, saya Muchammad Maulana, NIM 10241043.
>
> Pada video ini saya akan menjawab Checkpoint 5 sampai 7 Week 10 pada proyek KampusLMS.
>
> Setelah menjawab setiap pertanyaan, saya akan menunjukkan langkah validasinya melalui source code, halaman aplikasi, dan feature test.

---

# 3. Checkpoint 5 — Nilai dan Feedback pada Payload Notifikasi

## A. Jawaban

**Narasi:**

> Nilai mahasiswa dan feedback tidak dimasukkan ke payload notifikasi karena notifikasi disimpan di database, tepatnya pada kolom `data` tabel `notifications`.
>
> Data pada payload tersebut tidak otomatis terenkripsi oleh Laravel. Oleh karena itu, saya menerapkan prinsip data minimum: payload hanya menyimpan informasi yang dibutuhkan untuk mengenali notifikasi dan membuka halaman tujuan.
>
> Payload menyimpan jenis notifikasi, ID submission, ID tugas, judul tugas, nama mata kuliah, dan URL. Nilai serta feedback tetap diambil dari database ketika mahasiswa membuka halaman tugas, setelah identitas dan hak aksesnya diperiksa.
>
> Dengan cara ini, data sensitif tidak disalin ke dalam payload notifikasi yang dapat tersimpan lebih lama.

## B. Langkah Validasi

### 1. Validasi notification nilai

**Buka file:**

[`GradeReleasedNotification.php`](../../app/Notifications/GradeReleasedNotification.php)

**Sorot method:**

```php
public function toDatabase(object $notifiable): array
```

**Tunjukkan bahwa array hanya berisi:**

```php
'kind'
'submission_id'
'assignment_id'
'assignment_title'
'course_id'
'course_name'
'url'
```

**Narasi pengarah:**

> Di method `toDatabase`, terlihat bahwa payload hanya berisi identitas notifikasi dan URL. Tidak ada key `score` maupun `feedback`.

### 2. Validasi notification tugas

**Buka file:**

[`NewAssignmentNotification.php`](../../app/Notifications/NewAssignmentNotification.php)

**Narasi:**

> Pada notifikasi tugas baru, prinsipnya sama. Payload tidak menyimpan instruksi tugas, nilai, atau feedback. Yang disimpan hanya data ringkas agar mahasiswa dapat mengenali notifikasi dan membuka tugas yang sesuai.

### 3. Validasi dengan feature test

**Buka file:**

[`WeekTenNotificationFeatureTest.php`](../../tests/Feature/WeekTenNotificationFeatureTest.php)

**Cari test:**

```php
test_database_notification_payload_does_not_store_grade_or_feedback
```

**Sorot assertion:**

```php
$this->assertArrayNotHasKey('score', $payload);
$this->assertArrayNotHasKey('feedback', $payload);
```

**Jalankan terminal:**

```powershell
php artisan test tests\Feature\WeekTenNotificationFeatureTest.php
```

**Narasi:**

> Test ini membaca payload yang benar-benar tersimpan di database, lalu memastikan key `score` dan `feedback` tidak ada. Jadi, keputusan untuk tidak menyimpan data sensitif divalidasi pada hasil database, bukan hanya pada source code.

---

# 4. Checkpoint 6 — Pembatasan Akses Notifikasi

## A. Jawaban

**Narasi:**

> Mahasiswa tidak dapat menandai notifikasi milik orang lain sebagai sudah dibaca karena setiap query notifikasi menggunakan relasi user yang sedang login.
>
> Saat endpoint menerima ID notifikasi, aplikasi mencari ID tersebut melalui `$request->user()->notifications()`, bukan melalui seluruh tabel notifications.
>
> Jika notifikasi tersebut bukan milik user aktif, `firstOrFail()` tidak menemukan data dan mengembalikan response 404. Karena itu, pengguna lain tidak dapat mengubah status baca notifikasi tersebut.
>
> Fitur tandai semua dibaca juga hanya mengubah notifikasi yang belum dibaca milik user aktif.

## B. Langkah Validasi

### 1. Validasi controller

**Buka file:**

[`NotificationController.php`](../../app/Http/Controllers/NotificationController.php)

**Cari method:**

```php
public function markRead(Request $request, string $notification): RedirectResponse
```

**Sorot kode:**

```php
$ownedNotification = $request->user()
    ->notifications()
    ->whereKey($notification)
    ->firstOrFail();

$ownedNotification->markAsRead();
```

**Narasi pengarah:**

> Bagian ini membuktikan bahwa notifikasi dicari melalui relasi user aktif. Setelah ditemukan, barulah notifikasi tersebut ditandai sudah dibaca.

### 2. Validasi route dan halaman inbox

**Buka file:**

[`web.php`](../../routes/web.php)

**Tunjukkan route yang berada dalam middleware `auth`:**

```php
Route::middleware('auth')->group(function () {
    Route::get('/notifications', ...);
    Route::post('/notifications/read-all', ...);
    Route::post('/notifications/{notification}/read', ...);
});
```

**Lanjut buka browser:**

```text
/notifications
```

**Narasi:**

> Route notifikasi dilindungi middleware `auth`, sehingga hanya pengguna yang login yang dapat mengaksesnya. Pada halaman inbox tersedia aksi untuk membuka notifikasi, menandai satu notifikasi, dan menandai semua notifikasi sebagai sudah dibaca.

**Jika perlu, buka:**

[`index.blade.php`](../../resources/views/notifications/index.blade.php)

**Tunjukkan:**

```php
route('notifications.read-all')
route('notifications.read', $notification->id)
```

### 3. Validasi dengan feature test

**Buka file:**

[`WeekTenNotificationFeatureTest.php`](../../tests/Feature/WeekTenNotificationFeatureTest.php)

**Cari test yang berkaitan dengan:**

```php
test_student_can_mark_all_owned_notifications_as_read
```

dan test yang memastikan ID notifikasi pengguna lain menghasilkan 404.

**Narasi:**

> Feature test membuat dua user dengan notifikasi berbeda. Test memastikan user hanya dapat mengubah notifikasinya sendiri. Ketika user mencoba memakai ID milik user lain, response yang diharapkan adalah 404 dan `read_at` milik user lain tetap kosong.

**Jalankan terminal:**

```powershell
php artisan test tests\Feature\WeekTenNotificationFeatureTest.php
```

---

# 5. Checkpoint 7 — Implementasi dengan Bantuan AI

## A. Jawaban

**Narasi:**

> Saya menggunakan bantuan AI untuk menyusun kerangka awal event, queued listener, database notification, halaman inbox, dan feature test.
>
> Namun, kode dari AI tidak langsung diterima tanpa pemeriksaan. Saya mencocokkannya dengan Laravel 12, route, policy, model, relasi database, dan konfigurasi queue yang sudah ada di proyek.
>
> Saya kemudian melakukan penyesuaian dan validasi, terutama pada tiga hal: penerima notifikasi, keamanan payload, dan pembatasan akses notifikasi.
>
> Saya juga memperbaiki relasi pemilik submission agar menggunakan foreign key `user_id`. Perbaikan ini memastikan listener nilai menemukan mahasiswa yang benar dan notifikasi nilai tidak salah dikirim.

## B. Langkah Validasi

### 1. Validasi listener tugas

**Buka file:**

[`NotifyStudentsOfPublishedAssignment.php`](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php)

**Sorot:**

```php
class NotifyStudentsOfPublishedAssignment implements ShouldQueue
```

dan:

```php
$students = $assignment->course->students()
    ->where('users.role', 'mahasiswa')
    ->get(['users.id']);
```

**Narasi pengarah:**

> Listener ini menggunakan `ShouldQueue`, sehingga proses notifikasi berjalan melalui queue, bukan memperlambat request utama.
>
> Query mengambil mahasiswa dengan role `mahasiswa` yang terdaftar pada course. Jadi, notifikasi tugas tidak dikirim kepada seluruh user dan tidak dikirim kepada mahasiswa yang tidak terdaftar.

### 2. Validasi listener nilai

**Buka file:**

[`NotifyStudentOfGradeAwarded.php`](../../app/Listeners/NotifyStudentOfGradeAwarded.php)

**Sorot:**

```php
$grade = $event->grade->loadMissing(
    'submission.user',
    'submission.assignment.course'
);
$student = $grade->submission->user;
```

**Narasi:**

> Listener nilai mengambil user dari relasi submission. Dengan demikian, notifikasi nilai hanya dikirim kepada mahasiswa pemilik submission tersebut.

### 3. Validasi hasil implementasi

**Buka file:**

[`WeekTenNotificationFeatureTest.php`](../../tests/Feature/WeekTenNotificationFeatureTest.php)

**Jelaskan test yang terlihat:**

> Feature test memvalidasi bahwa tugas draft tidak mengirim notifikasi, tugas published memasukkan listener ke queue, notifikasi hanya dikirim kepada mahasiswa terdaftar, dan notifikasi nilai hanya dikirim kepada pemilik submission.
>
> Test juga memeriksa payload agar tidak berisi nilai atau feedback, serta memastikan user tidak dapat menandai notifikasi milik user lain.

**Jalankan terminal:**

```powershell
php artisan test tests\Feature\WeekTenNotificationFeatureTest.php
```

**Narasi saat hasil test tampil:**

> Hasil test menjadi validasi bahwa implementasi yang dibantu AI sudah disesuaikan dengan kebutuhan proyek dan bekerja sesuai aturan yang ditentukan.

---

# 6. Penutup

**Tampilan:** hasil test atau halaman `/notifications`.

**Narasi:**

> Kesimpulannya, pada Checkpoint 5 payload notifikasi hanya menyimpan data minimum dan tidak menyimpan nilai maupun feedback.
>
> Pada Checkpoint 6, akses notifikasi dibatasi berdasarkan user yang sedang login, sehingga mahasiswa tidak dapat mengubah notifikasi milik orang lain.
>
> Pada Checkpoint 7, AI digunakan sebagai alat bantu penyusunan kerangka, sedangkan hasil akhirnya tetap diperiksa, disesuaikan dengan proyek, dan divalidasi menggunakan feature test.
>
> Demikian presentasi saya. Terima kasih.

---

## Catatan Saat Presentasi

- Jawab pertanyaan checkpoint terlebih dahulu, baru tunjukkan source code dan test sebagai validasi.
- Jangan menghapus `ShouldQueue` hanya untuk demonstrasi.
- Jangan menampilkan password, isi `.env`, atau data pribadi.
- Jika inbox kosong, gunakan source code dan hasil feature test sebagai bukti implementasi.
- Saat menjelaskan AI, gunakan kalimat:

  > AI membantu menyusun kerangka awal, tetapi implementasi diverifikasi dan disesuaikan dengan proyek.
