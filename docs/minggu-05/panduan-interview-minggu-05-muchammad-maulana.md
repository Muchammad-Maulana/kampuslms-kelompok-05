# Panduan Belajar Interview Build Minggu 5

**Nama:** Muchammad Maulana  
**NIM:** 10241043  
**Proyek:** KampusLMS, Laravel 12

Panduan ini menjelaskan checkpoint Week 5 dengan bahasa sederhana, contoh situasi, potongan kode inti, letak implementasi di repo, dan cara demo aman. Targetnya memahami alur, bukan menghafal istilah.

---

## Peta Besar Request

Saat seseorang membuka halaman detail course, alurnya kira-kira begini:

```text
Browser meminta URL
  -> route mencocokkan URL dan HTTP method
  -> middleware memeriksa login dan role
  -> model binding mencari course
  -> controller memeriksa hak user atas course
  -> controller mengirim view atau menolak request
```

Contoh URL dosen setelah database lokal dibuat dengan seeder:

```text
GET /dosen/mata-kuliah/1
```

- `GET`: browser meminta halaman.
- `/dosen`: area dosen.
- `mata-kuliah/1`: detail course dengan ID 1.
- Laravel menjalankan middleware `auth` dan `role:dosen` sebelum controller.
- `Course $course` membuat Laravel mencari course ID 1.
- Controller memeriksa apakah dosen yang login memang pengampu course tersebut.

File utama untuk mengikuti alur ini:

- Route: `routes/web.php`
- Middleware: `app/Http/Middleware/EnsureUserHasRole.php`
- Controller course: `app/Http/Controllers/CourseController.php`
- Feature test: `tests/Feature/WeekFiveFeatureTest.php`

---

# Checkpoint 1: IDOR

## Apa artinya?

IDOR adalah *Insecure Direct Object Reference*: pengguna mengganti identifier di URL atau request, lalu dapat melihat/mengubah data orang lain karena server hanya mengecek bahwa data itu ada.

Contoh umum IDOR di LMS kadang memakai URL submission, tetapi project KampusLMS saat ini belum memiliki route `/submissions/{id}`. Jadi jangan gunakan URL submission sebagai demo project kita.

Contoh yang benar-benar tersedia pada project kita adalah detail mata kuliah:

```text
Mahasiswa demo membuka /mahasiswa/mata-kuliah/1
Lalu ia mengganti URL menjadi /mahasiswa/mata-kuliah/2
Course 1: terdaftar, jadi detail tampil (200).
Course 2: tidak terdaftar, jadi akses ditolak (403).
```

Tidak perlu menebak password atau meretas server. Yang diuji adalah apakah server memeriksa pendaftaran mahasiswa setiap kali URL detail diminta.

## Bagaimana Week 5 mencegah contoh yang setara?

Di `CourseController@show`, aplikasi menentukan apakah user boleh melihat course:

```php
$canView = match ($user->role) {
    'admin' => true,
    'dosen' => $course->lecturer_id === $user->id,
    'mahasiswa' => $user->courses()->whereKey($course->getKey())->exists(),
    default => false,
};

abort_unless($canView, 403);
```

Arti tiap bagian:

- `match ($user->role)`: pilih aturan sesuai role user yang sedang login.
- `'admin' => true`: admin boleh melihat course mana pun.
- `'dosen' => ...`: dosen boleh kalau `lecturer_id` course sama dengan ID dosen login.
- `'mahasiswa' => ...exists()`: mahasiswa boleh kalau course terdaftar di relasi `courses` miliknya.
- `default => false`: role tak dikenal tidak diberi akses.
- `abort_unless(..., 403)`: kalau syarat akses salah, hentikan request dengan HTTP 403.

Contoh konkret yang dipakai test project: sebuah course dimiliki Dosen B, sedangkan yang login Dosen A.

```text
course.lecturer_id = ID Dosen B
user.id            = ID Dosen A
hasil perbandingan = false
response           = 403 Forbidden
```

## Jawaban lisan

