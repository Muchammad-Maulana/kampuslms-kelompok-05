# Panduan Memahami dan Menjelaskan Minggu 13

Panduan ini membantu saya menjelaskan tujuan Minggu 13, pekerjaan yang sudah dilakukan, batas yang belum selesai, dan cara mempertanggungjawabkan jawaban. Gunakan sebagai bahan belajar dan presentasi, bukan untuk mengaku telah melakukan pekerjaan yang belum dilakukan.

## 1. Inti Minggu 13

Minggu 13 membahas cara bekerja dengan perubahan kode secara bertanggung jawab dalam sebuah tim:

1. **READ:** memahami riwayat Git dan kontribusi tanpa menyimpulkan terlalu banyak dari jumlah commit.
2. **BREAK:** mencoba workflow dan memahami risiko kesalahan Git, konflik, serta file rahasia.
3. **FIX:** memperbaiki repository latihan pada branch yang benar, bukan mengubah branch utama sembarangan.
4. **BUILD:** memakai pull request, CI, aturan branch, dan peer review agar perubahan diperiksa sebelum digabung.

Tujuannya bukan sekadar bisa mengetik perintah Git. Saya harus bisa menjelaskan **apa yang saya ubah, bukti bahwa perubahan bekerja, risikonya, dan batas kesimpulan saya**.

## 2. Cara mempertanggungjawabkan jawaban

Untuk setiap pertanyaan, gunakan urutan ini:

1. **Jawab inti lebih dahulu.** Sampaikan kesimpulan dalam satu kalimat.
2. **Jelaskan alasannya.** Terangkan konsep di balik jawaban, bukan hanya nama perintah.
3. **Tunjukkan bukti.** Sebutkan commit, berkas, test, hasil CI, atau demonstrasi yang benar-benar tersedia.
4. **Sebutkan batasnya.** Bedakan simulasi lokal dari kolaborasi nyata, serta data Git dari bukti identitas orang.
5. **Sebutkan langkah berikutnya.** Jika belum selesai, jelaskan apa yang dibutuhkan dan siapa yang perlu membantu.

Contoh pola lisan:

> “Saya memeriksa **[bagian yang diperiksa]**. Buktinya **[file/commit/test yang benar-benar saya lihat]**. Dari situ saya menyimpulkan **[kesimpulan]**. Batasnya **[hal yang belum diuji atau belum dapat dipastikan]**, jadi langkah berikutnya adalah **[aksi]**.”

Jangan mengarang angka, hasil test, review, atau pengaturan GitHub. Jika belum memeriksa sesuatu, jawaban yang jujur adalah “belum saya verifikasi” lalu jelaskan cara memverifikasinya.

## 3. READ — Memahami audit riwayat Git

Angka audit dalam laporan dihitung dari snapshot `origin/ci-milestone-m1` pada commit `4f6c566`, bukan dari kondisi terbaru branch. Snapshot itu memiliki 86 commit termasuk merge commit. Pada snapshot tersebut:

- 28 commit memakai identitas bersama `Kelompok 5`.
- 9 commit terhubung ke variasi nama/email saya; 3 di antaranya merge commit.
- 32 dari 71 commit non-merge menggunakan format Conventional Commits yang umum, sekitar 45,1%.
- Commit `29e86db` adalah commit terbesar yang teridentifikasi atas nama saya: 66 file berubah, 3.391 baris ditambahkan, dan 284 dihapus.

Angka-angka itu menjelaskan **apa yang tampak pada metadata Git**, bukan ukuran pasti kontribusi atau kualitas seseorang. Nama/email dapat berbeda, identitas dapat dibagikan, commit dapat dibuat dengan author metadata berbeda, dan merge commit bisa menggabungkan pekerjaan beberapa orang. Karena itu, `git shortlog` berguna untuk audit awal tetapi tidak cukup sebagai bukti tunggal.

Pesan Conventional Commit seperti `feat: add course leaderboard`, `fix: validate submission deadline`, atau `docs: explain week 13 workflow` membuat tujuan perubahan lebih mudah dipindai. Commit besar seperti scaffold 66 file akan lebih mudah diperiksa bila bagian yang tidak saling bergantung dipisahkan menjadi commit kecil dengan tujuan jelas.

**Jika ditanya tentang jeda commit:** sebutkan tanggal yang terlihat pada snapshot audit, tetapi jangan menebak minggu kuliah tanpa mencocokkannya dengan kalender kelas. Identitas bersama `Kelompok 5` juga tidak bisa dialokasikan ke anggota tertentu hanya dari log.

