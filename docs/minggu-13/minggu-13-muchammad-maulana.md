# Minggu 13 — Kolaborasi, Version Control, dan Peer Review

Panduan untuk memahami materi, menjelaskan pekerjaan, dan menyiapkan checkpoint ada di [Panduan Memahami dan Menjelaskan Minggu 13](panduan-pemahaman-minggu-13.md).

## Identitas dan kondisi kerja

- **Nama:** Muchammad Maulana
- **NIM:** 10241043
- **Kode kelompok:** A05
- **Proyek:** KampusLMS — Laravel 12
- **Kondisi kelompok:** Saat ini saya mengerjakan tugas Minggu 13 sendiri.
- **Repo peer review:** [SufSoup/kampuslms-a-kelompok-06](https://github.com/SufSoup/kampuslms-a-kelompok-06)
- **PR yang tersedia:** [SufSoup/kampuslms-a-kelompok-06#56](https://github.com/SufSoup/kampuslms-a-kelompok-06/pull/56), sinkronisasi `dev` ke `main`, masih terbuka saat diperiksa. PR mencakup 87 file dan 45 commit, sehingga ruang lingkupnya besar.
- **Status review:** Target sudah ditemukan. Temuan review belum ditulis atau dikirim; itu harus saya lakukan sendiri setelah membaca dan memahami kode.
- **Dasar audit Git:** angka audit dihitung dari snapshot `origin/ci-milestone-m1` pada commit `4f6c566`, sebelum push Minggu 13. Fitur dan laporan awal Minggu 13 dipush pada `d2184c2`; pembaruan laporan terakhir dipush pada `0247929`.

## READ — Riwayat Git kelompok

Ringkasan dari riwayat branch yang sudah dipush:

- Ada **86 commit** pada `origin/ci-milestone-m1`; jumlah ini mencakup merge commit.
- `git shortlog` mencatat **28 commit** atas identitas bersama `Kelompok 5`.
- Setelah menggabungkan variasi nama/email yang terhubung ke NIM saya, ada **9 commit** atas nama saya. Tiga di antaranya adalah merge commit. Identitas Git dapat diubah, jadi angka ini bukan bukti tunggal siapa yang mengerjakan kode.
- Dari 71 commit non-merge, 32 mengikuti format Conventional Commits yang umum, sekitar **45.1%**. Banyak pesan lama tidak memakai format tersebut.
- Commit atas nama saya pada branch yang dipush terlihat pada 9 September, 16 September, 1 Oktober, dan 7 Oktober 2026. Tidak ada commit yang dapat diatribusikan dengan jelas ke identitas saya setelah 7 Oktober pada ref tersebut; commit beridentitas `Kelompok 5` tidak bisa dialokasikan ke orang tertentu dari Git saja.

Jawaban refleksi:

1. **Jumlah commit saya:** 9 berdasarkan nama/email yang digunakan. Angka ini termasuk merge dan variasi identitas.
2. **Periode tanpa commit yang teridentifikasi:** terdapat jeda di antara tanggal-tanggal di atas dan tidak ada commit pribadi teridentifikasi setelah 7 Oktober pada branch yang diperiksa. Pemetaan ke nomor minggu kuliah perlu dicocokkan dengan kalender kelas.
3. **Kepatuhan Conventional Commits:** 32 dari 71 commit non-merge (45.1%). Perhitungan ini memakai tipe umum seperti `feat:`, `fix:`, `docs:`, atau `test:` dalam huruf kecil.
4. **Contoh pesan yang perlu diperbaiki:** commit `6b65e29`, `Refactor: Fix duplikat routes dan standardize parameter naming`, sebaiknya menjadi `refactor: remove duplicate routes and standardize route parameter names`.
5. **Commit terbesar yang teridentifikasi atas nama saya:** `29e86db`, dengan 66 file berubah, 3,391 baris ditambahkan, dan 284 dihapus. Perubahan scaffold dan pemindahan struktur sebesar ini akan lebih mudah ditinjau jika dipisah menjadi beberapa commit yang lebih kecil.

## BREAK — Latihan workflow

- Pemeriksaan lokal menemukan `.env.example` sebagai satu-satunya berkas `.env*` yang dilacak saat ini. Tidak ada `.env` rahasia yang sengaja ditambahkan.
- Saya tidak mendorong `.env` latihan ke repo publik. CI kini memiliki pemeriksaan untuk menolak berkas `.env` yang dilacak selain `.env.example`; hasil GitHub Actions tetap perlu diperiksa setelah perubahan dibuka sebagai PR.
- Menghapus `.env` dari working tree tidak menghapusnya dari commit lama. Jika rahasia pernah dipush, rahasia harus dianggap bocor dan diganti; menghapus file saja tidak cukup.
- Di repository sementara lokal yang berisi data palsu, pemeriksaan menolak `.env` yang dilacak; setelah `git rm --cached`, file masih ada di riwayat dan tetap ada di disk lokal.
- Konflik berhasil disimulasikan dan diselesaikan dengan menggabungkan perubahan dari dua branch. Karena kedua branch dibuat sendiri, ini latihan resolusi konflik, bukan bukti kolaborasi dua orang.
- Commit dengan `--author` menghasilkan identitas author yang berbeda dari committer, menunjukkan metadata author Git dapat diubah.
- Tidak ada secret asli yang dipakai dan tidak ada latihan `.env` yang dipush.
- Push langsung ke `main` dan penguncian merge saat CI gagal belum diuji; keduanya memerlukan aturan branch protection dan PR GitHub.
- Status branch protection belum dapat diverifikasi dari repository lokal. Pengaturannya berada di GitHub; persyaratan satu approval juga memerlukan reviewer lain.
- Commit author dapat diatur melalui metadata Git, sehingga `git shortlog` bukan bukti tunggal kontribusi.

## FIX — Repo latihan

Belum dikerjakan: repo yang benar dengan branch `w13` belum dapat dipastikan. Jika repo latihan terkonfirmasi, perbaikan dilakukan di branch latihan, bukan di `main` atau `dev`. Secret yang sudah pernah masuk riwayat tidak dapat dianggap aman hanya dengan menghapus file; mitigasi yang benar adalah mengganti secret dan menyebutkannya di PR.

Pencarian GitHub menemukan dua repository publik dengan nama serupa, tetapi tidak menemukan branch `w13` yang disebut modul. Saya tidak mengubah repo-repo tersebut karena belum dapat dipastikan mana yang ditugaskan.

## BUILD — Pekerjaan kelompok dan peer review

- [x] Menambahkan template PR di `.github/pull_request_template.md`.
- [x] Menambahkan pemeriksaan CI untuk mencegah `.env` selain `.env.example` ikut dilacak.
- [x] Mencatat kondisi pengerjaan solo di README tanpa mengatribusikan commit bersama ke anggota tertentu.
- [x] Membangun fitur A05: papan peringkat per mata kuliah dan lencana pengumpulan tepat waktu.
- [ ] Memastikan branch protection aktif, PR wajib, CI wajib hijau, dan minimal satu approval. Perlu verifikasi/pengaturan di GitHub oleh pemilik repo; satu approval membutuhkan reviewer lain.
- [x] Mengidentifikasi repo kelompok lain dan PR terbuka [SufSoup/kampuslms-a-kelompok-06#56](https://github.com/SufSoup/kampuslms-a-kelompok-06/pull/56) sebagai target review.
- [ ] Menulis dan mengirim review individu sendiri setelah memahami kode; AI hanya digunakan untuk membantu memahami kode atau menajamkan temuan yang sudah saya tulis.
- [ ] Menanggapi review yang masuk ke repo kelompok dan memperbaiki setidaknya satu temuan yang disepakati.

### Catatan memahami PR peer

PR [SufSoup/kampuslms-a-kelompok-06#56](https://github.com/SufSoup/kampuslms-a-kelompok-06/pull/56) adalah sinkronisasi `dev` ke `main` berukuran besar (87 file, 45 commit). Perubahannya mencakup validasi deadline submission API, respons generik lupa password, penyaringan pembaca resource, cache dashboard, pagination, indeks database, serta konfigurasi deployment. Dua check run GitHub yang terlihat pada head commit berstatus sukses.

Area baca untuk membantu review, bukan temuan siap tempel:

- Keamanan: validasi submission di `lms/app/Http/Controllers/Api/SubmissionController.php`, reset password di `lms/app/Http/Controllers/AuthController.php`, dan visibilitas resource di `lms/app/Http/Controllers/CourseController.php`.
- Performa: invalidasi cache di `lms/app/Support/DashboardCache.php`, pagination dan perhitungan submission, serta migration indeks database.
- Struktur: PR menggabungkan deployment, security, performa, dan UI dalam satu sinkronisasi besar; baca perubahan per jalur agar maksud tiap bagian tetap jelas.

Pertanyaan untuk dijawab sendiri saat menulis review:

1. Apakah aturan deadline submission konsisten pada seluruh jalur pengumpulan?
2. Apakah respons lupa password tetap sama untuk email terdaftar dan tidak terdaftar?
3. Apakah data resource yang terlihat sesuai dengan hak akses pengguna?
4. Apakah perubahan cache terpicu saat semua data sumber yang relevan berubah?
5. Apakah pagination dan jumlah submission memakai cakupan mahasiswa yang konsisten?

## Checkpoint

- `git rm --cached .env` hanya menghentikan pelacakan pada versi berikutnya; salinan pada riwayat Git lama tetap ada.
- PR kecil lebih mudah dipahami, diuji, dan direview daripada PR besar yang mencampur banyak fitur.
- Jumlah commit saja tidak cukup untuk menilai kontribusi karena commit dapat digabung, memakai identitas bersama, atau memiliki author metadata yang diubah.
