# KampusLMS REST API v1

Base URL lokal: `http://127.0.0.1:8000/api/v1`

Seluruh endpoint memakai JSON. Sertakan `Accept: application/json`. Endpoint yang dilindungi memerlukan `Authorization: Bearer <token>`.

## Kontrak V1

Kontrak proyek menetapkan path di bawah `/api/v1`, field resource whitelist, pagination Laravel (`data`, `links`, `meta`), dan status HTTP pada tabel di bawah. Single-resource response memakai `{ "data": ... }`; login memakai `{ "token": ..., "user": ... }`; delete sukses tidak memiliki body (`204`). Jalur frontend minggu 7–16 ditetapkan **Blade + Livewire**.

## Autentikasi

### Login

`POST /auth/login` — maksimal 5 request per menit.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/auth/login \
  -H 'Accept: application/json' \
  -H 'Content-Type: application/json' \
  -d '{"email":"mahasiswa@kampuslms.test","password":"password","device_name":"curl"}'
```

Login gagal menggunakan pesan umum `Email atau kata sandi salah.` untuk menghindari user enumeration.

### Profil token aktif

`GET /me` — perlu token.

### Logout

`POST /auth/logout` — perlu token. Menghapus token yang sedang dipakai dan mengembalikan `204 No Content`.

## Courses

Mutasi course hanya dapat dilakukan admin, konsisten dengan route web yang sudah ada.

### Daftar

`GET /courses` — perlu token. Admin melihat semua course, dosen hanya course yang diajarkan, mahasiswa hanya course yang diikuti. Mendukung query `q`, `status`, dan pagination Laravel `page`.

```bash
curl http://127.0.0.1:8000/api/v1/courses \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer <token>'
```

Response koleksi memakai `data`, `links`, dan `meta` bawaan paginator Laravel. Course mengeluarkan field whitelist: `id`, `code`, `name`, `description`, `sks`, `status`, lecturer yang sudah dimuat, jumlah materials/assignments, dan `created_at`.

### Detail

`GET /courses/{course}` — perlu token dan hak akses atas course.

### Daftar materi

`GET /courses/{course}/materials` — perlu token dan hak akses atas course. Daftar
dibatasi ke materi yang dapat dilihat oleh role pemanggil dan memakai pagination.

### Buat course

`POST /courses` — admin saja. Memakai validasi course yang sama dengan aplikasi web dan mengembalikan `201 Created` beserta resource course.

Field wajib: `code`, `name`, `sks`, `lecturer_id`, dan `status`. `description` opsional.

### Perbarui course

`PUT/PATCH /courses/{course}` — admin saja. Menerima field course yang ingin diubah dan mengembalikan `200 OK` beserta resource course yang diperbarui.

### Hapus course

`DELETE /courses/{course}` — admin saja. Mengembalikan `204 No Content`.

## Assignments

### Daftar

`GET /courses/{course}/assignments` — perlu token dan hak akses atas course. Mahasiswa hanya melihat assignment berstatus `published`. Mendukung `?status=draft|published` dan pagination `?page=`.

### Buat assignment

`POST /assignments` — admin atau dosen pemilik course. Sertakan `course_id`; server memeriksa ownership melalui `AssignmentPolicy` dan mengisi `created_by` dari token. Mengembalikan `201 Created`. Path nested `/courses/{course}/assignments` tetap tersedia.

```bash
curl -X POST http://127.0.0.1:8000/api/v1/assignments \
  -H 'Accept: application/json' \
  -H 'Authorization: Bearer <token>' \
  -H 'Content-Type: application/json' \
  -d '{"course_id":1,"title":"Tugas API","instructions":"Kerjakan tugas.","due_at":"2026-10-20T12:00:00Z","status":"published"}'
```

Field wajib: `title`, `instructions`, dan `due_at`. `max_score`, `allow_late`, dan `status` opsional.

### Detail

`GET /courses/{course}/assignments/{assignment}` — assignment harus menjadi milik course pada URI. Draft assignment disembunyikan dari mahasiswa.

### Perbarui assignment

`PUT/PATCH /assignments/{assignment}` — admin atau dosen pengampu. Field yang dapat diubah: `title`, `instructions`, `due_at`, `max_score`, `allow_late`, dan `status`. Path nested `/courses/{course}/assignments/{assignment}` tetap tersedia dan memeriksa pasangan course-assignment.

### Hapus assignment

`DELETE /assignments/{assignment}` — admin atau dosen pengampu. Mengembalikan `204 No Content`. Path nested juga tersedia.

Pengumpulan tugas, penilaian, dan notifikasi belum diimplementasikan pada milestone ini; alur akademik penuh dijadwalkan untuk M3.

## Status response

| Status | Makna |
|---|---|
| 200 | Request berhasil |
| 201 | Resource berhasil dibuat |
| 204 | Berhasil tanpa isi |
| 401 | Token tidak tersedia atau tidak valid |
| 403 | Pengguna terautentikasi tetapi tidak berhak |
| 404 | Resource tidak ditemukan, parent tidak cocok, atau draft disembunyikan |
| 422 | Validasi gagal |
| 429 | Batas request tercapai |

