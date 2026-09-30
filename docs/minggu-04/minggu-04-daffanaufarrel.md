# Catatan Minggu 4
Nama : Muhammad Daffa Naufarrel Putranada\
NIM : 10241045\
Kelas : A

## 4.3 Read → Break → Fix → Build

### READ — Telusuri satu siklus form gagal (30 menit)

Tanpa AI. Buat form tambah mata kuliah, isi dengan data yang pasti tidak valid (SKS = 99), kirim, lalu jawab:

1. Method apa yang menerima request? Di controller mana?
2. Di titik mana persisnya validasi terjadi — sebelum atau sesudah baris pertama method controller?
3. Ke mana Laravel me-redirect setelah gagal? Siapa yang menentukan tujuannya?
4. Dari mana `@error('sks')` mengambil pesannya?
5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai itu bertahan?
6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel. Catat namanya.

### Jawaban

1. 