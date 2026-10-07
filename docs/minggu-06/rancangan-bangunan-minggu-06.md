# Rancangan Bangunan Minggu 6 — REST API dan Integrasi

## Tujuan

Membuat API REST KampusLMS yang terautentikasi dengan Sanctum, mengembalikan resource yang eksplisit, menjaga otorisasi role, dan terdokumentasi untuk frontend.

## Prinsip yang dipegang

- API tidak mengembalikan model mentah.
- Setiap endpoint terlindungi membutuhkan token valid melalui `auth:sanctum`.
- `401` untuk token hilang atau tidak valid.
- `403` untuk token valid tetapi tidak punya hak.
- `404` untuk resource tidak ditemukan atau tidak cocok dengan parent.
- Eager loading digunakan agar tidak ada N+1.
- Endpoint login dibatasi 5 permintaan per menit.
- Route API berada pada `/api/v1`.

## Checklist implementasi

1. **Dependency dan autentikasi**
   - Pasang Laravel Sanctum.
   - Aktifkan route API dan migration token.
   - Tambahkan `HasApiTokens` ke model User.
   - Buat endpoint login dan `/me`.

2. **Resource API**
   - Buat `UserResource`, `CourseResource`, `MaterialResource`, `AssignmentResource`, `SubmissionResource`, dan `GradeResource`.
   - Batasi field yang dapat dikirim ke klien.
   - Gunakan `whenLoaded()` dan `whenCounted()` untuk performa.

3. **Course API**
   - `GET`, `POST`, `PUT/PATCH`, dan `DELETE` pada `/api/v1/courses`.
   - Admin, dosen, dan mahasiswa mendapat query berbeda.
   - Student hanya melihat course yang terdaftar.
   - Mutasi course dibatasi admin.

4. **Assignment API**
   - `GET`, `POST`, `PUT/PATCH`, dan `DELETE` pada assignment bersarang di course.
   - Mahasiswa hanya melihat assignment status `published`.
   - Assignment harus berada di course yang sama.

5. **Verifikasi**
   - Feature test API untuk autentikasi, resource, otorisasi, dan nested route.
   - Script curl otorisasi.
   - Dokumentasi `docs/api.md`.

6. **Frontend gerbang**
   - Jalur terpilih: **Blade + Livewire**.
   - Pertahankan Blade yang sudah ada; gunakan API v1 untuk integrasi klien.

## Status penyelesaian

Kontrak API v1 KampusLMS dicatat pada [`docs/api.md`](../api.md) dan diuji melalui feature tests. Kontrak ini dibakukan dari model, otorisasi, dan kebutuhan REST yang tersedia. Jalur frontend minggu 7–16 ditetapkan **Blade + Livewire**.

## Flow request

```text
Browser/API client
  -> POST /api/v1/auth/login
  -> token Sanctum
  -> GET /api/v1/courses
  -> middleware auth:sanctum
  -> controller API
  -> API Resource
  -> JSON + meta
```

## Demo

1. Jalankan `php artisan migrate:fresh --seed`.
2. Login melalui endpoint API menggunakan akun demo.
3. Panggil endpoint courses dengan bearer token.
4. Ubah course ID yang tidak tersedia untuk token dan lihat status 403.
5. Panggil assignment draft sebagai mahasiswa dan lihat 404 atau tidak ada data.

## Verifikasi

```powershell
php artisan test tests/Feature/WeekSixApiFeatureTest.php
php artisan route:list --path=api
php artisan test --filter=WeekFiveFeatureTest
```
