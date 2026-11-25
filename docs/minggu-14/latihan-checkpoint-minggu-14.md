# Latihan Checkpoint Minggu 14

Dokumen ini berisi pertanyaan yang dapat digunakan untuk latihan lisan,
jawaban yang siap dipertanggungjawabkan, serta tautan bukti yang dapat diklik.

Petunjuk:

- baca soal;
- jawab dengan kata-kata sendiri;
- jelaskan bukti;
- sebutkan batas yang belum diverifikasi;
- jangan mengarang hasil atau fitur.

## 1. Peragakan: hapus satu fitur, tunjukkan test menjadi merah

### Soal

Bagaimana cara membuktikan bahwa sebuah test benar-benar menguji fitur,
bukan sekadar hijau tanpa alasan?

### Jawaban

Pilih satu fitur kecil, misalnya unduhan submission oleh mahasiswa lain.

1. Pastikan test `only_submission_owner_course_lecturer_and_admin_can_download_submission`
   dapat dijalankan dan lulus.
2. Hapus sementara pemeriksaan Policy pada endpoint download atau ubah
   controller agar user lain diperbolehkan.
3. Jalankan test tersebut.
4. Tunjukkan test menjadi merah karena `assertForbidden()` tidak terpenuhi.
5. Pulihkan kode.
6. Jalankan test kembali sampai hijau.

Test ini dapat membuka file atau browser dari mahasiswa lain menjadi
`403`; setelah kontrol otorisasi dihapus, test seharusnya tidak lagi
mendapatkan respons tersebut.

### Bukti

- [Test unduhan submission](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Policy Submission](../../app/Policies/SubmissionPolicy.php)
- [Controller submission](../../app/Http/Controllers/SubmissionController.php)
- [Panduan demonstrasi fitur rusak](panduan-pemahaman-minggu-14.md)

### Perintah

```powershell
php artisan test --filter=only_submission_owner_course_lecturer_and_admin_can_download_submission
```

### Status jujur

Test sudah tersedia dan suite lulus. Demonstrasi menonaktifkan fitur secara
sementara masih perlu diperagakan langsung saat checkpoint.

---

## 2. Mengapa test yang hanya `assertStatus(200)` sering tidak bermakna?

### Soal

Mengapa `assertStatus(200)` atau `assertOk()` tidak selalu membuktikan bahwa
fitur bekerja?

### Jawaban

Status 200 hanya membuktikan halaman atau request menerima respons berhasil.
Status tersebut tidak membuktikan:

- data yang ditampilkan benar;
- data milik user lain tidak bocor;
- row database dibuat;
- row lama tidak terhapus;
- file benar-benar tersimpan;
- notifikasi benar-benar dikirim;
- role atau Policy berjalan dengan benar.

Test bermakna harus memeriksa paling sedikit satu efek atau isi:

- `assertForbidden()` untuk penolakan;
- `assertDatabaseHas()` atau `assertDatabaseMissing()` untuk mutasi;
- `assertExists()` atau `assertMissing()` untuk file;
- `assertSee()` atau `assertDontSee()` untuk isi yang benar;
- `Notification::assertSentTo()` untuk penerima notifikasi.

Contoh test yang lebih kuat membuat data sendiri, mengirim request, lalu
memeriksa respons dan efek.

### Bukti

- [Test upload submission](../../tests/Feature/SubmissionUploadFeatureTest.php)
- [Test late submission](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Test notifikasi dan grade](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Penjelasan test bermakna](panduan-pemahaman-minggu-14.md)

### Jawaban singkat untuk presentasi

> “200 hanya membuktikan bahwa respons berhasil. Itu belum membuktikan data
> benar, mutasi terjadi, atau ID milik user lain ditolak. Saya menambah
> assertion database, storage, isi, atau 403 agar test benar-benar menguji
> perilaku.”

---

## 3. Mengapa test jalur ditolak lebih penting dari test jalur sukses?

### Soal

Jelaskan mengapa test kasus 403, validasi gagal, dan pengguna tidak berhak
lebih penting pada aplikasi LMS.

### Jawaban

Jalur sukses membuktikan fitur dapat bekerja. Jalur ditolak mencegah:

