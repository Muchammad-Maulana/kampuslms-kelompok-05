# Panduan Memahami dan Menjelaskan Minggu 14

Panduan ini membantu memahami, mempraktikkan, dan menjelaskan Minggu 14:
Debugging dan Testing pada proyek KampusLMS. Gunakan sebagai bahan belajar,
bukan untuk mengaku melakukan pekerjaan yang belum benar-benar dilakukan.

## 1. Inti Minggu 14

Minggu 14 meminta dua kemampuan yang saling berkaitan:

1. **Debugging secara sistematis**, yaitu menemukan penyebab kegagalan
   tanpa menebak, mengubah banyak hal sekaligus, atau sekadar menyembunyikan
   pesan error.
2. **Pengujian otomatis yang bermakna**, terutama feature test yang memeriksa
   alur HTTP dan hasilnya di database, storage, queue, atau notifikasi.

Tujuannya bukan sekadar membuat test berwarna hijau. Tujuan akhirnya adalah
kemampuan menjawab:

- gejala apa yang diamati;
- bukti apa yang membuktikan hipotesis;
- bagian mana yang diperbaiki;
- test apa yang mencegah bug muncul kembali;
- apa yang belum sempat diuji.

Ringkasan proyek saat ini:

- suite lokal terbaru: **91 test lulus, 460 assertion**;
- hasil terukur diperoleh dengan `php artisan test`;
- suite berisi unit test dan feature test;
- bagian terbesar adalah feature test;
- test menggunakan `RefreshDatabase`, `Storage::fake()`,
  `Notification::fake()`, dan queue fake;
- CI menjalankan migrate, build aset, dan seluruh suite.

Angka tersebut adalah hasil yang telah dijalankan pada 25 November 2026,
tetapi perlu dijalankan lagi sebelum presentasi atau push baru.

## 2. Cara menjawab pertanyaan Minggu 14

Gunakan urutan berikut untuk setiap pertanyaan:

1. **Jawab inti dahulu**, lalu berikan definisi singkat.
2. **Jelaskan mekanisme**, misalnya alur request sampai assertion.
3. **Tunjukkan bukti nyata**, berupa nama test, berkas, command, atau hasil.
4. **Bedakan bukti dari perkiraan**.
5. **Sebutkan batas atau pekerjaan yang belum dilakukan**, jika ada.

Contoh pola lisan:

> “Debugging adalah proses mempersempit penyebab kegagalan. Saya mulai dari
> gejala, memeriksa satu hipotesis, lalu memperbaiki sumbernya. Pada bug
> notifikasi, bukti yang saya gunakan adalah tabel jobs dan notifications.
> Test yang mencegah regresinya ada di WeekTenNotificationFeatureTest.
> Batasnya, worker production belum aktif dan belum diverifikasi pada Railway.”

Kalimat itu lebih kuat daripada sekadar menyebut nama alat. Ini menunjukkan
alur berpikir, bukti, dan batas kesimpulan.

## 3. Baca — Konsep debugging

### 3.1 Lima langkah debugging

Urutan debugging yang digunakan adalah:

1. **Reproduksi**

   Ulangi bug dengan langkah dan data yang sama. Sebelum memperbaiki,
   catat gejala, user/role, endpoint, status HTTP, dan data yang diharapkan.

2. **Persempit**

   Tentukan lapisan yang masih benar dan lapisan yang mulai salah:
   request, route, middleware, controller, Policy, query, model, database,
   queue, storage, atau view.

3. **Rumuskan satu hipotesis**

   Pola yang baik:

   > “Saya menduga penyebabnya X, karena Y.”

   Hindari mengubah lima bagian sekaligus. Satu hipotesis harus dapat diuji.

4. **Uji hipotesis paling murah**

   Mulai dari pemeriksaan yang cepat dan terbatas:
   test terpilih, log, tabel database, atau query. Baru beralih ke perubahan
   konfigurasi atau debugging yang lebih mahal.

