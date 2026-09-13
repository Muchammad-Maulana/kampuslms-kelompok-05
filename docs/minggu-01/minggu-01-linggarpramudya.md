# Catatan Minggu 1
Nama : Linggar Pramudya\
Nim : 10241039\
Kelas : A

## 1.3 Read → Break → Fix → Build

### READ — Bedah instalasi Anda sendiri (45 menit)

Setelah instalasi selesai dan halaman selamat datang Laravel muncul, kerjakan **tanpa AI**:

1. Buka `public/index.php`. Baca dari atas ke bawah. Tulis dalam 3 kalimat apa yang dilakukan berkas ini.
2. Buka `bootstrap/app.php`. Identifikasi bagian mana yang mengurus route, mana yang mengurus middleware, mana yang mengurus exception.
3. Buka `routes/web.php`. Temukan route yang menghasilkan halaman selamat datang. Ubah teksnya, muat ulang browser, pastikan berubah.
4. Jalankan `php artisan route:list`. Cocokkan keluarannya dengan isi `routes/web.php`.

### Jawaban
1. **Menerima Request User**

2. **Hasil identifikasi**

   - ini yang mengurus routesnya

     ```php
     return Application::configure(basePath: dirname(__DIR__))
         ->withRouting(
             web: __DIR__.'/../routes/web.php',
             commands: __DIR__.'/../routes/console.php',
             health: '/up',
         )
     ```

   - ini yang mengurus middleware

     ```php
     ->withMiddleware(function (Middleware $middleware): void {
         //
     })
     ```

   - ini yang mengurus exception

     ```php
     ->withExceptions(function (Exceptions $exceptions): void {
         //
     })->create();
     ```

3. **ini yang menghasilkan halaman selamat datang**

   ```php
   Route::get('/', function () {
       return view('welcome');
   });
   ```

   ketika welcomenya diganti itu websitenya langsung error, karena terhubung ke resource, view.

4. **`php artisan route:list` dan `route/web.php`**

   - `route/web.php`

     ```php
     Route::get('/', function () {
         return view('welcome');
     });
     ```

   - `php artisan route:list`

     ```text
      PS C:\Users\Linggar\Downloads\proweb\kampuslms-kelompok-05\test> php artisan route:list
      GET|HEAD  / ................................................................................... routes/web.php:5
      GET|HEAD  storage/{path} storage.local › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemServicePr…
      PUT       storage/{path} storage.local.upload › vendor/laravel/framework/src/Illuminate/Filesystem/FilesystemSe…
      GET|HEAD  up ....... vendor/laravel/framework/src/Illuminate/Foundation/Configuration/ApplicationBuilder.php:219
     ```



### BREAK — Rusak dengan sengaja (30 menit)

Lakukan satu per satu, catat pesan errornya, lalu kembalikan:

| # | Yang dirusak | Prediksi Anda sebelum mencoba | Pesan error sebenarnya |
|--|--------------|-------------------------------|------------------------|
| 1 | Ganti nama `.env` menjadi `.env.bak` | Laravel akan error karena `.env` tidak ditemukan |Muncul This site can’t be reached, dan refused to connect  |
| 2 | Kosongkan nilai `APP_KEY` di `.env` |Laravel masih berjalan, tetapi login akan error |Muncul `No application encryption key has been specified.`|
| 3 | Ubah `DB_DATABASE` menjadi nama yang tidak ada |Sistem akan error saat membutuhkan database|Muncul `Database file at path [laravel] does not exist.` |
| 4 | Ubah `APP_DEBUG=false`, lalu ulangi nomor 3 |Detail error akan disembunyikan | Muncul `500 Server Error` |