- IDOR;
- dosen mengubah course dosen lain;
- mahasiswa membuka course yang tidak diikuti;
- mahasiswa mengunduh submission mahasiswa lain;
- user tanpa role mengubah akun atau assignment;
- penyimpanan submission setelah deadline;
- notifikasi atau grade dibaca oleh pihak yang salah.

Pengguna dapat memanggil endpoint langsung meskipun tombol disembunyikan
di Blade. Oleh karena itu, otorisasi harus diuji di server dan statusnya
harus 403, bukan hanya tombol tidak tampil.

Test di proyek membuktikan:

- dosen lain tidak dapat mengelola assignment/course miliknya;
- mahasiswa lain tidak dapat mengunduh submission;
- mahasiswa tidak terdaftar tidak dapat submit;
- mahasiswa tidak dapat membaca submission atau grade orang lain.

### Bukti

- [Test otorisasi course](../../tests/Feature/WeekFiveFeatureTest.php)
- [Test otorisasi CRUD](../../tests/Feature/WeekSevenCrudFeatureTest.php)
- [Test file dan submission](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Test notifikasi dan rekap nilai](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Tabel titik rawan keamanan](../keamanan.md)

### Jawaban singkat untuk presentasi

> “Jalur ditolak penting karena menyembunyikan tombol bukan kontrol keamanan.
> Test harus membuktikan request langsung ditolak di server, idealnya dengan
> 403 dan pemeriksaan bahwa data tidak berubah.”

---

## 4. Apa fungsi `RefreshDatabase`?

### Soal

Apa fungsi `RefreshDatabase`, dan mengapa test tidak boleh bergantung pada
data test sebelumnya?

### Jawaban

`RefreshDatabase` memastikan database test bersih untuk test yang memakai trait
tersebut. Data dari test sebelumnya tidak boleh memengaruhi test berikutnya.

Pada test:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