## 4. BREAK — Apa yang dipelajari dari latihan workflow

### File `.env` dan riwayat Git

`.env` biasanya berisi konfigurasi lokal dan dapat memuat kredensial. `.env.example` boleh dilacak jika hanya berisi nama variabel dan contoh aman, bukan secret aktif. Pemeriksaan CI yang ditambahkan menolak file `.env*` terlacak selain `.env.example`.

Pada repository sementara dengan data palsu, latihan memperlihatkan bahwa:

- `git rm --cached .env` menghapus file dari index Git untuk commit berikutnya, tetapi tidak menghapus file lokal.
- Perintah tersebut **tidak** menghapus salinan `.env` dari commit lama. Jika secret pernah dipush, secret harus dianggap bocor dan diganti; menghapus file dari commit terbaru tidak cukup.
- Tidak ada secret asli yang digunakan dan latihan tidak dipush ke repository publik.

Pemeriksaan CI juga bukan pengganti branch protection. CI melaporkan hasil pemeriksaan; branch protection yang dikonfigurasi pemilik repository dapat mewajibkan PR dan status CI sukses sebelum merge.

### Konflik merge

Konflik terjadi ketika dua perubahan yang digabung tidak dapat dipadukan otomatis, misalnya kedua branch mengubah baris yang sama. Latihan dilakukan di repository lokal sementara: perubahan dari dua branch menimbulkan konflik, lalu konflik diselesaikan dan hasil gabungan diperiksa.

Saat menjelaskan, urutannya: identifikasi bagian konflik, pahami maksud kedua perubahan, pilih atau gabungkan hasil yang benar, hapus penanda konflik, jalankan pemeriksaan yang sesuai, lalu commit hasil resolusi. Karena kedua branch simulasi dibuat sendiri, latihan ini membuktikan pemahaman resolusi konflik, **bukan** pengalaman kolaborasi langsung dengan dua orang.

### Identitas commit

Latihan `--author` menunjukkan metadata author dapat berbeda dari committer. Artinya, nama pada commit tidak otomatis membuktikan siapa yang mengetik atau meninjau kode. Untuk memahami kontribusi, cocokkan riwayat dengan PR, diskusi, review, issue, demonstrasi, dan pembagian kerja.

### Hal yang belum diuji

Push langsung ke `main`, penolakan merge saat CI gagal, dan branch protection belum diuji pada pengaturan GitHub repository. Itu memerlukan izin pemilik/admin dan pengaturan aturan branch. Satu approval juga tidak bisa dipenuhi dengan review diri sendiri; perlu reviewer lain.

## 5. FIX — Repository latihan

Perbaikan repository `kampuslms-broken` **belum dilakukan** karena URL repository yang benar dan branch `w13` belum dapat dipastikan. Pencarian menemukan repository bernama mirip, tetapi tidak ada bukti cukup untuk memastikan mana yang ditugaskan. Mengubah repository yang salah berisiko merusak pekerjaan orang lain.

Langkah yang benar adalah meminta dosen/asdos mengirim URL tepat dan memastikan branch latihan. Setelah itu, buat perubahan pada branch latihan, jalankan test yang tersedia, dan ajukan PR sesuai instruksi. Jangan mengubah `main` atau `dev` tanpa izin.

## 6. BUILD — Apa yang sudah dibuat di repository kelompok

Untuk pekerjaan workflow Minggu 13, repository kelompok mendapatkan:

- template PR yang meminta tujuan, cara menguji, checklist, dan penggunaan AI;
- pemeriksaan CI untuk mencegah `.env` selain `.env.example` ikut dilacak;
- catatan README bahwa pekerjaan Minggu 13 dikerjakan solo, tanpa mengklaim commit identitas bersama sebagai kontribusi individu;
- laporan audit dan latihan workflow.

Dalam rangkaian perubahan proyek yang sama, fitur A05 juga dibuat: papan peringkat per mata kuliah dan lencana ketepatan waktu. Test fitur fokus sebelumnya lulus (`2` test, `11` assertions), dan build frontend berhasil. Fitur A05 adalah pekerjaan produk tambahan; keberadaannya tidak berarti semua butir Minggu 13 otomatis selesai.

