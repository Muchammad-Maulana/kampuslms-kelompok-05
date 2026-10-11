# Catatan Minggu 4
Nama : Linggar Pramudya\
Nim : 10241039\
Kelas : A

## 4.3 Read → Break → Fix → Build

### READ — Penelusuran Proses Form dengan Validasi Gagal

Pengujian dilakukan dengan membuka formulir tambah mata kuliah dan memasukkan nilai SKS yang tidak valid, misalnya 99, untuk mengetahui cara Laravel menangani kegagalan validasi.

**Soal**

1. Method apa yang menerima request? Di controller mana?
2. Di titik mana persisnya validasi terjadi, sebelum atau sesudah baris pertama method controller?
3. Ke mana Laravel melakukan redirect setelah validasi gagal? Siapa yang menentukan tujuannya?
4. Dari mana `@error('sks')` mengambil pesan kesalahannya?
5. Dari mana `old('sks')` mengambil nilainya? Berapa lama nilai tersebut bertahan?
6. Buka DevTools → Application → Cookies. Temukan cookie session Laravel dan catat namanya.

**Jawaban**

1. Request dari form tambah mata kuliah diterima oleh method `store(StoreCourseRequest $request)` yang terdapat dalam file `CourseController.php`.

2. Validasi dilakukan sebelum kode di dalam method `store()` dijalankan. Laravel memeriksa input melalui `StoreCourseRequest` terlebih dahulu. Jika data tidak sesuai aturan, proses dihentikan dan pengguna diarahkan kembali ke halaman sebelumnya tanpa menjalankan isi method tersebut.

3. Laravel biasanya mengarahkan pengguna kembali ke halaman form sebelumnya ketika validasi gagal. Tujuan redirect ditangani oleh mekanisme validasi Laravel dengan menggunakan URL sebelumnya, sehingga pengguna dapat memperbaiki input yang bermasalah.

4. Directive `@error('sks')` mengambil pesan kesalahan untuk field `sks` dari error bag Laravel. Pesan tersebut tersedia ketika proses validasi menemukan input yang tidak memenuhi aturan.

5. Helper `old('sks')` mengambil nilai input sebelumnya yang disimpan sementara melalui flash session saat validasi gagal. Nilai tersebut digunakan ketika halaman form ditampilkan kembali setelah redirect dan umumnya tersedia untuk request berikutnya sebelum data flash dibersihkan.

6. Nama cookie session dapat ditemukan melalui DevTools pada bagian Application → Cookies. Pada aplikasi ini, nama cookie yang digunakan adalah `kampuslms_session`, sesuai konfigurasi session aplikasi. Nama tersebut perlu dicocokkan dengan cookie yang benar-benar muncul di browser.

---

### BREAK — Delapan Kerusakan dan Hasil Pengamatan

| # | Yang dirusak                                                                                                       | Prediksi                                                                   | Yang Sebenarnya Terjadi                                                                                                                                                                                                                 |
| - | ------------------------------------------------------------------------------------------------------------------ | -------------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 | Menghapus `@csrf` dari form, lalu mengirim form.                                                                   | Form masih dapat dikirim seperti biasa.                                    | Laravel menampilkan error HTTP 419 Page Expired karena token CSRF tidak tersedia. Token ini membantu mencegah request palsu yang dikirim melalui browser pengguna.                                                                      |
| 2 | Mengganti `$request->validated()` menjadi `$request->all()`, lalu mengirim field tambahan melalui `curl`.          | Hanya data yang diizinkan oleh form yang akan diproses.                    | Seluruh input dalam request dapat terbaca, termasuk field tambahan yang tidak disediakan pada form. Jika tidak ada perlindungan lain, data yang tidak diinginkan berpotensi ikut diproses.                                              |
| 3 | Menghapus validasi `exists:users,id` pada `lecturer_id`, lalu mengirim `lecturer_id=99999`.                        | Sistem akan menolak ID dosen yang tidak ditemukan dengan pesan validasi.   | Jika validasi dihapus, request dapat mencapai database. Dengan foreign key constraint, penyimpanan biasanya gagal dan dapat menghasilkan error database. Tanpa constraint, data dengan referensi yang tidak valid berpotensi tersimpan. |
| 4 | Menghapus validasi `in:...` pada `status`, lalu mengirim `status=superadmin`.                                      | Sistem hanya menerima nilai status yang sudah ditentukan.                  | Nilai `superadmin` dapat lolos dari pemeriksaan aplikasi jika tidak ada validasi pengganti. Database mungkin menyimpannya atau menolaknya, tergantung tipe kolom dan constraint yang digunakan.                                         |
| 5 | Menghapus `->withQueryString()`, melakukan pencarian, lalu membuka halaman 2.                                      | Kata pencarian dan filter akan tetap digunakan ketika berpindah halaman.   | Parameter pencarian dan filter tidak ikut dibawa pada tautan pagination. Akibatnya, halaman berikutnya dapat menampilkan data tanpa filter sebelumnya.                                                                                  |
| 6 | Mengganti `return redirect()` menjadi `return view()` pada method `store`, lalu menekan F5 setelah menyimpan data. | Halaman dapat dimuat ulang tanpa mengirim ulang data.                      | Browser dapat meminta konfirmasi pengiriman ulang form. Jika pengiriman diulang dan tidak ada perlindungan tambahan, data yang sama berpotensi tersimpan kembali. Pola Post-Redirect-Get (PRG) membantu mencegah masalah tersebut.      |
| 7 | Menghapus `old(...)` dari seluruh input, lalu mengirim form dengan satu kesalahan.                                 | Data yang sudah diisi sebelumnya tetap muncul pada form.                   | Input yang sudah benar dapat kembali kosong setelah validasi gagal karena nilai sebelumnya tidak lagi ditampilkan pada masing-masing field. Pengguna harus mengisi ulang data yang telah dimasukkan.                                    |
| 8 | Menghapus pembungkus `where(fn ($q) => ...)` pada pencarian, lalu mencari nama sambil memfilter `status=archived`. | Hasil pencarian tetap mengikuti kata kunci dan filter status yang dipilih. | Kondisi pencarian dapat mengubah pengelompokan logika SQL sehingga data yang tidak sesuai filter ikut muncul. Dampaknya bergantung pada susunan query yang digunakan.                                                                   |