use RefreshDatabase;
```

atau pada class PHPUnit:

```php
class WeekNineFileHandlingTest extends TestCase
{
    use RefreshDatabase;
}
```

Manfaat:

- test dapat dijalankan secara acak atau terpisah;
- test tidak bergantung pada urutan eksekusi;
- data tidak tercampur antara test;
- test dapat dijalankan pada database CI yang baru;
- hasil test dapat direproduksi.

Test tetap harus membuat user, course, assignment, atau submission sendiri
melalui factory atau model. `RefreshDatabase` tidak menggantikan pembuatan
data test yang eksplisit.

### Bukti

- [Penerapan RefreshDatabase pada test submission](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Penerapan pada test otorisasi](../../tests/Feature/WeekFiveFeatureTest.php)
- [Penerapan pada test notifikasi](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Workflow CI](../../.github/workflows/ci.yml)

### Jawaban singkat untuk presentasi

> “RefreshDatabase membersihkan atau memulihkan database agar test terisolasi.
> Sebuah test tidak boleh menunggu data dibuat oleh test lain.”

---

## 5. Mengapa migrasi harus reversible?

### Soal

Mengapa method `down()` pada migration harus dapat membatalkan perubahan?

### Jawaban

Migration reversible memungkinkan Laravel melakukan:

- `migrate:fresh`;
- `migrate:refresh`;
- `migrate:rollback`;
- penyegaran database test.

Jika method `down()` salah atau tidak menghapus objek yang dibuat `up()`,
rollback dan refresh dapat gagal meskipun aplikasi saat ini masih berjalan.

Dalam CI proyek:

```powershell
php artisan migrate:fresh --seed --force
php artisan migrate:refresh --seed --force
php artisan test
```

Perintah tersebut membuktikan database dapat dibentuk dan dikembalikan.
Pada produksi, jangan mengganti kedua perintah tersebut dengan
`migrate:fresh`, karena dapat menghapus data nyata.

### Bukti

- [Folder migration](../../database/migrations)
- [Workflow migration dan test](../../.github/workflows/ci.yml)
- [Dokumentasi deployment](../deployment.md)

### Jawaban singkat untuk presentasi

> “Migrasi reversible dibutuhkan agar database dapat di-refresh dan di-rollback.
> Dalam test, ini membuat database bersih dapat dibuat ulang. Di production,
> saya memakai migration normal, bukan fresh.”

---

## 6. Buka `docs/debugging.md`; ceritakan satu bug

### Soal

Pilih satu bug yang tercatat dan ceritakan gejala, penyempitan,
akar penyebab, perbaikan, serta test pencegahnya.

### Jawaban model: notifikasi tidak muncul

**Gejala**

Tugas atau nilai berhasil disimpan, tetapi notifikasi database belum muncul.

**Hipotesis awal**

Kemungkinan penyebab:

- event tidak dipicu;
- listener salah;
- queue worker tidak berjalan;
- worker menggunakan kode lama.

**Pemeriksaan**

Urutan yang masuk akal:

1. Pastikan test listener mendeteksi event.
2. Periksa tabel `jobs`.
3. Jalankan queue worker sekali.
4. Periksa tabel `notifications`.
5. Periksa `failed_jobs` dan log.

**Akar penyebab**

Proses queue database membutuhkan worker terpisah. Jika worker tidak aktif,
data tugas/nilai tetap tersimpan tetapi notifikasi menunggu di queue.

**Perbaikan**

- listener menerapkan `ShouldQueue`;
- retry dan backoff dikonfigurasi;
- kegagalan dicatat ke log;
- worker dapat dijalankan dengan queue work.

**Test pencegah**

Test memeriksa:

- published assignment memicu listener;
- draft tidak memicu listener;
- penerima hanya mahasiswa terdaftar;
- nilai hanya dikirim ke pemilik submission;
- payload tidak menyimpan score atau feedback.

### Bukti

- [Dokumentasi tiga bug](../debugging.md)
- [Test notifikasi](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Laporan Minggu 10](../minggu-10/minggu-10-muchammad-maulana.md)
- [Migration notifications](../../database/migrations/2026_10_26_000001_create_notifications_table.php)
- [Listener assignment](../../app/Listeners/NotifyStudentsOfPublishedAssignment.php)
- [Listener grade](../../app/Listeners/NotifyStudentOfGradeAwarded.php)

### Jawaban singkat untuk presentasi

> “Saya mempersempit dari penyimpanan data ke event, listener, jobs, worker,
> dan notifications. Penyimpanan berhasil, jadi masalahnya ada pada queue
> atau worker. Aku mencatat bug ini dan menambahkan test yang memeriksa
> penerima serta payload.”

---

## 7. Mengapa `dd()` tidak boleh masuk PR?

### Soal

Jelaskan perbedaan `dd()`, `dump()`, dan `Log::info()`.

### Jawaban

`dd()`:

- menghentikan eksekusi;
- menampilkan data ke respons atau terminal;
- dapat membuat seluruh test/request berhenti;
- dapat membocorkan data jika ada di production.

`dump()`:

- menampilkan data tetapi tetap melanjutkan eksekusi;
- masih dapat mencemari respons atau test;
- tetap tidak boleh masuk PR.

`Log::info()`:

- menulis ke log, bukan langsung menghentikan request;
- boleh dipakai untuk data diagnostik yang aman;
- tidak boleh mencatat password, token, atau data sensitif.

Aturan praktis:

```text
dd/dump -> hanya saat debugging lokal, lalu hapus
Log::info -> hanya untuk event diagnostik yang tidak sensitif
```

Dalam test, `dd()` dapat menyebabkan test gagal atau mengganggu seluruh proses
karena response tidak dapat dilanjutkan secara normal.

### Bukti

- [Dokumentasi debugging](../debugging.md)
- [Pencarian penggunaan dd/dump](../../app)
- [Panduan Minggu 14](panduan-pemahaman-minggu-14.md)

### Jawaban singkat untuk presentasi

> “dd dan dump menghentikan atau mencemari alur pengguna. Log dapat disimpan
> untuk pemeriksaan produksi, tetapi hanya data yang aman.”

---

## 8. Perbedaan feature test dan unit test

### Soal

Apa perbedaan feature test dan unit test dalam Laravel?

### Jawaban

**Feature test**

Mengirim request melalui HTTP atau route, lalu memeriksa:

- response;
- middleware;
- controller;
- Policy;
- database;
- storage;
- notification;
- queue.

**Unit test**

Memanggil method atau logika secara terisolasi, misalnya:

- hitung status late;
- fungsi pembantu;
- aturan perhitungan nilai.

KampusLMS memakai feature test sebagai utama karena otorisasi dan alur CRUD
harus diuji dari sisi aplikasi. Unit test dipakai secukupnya untuk logika
perhitungan kecil.

### Bukti

- [Feature test submission](../../tests/Feature/SubmissionUploadFeatureTest.php)
- [Feature test otorisasi](../../tests/Feature/WeekSevenCrudFeatureTest.php)
- [Unit test Example](../../tests/Unit/ExampleTest.php)
- [Laporan Minggu 14](minggu-14-muchammad-maulana.md)

### Jawaban singkat untuk presentasi

> “Feature test memeriksa jalur aplikasi melalui HTTP. Unit test memeriksa
> satu fungsi kecil secara terisolasi. Pada LMS, feature test lebih penting
> untuk membuktikan otorisasi dan efek database.”

---

## 9. Apa itu test yang menipu?

### Soal

Sebutkan tanda test palsu atau test yang tidak menguji fitur.

### Jawaban

Tanda test yang menipu:

- hanya memeriksa status 200;
- tidak memiliki assertion;
- memeriksa hal yang tidak mungkin gagal;
- bergantung pada seeder;
- bergantung pada test sebelumnya;
- tidak memeriksa database atau storage;
- menggunakan data milik test lain;
- memeriksa tombol yang disembunyikan tanpa menguji endpoint.

Cara mengujinya:

1. hapus atau rusak fitur;
2. jalankan test;
3. pastikan test menjadi merah;
4. pulihkan fitur;
5. pastikan test kembali hijau.

Test yang tetap hijau saat fitur dihapus belum membuktikan apa pun.

### Bukti

- [Test IDOR submission](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Test otorisasi role](../../tests/Feature/WeekSevenCrudFeatureTest.php)
- [Panduan quality check](panduan-pemahaman-minggu-14.md)
- [Laporan Minggu 14](minggu-14-muchammad-maulana.md)

---

## 10. Bagaimana memakai `Storage::fake()`?

### Soal

Mengapa file upload tidak boleh ditulis ke storage sungguhan saat test?

### Jawaban

`Storage::fake()` membuat disk sementara untuk test. File uji dapat diperiksa
tanpa menumpuk berkas pribadi atau berbahaya pada storage development.

Contoh:

```php
Storage::fake('local');

