# Nama Proyek : LMS Kelompok 5

## Deployment

Aplikasi production: [KampusLMS di Railway](https://kampuslms-kelompok-05-production.up.railway.app/).

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

## Daftar Anggota
- [Linggar Pramudia (10241039)](https://github.com/Ling9ar)
- [Melodiva Rosananda (10241041)](https://github.com/melodivarosa)
- [Muchammad Maulana (10241043)](https://github.com/Muchammad-Maulana)
- [Muhammad Daffa Naufarrel Putranada (10241045)](https://github.com/Azroef)