> IDOR adalah celah ketika user dapat mengakses objek milik orang lain dengan mengganti ID pada URL atau request. Binding hanya mencari record, jadi saya tetap memeriksa hak akses di server. Di detail course, dosen harus menjadi pengampu dan mahasiswa harus terdaftar. Jika tidak sesuai, controller menghentikan request dengan 403.

## Demo di depan dosen

1. Buka `tests/Feature/WeekFiveFeatureTest.php`.
2. Cari `test_lecturer_cannot_view_another_lecturers_course()`.
3. Tunjukkan bahwa test membuat dua dosen dan course yang dimiliki dosen kedua.
4. Request dikirim sebagai dosen pertama; test mengharapkan 403.
5. Jalankan:

```powershell
php artisan test --filter=test_lecturer_cannot_view_another_lecturers_course
```

Test menggunakan database sementara dari `RefreshDatabase`, bukan database kelas/produksi.

---

# Checkpoint 2: UUID dan IDOR

## Kenapa UUID bukan perbaikan utama?

ID berurutan seperti `12`, `13`, `14` mudah ditebak. UUID seperti `550e8400-e29b-41d4-a716-446655440000` lebih sulit ditebak. Tetapi identifier bukan izin.

Jika penyerang mendapatkan UUID dari tautan, log, screenshot, atau response lain, maka tanpa pemeriksaan otorisasi ia tetap dapat mencoba membukanya. Jadi:

```text
UUID + tidak ada pemeriksaan izin = tetap rentan
ID biasa + pemeriksaan izin yang benar = akses tetap ditolak
```

## Jawaban lisan

> UUID mengurangi kemudahan menebak identifier, tetapi tidak membuktikan bahwa user berhak mengakses datanya. Otorisasi harus tetap diperiksa server pada setiap request. UUID boleh menjadi lapisan tambahan, bukan pengganti pemeriksaan izin.

## Apa yang ditunjukkan di repo?

Project ini memakai ID database biasa. Tunjukkan pemeriksaan kepemilikan di `CourseController@show`. Jangan mengatakan project sudah memakai UUID untuk keamanan.

---

# Checkpoint 3: Route Model Binding

## Apa yang terjadi?

Route membawa parameter `{course}`. Controller menerima objek `Course`, bukan angka mentah:

```php
Route::get('mata-kuliah/{course}', [CourseController::class, 'show']);
```

```php
public function show(Request $request, Course $course)
{
    // $course sudah merupakan model Course yang ditemukan Laravel.
}
```

Kalau URL berisi `/mata-kuliah/1`, Laravel mencari record Course dengan ID 1 lalu memasukkannya ke parameter `$course`. Kalau record tidak ditemukan, Laravel memberi 404.

Cara manual yang tidak dipakai pada controller Week 5:

```php
public function show($id)
{
    $course = Course::findOrFail($id);
}
```

Cara binding lebih ringkas dan menyatakan tipe objek yang dibutuhkan langsung pada signature method.

## Apa yang TIDAK dijamin?

Binding hanya menjawab:

> “Apakah record dengan identifier ini ada?”

Binding tidak menjawab:

> “Apakah user yang login boleh melihat record ini?”

Itu sebabnya `CourseController@show` tetap punya `$canView` dan `abort_unless()`.

## Jawaban lisan

> Route model binding mengubah parameter URL menjadi objek model. Jika record tidak ada, Laravel mengembalikan 404. Tetapi binding bukan otorisasi. Setelah model ditemukan, controller tetap memeriksa role dan kepemilikan atau pendaftaran user.

## Demo

Buka `CourseController.php`, method `show(Request $request, Course $course)`. Tunjukkan parameter bertipe `Course`, lalu tunjukkan pemeriksaan `$canView`. Jelaskan: baris pertama adalah binding; pemeriksaan berikutnya adalah otorisasi.

---

# Checkpoint 4: Nested Route, `scopeBindings()`, dan `shallow()`

## Hubungan parent-child

Satu course memiliki banyak assignment dan materi:

```php
// app/Models/Course.php
public function assignments(): HasMany
{
    return $this->hasMany(Assignment::class);
}

public function materials(): HasMany
{
    return $this->hasMany(Material::class);
}
```

