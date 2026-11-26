# Debugging Tiga Bug Nyata

Dokumen ini mencatat tiga bug yang benar-benar ditemukan dan diperbaiki selama
pengembangan KampusLMS. Bukti diambil dari riwayat Git, dokumentasi mingguan,
kode saat ini, dan metadata GitHub Actions. Tidak ada log yang dikarang: bagian
yang tidak memiliki teks log asli ditandai **PERLU KONFIRMASI MANUAL**.

## Ringkasan

| Bug | Gejala utama | Perbaikan | Test pencegahan |
|---|---|---|---|
| 1. Foreign key relasi submission salah | Listener nilai tidak menemukan pemilik submission; notifikasi nilai berpotensi gagal | Commit `e80ad73` memakai foreign key `user_id` secara eksplisit | `WeekTenNotificationFeatureTest::test_grade_listener_notifies_only_submission_owner_without_grade_or_feedback_data` |
| 2. URL publik memakai HTTP di balik proxy Railway | Tautan login dan aset memakai HTTP setelah deployment HTTPS | Commit `4125669` mempercayai forwarded headers proxy | `ExampleTest::test_forwarded_https_is_used_for_generated_urls` |
| 3. CI belum menyiapkan environment dan aset frontend | GitHub Actions gagal sebelum test berjalan | Commit `256cc95` menambahkan `.env.example`; `c48bd42` menambahkan build Vite | Langkah CI `php artisan test` setelah `npm ci` dan `npm run build` |

---

## Bug 1 - Relasi `Submission::student()` memakai foreign key yang salah

### Gejala

Saat dosen menyimpan nilai, event `GradeAwarded` memicu listener
`NotifyStudentOfGradeAwarded`. Listener memuat relasi `submission.user` untuk
menentukan mahasiswa penerima notifikasi. Relasi tersebut bisa menghasilkan
pemilik yang salah atau gagal menemukan pemilik submission, sehingga notifikasi
nilai tidak sampai ke mahasiswa yang benar.

### Bukti

- Commit `e80ad73cbda87870e0db105b9dbe995a96349e74` (26 Oktober 2026,
  `feat: add week ten queued notifications and grade reports`) mengubah
  `app/Models/Submission.php` tepat satu baris:

  ```diff
  - return $this->belongsTo(User::class);
  + return $this->belongsTo(User::class, 'user_id');
  ```

- `docs/minggu-10/minggu-10-muchammad-maulana.md` mencatat perbaikan ini:
  "Memperbaiki relasi `Submission::student()` agar memakai foreign key skema
  `user_id`, sehingga listener dapat menemukan pemilik submission secara
  konsisten."
- Migrasi `2026_09_14_000005_create_submissions_table.php` menyimpan pemilik pada
  kolom `user_id`, bukan `student_id`.
- Listener `app/Listeners/NotifyStudentOfGradeAwarded.php` memanggil
  `$grade->submission->user`.

### Penyebab

Eloquent menurunkan foreign key dari nama method relasi. Method `student()`
tanpa argumen kedua dianggap memakai kolom `student_id`. Skema submission tidak
memiliki kolom itu; kolom yang benar adalah `user_id`. Query relasi menjadi salah
dan pemilik submission tidak dapat diandalkan.

Teks exception asli dari masa sebelum perbaikan tidak tersimpan di repository.
**PERLU KONFIRMASI MANUAL** jika ingin menyalin pesan SQL/exception persis dari
log pengembangan saat itu.

### Cara menemukan

1. Membaca listener dan mencari sumber `submission->user`.
2. Membandingkan nama relasi `student()` dengan kolom pada migrasi
   `submissions`.
3. Menjalankan feature test notifikasi dan memeriksa apakah `submission->user`
   benar-benar berisi mahasiswa pemilik submission.

### Perbaikan

Relasi dibuat eksplisit:

```php
public function student(): BelongsTo
{
    return $this->belongsTo(User::class, 'user_id');
}
```

Dengan begitu `student()`, `user()`, dan eager loading `submission.user` memakai
kolom yang sama dengan skema database.

### Test pencegahan

`tests/Feature/WeekTenNotificationFeatureTest.php`:

- `test_grade_listener_notifies_only_submission_owner_without_grade_or_feedback_data`
  memanggil `$this->assertNotNull($grade->submission->user)` lalu memastikan
  notifikasi hanya dikirim ke pemilik submission.