$this->actingAs($student)->post(..., [
    'file' => UploadedFile::fake()->create(
        'jawaban.pdf',
        100,
        'application/pdf'
    ),
]);

Storage::disk('local')->assertExists($submission->file_path);
```

Pada upload ulang, test harus memastikan file lama hilang dan file baru ada.
Pada test invalid type atau ukuran, test harus memastikan tidak ada baris
submission atau file baru.

### Bukti

- [Test handling file](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Model Submission](../../app/Models/Submission.php)
- [Controller Submission](../../app/Http/Controllers/SubmissionController.php)

### Jawaban singkat untuk presentasi

> “Storage fake menjaga test terisolasi. Saya memeriksa file lama hilang,
> file baru ada, dan file tidak masuk disk publik.”

---

## 11. Bagaimana memakai `Notification::fake()`?

### Soal

Jelaskan manfaat Notification fake dan Queue fake.

### Jawaban

`Notification::fake()` mengganti mekanisme notifikasi sungguhan saat test.
Test dapat memeriksa:

- penerima yang benar;
- jenis notification yang dikirim;
- notification tidak dikirim ke user lain;
- payload tidak menyimpan nilai atau feedback sensitif.

`Queue::fake()` memeriksa bahwa listener atau job masuk queue tanpa menjalankan
worker sungguhan.

Contoh assertion:

```php
Notification::assertSentTo(
    $student,
    NewAssignmentNotification::class
);