- Course adalah **parent**.
- Assignment atau material adalah **child**.
- Relasi `hasMany` memberi Laravel cara mencari child milik parent tersebut.

## Nested URL

Contoh daftar assignment dalam course ID 1:

```text
GET /dosen/mata-kuliah/1/assignments
```

Contoh detail bertingkat:

```text
GET /dosen/mata-kuliah/1/assignments/99
```

Secara bisnis, assignment 99 harus milik course 1. URL yang mencoba mengambil assignment milik course lain tidak boleh berhasil.

Route di `routes/web.php` disusun dalam grup:

```php
Route::scopeBindings()->group(function () {
    Route::resource('mata-kuliah.assignments', AssignmentController::class)
        ->only(['index', 'show'])
        ->parameters([
            'mata-kuliah' => 'course',
            'assignments' => 'assignment',
        ])
        ->shallow();
});
```

- `scopeBindings()`: meminta Laravel membatasi binding child melalui relasi parent.
- `resource('mata-kuliah.assignments', ...)`: membentuk route resource bertingkat.
- `only(['index', 'show'])`: hanya membuat route daftar dan detail.
- `parameters(...)`: menentukan nama parameter model yang diterima controller.
- `shallow()`: route detail boleh lebih pendek, misalnya `/dosen/assignments/99`, tanpa mengulang ID course.

## Detail penting tentang implementasi repo

Karena `shallow()` menghilangkan parent dari URL detail, controller juga menyediakan route detail nested khusus untuk menguji kecocokan parent-child. Di `AssignmentController@showScoped()` ada pemeriksaan eksplisit:

```php
abort_unless($assignment->course_id === $course->id, 404);
```

Artinya, sekalipun assignment ditemukan, request nested akan ditolak jika `course_id` assignment tidak sama dengan ID course pada URL.

Akurasi saat interview: test yang ada membuktikan proteksi gabungan nested route dan pemeriksaan `course_id` ini. Jangan mengatakan test tersebut mengisolasi perilaku otomatis Laravel dari `scopeBindings()` saja.

## Jawaban lisan

> Nested route menggambarkan hubungan resource anak dengan course induknya. `scopeBindings()` membatasi binding child melalui relasi parent. Di implementasi ini, controller juga membandingkan `course_id` untuk memastikan assignment benar-benar milik course di URL. Kalau berbeda, response 404.

## Demo

1. Tunjukkan relasi `Course::assignments()` di `app/Models/Course.php`.
2. Tunjukkan grup `Route::scopeBindings()` di `routes/web.php`.
3. Buka test `test_scoped_nested_route_rejects_an_assignment_from_another_course()`.
4. Test membuat Course A, Course B, dan assignment milik B; lalu meminta assignment itu melalui URL Course A.
5. Hasil yang diharapkan adalah 404.

```powershell
php artisan test --filter=test_scoped_nested_route_rejects_an_assignment_from_another_course
```

---

# Checkpoint 5: Middleware Laravel 12

## Tiga potongan yang perlu dipahami

### 1. Alias didaftarkan

Di `bootstrap/app.php`:

```php
$middleware->alias([
    'role' => EnsureUserHasRole::class,
]);
```

Artinya saat route menulis `role:admin`, Laravel menjalankan class `EnsureUserHasRole`.

### 2. Route memakai alias

Di `routes/web.php`:

```php
Route::middleware(['auth', 'role:dosen'])
    ->prefix('dosen')
    ->name('dosen.')
    ->group(function () {
        // route dosen berada di dalam group ini
    });
```

Urutannya secara konsep:

- `auth`: user harus login.
- `role:dosen`: role user harus dosen.
- `prefix('dosen')`: URL dalam group dimulai dengan `/dosen`.
- `name('dosen.')`: nama route dalam group dimulai dengan `dosen.`.

### 3. Middleware memeriksa request

Di `EnsureUserHasRole.php`:

```php
$user = $request->user();

abort_unless($user, 401);
abort_unless(in_array($user->role, $roles, true), 403);

return $next($request);
```