- `test_grading_a_submission_dispatches_a_queued_grade_listener` memastikan
  penilaian melalui route benar-benar menyimpan grade dan memasukkan listener
  ke queue.

Jika foreign key dikembalikan ke default `student_id`, relasi pemilik tidak lagi
menunjuk mahasiswa yang benar dan assertion penerima notifikasi akan gagal.

### Status

Sudah diperbaiki pada commit `e80ad73`; seluruh test notifikasi lulus pada suite
saat ini.

---

## Bug 2 - URL publik terbentuk HTTP di balik proxy Railway

### Gejala

Setelah aplikasi berhasil di-deploy ke Railway dan domain HTTPS dapat dibuka,
tautan login serta aset yang dihasilkan Laravel masih memakai `http://`. Pada
halaman HTTPS, ini dapat menyebabkan mixed content atau tautan keluar dari sesi
aman.

### Bukti

- `docs/minggu-12` mencatat: "Setelahnya situs dapat dibuka, tetapi proxy TLS
  Railway belum dikenali sehingga tautan login dan aset terbentuk dengan HTTP.
  Aplikasi kini mempercayai forwarded headers dari Railway agar URL publik
  menggunakan HTTPS. Regression test untuk URL forwarded HTTPS lulus."
- Commit `4125669be63b99fe8bc8ecc70803e5897b356f7f` (12 November 2026,
  `fix: trust Railway forwarded HTTPS headers`) menambahkan:

  ```php
  $middleware->trustProxies(at: '*');
  ```

- Commit yang sama menambahkan
  `tests/Feature/ExampleTest.php::test_forwarded_https_is_used_for_generated_urls`.
- `docs/deployment.md` juga mencantumkan gejala "Tautan login/aset memakai HTTP
  di balik Railway" dan merujuk regression test tersebut.

### Penyebab

Railway mengakhiri TLS di proxy lalu meneruskan request ke aplikasi melalui HTTP
dengan header `X-Forwarded-Proto: https`. Laravel hanya memakai header forwarded
jika proxy dipercaya. Tanpa konfigurasi `trustProxies`, aplikasi menganggap
request datang sebagai HTTP sehingga `route()` dan `url()` menghasilkan URL
HTTP.

### Cara menemukan

1. Membuka halaman publik dan memeriksa URL pada tautan/aset.
2. Memeriksa header request `X-Forwarded-Proto` dan `X-Forwarded-Host` dari proxy.
3. Menambahkan test yang mengirim header tersebut dan memastikan URL yang
   dihasilkan memakai `https://`.

### Perbaikan

`bootstrap/app.php` sekarang mempercayai forwarded headers:

```php
$middleware->trustProxies(at: '*');
```

Test regresi mengirim:

```php
$response = $this->get('/', [
    'X-Forwarded-Host' => 'lms.example.test',
    'X-Forwarded-Proto' => 'https',
]);

$response->assertSee('https://lms.example.test/login');
```

### Test pencegahan

`tests/Feature/ExampleTest.php::test_forwarded_https_is_used_for_generated_urls`
gagal jika `trustProxies` dihapus, karena URL yang dirender kembali menjadi
`http://`.

Catatan keamanan: `at: '*'` mempercayai semua proxy. Ini sesuai topologi
deployment saat ini yang berada di belakang edge Railway. Jika arsitektur berubah
dan aplikasi dapat diakses langsung, daftar proxy harus dipersempit ke alamat
proxy yang diketahui.

### Status

Sudah diperbaiki pada commit `4125669`; regression test lulus pada suite saat ini.

---

## Bug 3 - CI gagal karena environment dan aset frontend belum disiapkan

### Gejala

Workflow GitHub Actions pertama untuk proyek berwarna merah. Test tidak pernah
mencapai tahap `php artisan test` karena pipeline berhenti lebih awal.

### Bukti

- Run `35107401640` untuk commit `e92de3b` (16 September 2026) berhenti pada
  langkah **Copy .env**. Langkah berikutnya berstatus skipped. Penyebabnya:
  workflow menjalankan `cp .env.example .env`, tetapi `.env.example` belum
  dilacak Git.
  <https://github.com/Muchammad-Maulana/kampuslms-kelompok-05/actions/runs/35107401640>