Notification::assertNotSentTo(
    $otherStudent,
    NewAssignmentNotification::class
);
```

### Bukti

- [Test notification](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Notification assignment](../../app/Notifications/NewAssignmentNotification.php)
- [Notification grade](../../app/Notifications/GradeReleasedNotification.php)

### Jawaban singkat untuk presentasi

> “Fake membuat test deterministik. Saya dapat membuktikan penerima dan jenis
> notifikasi tanpa mengirim notifikasi atau menjalankan worker nyata.”

---

## 12. Apa yang dilakukan saat notifikasi tidak muncul?

### Soal

Susun urutan pemeriksaan dari yang paling murah hingga yang paling mahal.

### Jawaban

Urutan:

1. Pastikan event dipicu setelah assignment atau grade disimpan.
2. Jalankan test listener yang tersedia.
3. Periksa tabel `jobs`.
4. Periksa apakah worker aktif.
5. Jalankan satu worker dengan `--once`.
6. Periksa tabel `notifications`.
7. Periksa `failed_jobs`.
8. Baca log listener atau queue worker.
9. Pastikan deployment memakai kode listener terbaru.

Jika data tugas/nilai sudah tersimpan tetapi job tidak ada, pemeriksaannya
berpusat pada event/listener. Jika job ada tetapi tidak diproses, pemeriksaannya
berpusat pada worker dan deployment.

### Bukti

- [Test queue dan notification](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Dokumentasi Minggu 10 queue](../minggu-10/minggu-10-muchammad-maulana.md)
- [Dokumentasi debugging](../debugging.md)

### Perintah

```powershell
php artisan test --filter=WeekTenNotificationFeatureTest
php artisan queue:work --once
php artisan queue:failed
```

---

## 13. Apa perbedaan 200, 403, dan redirect login?

### Soal

Kapan menggunakan status/assertion HTTP tersebut?

### Jawaban

**200/`assertOk()`**

- pengguna diizinkan;
- halaman atau API mengembalikan data;
- tetap perlu assertion isi atau efek.

**403/`assertForbidden()`**

- user sudah login;
- user memiliki identitas/role;
- user tidak berhak mengakses atau mengubah resource.

**302 ke login/`assertRedirect(route('login'))`**

- pengguna belum login;
- middleware authentication mengarahkan ke login.

**404**

Dapat dipakai untuk menyembunyikan eksistensi resource privat, misalnya
notifikasi milik user lain. Namun 403 lebih tepat pada kasus pengguna
terautentikasi yang jelas tidak berhak.

### Bukti

- [Test guest redirected](../../tests/Feature/WeekFourFeatureTest.php)
- [Test forbidden role](../../tests/Feature/WeekSevenCrudFeatureTest.php)
- [Test ID notifikasi user lain](../../tests/Feature/WeekTenNotificationFeatureTest.php)

---

## 14. Bagaimana menjamin otorisasi tidak ada bocor?

### Soal

Apa lapisan yang harus dijaga agar endpoint tidak dapat diakses lewat ID atau
role yang salah?

### Jawaban

Gunakan beberapa lapisan:

1. Middleware autentikasi.
2. Middleware role pada route admin/dosen/mahasiswa.
3. Policy pada resource.
4. Query scope pada daftar dan detail.
5. Nested route binding dan pencocokan parent-child.
6. Feature test untuk role dan user yang berbeda.

Blade tidak boleh menjadi kontrol keamanan. Tombol yang disembunyikan tidak
mencegah request langsung.

### Bukti

- [Tabel keamanan](../keamanan.md)
- [Test policy model](../../tests/Feature/ModelPoliciesTest.php)
- [Test course dan assignment](../../tests/Feature/WeekFiveFeatureTest.php)
- [Test API role](../../tests/Feature/WeekSixApiFeatureTest.php)

---

## 15. Apa hasil CI Minggu 14 saat ini?

### Soal

Sebutkan hasil dan perintah yang dijalankan CI.

### Jawaban

Workflow CI menjalankan:

- setup PHP 8.3;
- setup SQLite;
- copy `.env.example`;
- pemeriksaan `.env` terlacak;
- Composer dan npm install;
- build frontend;
- generate key;
- `migrate:fresh --seed --force`;
- `migrate:refresh --seed --force`;
- `php artisan test`.

Hasil lokal terbaru yang sudah diperoleh:

```text
91 tests passed (460 assertions)
```

Angka itu harus dijalankan ulang sebelum presentasi bila ada perubahan.
Status GitHub Actions terbaru pada push/PR juga harus diperiksa, karena status
remote dapat berubah.

### Bukti

- [Workflow CI](../../.github/workflows/ci.yml)
- [Laporan Minggu 14](minggu-14-muchammad-maulana.md)

---

## 16. Sebutkan tiga bug nyata yang didokumentasikan

### Jawaban

**Bug 1: notifikasi tidak muncul**

- Penyebab sementara: queue worker tidak aktif atau tidak dikonfigurasi;
- bukti: data tugas/nilai tersimpan, job menunggu atau gagal;
- test: `WeekTenNotificationFeatureTest`.

**Bug 2: HTTP 500 saat deployment**

- Penyebab: tabel session belum tersedia saat aplikasi menerima request;
- perbaikan: migration pre-deploy;
- tes: CI migration dan test HTTPS forwarded headers.

**Bug 3: ID submission/notifikasi dapat berganti**

- Penyebab: pemeriksaan tampilan saja atau query tidak dibatasi;
- perbaikan: Policy dan query ber-cakupan user;
- test: Week 9 dan Week 10.

### Bukti

- [Dokumentasi debugging](../debugging.md)
- [Test Week 9](../../tests/Feature/WeekNineFileHandlingTest.php)
- [Test Week 10](../../tests/Feature/WeekTenNotificationFeatureTest.php)
- [Laporan deployment Minggu 12](../minggu-12/minggu-12-muchammad-maulana.md)

---

## 17. Apa yang sudah selesai dan apa yang masih perlu diuji?

### Jawaban

Sudah tersedia:

- laporan Minggu 14;
- panduan pemahaman;
- dokumentasi debugging;
- test otorisasi submission;
- Storage/Notification fake;
- pemetaan test pada dokumentasi keamanan;
- CI menjalankan suite.

Masih perlu diperagakan:

- hapus atau rusak satu fitur;
- jalankan test;
- tunjukkan test menjadi merah;
- pulihkan;
- tunjukkan test menjadi hijau kembali.

Belum dapat diklaim:

- worker production aktif;
- seluruh 15 baris keamanan dipenuhi oleh test khusus bila belum diverifikasi;
- test CI terbaru pada push sesudah perubahan ini.

### Bukti

- [Laporan Minggu 14](minggu-14-muchammad-maulana.md)
- [Dokumentasi debugging](../debugging.md)
- [Tabel keamanan](../keamanan.md)
- [Workflow CI](../../.github/workflows/ci.yml)

---

# Ringkasan kalimat untuk semua checkpoint

Gunakan kalimat-kalimat berikut sebagai kerangka lisan:

## Kalimat 1 — Debugging

> “Saya memulai debugging dengan mereproduksi gejala, mempersempit lapisan,
> membuat satu hipotesis, menguji hipotesis termurah, memperbaiki akar
> masalah, lalu menambahkan test agar bug tidak kembali.”

## Kalimat 2 — Feature test

> “Saya memakai feature test sebagai verifikasi utama karena test tersebut
> menguji request, route, middleware, controller, Policy, database, dan
> respons sebagai satu alur.”

## Kalimat 3 — Test bermakna

> “Saya tidak mengandalkan status 200 saja. Saya memeriksa 403, data database,
> file storage, atau penerima notifikasi agar test benar-benar membuktikan
> perilaku.”

## Kalimat 4 — RefreshDatabase

> “RefreshDatabase membuat state database terisolasi antar-test, sehingga test
> tidak saling mengotori atau bergantung pada urutan.”

## Kalimat 5 — Fake

> “Storage::fake dan Notification::fake memastikan test tidak menulis file atau
> mengirim notifikasi nyata.”

## Kalimat 6 — Otorisasi

> “Blade hanya menyembunyikan tampilan. Keamanan harus dibuktikan di middleware,
> Policy, query, dan test HTTP.”

## Kalimat 7 — BREAK

> “Saya membuktikan test bermakna dengan menghapus fitur secara sementara.
> Test yang tepat menjadi merah, kemudian kembali hijau setelah fitur
> dipulihkan.”