5. **Perbaiki dan tulis test**

   Perbaikan permanen membutuhkan regression test. Tanpa test, bug yang sama
   dapat kembali pada refactor berikutnya.

### 3.2 Cara membaca exception

Pada stack trace Laravel, cari baris pertama yang menunjuk ke berkas di `app/`,
bukan vendor. Baris vendor biasanya menjelaskan framework mana yang menjawab
exception, sedangkan baris aplikasi menunjukkan titik pemanggilan yang perlu
diperiksa.

Urutan pemeriksaan:

1. Baca baris pertama pesan exception.
2. Cari lokasi pertama di `app/`, `routes/`, atau `tests/`.
3. Baca request atau data yang masuk ke lokasi tersebut.
4. Telusuri relasi, query, Policy, dan model yang dipakai.
5. Jalankan satu pemeriksaan reproduksi atau test.

### 3.3 Alat debugging

```php
dd($variabel);
dump($variabel);
Log::info('cek', ['user' => $user->id]);
DB::listen(fn ($query) => Log::debug($query->sql, $query->bindings));
```

Arti penggunaan:

- `dd()` menghentikan eksekusi lalu menampilkan variabel;
- `dump()` menampilkan variabel tetapi tetap melanjutkan eksekusi;
- `Log::info()` menulis log yang dapat diperiksa setelah request selesai;
- `DB::listen()` mencatat query SQL dan binding-nya.

Aturan proyek:

- `dd()` dan `dump()` tidak boleh masuk PR;
- `Log::info()` dapat dipakai bila data sensitif tidak dicetak;
- jangan menulis password, token, nilai rahasia, atau payload pribadi ke log;
- baca log dengan:

```powershell
Get-Content storage\logs\laravel.log -Tail 100
php artisan pail
```

`php artisan pail` dapat memerlukan paket Laravel Pail sebagai dependency
pengembangan.

### 3.4 Bug bukan “bakuk” berarti bisa diatasi berulang

Bug yang tidak menghasilkan pesan error tetap dapat didiagnosis:

1. bandingkan kondisi yang bekerja dan tidak bekerja;
2. tuliskan input yang sama dan input yang berbeda;
3. tambahkan satu pengamatan data pada satu titik;
4. hapus satu kemungkinan dengan bukti;
5. ulangi sampai akar penyebab ditemukan.

Contoh: notifikasi tidak muncul. Jika tugas atau nilai tersimpan, penyimpanan
data sudah berjalan. Pemeriksaan berikutnya adalah event/listener, tabel jobs,
worker, dan tabel notifications. Tidak ada gunanya mengubah validasi form
sebelum membuktikan bahwa tahap penyimpanan memang berjalan.

## 4. Jenis test pada KampusLMS

### 4.1 Feature test

Feature test adalah jenis utama pada proyek ini. Ia menguji alur HTTP:

```text
login/request
  -> middleware
  -> route
  -> controller
  -> Policy/query
  -> database/storage/notification
  -> response
```

Contoh feature test adalah:

```php
test('only submission owner course lecturer and admin can download submission')
```

Test tersebut membuat `student`, `lecturer`, `admin`, dan `otherStudent`,
lalu menguji status download atau forbidden. Test masuk ke jalur aplikasi,
bukan sekadar memanggil satu method secara terisolasi.

### 4.2 Unit test

Unit test menguji method atau logika yang kecil dan terisolasi, misalnya
perhitungan status terlambat atau fungsi pembantu. Unit test berguna untuk
logika perhitungan, tetapi tidak cukup untuk menguji otorisasi HTTP.

Otorisasi harus diuji melalui feature test karena feature test membuktikan
bahwa role, route, middleware, Policy, dan respons bekerja bersama.

### 4.3 Porsi test pada proyek

KampusLMS memiliki lebih dari satu feature test untuk setiap minggu:

- autentikasi dan role;
- course dan enrollment;
- course, assignment, dan material;
- submission dan grade;
- notification dan queue;
- API;
- file handling.

Penambahan Minggu 14 adalah test untuk mahasiswa yang tidak terdaftar:

```php
test_unenrolled_student_cannot_submit_assignment()
```

Test itu membuat assignment dan student lain, mencoba upload melalui route
mahasiswa, lalu memeriksa:

- response `403`;
- tidak ada baris submission milik mahasiswa itu.

## 5. RefreshDatabase

### 5.1 Fungsi

`RefreshDatabase` membuat atau memulihkan database menjadi keadaan bersih
untuk test. Pengujian memakai database yang tidak bergantung pada hasil test
sebelumnya.

Setiap test class pada contoh Minggu 14 memakai:

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContohFeatureTest extends TestCase
{
    use RefreshDatabase;
}
```

Pola ini memastikan test dapat dijalankan satu kali atau berulang, dalam urutan
apa pun, dan tidak mengandalkan data yang tersisa dari test lain.

### 5.2 Mengapa migrasi reversible

Ketika test memulai database bersih, Laravel menjalankan migrasi. Pada perubahan
data antar-test atau penyegaran database, migration dapat perlu diturunkan
melalui method `down()`.

Perintah CI:

```powershell
php artisan migrate:fresh --seed --force
php artisan migrate:refresh --seed --force
php artisan test
```

Memakai `migrate:fresh` dan `migrate:refresh` membuktikan migrasi dapat
membuat dan mengembalikan struktur secara konsisten. Jika satu migration tidak
memiliki `down()` yang benar, refresh dapat gagal meskipun aplikasi terlihat
berjalan.

Jangan menjalankan `migrate:fresh` pada database berisi data produksi.

## 6. Storage::fake dan Notification::fake

### 6.1 Storage fake

Test upload tidak boleh menulis file sungguhan ke `storage/app/private`.
Gunakan:

```php
Storage::fake('local');
```

Kemudian buat file uji:

```php
$file = UploadedFile::fake()->create(
    'jawaban.pdf',
    100,
    'application/pdf'
);
```

Pemeriksaan yang bermakna:

```php
Storage::disk('local')->assertExists($submission->file_path);
Storage::disk('local')->assertMissing($oldPath);
```

Pada test Minggu 9:

- path baru dimulai dengan `submissions/{assignmentId}/`;
- nama asli tidak digunakan sebagai nama file acak;
- upload ulang menghapus file lama;
- file tidak ada pada disk publik.

### 6.2 Notification fake

Test notifikasi menggunakan:

```php
Notification::fake();
```

Contoh:

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

Hal ini membuktikan penerima benar dan data tidak dikirim ke mahasiswa lain.
Gunakan Queue fake juga jika ingin memeriksa bahwa listener tidak diproses
secara sinkron atau menghitung queued job.

## 7. Menulis feature test yang bermakna

### 7.1 Struktur test

Test yang baik memiliki enam bagian:

1. kondisi awal;
2. data user dan resource;
3. autentikasi atau role;
4. aksi HTTP;
5. hasil respons;
6. efek database, storage, queue, atau notifikasi.

Contoh pola:

```php
public function test_unenrolled_student_cannot_submit_assignment(): void
{
    Storage::fake('local');

    // Buat dosen, course, assignment, dan dua mahasiswa.
    // Mahasiswa yang kedua tidak di-enroll.

    $this->actingAs($otherStudent)
        ->post(route(...), [
            'file' => UploadedFile::fake()->create(
                'jawaban.pdf',
                100,
                'application/pdf'
            ),
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('submissions', [
        'assignment_id' => $assignment->id,
        'user_id' => $otherStudent->id,
    ]);
}
```

### 7.2 Jalur sukses

Contoh:

- login dengan kredensial benar;
- dosen membuat assignment pada course miliknya;
- mahasiswa terdaftar mengumpulkan tugas;
- dosen pengampu menyimpan nilai.

Test jalur sukses sebaiknya memeriksa:

- redirect atau HTTP status;
- pesan session;
- baris database yang benar;
- storage atau notifikasi yang relevan.

### 7.3 Jalur ditolak

Contoh:

- dosen lain mengedit course;
- mahasiswa mengunduh submission milik mahasiswa lain;
- mahasiswa mengumpulkan setelah deadline bila laten dilarang;
- mahasiswa mengakses course yang tidak diikuti.

Test jalur ditolak harus memeriksa `403`, bukan sekadar `!= 200`.
Tambahkan pemeriksaan database bila aksi berbahaya dapat tetap berubah
walaupun respons terlihat ditolak.

### 7.4 Jalur validasi gagal

Contoh:

- nama course kosong;
- file bukan PDF;
- ukuran file lebih dari 5 MB;
- student_id bukan user ber-role mahasiswa;
- submission setelah deadline.

Test validasi harus memeriksa:

- field error;
- tidak ada baris baru;
- tidak ada file tersimpan;
- tidak ada notifikasi terpicu.

## 8. Tanda test yang menipu

### 8.1 Test hanya memeriksa 200

Test seperti ini bisa lolos meskipun halaman kosong atau controller tidak
melakukan fitur yang seharusnya:

```php
$this->get('/courses')->assertOk();
```

Status 200 tidak membuktikan bahwa:

- data benar;
- pengguna yang tidak berhak tidak melihat data;
- user terdaftar tidak membaca course orang lain;
- data dari seeder tidak terjadi kebocoran.

### 8.2 Tidak ada assertion bermakna

Test yang mengirim request tetapi tidak memeriksa respons, database, storage,
atau objek berarti tidak membuktikan apa pun.

### 8.3 Bergantung pada test lain

Contoh berbahaya:

```text
Test A membuat course.
Test B menganggap course itu sudah ada.
```

Test B bisa lulus setelah Test A dijalankan lebih dulu dan gagal saat dijalankan
sendiri. Solusinya adalah membuat data sendiri pada setiap test.

### 8.4 Bergantung pada seeder

Test tidak boleh mengandalkan seeder hanya karena akun atau course tertentu
ada saat development. Jalankan test di database CI yang berbeda dan buat data
melalui factory atau model.

### 8.5 Tidak memeriksa efek

Test upload hanya memeriksa redirect tetapi tidak memeriksa file atau submission
bisa tidak bermakna. Test grading hanya memeriksa redirect tetapi tidak memeriksa
baris grade juga belum cukup.

## 9. Demonstrasi hijau menjadi merah

Contoh yang paling mudah pada proyek KampusLMS adalah unduhan submission:

1. Buka test:

```text
tests/Feature/WeekNineFileHandlingTest.php
test_only_submission_owner_course_lecturer_and_admin_can_download_submission
```

2. Pastikan test lulus:

```powershell
php artisan test --filter=only_submission_owner_course_lecturer_and_admin_can_download_submission
```

3. Ubah sementara endpoint/controller agar menghilangkan pemeriksaan Policy.

4. Jalankan test yang sama.

5. Test harus berubah menjadi merah. Minimal, mahasiswa lain tidak lagi
   `assertForbidden()`.

6. Pulihkan sumber kontrol dan jalankan test lagi hingga hijau.

Demonstrasi tersebut membuktikan test benar-benar menguji fitur yang
dimaksud. Jika test tetap hijau, test tersebut salah atau tidak mencapai
jalur download yang seharusnya.

Pada perubahan nyata, jangan commit kode yang sengaja dirusak.

## 10. `assertStatus` dan `assertForbidden`

### 10.1 `assertOk` atau `assertStatus(200)`

Hanya membuktikan respons berhasil. Belum membuktikan isi atau otorisasi.

### 10.2 `assertForbidden`

Di Laravel, `assertForbidden()` membuktikan respons HTTP 403. Ini cocok untuk
pengguna yang terautentikasi tetapi tidak berhak.

### 10.3 Tamu atau pengguna belum login

Biasanya pengguna tamu diarahkan ke login, bukan langsung menerima 403.
Gunakan `assertRedirect(route('login'))` untuk kasus ini.

404 dapat digunakan untuk resource privat milik orang lain bila endpoint sengaja
tidak mengungkap keberadaannya, tetapi 403 cocok untuk role yang teridentifikasi
tetapi tidak berhak.

## 11. Menjamin otorisasi

Otorisasi di KampusLMS dapat terbukti di beberapa lapisan:

### 11.1 Middleware role

Route admin dibatasi oleh middleware role. Ini menolak role selain admin pada
titik masuk.

### 11.2 Policy

Policy memeriksa hubungan resource dengan user, misalnya:

- dosen pemilik course;
- mahasiswa terdaftar;
- pemilik submission;
- dosen pengampu.

Policy harus dipanggil oleh controller, bukan hanya direplikasi di Blade.

### 11.3 Query scope

Beberapa list tidak boleh memakai seluruh tabel. Query harus dibatasi:

- course dosen oleh `lecturer_id`;
- course mahasiswa oleh enrollment;
- submission oleh user atau course pengampu;
- notification oleh user yang login.

### 11.4 Test otorisasi

Test otorisasi yang baik membuat dua pihak, misalnya dosen A dan dosen B,
lalu memastikan dosen A hanya dapat mengubah course miliknya.

Contoh yang sudah ada:

- dosen tidak dapat mengedit course dosen lain;
- dosen tidak dapat mengelola course dosen lain;
- mahasiswa tidak dapat mengunduh submission mahasiswa lain;
- mahasiswa tidak dapat melihat notification milik user lain.

## 12. Testing notifikasi dan queue

Alur event:

```text
AssignmentPublished
  -> NotifyStudentsOfPublishedAssignment
  -> notification untuk mahasiswa terdaftar

GradeAwarded
  -> NotifyStudentOfGradeAwarded
  -> notification untuk pemilik submission
```

Jika worker mati:

- tugas atau nilai tetap tersimpan;
- job menunggu di tabel `jobs`;
- notifikasi belum dibuat atau belum diproses;
- aplikasi tidak otomatis menyelesaikan queue.

Pemeriksaan urut:

1. Pastikan event dipicu.
2. Pastikan listener memakai `ShouldQueue`.
3. Periksa tabel `jobs`.
4. Jalankan queue worker.
5. Periksa `notifications` dan `failed_jobs`.
6. Periksa log worker atau Laravel.

Perintah:

```powershell
php artisan queue:work --once
php artisan queue:failed
php artisan queue:retry all
```

Test Week 10 memeriksa bahwa:

- draft tidak memicu notifikasi tugas;
- published mengirim ke mahasiswa terdaftar;
- grade hanya mengirim ke pemilik submission;
- payload database tidak menyimpan score atau feedback;
- notifikasi asing tidak dapat ditandai dibaca.

## 13. CI pada Minggu 14

Workflow GitHub Actions saat ini berada di
[ci.yml](../../.github/workflows/ci.yml).

Urutan utama:

1. checkout source;
2. setup PHP 8.3 dan extension SQLite;
3. copy `.env.example` menjadi `.env`;
4. tolak `.env` rahasia yang terlacak;
5. install Composer dan npm;
6. build frontend;
7. generate application key;
8. jalankan migrate fresh dan migrate refresh;
9. jalankan `php artisan test`.

Jika CI gagal:

- jangan mengubah test agar melewati CI dengan cara mengurangi assertion;
- baca log tahap yang gagal;
- reproduksi command yang sama secara lokal;
- perbaiki migrasi, factory, route, atau test yang benar;
- jalankan test penuh sebelum push.

## 14. Tiga bug nyata untuk dijelaskan

### Bug 1 — Notifikasi tidak muncul

**Gejala:** data tugas atau nilai berhasil disimpan, tetapi notifikasi
belum terlihat.

**Hipotesis:** event tidak dipicu, listener salah, atau worker tidak berjalan.

**Pemeriksaan:** test listener, tabel `jobs`, tabel `notifications`,
`queue:failed`, dan log.

**Akar masalah:** queue database membutuhkan worker terpisah. Worker
production belum dikonfigurasi secara aktif.

**Perbaikan:** listener queued, retry, backoff, dan log pada job gagal.

**Test pencegah:** `WeekTenNotificationFeatureTest` memeriksa penerima,
payload, queue, dan notifikasi grade.

### Bug 2 — HTTP 500 saat deployment awal

**Gejala:** session atau migrasi belum tersedia pada service production.

**Hipotesis:** environment, migration, dokumen root, atau service database.

**Pemeriksaan:** log, `/up`, konfigurasi session, dan tabel database.

**Akar masalah:** tabel `railway.sessions` belum dibuat saat request pertama.

**Perbaikan:** jalankan `php artisan migrate --force` sebagai pre-deploy
command dan jangan `migrate:fresh` pada data produksi.

**Test pencegah:** CI menjalankan migration fresh dan refresh; test HTTPS
memverifikasi forwarded headers.

### Bug 3 — ID submission atau notifikasi dapat mengganti resource

**Gejala:** pengguna dapat mencoba mengganti ID URL untuk membuka resource
orang lain.

**Hipotesis:** Blade menyembunyikan tombol, tetapi server tidak membatasi
query atau route.

**Pemeriksaan:** buat dua user, satu submission/notifikasi milik masing-masing,
lalu kirim request sebagai user lain.

**Akar masalah:** kontrol hanya ada di Blade atau tidak ada pengecekan
Policy/server-side.

**Perbaikan:** SubmissionPolicy, GradePolicy, scope query, dan route download
yang memeriksa pemilik serta role.

**Test pencegah:** test Week 9 untuk submission dan test Week 10 untuk
notification.

Dokumen lengkapnya ada di [docs/debugging.md](../debugging.md).

## 15. Pemetaan titik rawan keamanan

Dokumen [keamanan.md](../keamanan.md) berisi 15 baris titik rawan IDOR.
Setiap baris menjelaskan:

- endpoint atau referensi;
- cara ID dapat mengganti resource;
- Policy atau query yang menutupnya;
- nama test yang membuktikannya.

Contoh kolom test:

- course lain: test lecturer tidak dapat melihat course dosen lain;
- submission orang lain: test download submission dengan user berbeda;
- enrollment: test dosen lain tidak dapat mengelola enrollment;
- grade: test dosen lain tidak dapat mengubah nilai.

Dalam presentasi, jangan mengklaim semua baris 100% aman hanya karena ada
nama test. Sebutkan test yang benar-benar ada dan jalur yang sudah diperiksa.

## 16. Status dan batas yang jujur

Yang sudah dilakukan:

- suite test dijalankan dan hasil dicatat;
- test mahasiswa tidak terdaftar menambahkan satu kasus otorisasi;
- `RefreshDatabase`, Storage fake, dan Notification fake dipakai;
- test hasil 403 dan efek database ditambahkan;
- CI menjalankan suite;
- debugging dokumentasi dibuat;
- tiga bug nyata dirangkum;
- kolom test ditambahkan ke dokumentasi keamanan.

Yang perlu diperagakan saat checkpoint:

- hapus sementara satu fitur;
- jalankan test;
- tunjukkan test menjadi merah;
- pulihkan fitur;
- tunjukkan test kembali hijau.

Yang belum dapat diklaim:

- semua test pelanggaran keamanan pada seluruh route belum tentu memiliki satu
  test unik untuk setiap baris bila belum diperiksa;
- demonstrasi penghapusan fitur belum diperagakan di depan dosen;
- worker production belum dibuktikan aktif.

## 17. Jawaban model untuk checkpoint

### Mengapa test hanya `assertStatus(200)` sering tidak bermakna?

Karena 200 hanya membuktikan server mengembalikan respons berhasil. Test tidak
membuktikan isi, otorisasi, mutasi database, atau efek file/notification.
Test bermakna memeriksa setidaknya satu hasil yang harus berubah atau ditolak.

### Mengapa test jalur ditolak lebih penting?

Karena jalur sukses sering diuji secara manual dan mudah terlihat. Jalur ditolak
mencegah IDOR dan eskalasi role. Satu pengguna dapat mengganti ID atau langsung
memanggil endpoint walaupun tombol tidak ditampilkan di UI.

### Apa fungsi `RefreshDatabase`?

Menyediakan state database yang bersih untuk setiap test. Ini mencegah satu test
tergantung pada data atau mutasi test sebelumnya.

### Mengapa migrasi harus reversible?

`RefreshDatabase`, `migrate:fresh`, dan `migrate:refresh` memerlukan kemampuan
memebuat dan mengembalikan struktur database. Migration `down()` yang salah
dapat membuat reset database gagal.

### Apa perbedaan `dd()` dan `Log::info()`?

`dd()` menghentikan request dan menampilkan informasi, sementara `Log::info()`
menulis data ke log untuk diagnosis berkelanjutan. `dd()` tidak boleh masuk PR
karena dapat merusak request pengguna. Log tetap harus dijaga agar tidak
mencetak data rahasia.

### Feature test atau unit test mana yang dipakai utama?

Feature test menjadi utama karena CRUD dan otorisasi Laravel diuji melalui
request HTTP. Unit test digunakan secukupnya untuk logika perhitungan yang dapat
diisolasi.

### Bagaimana membuktikan test bermakna?

Rusak atau hapus fitur yang diuji, jalankan test, lalu pastikan test menjadi
merah. Jika test tetap hijau, test tidak menguji fitur tersebut atau assertion
terlalu lemah.

## 18. Kuis cepat Minggu 14

Jawab sebelum membuka jawaban di bagian ini:

1. Apa perbedaan feature test dan unit test?
2. Apa fungsi `RefreshDatabase`?
3. Kapan memakai `assertForbidden()`?
4. Mengapa test sebaiknya tidak bergantung pada seeder?
5. Apa tujuan `Storage::fake()`?
6. Apa tujuan `Notification::fake()`?
7. Apa tanda test hanya memeriksa status 200?
8. Sebutkan lima langkah debugging.
9. Apa yang diperiksa setelah notifikasi tidak muncul?
10. Mengapa test jalur ditolak penting pada aplikasi LMS?

Jawaban ringkas:

1. Feature test menguji alur HTTP; unit test menguji method atau logika kecil.
2. Membuat database test kembali bersih dan terisolasi.
3. Saat user terautentikasi tetapi tidak berhak; gunakan redirect login untuk tamu.
4. Karena data seeder mungkin tidak ada pada database CI atau dapat berubah.
5. Menggunakan storage sementara tanpa menulis file nyata.
6. Mengumpulkan dan memeriksa notifikasi tanpa mengirim data sungguhan.
7. Status saja tidak membuktikan isi, data, efek, atau otorisasi.
8. Reproduksi, persempit, hipotesis tunggal, uji, perbaiki dan tulis test.
9. Event, listener, jobs, worker, notifications, failed jobs, dan log.
10. Untuk mencegah IDOR, eskalasi role, dan kebocoran resource antar-user.

## 19. Checklist sebelum presentasi

- [ ] Saya bisa menjelaskan metode debugging dalam lima langkah.
- [ ] Saya bisa membedakan feature test dan unit test.
- [ ] Saya bisa menjelaskan `RefreshDatabase` dan mengapa migrasi reversible.
- [ ] Saya bisa menjelaskan Storage fake dan Notification fake.
- [ ] Saya bisa menunjuk satu test yang melakukan assertion bermakna.
- [ ] Saya bisa menjelaskan perbedaan 200, 403, dan redirect login.
- [ ] Saya bisa menjelaskan satu test yang membuat bug menjadi merah saat fitur dihapus.
- [ ] Saya bisa menceritakan tiga bug nyata beserta penyebab dan test pencegahnya.
- [ ] Saya bisa membaca log dan stack trace sampai baris pertama di `app/`.
- [ ] Saya bisa menyebutkan hasil suite terakhir secara tepat.
- [ ] Saya tidak mengklaim worker production aktif bila belum diperiksa.
- [ ] Saya mampu menyebutkan bagian yang belum dipraktikkan saat checkpoint.