- `$request->user()`: user yang sedang login, atau `null`.
- Tidak ada user: 401, belum terautentikasi.
- Ada user tetapi role tidak cocok: 403, sudah dikenal tetapi tidak diizinkan.
- `$next($request)`: request boleh lanjut ke controller.

## Kenapa bukan `Kernel.php`?

Project memakai Laravel 12. Di setup ini alias middleware dikonfigurasi di `bootstrap/app.php` lewat `withMiddleware()`, bukan pola lama mendaftarkan alias di `app/Http/Kernel.php`.

## Jawaban lisan

> Pada Laravel 12, alias middleware proyek ini didaftarkan di `bootstrap/app.php`. Route memakai alias `role`, lalu middleware membandingkan role user. User yang belum login mendapat 401, sedangkan user login dengan role yang salah mendapat 403.

## Demo

Buka berurutan:

1. `bootstrap/app.php` untuk alias `role`.
2. `app/Http/Middleware/EnsureUserHasRole.php` untuk logika pemeriksaan.
3. `routes/web.php` untuk contoh pemakaian `auth` dan `role:dosen`.
4. Test `test_user_without_admin_role_receives_a_safe_forbidden_page()` untuk bukti user non-admin ditolak dari `/admin/users`.

---

# Checkpoint 6: Kenapa Middleware Role Tidak Cukup untuk Hak Per-Object?

## Contoh sederhana

`role:dosen` hanya bertanya:

> “Apakah user ini dosen?”

Ia tidak bertanya:

> “Apakah course ini milik dosen yang sedang login?”

Dua dosen sama-sama punya role `dosen`, tetapi hak terhadap course mereka berbeda. Karena itu dibutuhkan pemeriksaan per objek setelah route model binding.

Contoh pemeriksaan di `CourseController@show`:

```php
'dosen' => $course->lecturer_id === $user->id,
```

Ini memastikan dosen hanya membuka course yang `lecturer_id`-nya sama dengan user aktif.

## Kondisi route edit saat ini

Di repo saat ini, CRUD course berada dalam route group admin. Dosen punya route baca course, materi, dan tugas, tetapi belum punya route untuk mengedit course. Jadi jangan mendemokan atau mengklaim bahwa endpoint edit dosen tersedia. Contoh ownership edit adalah prinsip konsep; test nyata yang tersedia menguji dosen ditolak saat membuka detail course milik dosen lain.

## Jawaban lisan

> Middleware role hanya membatasi siapa yang boleh memasuki area dosen. Ia tidak memeriksa kepemilikan setiap course. Pemeriksaan objek dilakukan di controller: dosen harus menjadi pengampu course. Di versi aplikasi ini route edit course hanya ada untuk admin, jadi bukti yang dapat saya demo adalah penolakan akses detail course dosen lain.

---

# Tabel Status HTTP

| Status | Maksud sederhana | Contoh pada alur aplikasi |
|---|---|---|
| 401 | Belum login/tidak ada identitas yang valid | Middleware role tidak menemukan user pada request. |
| 403 | Sudah login tetapi tidak punya hak | Mahasiswa mencoba `/admin/users`, atau dosen membuka course dosen lain. |
| 404 | Resource tidak ditemukan atau sengaja tidak diungkap | ID tidak ada, parent-child nested tidak cocok, atau mahasiswa meminta assignment draft. |
| 200 | Request berhasil dan halaman ditampilkan | Mahasiswa terdaftar membuka detail course. |

Cara mengingat: **401 = siapa kamu belum terbukti; 403 = tahu siapa kamu, tapi tidak boleh; 404 = resource tidak tersedia/ditemukan.**

---

# Contoh Test Dibaca Baris Demi Baris

Contoh dari `test_lecturer_cannot_view_another_lecturers_course()`:

```php
$lecturer = User::factory()->create(['role' => 'dosen']);
$otherLecturer = User::factory()->create(['role' => 'dosen']);
$course = Course::factory()->create(['lecturer_id' => $otherLecturer->id]);

$response = $this->actingAs($lecturer)
    ->get(route('dosen.mata-kuliah.show', $course));

$response->assertForbidden();
```

