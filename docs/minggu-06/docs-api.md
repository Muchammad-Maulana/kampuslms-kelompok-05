# Dokumentasi API KampusLMS

Dokumentasi endpoint terbaru dan request yang dapat dijalankan berada di [`docs/api.md`](../api.md). Bagian ini merupakan ringkasan materi; kontrak persis menunggu Bagian 5 spesifikasi LMS.

## Autentikasi

Semua endpoint data memakai token Bearer dari Sanctum.

### Login

```http
POST /api/v1/auth/login
Accept: application/json
Content-Type: application/json

{
  "email": "mahasiswa@kampuslms.test",
  "password": "password"
}
```

Response:

```json
{
  "token": "1|...",
  "user": {
    "id": 3,
    "name": "Mahasiswa Demo",
    "nim_nip": "10241043",
    "email": "mahasiswa@kampuslms.test",
    "role": "mahasiswa",
    "created_at": "2026-10-06T00:00:00.000000Z"
  }
}
```

## Course

### Daftar course

```http
GET /api/v1/courses
Authorization: Bearer <token>
Accept: application/json
```

Response:

```json
{
  "data": [],
  "meta": {
    "per_page": 15,
    "current_page": 1,
    "total": 0
  }
}
```

- Admin melihat semua course.
- Dosen melihat course yang diajarkan.
- Mahasiswa melihat course yang terdaftar.

### Detail course

```http
GET /api/v1/courses/{course}
Authorization: Bearer <token>
```

Gunakan status `403` ketika token memiliki role tetapi tidak memiliki hak akses course.

## Assignment

### Daftar assignment

```http
GET /api/v1/courses/{course}/assignments
Authorization: Bearer <token>
```

Mahasiswa hanya menerima assignment dengan status `published`.

### Detail assignment

```http
GET /api/v1/courses/{course}/assignments/{assignment}
Authorization: Bearer <token>
```

Jika assignment tidak milik course yang diminta, endpoint mengembalikan `404`.

## Status code

| Status | Maksud |
|---|---|
| 200 | Berhasil |
| 201 | Resource berhasil dibuat |
| 204 | Berhasil tanpa isi |
| 401 | Token tidak tersedia atau tidak valid |
| 403 | Token valid tetapi tidak punya hak |
| 404 | Resource tidak ditemukan atau tidak cocok |
| 422 | Validasi gagal |
| 429 | Rate limit tercapai |

API assignment juga menyediakan `POST` (201), `PUT/PATCH` (200), dan `DELETE` (204). Akses tulis dibatasi untuk admin dan dosen pemilik course.
