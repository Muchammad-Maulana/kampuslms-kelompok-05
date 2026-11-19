# Nama Proyek : LMS Kelompok 5 — A05

## Deployment

Aplikasi production: [KampusLMS di Railway](https://kampuslms-kelompok-05-production.up.railway.app/).

## Fitur A05: Papan Peringkat dan Lencana

Pada setiap mata kuliah, mahasiswa terdaftar dan dosen pengampu dapat melihat jumlah pengumpulan tepat waktu, persentase submission tepat waktu, peringkat, dan lencana mahasiswa. Peringkat memakai jumlah pengumpulan tepat waktu sebagai kriteria utama dan persentase sebagai pemecah nilai seri. Hanya tugas terbit yang sudah melewati batas waktu yang dihitung; nilai tugas tidak ditampilkan. Lencana diberikan pada 3, 5, dan 10 pengumpulan tepat waktu.

## Autentikasi

- Pengguna masuk melalui `/login` dan keluar melalui tombol **Keluar**.
- Tidak ada pendaftaran publik. Admin yang sudah masuk dapat membuat akun melalui **Pengguna > Tambah Pengguna** (`/admin/users/create`).
- Admin dapat mengatur ulang kata sandi dari halaman detail pengguna. Kata sandi baru harus dikonfirmasi dan minimal 8 karakter; token API pengguna tersebut juga dicabut.
- Lima percobaan login gagal untuk kombinasi email dan alamat IP yang sama akan memicu cooldown selama satu menit.

## Queue, notifikasi, dan rekap nilai

Queue aplikasi menggunakan koneksi database (`QUEUE_CONNECTION=database`). Jalankan migrasi untuk menyiapkan `jobs`, `failed_jobs`, dan `notifications`, lalu jalankan worker di terminal terpisah:

```bash
php artisan migrate
php artisan queue:work
```

Tanpa worker, notifikasi tugas baru dan nilai yang sudah diberikan tetap mengantre dan belum tersimpan di kotak notifikasi pengguna. Saat mengembangkan, `composer run dev` juga menjalankan queue listener. Periksa kegagalan dengan `php artisan queue:failed`, dan coba ulang job yang gagal menggunakan `php artisan queue:retry all`.

Lonceng notifikasi memperbarui jumlah yang belum dibaca setiap 30 detik. Halaman `/notifications` menampilkan notifikasi milik akun yang sedang masuk dan menyediakan aksi tandai dibaca. Rekap nilai tersedia di halaman detail course dosen dan menu **Rekap Nilai** mahasiswa.

## Anggota dan Pembagian Peran

| Anggota | Peran/status pada Minggu 1-13 |
|---|---|
| [Muchammad Maulana (10241043)](https://github.com/Muchammad-Maulana) | Anggota aktif A05; saat ini mengerjakan fitur papan peringkat dan lencana ketepatan waktu secara solo, serta tugas Minggu 13.. |
| [Linggar Pramudya (10241039)](https://github.com/Ling9ar) | Membuat factory dan seeder, tampilan/course UI, throttle/login, serta validasi dan konfirmasi delete. |
| [Melodiva Rosananda (10241041)](https://github.com/melodivarosa) | Membuat route dan CRUD mata kuliah. |
| [Muhammad Daffa Naufarrel Putranada (10241045)](https://github.com/Azroef) | Membuat CRUD pengguna dan mata kuliah, autentikasi/throttle, halaman 403, route model binding, policy, authorization, serta tabel titik rawan IDOR. |

Sebagian commit lama memakai identitas bersama Kelompok 5, sehingga riwayat Git tidak cukup untuk membagi semua pekerjaan lama ke anggota tertentu. Jumlah commit juga bukan satu-satunya ukuran kontribusi..