Penjelasan:

1. Buat Dosen A.
2. Buat Dosen B.
3. Buat course yang dimiliki Dosen B.
4. Berpura-pura request berasal dari Dosen A (`actingAs`).
5. Minta route detail course.
6. Pastikan aplikasi menjawab 403.

Ini bukan hanya mengetes tampilan. Test mengirim HTTP request ke route dan memeriksa status response.

---

# Peta File yang Perlu Dibuka Saat Interview

| Pertanyaan dosen | File/method | Tunjukkan |
|---|---|---|
| Route role ada di mana? | `routes/web.php` | Group `admin`, `dosen`, `mahasiswa`; middleware, prefix, name prefix. |
| Alias middleware Laravel 12? | `bootstrap/app.php` | Alias `role` menuju `EnsureUserHasRole`. |
| Apa isi middleware? | `app/Http/Middleware/EnsureUserHasRole.php` | Pemeriksaan user, 401, 403, dan `$next`. |
| Bagaimana daftar course difilter? | `app/Http/Controllers/CourseController.php`, `index()` | Query berbeda untuk admin, dosen, mahasiswa. |
| Bagaimana akses detail course dicek? | `app/Http/Controllers/CourseController.php`, `show()` | `$canView`, `abort_unless`, pemilik dan pendaftar. |
| Bagaimana materi dibatasi? | `app/Http/Controllers/MaterialController.php` | Pemeriksaan akses memakai course pemilik materi. |
| Bagaimana tugas draft disembunyikan? | `app/Http/Controllers/AssignmentController.php` | Filter `status = published` untuk mahasiswa. |
| Relasi course dan mahasiswa? | `app/Models/User.php`, `courses()`; `app/Models/Course.php`, `students()` | Relasi many-to-many melalui pivot `course_user`. |
| Bukti test? | `tests/Feature/WeekFiveFeatureTest.php` | Buka test sesuai kasus yang sedang dijelaskan. |

---

# Rencana Demo 5 Menit

## Persiapan data demo

Seeder membuat akun demo mahasiswa selalu terdaftar pada course pertama, tetapi tidak pada course kedua. Ini membuat percobaan mengganti ID course bisa diulang dengan hasil yang sama.

Untuk mereset database lokal agar kondisi demo pasti:

```powershell
php artisan migrate:fresh --seed
```

Perhatian: perintah ini menghapus seluruh tabel pada database yang sedang dikonfigurasi lalu mengisinya ulang. Pastikan `.env` menunjuk ke database development/lokal sebelum menjalankannya. Jangan jalankan pada database penting atau production.

Akun frontend:

```text
Email: mahasiswa@kampuslms.test
Password: password
Role: Mahasiswa
```

## Demo layar: mahasiswa boleh Course 1, ditolak di Course 2

1. Buka `/login` dan login dengan akun mahasiswa di atas.
2. Dari dashboard klik **Buka daftar mata kuliah** atau menu **Mata Kuliah**.
3. Buka salah satu course yang tersedia. Perhatikan URL, misalnya:

```text
http://127.0.0.1:8000/mahasiswa/mata-kuliah/1
```

4. Jelaskan bahwa course pertama memang ditautkan ke mahasiswa demo melalui tabel pivot `course_user`.
5. Ubah hanya angka terakhir pada address bar dari `/1` menjadi `/2`, lalu tekan Enter:

```text
http://127.0.0.1:8000/mahasiswa/mata-kuliah/2
```

6. Dengan database hasil seeder, mahasiswa demo tidak terdaftar pada course kedua, sehingga halaman yang diharapkan adalah **403 Akses ditolak**.

Kalimat sambil menunjuk layar:

> Saya login sebagai mahasiswa demo. Course pertama bisa dibuka karena mahasiswa ini terdaftar di course tersebut. Saya mengganti ID URL ke course kedua. Model course-nya ada, tetapi mahasiswa tidak terdaftar, jadi `CourseController@show` mengembalikan 403. Ini membuktikan keputusan akses dibuat di server, bukan hanya dengan menyembunyikan course dari daftar.