- Commit `256cc95ed5be51af7ad3aa3b582851d0c67de8a9` (11 Oktober 2026,
  `fix: add environment example for CI`) menambahkan `.env.example`.
- Run `38137112329` untuk commit `256cc95` sudah melewati langkah **Copy .env**
  dan **Install Composer dependencies**, tetapi berhenti pada langkah
  **Run tests**.
  <https://github.com/Muchammad-Maulana/kampuslms-kelompok-05/actions/runs/38137112329>
- Commit `c48bd42030847503485c92d79dd9e86c7c3e9df1` (11 Oktober 2026,
  `fix: build vite assets in CI`) menambahkan langkah Node.js 22, `npm ci`, dan
  `npm run build` sebelum migrasi dan test.
- Run `38137535886` untuk commit `c48bd42` berhasil penuh.
  <https://github.com/Muchammad-Maulana/kampuslms-kelompok-05/actions/runs/38137535886>
- Run terbaru pada branch `main`, `44446923149`, juga berhasil.
  <https://github.com/Muchammad-Maulana/kampuslms-kelompok-05/actions/runs/44446923149>

Metadata langkah run diambil dari GitHub Actions Jobs API. Log mentah langkah
yang gagal memerlukan autentikasi dan tidak tersedia untuk diunduh pada sesi ini.
Karena itu teks error persis pada langkah **Run tests** setelah `.env.example`
ditambahkan ditandai **PERLU KONFIRMASI MANUAL**. Yang terverifikasi adalah
langkah mana yang gagal, commit perbaikan, dan run setelah perbaikan berhasil.

### Penyebab

1. **Tahap pertama:** `.env.example` belum ada di repository, sehingga
   `cp .env.example .env` gagal dan seluruh langkah setelahnya dilewati.
2. **Tahap kedua:** setelah environment tersedia, pipeline belum membangun aset
   frontend. Aplikasi menggunakan `@vite` pada layout Blade; tanpa hasil build
   Vite, halaman yang dirender test dapat gagal. Commit `c48bd42` secara eksplisit
   menambahkan build aset sebelum test.

Penyebab tahap kedua dikuatkan oleh isi commit perbaikan dan urutan kegagalan,
tetapi pesan error asli tidak tersimpan di repository. **PERLU KONFIRMASI
MANUAL** untuk kutipan log persis.

### Cara menemukan

1. Membuka halaman run GitHub Actions dan melihat langkah pertama yang gagal.
2. Membandingkan commit sebelum/sesudah perbaikan pada `.github/workflows/ci.yml`.
3. Memeriksa `.env.example`, `package-lock.json`, dan penggunaan `@vite` pada
   layout.
4. Menjalankan ulang pipeline setelah setiap perbaikan; run `38137535886`
   menjadi bukti pipeline dapat menyelesaikan test.

### Perbaikan

Workflow sekarang menjalankan:

1. `cp .env.example .env`
2. Pemeriksaan agar file `.env` asli tidak ikut ter-commit
3. `composer install`
4. Node.js 22, `npm ci`, dan `npm run build`
5. `php artisan key:generate`
6. `php artisan migrate:fresh --seed --force` dan `migrate:refresh --seed --force`
7. `php artisan test`

### Test pencegahan

Tidak ada feature test aplikasi khusus untuk konfigurasi pipeline; pencegahan
diuji oleh pipeline CI itu sendiri. Setiap push/PR menjalankan langkah build dan
test, sehingga regresi konfigurasi akan langsung terlihat. Untuk verifikasi lokal
di Windows:

```powershell
npm ci
npm run build
php artisan test
```

Jika `npm ci` belum tersedia karena dependency belum diinstal, jalankan
`npm install` terlebih dahulu.

### Status

Sudah diperbaiki. Run CI terbaru pada `main` berhasil, dan workflow saat ini
memuat seluruh langkah yang diperlukan.

---

## Verifikasi ulang

```powershell
php artisan test
php artisan test --filter=WeekTenNotificationFeatureTest
php artisan test --filter=ExampleTest
```

Untuk memeriksa tiga bug di atas secara terpisah:

- Bug 1: `php artisan test --filter=test_grade_listener_notifies_only_submission_owner_without_grade_or_feedback_data`
- Bug 2: `php artisan test --filter=test_forwarded_https_is_used_for_generated_urls`
- Bug 3: jalankan workflow CI atau tiga perintah lokal pada bagian test pencegahan.