Template PR dan CI sudah ada di branch `ci-milestone-m1`; branch protection belum diverifikasi. Pada saat laporan terakhir diperiksa, PR peer yang ditemukan adalah [SufSoup/kampuslms-a-kelompok-06#56](https://github.com/SufSoup/kampuslms-a-kelompok-06/pull/56). Status PR dan check perlu dicek ulang sebelum presentasi karena dapat berubah.

## 7. Peer review — Lima pertanyaan untuk memahami PR

Lima pertanyaan berikut adalah **panduan pemeriksaan**, bukan temuan siap kirim. Saya perlu membuka diff, mengikuti alur kode, mencatat bukti file/baris, lalu menulis kesimpulan dengan kata-kata sendiri. Jangan menyatakan ada bug hanya karena pertanyaan ini diajukan.

### 1. Apakah aturan deadline submission konsisten pada seluruh jalur pengumpulan?

**Yang perlu dipahami:** mahasiswa dapat mengirim tugas melalui lebih dari satu endpoint atau alur. Aturan deadline harus diterapkan di sisi server untuk setiap jalur yang membuat atau mengubah submission. Pemeriksaan di UI saja tidak cukup karena request dapat dikirim langsung.

**Cara memeriksa:** cari semua handler create/update/resubmit; ikuti pemanggilan service/policy; periksa perlakuan terhadap batas waktu, zona waktu, dan submission yang sudah ada. Lihat apakah ada test untuk sebelum deadline, tepat di batas, dan setelah deadline.

**Cara menyampaikan jawaban:** “Saya menelusuri jalur **[nama endpoint]** dan **[jalur lain]**. Keduanya **[memakai/tidak memakai]** aturan deadline yang sama, terlihat pada **[bukti]**. Saya juga memeriksa **[test/batas waktu]**. Karena itu kesimpulan saya **[hasil sesuai bukti]**.”

Jika belum menelusuri semua jalur, jawab bahwa pemeriksaan belum lengkap; jangan menyebut aturan “konsisten” hanya karena menemukan satu validasi.

### 2. Apakah respons lupa password sama untuk email terdaftar dan tidak terdaftar?

**Yang perlu dipahami:** respons publik seharusnya tidak membocorkan apakah suatu email terdaftar. Bandingkan status HTTP, pesan, bentuk respons, dan validasi yang dapat diamati. Di sisi server, email reset hanya dapat diproses untuk akun yang sesuai.

**Cara memeriksa:** ikuti handler reset password dan respons sukses/gagal; lihat test untuk kedua kondisi email. Pertimbangkan juga apakah perbedaan waktu yang mencolok atau pesan validasi lain membocorkan informasi.

**Cara menyampaikan jawaban:** jelaskan kondisi mana yang dibandingkan dan bukti test/handler. Jangan menyimpulkan aman hanya karena satu pesan UI terlihat generik; respons server dan semua jalur error juga perlu diperiksa.

### 3. Apakah resource hanya terlihat oleh pengguna yang berhak?

**Yang perlu dipahami:** menyembunyikan tombol di UI bukan kontrol akses. Hak akses harus diterapkan di server untuk daftar resource, detail, unduh, dan endpoint lain. Memiliki ID resource tidak boleh cukup untuk membaca resource milik mata kuliah/pengguna lain.

**Cara memeriksa:** identifikasi aturan siapa yang boleh membaca—misalnya peserta terdaftar dan dosen terkait—lalu periksa query daftar serta akses langsung ke detail/unduh. Cari test untuk pengguna yang berhak dan tidak berhak, termasuk resource dari mata kuliah lain.

**Cara menyampaikan jawaban:** sebutkan aturan akses yang benar-benar terlihat, endpoint yang diperiksa, dan kasus test. Jika hanya halaman daftar yang diperiksa, jangan mengklaim seluruh akses aman.

### 4. Apakah cache dashboard diperbarui ketika sumber datanya berubah?

**Yang perlu dipahami:** cache mempercepat pembacaan dengan menyimpan hasil sementara, tetapi data dapat menjadi basi jika perubahan sumber tidak menghapus atau memperbarui cache. Sumber perubahan bisa mencakup submission, nilai, keanggotaan, atau data lain yang dipakai dashboard.

**Cara memeriksa:** temukan data yang dipakai untuk membentuk dashboard, kunci cache (termasuk pemisahan pengguna/mata kuliah), TTL, serta tempat invalidasi setelah perubahan. Cocokkan setiap operasi tulis dengan kebutuhan pembaruan dan test-nya.

**Cara menyampaikan jawaban:** jelaskan satu sumber data, kapan cache dibentuk, dan perubahan apa yang membuatnya invalid. Hindari klaim “cache selalu benar” bila hanya menemukan TTL; TTL membatasi umur cache tetapi tidak menjamin pembaruan segera.

### 5. Apakah pagination dan jumlah submission memakai cakupan yang konsisten?

**Yang perlu dipahami:** baris pada halaman dan angka total harus dihitung dari filter/otorisasi yang sama. Bila query baris hanya untuk mahasiswa di kelas tertentu tetapi query total tidak memakai filter itu, angka pagination dapat salah atau membocorkan jumlah data.

**Cara memeriksa:** bandingkan filter query daftar dengan query count; periksa duplikasi akibat join, urutan yang stabil antar halaman, batas ukuran halaman, dan cakupan pengguna/mata kuliah. Cari test untuk halaman pertama/berikutnya dan jumlah total.

**Cara menyampaikan jawaban:** sebutkan filter yang dibandingkan dan bukti hasil test. Jangan menyamakan “ada pagination” dengan “pagination benar”; total, akses, dan konsistensi antar halaman juga penting.

## 8. Penjelasan checkpoint

### Checkpoint 1 — `git rm --cached .env` tidak menghapus riwayat

Jawaban inti: perintah itu menghentikan pelacakan file pada commit berikutnya, bukan menghapus file dari commit lama. Jika secret pernah masuk remote, segera rotasi/ganti secret, lalu ikuti prosedur pembersihan riwayat yang disetujui tim. Pembersihan riwayat saja tidak membuat secret lama aman. Dalam latihan ini, data yang dipakai palsu dan tidak dipush.

### Checkpoint 2 — Mengapa PR kecil lebih baik?

PR kecil dengan satu tujuan lebih mudah dipahami, diuji, dibahas, dan dibatalkan bila perlu. Perubahan kecil juga membuat konflik dan regresi lebih mudah dilokalisasi. PR besar tidak otomatis salah, tetapi perubahan yang tidak berkaitan sebaiknya dipisah bila memungkinkan. Contoh pada repository peer adalah PR sinkronisasi besar; itu alasan untuk menelusuri per area, bukan bukti bahwa kodenya pasti bermasalah.

### Checkpoint 3 — Mengapa jumlah commit bukan ukuran kontribusi?

Jumlah commit dipengaruhi merge, squash, identitas bersama, perubahan email, dan metadata author. Satu commit besar dapat berisi banyak pekerjaan; banyak commit kecil juga belum tentu menunjukkan dampak besar. Gunakan commit sebagai satu petunjuk, lalu lengkapi dengan PR, review, issue, test, demonstrasi, dan pembagian tugas. Pada laporan, angka audit dibatasi pada snapshot yang disebutkan, bukan dipresentasikan sebagai keadaan branch terkini.

## 9. Status Minggu 13 dan jawaban “sudah selesai?”

**Belum seluruhnya selesai.** Yang sudah dilakukan: audit Git dan refleksi; simulasi lokal aman untuk file `.env`, konflik, dan metadata author; template PR dan pemeriksaan CI; pembaruan README/laporan; identifikasi satu target peer review. Pekerjaan fitur A05 juga sudah dipush.

Yang masih terbuka:

- repository latihan dan branch `w13` yang benar belum dikonfirmasi, jadi bagian FIX belum dikerjakan;
- saya belum menulis/mengirim review individu. Saya harus membaca PR dan menuliskan sendiri temuan yang benar-benar didukung bukti;
- branch protection, required CI, dan approval belum diverifikasi/dikonfigurasi;
- belum ada review masuk yang dapat saya tanggapi dan perbaiki. Jika kelompok tetap solo, minta dosen/asdos atau pemilik repository membantu mengatur reviewer dan pembagian syarat yang membutuhkan orang lain.

Status ini lebih dapat dipertanggungjawabkan daripada menyebut semua selesai padahal sebagian butir memerlukan akses atau orang lain. Branch berisi pembaruan laporan terakhir pada commit `0247929`; angka audit tetap merujuk snapshot historis `4f6c566`.

## 10. Checklist sebelum presentasi

- [ ] Saya bisa menjelaskan arti 86, 28, 9, dan 32/71 tanpa menyebutnya bukti mutlak kontribusi.
- [ ] Saya bisa menerangkan perbedaan menghapus file dari index, working tree, dan riwayat Git.
- [ ] Saya bisa menjelaskan bagaimana konflik dibuat dan diselesaikan, serta menyebutnya simulasi solo.
- [ ] Saya membuka kode PR peer sebelum membuat kesimpulan dan mencatat file/baris serta test yang relevan.
- [ ] Saya membedakan hal yang sudah dipush dari hal yang belum diverifikasi di GitHub.
- [ ] Saya menyebutkan keterbatasan dan langkah berikutnya dengan jujur.