Kalau course pertama di lingkungan lokal bukan ID 1 atau course kedua bukan ID 2, gunakan URL yang dibuat tombol **Detail** dari daftar, lalu ganti ID ke ID course lain. Perilaku ditentukan oleh pendaftaran, bukan oleh angka tertentu. Untuk demo yang persis memakai `/1` dan `/2`, jalankan reset-seed di atas dahulu.

## Potongan kode yang ditunjukkan setelah demo

Di `app/Http/Controllers/CourseController.php`, method `show()`:

```php
'mahasiswa' => $user->courses()
    ->whereKey($course->getKey())
    ->exists(),
```

Arti sederhananya:

- `courses()` mengambil relasi course yang diikuti mahasiswa login.
- `whereKey(...)` membatasi pencarian ke course yang diminta di URL.
- `exists()` menghasilkan `true` kalau ada baris pendaftaran yang cocok, selain itu `false`.

> Kalau hasilnya `false`, baris berikutnya menghentikan request:

```php
abort_unless($canView, 403);
```

Artinya: “lanjut hanya jika `$canView` bernilai true; kalau false, kirim response 403.”

## Test otomatis jika live demo tidak praktis

Gunakan test khusus yang menjamin akun mahasiswa demo boleh course pertama dan ditolak pada course kedua:

```powershell
php artisan test --filter=test_demo_student_can_open_first_seeded_course_but_not_second
```

Test ini menjalankan database sementara, memastikan relasi enrollment, lalu memeriksa response `200` untuk course pertama dan `403` untuk course kedua. Untuk seluruh skenario Week 5:

```powershell
php artisan test --filter=WeekFiveFeatureTest
```

## Urutan singkat presentasi

1. Tunjukkan menu **Mata Kuliah** setelah login sebagai mahasiswa.
2. Buka Course 1 dan tunjukkan halaman detail course.
3. Ganti URL menjadi Course 2 dan tunjukkan halaman 403.
4. Tunjukkan potongan query enrollment dan `abort_unless()` di `CourseController@show`.
5. Tunjukkan test otomatis sebagai bukti bahwa hasilnya bisa direproduksi.
6. Jika waktu cukup, tunjukkan halaman detail course: tombol Materi/Tugas membawa user ke daftar resource yang masih diperiksa otorisasinya.

Kalimat penutup demo:

> Mengubah URL memang berhasil meminta course lain, tetapi tidak otomatis memberi izin. Server memeriksa relasi pendaftaran setiap kali detail course diminta. Karena mahasiswa ini tidak terdaftar pada course kedua, server menolak request dengan 403.

```powershell
php artisan test --filter=WeekFiveFeatureTest
```

Hasil terbaru repo setelah penambahan navigasi dashboard: **12 tests passed (31 assertions)**.

---

# Jawaban Penutup

> Pada Build Minggu 5 saya mengatur route menurut role. Middleware memastikan user login dan punya role yang tepat. Route model binding mencari model dari URL, tetapi tidak dianggap sebagai izin. Controller dan query tetap memeriksa apakah dosen pemilik course atau mahasiswa terdaftar. Untuk assignment dan materi, nested route menjaga hubungan child dengan course. Saya membuktikan perilaku ini lewat feature test yang mengharapkan 403 atau 404 untuk akses yang tidak sesuai.

---

# Tips Latihan

- Untuk setiap istilah, beri contoh yang ada di demo: akun Mahasiswa Demo, course ID 1 yang diikuti, dan course ID 2 yang tidak diikuti.
- Jelaskan dengan urutan: request → route → middleware → model binding → pemeriksaan controller → response.
- Tunjuk potongan kode pendek, jangan membaca seluruh file.
- Bedakan status: 401 belum terautentikasi, 403 tidak punya izin, 404 tidak ditemukan/tidak diungkap.
- Jangan mengaku ada Policy; saat ini pemeriksaan ada di controller.
- Jangan mengaku dosen dapat edit course; route edit saat ini milik admin.
