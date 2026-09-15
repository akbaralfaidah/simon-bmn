# Paket Prompt Google Stitch — SIMON

Status: rencana fitur dan arah desain telah di-ACC pengguna dalam percakapan. Paket ini berisi prompt untuk menghasilkan desain, belum merupakan hasil desain atau aplikasi yang berjalan. Diperiksa pada 15 September 2026.

Acuan: [Perencanaan SIMON V1](C:/laragon/www/bmn-gakkum-jambi/docs/PERENCANAAN_SIMON_V1.md). Kebutuhan operasional yang masih memerlukan verifikasi instansi tetap berlaku; ACC desain tidak menggantikan bukti mandat atau pengesahan dokumen.

## Cara memakai paket ini

1. Salin Prompt 00 sebagai brief awal pada proyek desain SIMON di Google Stitch. Versi teks siap salin ada pada [STITCH_PROMPT_UTAMA_SIMON.txt](C:/laragon/www/bmn-gakkum-jambi/docs/STITCH_PROMPT_UTAMA_SIMON.txt).
2. Setelah arah visual awal terlihat, lanjutkan Prompt 01–15 satu kelompok pada satu giliran dalam proyek yang sama. Kelompok prompt adalah urutan kerja yang disarankan, bukan klaim tentang batas fitur Stitch.
3. Setiap prompt lanjutan mengacu ke aturan Prompt 00. Jika memakai proyek baru, sertakan kembali Prompt 00 dan referensi desain yang sudah diterima.
4. Gunakan Prompt 16 untuk review konsistensi dan mobile; Prompt 17 untuk koreksi terarah jika hasil melenceng.
5. Salin hanya isi blok teks prompt yang diperlukan. Tidak perlu memberikan akses folder lokal, dokumen pegawai, atau arsip tanda tangan ke layanan desain.
6. Jika satu kelompok belum selesai seluruhnya, lanjutkan daftar layar yang tersisa dengan komponen dan aturan yang sama. Hasil desain harus ditinjau, bukan dianggap otomatis memenuhi alur/keamanan backend.

Prompt memakai bahasa Indonesia agar mudah diedit bersama pengguna. Seluruh microcopy yang diminta juga berbahasa Indonesia. Skill baseline-ui dipakai untuk merapikan spacing, hirarki, fokus/keyboard, validasi lokal, skeleton, serta dialog konfirmasi. Kebutuhan GSAP/Lottie dan warna khusus mengikuti permintaan pengguna, yang mengungguli preferensi library animasi/default palette pada skill.

## Urutan prompt

| Prompt | Fokus |
|---|---|
| 00 | Brief utama, identitas visual, Login dan Dashboard Pegawai pertama |
| 01 | Autentikasi dasar |
| 02 | Verifikasi, aktivasi, dan keamanan akun |
| 03 | Pegawai: katalog dan pengajuan tiga langkah |
| 04 | Pegawai: status pengajuan, barang saya, dan pengembalian |
| 05 | PJ: dashboard, tugas, persiapan, dan serah-terima |
| 06 | PJ: pemeriksaan pengembalian dan kondisi bermasalah |
| 07 | Inventarisasi, barang ruangan, DBR, dan review |
| 08 | Koordinator: dashboard dan pusat persetujuan |
| 09 | Master aset dan penetapan pemegang jangka panjang |
| 10 | Penerimaan, distribusi, mutasi, dan ASP/PSP |
| 11 | Laporan masalah, perawatan, dan penghapusan |
| 12 | Laporan periodik dan SPIP BMN |
| 13 | Surat, arsip, template, dan label QR |
| 14 | Administrasi, mandat, impor, dan audit |
| 15 | Pusat notifikasi, bantuan, dan keadaan umum |
| 16 | Pemeriksaan konsistensi dan adaptasi mobile |
| 17 | Prompt revisi terarah bila hasil mulai melenceng |

## Data demo bersama

| Entitas | Contoh fiktif |
|---|---|
| Pegawai | Nabila Putri; nabila@example.test; NIP tersamarkan |
| PJ | Dimas Santoso; dimas@example.test |
| Koordinator | Maya Prameswari; maya@example.test |
| Unit | Balai Jambi; Seksi Wilayah I Medan; Seksi Wilayah II Palembang |
| Pinjaman contoh | PIN-DEMO-0267; Proyektor dan GPS; 16–18 September 2026 |
| Aset pinjaman | AST-DEMO-001 Proyektor Epson EB-FH52; AST-DEMO-002 GPS Garmin Montana |
| Aset penugasan | AST-DEMO-003 Laptop HP Omnibook; pemegang Nabila Putri |
| Aset dirawat | AST-DEMO-004 Scanner Epson DS-570W II |
| Dokumen | Nomor mengandung DEMO, preview CONTOH, tanda tangan/cap kosong |

Status berbeda pada variasi layar adalah tahapan/alternatif cerita yang sama, bukan transaksi baru. Angka dashboard tambahan boleh dibuat sebagai contoh dengan konsistensi antarhalaman; jangan mengambil jumlah aset/pegawai asli dari inventaris untuk mockup.

## Prompt 00 — Brief utama

```text
Rancang UI/UX high-fidelity website responsif bernama SIMON — Sistem Informasi Barang Milik Negara, untuk pengelolaan BMN Balai Penegakan Hukum Lingkungan Hidup Jambi. Seluruh teks antarmuka wajib berbahasa Indonesia yang jelas, ramah, profesional, dan konsisten.

KONTEKS PRODUK
Pengguna bekerja di Balai Jambi, Seksi Wilayah I Medan, dan Seksi Wilayah II Palembang. Prioritas produk adalah kemudahan mencari barang, meminjam, memeriksa kondisi, mengelola penanggung jawab, dan menyelesaikan administrasi. Dashboard harus mengutamakan pekerjaan yang membutuhkan tindakan. Gunakan identitas visual yang terasa resmi, hangat, tenang, dan rapi.

Desain akan diimplementasikan dengan Laravel, React, TypeScript, Inertia.js, MySQL, dan Tailwind CSS. Animasi menggunakan GSAP dan Lottie. Susun komponen yang reusable dan realistis untuk stack tersebut. Fokus output tahap ini adalah desain dan spesifikasi interaksi; istilah implementasi backend tidak perlu muncul pada layar pengguna.

TIGA ROLE
1. Pegawai: melihat katalog yang diizinkan, mengajukan pinjaman, melihat status, menerima barang, meminta perpanjangan, mengembalikan, melaporkan masalah, dan membuka dokumen miliknya.
2. Penanggung Jawab Ruangan: melihat barang pada ruangan penugasannya, memeriksa kondisi, menyiapkan barang yang sudah disetujui, melaksanakan serah-terima, memeriksa pengembalian, dan melakukan inventarisasi.
3. Koordinator: menyetujui pengajuan sesuai kewenangan, mengelola aset, penetapan pemegang, distribusi, inventarisasi, penghapusan, laporan, dokumen, dan administrasi.

Buat navigasi sesuai role. Role berasal dari akun yang telah mendapat penugasan. Registrasi tidak memiliki pilihan role tinggi atau pengalih role bebas. Subkoordinator/Kepala TU direpresentasikan sebagai jabatan atau mandat pada tahapan review/pengesahan.

ATURAN ALUR
Peminjaman sementara: Pegawai mengajukan → Koordinator menyetujui → PJ memeriksa dan menyiapkan → formulir serta bukti penandatanganan dilengkapi → serah-terima → aktif → pemeriksaan pengembalian → penutupan Koordinator → selesai.
Penetapan laptop/kendaraan dinas jangka panjang adalah jenis transaksi tersendiri dengan pemegang dan BAST; jangan tampilkan sebagai pinjaman harian yang selalu terlambat.
Pengembalian dapat dilakukan per item. Kondisi baik tidak otomatis berarti tersedia: tampilkan status pemakaian/jadwal terpisah. Barang rusak atau jadwal bertabrakan memiliki penjelasan dan langkah tindak lanjut.
Pengajuan sendiri tidak boleh disetujui sendiri. Penghapusan aset membutuhkan kelengkapan pengesahan dan SK, dengan riwayat tetap terlihat.
Status surat dan status transaksi dipisahkan. Persetujuan aplikasi tidak otomatis menampilkan surat sebagai sudah ditandatangani.

SISTEM VISUAL WAJIB
- Background utama #FFFFFF pada halaman, sidebar, header, formulir, card, tabel, dan dialog.
- #015850 untuk tombol utama dengan teks putih, navigasi aktif, tautan teks, dan fokus yang jelas.
- #F77A04 sebagai aksen identitas dan perhatian; bila menjadi latar tombol gunakan teks #1E1935.
- #0A7DEF untuk ikon informasi dan elemen grafik. Hindari kombinasi teks kecil biru ini di atas putih atau teks putih kecil di atas biru ini.
- #1E1935 untuk judul, teks utama, label, angka, dan isi tabel.
- #739ABB untuk dekorasi dan ilustrasi pendukung, bukan teks kecil atau satu-satunya batas kontrol di atas putih.
- Tints transparan dari palet diperbolehkan untuk badge/border lembut; jangan menjadikan seluruh background abu-abu, hijau, atau gelap.
- Tanpa gradient, glow, glassmorphism, atau dark mode. Informasi status selalu menggunakan teks dan ikon, bukan warna saja.
- Tipografi Inter atau sans-serif serupa: teks utama 16 px, tabel/label 14–16 px, judul halaman 28–32 px, angka mudah dibandingkan.
- Grid 8 px; radius konsisten sekitar 12 px; border halus; shadow ringan; ikon garis satu keluarga.
- Desktop acuan 1440 px, sidebar sekitar 240 px, header 72 px, jarak konten 24–32 px. Mobile acuan 390 px dengan margin 16 px.
- Pada mobile, navigasi ringkas dan daftar berubah menjadi card yang informatif. Toolbar tidak boleh menutupi judul, kontrol, atau keyboard.
- Gunakan satu aksi utama yang jelas per bagian. Detail tambahan ditempatkan pada tab atau bagian yang dapat dibuka.

NOTIFIKASI DAN KONFIRMASI
Semua notifikasi hasil transaksi, error transaksi, dan konfirmasi penting harus berupa dialog putih di TENGAH layar dengan overlay lembut. Tidak memakai alert/confirm/prompt browser, toast pojok, atau notifikasi melayang di atas.
Dialog berisi ikon, judul jelas, penjelasan ringkas, objek terkait, dan tombol tindakan spesifik. Contoh: “Pengajuan berhasil dikirim” dengan tombol “Lihat Pengajuan”; “Batalkan pengajuan ini?” dengan tombol “Kembali” dan “Batalkan Pengajuan”.
Dialog harus tetap di tengah pada mobile, memiliki tinggi terbatas dan isi yang dapat digulir, serta tidak menutup otomatis sebelum terbaca.
Hanya satu dialog aktif. Keyboard/fokus terjaga dan kembali ke pemicu saat ditutup. Validasi kolom tampil tepat di bawah kolom. Notifikasi pekerjaan dari orang lain masuk ke pusat notifikasi tanpa menginterupsi pengguna; detail dibuka di tengah ketika dipilih.

AUTENTIKASI WAJIB
Sediakan login, register, verifikasi email, menunggu aktivasi akun, lupa password, reset password, token kedaluwarsa, dan autentikasi dua faktor.
Setiap kolom password punya ikon mata untuk tampil/sembunyi: login, register, konfirmasi password, ganti password, password baru dan konfirmasi saat reset.
Halaman lupa password meminta email; ikon mata muncul pada tahap reset ketika pengguna memasukkan password baru.
Gunakan label nyata, helper text, Caps Lock hint, dukungan paste/password manager, dan error yang tidak membocorkan keberadaan akun.
Sesudah register, pengguna menjadi Pegawai menunggu verifikasi/aktivasi. Gunakan pesan pemulihan netral: “Jika email terdaftar, petunjuk pemulihan akan dikirim.”

KENYAMANAN DAN AKSESIBILITAS
Setiap transaksi menampilkan Status saat ini, Diproses oleh, Langkah berikutnya, dan Batas waktu bila berlaku.
Identitas pegawai diisi otomatis. Pengajuan memakai tiga langkah: Pilih barang dan tanggal → Keperluan/pengambilan → Periksa dan ajukan.
Tombol sentuh minimal 44×44 px, fokus keyboard terlihat, tombol ikon punya nama aksesibel, input tetap berlabel, dan status tidak mengandalkan warna.
Sediakan loading skeleton, keadaan kosong dengan satu tindakan, pencarian tanpa hasil, validasi, gagal menyimpan, upload gagal, akses ditolak, dan konflik perubahan. Pertahankan isian ketika terjadi error.
GSAP untuk transisi opacity/transform sekitar 150–200 ms. Lottie secukupnya pada empty/success state, dengan alternatif statis dan reduced motion. Animasi tidak menunda aksi.

DATA CONTOH
Gunakan data fiktif: Nabila Putri sebagai Pegawai, Dimas Santoso sebagai PJ Ruangan, Maya Prameswari sebagai Koordinator; email memakai example.test, NIP ditulis tersamarkan.
Contoh aset: Proyektor Epson EB-FH52, GPS Garmin Montana, Laptop HP Omnibook, Scanner Epson DS-570W II. ID seperti AST-DEMO-001 dan surat seperti PIN-DEMO-0267. Pakai “Seksi Wilayah II Palembang” secara konsisten.
Gunakan foto ilustratif barang. Dokumen preview bertanda “CONTOH”. Gunakan wordmark SIMON dengan ikon aset sederhana; logo resmi hanya placeholder bila belum diberikan. Jangan membuat tanda tangan/cap resmi, angka inventaris nyata, atau tautan dokumen privat.

KELUARAN PERTAMA
Mulai dengan sistem visual/komponen inti, halaman Login, dan Dashboard Pegawai. Tampilkan Login dan Dashboard Pegawai pada desktop 1440 px dan mobile 390 px, ditambah contoh dialog tengah sukses dan konfirmasi.
Dashboard Pegawai menonjolkan pinjaman aktif, barang siap diambil, pengajuan perlu revisi, dan jatuh tempo, dengan tombol “Cari Barang”.
Menu Pegawai: Beranda, Katalog Barang, Pengajuan Saya, Barang Saya, Dokumen Saya, Bantuan; notifikasi dan profil di header.
Simpan konsistensi visual ini untuk layar berikutnya. Beri nama setiap layar berdasarkan role dan fungsinya. Sertakan catatan interaksi di luar mockup bila perilaku tidak dapat ditampilkan langsung.
```

## Prompt 01 — Autentikasi dasar

```text
Lanjutkan desain SIMON menggunakan seluruh aturan visual, bahasa Indonesia, tiga role, dialog tengah, dan data fiktif dari prompt utama. Gunakan komponen Login yang sudah dibuat sebagai acuan konsistensi.

Rancang alur autentikasi dasar yang saling terhubung:
1. Login: identitas SIMON, judul “Masuk ke SIMON”, email, password dengan tombol mata, petunjuk Caps Lock saat relevan, tombol “Masuk”, “Lupa password?”, dan “Daftar akun”. Form menjadi fokus utama; ilustrasi aset pendukung berukuran ringan.
2. Register: nama, email, NIP bila berlaku, pilihan unit, password, konfirmasi password, dan petunjuk minimum 15 karakter yang mendukung passphrase. Kedua kolom password memiliki tombol mata. Jelaskan singkat bahwa akun akan diverifikasi dan diaktifkan petugas. Tidak ada pilihan role Koordinator/PJ.
3. Lupa password: email, “Kirim Tautan Pemulihan”, dan “Kembali ke Login”. Setelah kirim, dialog tengah memakai pesan netral: “Jika email terdaftar, petunjuk pemulihan akan dikirim.” Sediakan keadaan sedang mengirim, kirim ulang dibatasi waktu, dan gagal kirim yang dapat dicoba lagi.
4. Reset password: password baru dan konfirmasi dengan tombol mata masing-masing, checklist syarat yang sederhana, dan “Simpan Password Baru”. Tampilkan variasi token kedaluwarsa dengan “Minta Tautan Baru”, serta dialog tengah sukses dengan “Kembali ke Login”.

Error berada di bawah kolom yang terkait dan tidak menghapus input. Password manager, autofill, dan paste tetap diperbolehkan. Jangan membocorkan akun terdaftar/tidak terdaftar melalui variasi pesan.

Buat form satu kolom di mobile. Area form tidak terhalang keyboard, tombol mata memiliki target sentuh 44 px dan nama aksesibel “Tampilkan password”/“Sembunyikan password”. Dialog tetap berada di tengah viewport.

Keluaran: empat halaman utama desktop dan adaptasi mobile untuk Register dan Reset, dengan variasi validasi, proses kirim, sukses, dan tautan kedaluwarsa. Hubungkan tombol ke langkah yang sesuai bila interaksi tersedia; jika tidak, tulis catatan transisi di luar mockup.
```

## Prompt 02 — Verifikasi, aktivasi, dan keamanan akun

```text
Lanjutkan SIMON dengan gaya dan aturan dari prompt utama. Desain bagian penyelesaian pendaftaran dan keamanan akun dalam bahasa Indonesia.

Buat tiga kelompok layar:
A. Verifikasi email dan status aktivasi. Tampilkan tahap “Daftar akun → Verifikasi email → Pemeriksaan petugas → Akun aktif”. Gunakan email demo yang tersamarkan, tombol kirim ulang dengan waktu tunggu, pilihan memperbaiki email melalui proses yang tepat, bantuan, dan keluar. Akun menunggu aktivasi belum menampilkan katalog/aset atau sidebar operasional penuh. Sertakan variasi “Perlu perbaikan data” dengan alasan dan tindakan, serta akun ditangguhkan dengan akses bantuan.
B. Tantangan autentikasi dua faktor. OTP enam digit dalam kontrol yang bisa dipaste/autofill dan berlabel jelas, “Verifikasi”, “Gunakan Kode Pemulihan”, “Kembali”, serta error kode salah/kedaluwarsa dan percobaan dibatasi. Recovery code boleh masuk lewat form terpisah. Hindari meminta password atau kode berulang tanpa penjelasan.
C. Profil dan Keamanan Akun. Tampilkan data dasar, unit, jabatan, perubahan NIP/unit melalui permintaan verifikasi, ganti password, status 2FA, setup dengan placeholder QR nonaktif, recovery code tersamarkan, dan sesi perangkat. PJ/Koordinator diberi penjelasan 2FA wajib; Pegawai dapat mengaktifkannya. Semua kolom password memiliki ikon mata. Tombol “Keluar dari Perangkat Lain” memakai konfirmasi tengah.

Gunakan dialog tengah untuk konfirmasi, keberhasilan, dan error tindakan penting. Perubahan password tidak mengaktifkan akun yang masih ditangguhkan. Jangan tampilkan tautan unduh recovery code kepada pengelola akun lain. Gunakan contoh perangkat generik dan lokasi tanpa mengklaim data pelacakan nyata.

Keluaran: halaman Status Pendaftaran, Verifikasi 2FA, dan Keamanan Akun; variasi recovery, email belum diverifikasi, sesi berakhir, dan aktivasi perlu perbaikan. Sertakan adaptasi mobile untuk OTP dan daftar sesi serta catatan perpindahan fokus setelah error.
```

## Prompt 03 — Pegawai: katalog dan pengajuan tiga langkah

```text
Lanjutkan SIMON untuk role Pegawai. Gunakan menu Beranda, Katalog Barang, Pengajuan Saya, Barang Saya, Dokumen Saya, Bantuan. Seluruh aturan prompt utama tetap berlaku.

Desain alur mencari dan mengajukan barang:
1. Katalog Barang: pencarian nama/kode, kategori, unit/ruangan, tanggal pinjam/kembali, dan “Hanya yang tersedia”. Kartu barang menampilkan foto proporsional, nama, lokasi, kondisi, dan ketersediaan pada tanggal yang dipilih. Pisahkan label “Baik” dari “Tersedia/Dipinjam/Perawatan”. Jangan tampilkan nama/NIP pemegang lain atau nilai finansial di katalog umum.
2. Detail barang: foto, spesifikasi ringkas, kelengkapan, lokasi pengambilan, aturan peminjaman, ketersediaan tanggal, tombol “Ajukan Peminjaman”, dan informasi yang diizinkan. Barang tidak tersedia diberi penjelasan serta “Ubah Tanggal” atau “Cari Barang Lain”.
3. Pengajuan tiga langkah: “Pilih barang dan tanggal” → “Keperluan dan pengambilan” → “Periksa dan ajukan”. Identitas Nabila Putri dan unit terisi otomatis. Tampilkan ringkasan barang/tanggal yang selalu mudah ditemukan, keperluan, lokasi/petugas pengambilan, lampiran hanya bila diperlukan, “Simpan Draf”, “Kembali”, “Lanjutkan”, dan “Ajukan Peminjaman”.

Gunakan contoh PIN-DEMO-0267 untuk proyektor AST-DEMO-001 dan GPS AST-DEMO-002 tanggal 16–18 September 2026. Kedua barang yang dipilih harus dapat ditelusuri ke ringkasan; total item konsisten. Sesudah terkirim tampilkan dialog tengah dan tombol menuju detail status. Jangan tampilkan keberhasilan sebelum proses selesai.

Sediakan kondisi: pencarian kosong, filter tanpa hasil dengan “Reset Filter”, tanggal kembali tidak valid, jadwal bentrok, barang berubah ketersediaan sebelum submit, lampiran gagal, dan draf tersimpan. Input tetap ada saat pengguna memperbaiki error.

Keluaran: Katalog, Detail Barang, dan tiga langkah pengajuan. Adaptasi mobile untuk katalog serta form langkah terakhir; pada mobile, ringkasan bisa dibuka tanpa menutupi tombol atau keyboard. Beri tujuan navigasi yang jelas untuk setiap aksi utama.
```

## Prompt 04 — Pegawai: status pengajuan, barang saya, dan pengembalian

```text
Lanjutkan SIMON untuk Pegawai dengan komponen yang sama. Desain aktivitas setelah mengajukan barang.

A. Pengajuan Saya: daftar dengan tab Semua, Menunggu, Perlu Revisi, Aktif, dan Selesai; pencarian nomor, filter periode, status tekstual, jumlah item, tanggal, dan tindakan relevan.
B. Detail pengajuan: nomor, barang, tanggal, keperluan, status, diproses oleh, langkah berikutnya, tenggat, timeline, catatan keputusan, kelengkapan dokumen, serta tempat pengambilan. Buat variasi Menunggu Persetujuan, Perlu Revisi dengan alasan spesifik, Ditolak, Siap Diambil, Aktif, dan Selesai. Aksi mengikuti status; dokumen belum bertanda tangan diberi checklist kebutuhan.
C. Barang Saya: dua tab yang jelas, “Pinjaman Sementara” dan “Aset Penugasan”. Tampilkan HP Omnibook sebagai aset penugasan dengan BAST dan tanggal review, tanpa badge terlambat harian. Barang pinjaman menunjukkan jatuh tempo dan aksi kembali/perpanjangan.
D. Perpanjangan: tanggal baru dan alasan, ringkasan dampak, informasi masih perlu persetujuan, serta kasus jadwal berbenturan.
E. Pengembalian: pilih item yang diserahkan, waktu, kondisi menurut pegawai, kelengkapan, dan foto bila diperlukan. Tampilkan “Pengembalian diajukan — menunggu pemeriksaan PJ”, bukan “Selesai” sebelum pemeriksaan/penutupan. Pada pengembalian sebagian, item yang belum kembali tetap aktif.
F. Laporan Masalah: barang terkait, jenis kerusakan/kehilangan, kronologi, foto, dan status tindak lanjut. Jangan otomatis memutuskan ganti rugi.

Dialog tengah wajib untuk batalkan pengajuan, kirim revisi, permintaan perpanjangan, dan hasil pengembalian. Isi form tetap tersedia saat error.

Gunakan PIN-DEMO-0267 sebagai satu cerita yang berubah tahap pada varian layar; beri label varian di luar mockup agar status berbeda tidak dianggap transaksi baru. Keluaran utama: daftar/detail pengajuan, Barang Saya, form pengembalian; sertakan variasi perpanjangan dan laporan masalah serta mobile untuk timeline dan pengembalian.
```

## Prompt 05 — PJ: dashboard, tugas, persiapan, dan serah-terima

```text
Lanjutkan SIMON untuk role Penanggung Jawab Ruangan, contoh pengguna Dimas Santoso. Pertahankan sistem visual dan dialog tengah.

Menu: Beranda, Tugas Saya, Barang Ruangan, Serah-Terima, Pengembalian, Inventarisasi, Kerusakan & Perawatan, Laporan Ruangan, Dokumen & Panduan. Tampilkan cakupan ruangan yang ditugaskan, bukan pemilih seluruh ruangan tanpa batas.

Buat:
1. Dashboard operasional: antrean “Siapkan Barang”, “Serah-Terima Hari Ini”, “Pengembalian Menunggu Cek”, dan “Cek Fisik”. Daftar tugas mendesak berada sebelum grafik. Aksi cepat “Buka Tugas” dan “Scan QR”.
2. Tugas Saya: filter jenis, ruangan, tenggat; tiap item memiliki nomor transaksi, barang, pemohon yang relevan, status, langkah berikutnya, dan tombol jelas.
3. Persiapan Barang: informasi persetujuan Koordinator, rincian item, kondisi sebelum, checklist kelengkapan, foto, lokasi, dan catatan. Tombol “Simpan Pemeriksaan” dan “Tandai Siap Diambil”. Bila barang rusak/tidak lengkap, tampilkan jalur “Laporkan Kendala”; penyerahan tidak dapat diselesaikan.
4. Serah-Terima: checklist identitas penerima, barang/NUP, kelengkapan, dokumen yang wajib ditandatangani, waktu penyerahan, dan konfirmasi kedua pihak. Bedakan tindakan PJ dengan konfirmasi yang harus dilakukan Pegawai melalui akunnya. Jangan membuat satu tombol yang berpura-pura menandatangani sebagai kedua pihak.
5. Scan QR: area kamera dengan instruksi ringkas, izin belum diberikan, QR tidak dikenali, dan alternatif “Cari Kode/NUP”. Hasil membuka hanya detail yang diizinkan.

PJ menyiapkan barang setelah persetujuan Koordinator; tidak ada tombol PJ untuk menyetujui permohonan yang belum disetujui. Dokumen yang kurang diberi alasan dan jalan melengkapi, bukan tombol final yang tampak aktif.

Keluaran: Dashboard PJ, Antrean Tugas, Detail Persiapan, dan Serah-Terima. Tampilkan versi mobile untuk checklist/foto dan QR; kontrol utama nyaman digunakan saat petugas memegang ponsel. Semua konfirmasi tetap berupa modal tengah.
```

## Prompt 06 — PJ: pemeriksaan pengembalian dan kondisi bermasalah

```text
Lanjutkan SIMON untuk PJ Ruangan. Buat alur pemeriksaan pengembalian yang bisa dilakukan per item dengan aturan visual yang sama.

Layar Antrean Pengembalian menampilkan jadwal, peminjam, nomor, ruangan, jumlah barang, dan status. Detail pemeriksaan memperlihatkan kondisi sebelum dan sesudah secara berdampingan pada desktop, bertumpuk pada mobile. Tampilkan foto yang bisa diperbesar, checklist aksesori, catatan peminjam, observasi PJ, waktu penerimaan fisik, dan status dokumen.

Gunakan PIN-DEMO-0267 dengan dua item:
- Proyektor dikembalikan dalam kondisi sesuai dan lengkap.
- GPS belum dikembalikan pada varian Pengembalian Sebagian.
Buat varian masalah lain ketika proyektor kembali dengan kabel tidak lengkap. Ini merupakan alternatif skenario, bukan mengubah data di layar yang sama tanpa penjelasan.

Aksi per item: “Kondisi Sesuai”, “Catat Ketidaksesuaian”, “Simpan Pemeriksaan”. Saat ada masalah, form meminta jenis temuan, bukti, catatan, pihak penindak lanjut, dan rencana pemeriksaan ulang. Jangan mengubah kondisi menjadi Baik atau menetapkan biaya ganti rugi otomatis.

Sesudah pemeriksaan lengkap, tampilkan “Menunggu Penutupan Koordinator”. Barang belum menjadi Tersedia hanya karena form pengembalian dikirim atau barang diterima. Riwayat memperlihatkan penerimaan fisik dan penutupan administratif sebagai dua kejadian berbeda.

Modal tengah merangkum item yang diproses sebelum kirim, serta hasil “Pemeriksaan berhasil disimpan”. Item tersisa tetap aktif dan jumlahnya konsisten. Sertakan keadaan foto gagal upload, data sudah diperbarui petugas lain, pemeriksaan belum lengkap, dan perbaikan selesai menunggu pemeriksaan ulang.

Keluaran: Antrean Pengembalian, Pemeriksaan Per Item, Detail Ketidaksesuaian, dan Ringkasan Pengembalian Sebagian. Buat adaptasi mobile pemeriksaan dengan kolom yang tidak padat dan ringkasan item yang mudah dilihat.
```

## Prompt 07 — Inventarisasi, barang ruangan, DBR, dan review

```text
Lanjutkan SIMON untuk alur inventarisasi. Gunakan role/cakupan sesuai layar, dengan gaya, komponen, dan data fiktif yang sama.

Desain dua konteks:
PJ Ruangan:
- Barang Ruangan: nama ruangan, PJ aktif, pencarian, filter kondisi dan status, daftar barang, tanggal pemeriksaan terakhir, dan tombol “Lihat DBR”.
- Sesi Cek Fisik: nama sesi/periode, ruangan, jumlah diperiksa/belum diperiksa, progress berdasarkan jumlah item, pencarian/QR, dan hasil per barang.
- Form hasil: Ditemukan Sesuai, Berbeda Lokasi, Identitas Berbeda, Belum Ditemukan, atau Barang Belum Terdaftar. Kondisi fisik tetap Baik/Rusak Ringan/Rusak Berat. Sertakan foto, kelengkapan, tanggal, catatan, dan koordinat bila dibutuhkan. Izin lokasi ditolak tetap memiliki input manual yang ditandai.
- Ringkasan Temuan: apa yang perlu diperbaiki, bukti, dan “Kirim untuk Review”. Temuan draf belum otomatis mengubah semua master aset.

Koordinator:
- Buat sesi inventarisasi: periode, cakupan ruangan, PJ, target waktu, dan instruksi.
- Review hasil: progress per ruangan, temuan belum selesai, catatan pemeriksa, “Minta Perbaikan”, dan tahap review/pengesahan.
- Tahapan bertingkat menggunakan label jabatan/mandat Subkoordinator → Koordinator → Kepala TU bila dipersyaratkan, tetap dalam tiga role aplikasi. Bukti pengesahan luar aplikasi dapat diunggah.

Desain preview DBR/kertas kerja sebagai dokumen terpisah dengan judul, ruangan, tanggal, tabel barang, kondisi, keterangan, dan area tanda tangan kosong bertanda CONTOH. Hasil final menyimpan periode dan versi, sehingga berbeda jelas dari draf yang masih bisa diubah.

Keluaran: Barang Ruangan, Sesi Cek Fisik, Form Temuan, dan Review Koordinator, ditambah preview ringkas DBR. Sertakan keadaan kosong, scan tidak cocok, hasil dikembalikan untuk diperbaiki, final terkunci, dan versi mobile cek fisik.
```

## Prompt 08 — Koordinator: dashboard dan pusat persetujuan

```text
Lanjutkan SIMON untuk Koordinator, contoh pengguna Maya Prameswari. Pertahankan palet, tipografi, grid, dialog tengah, dan label role yang sama.

Menu: Beranda, Persetujuan, Aset, Transaksi, Inventarisasi, Laporan & SPIP, Dokumen, Administrasi. Header menampilkan cakupan unit yang memang berwenang. Nama jabatan dan role tidak menjadi tombol pengalih role bebas.

Buat:
A. Dashboard: pekerjaan menunggu keputusan, pengembalian menunggu penutupan, inventaris belum selesai, dan temuan yang perlu tindak lanjut. Maksimal empat kartu ringkasan, lalu daftar tugas. Grafik ringkas hanya jika membantu membandingkan unit/kondisi. Setiap angka mengarah ke daftar tersaring.
B. Pusat Persetujuan: filter jenis Peminjaman, Perpanjangan, Penetapan, Inventarisasi, dan Penutupan; tampilkan jumlah hanya berdasarkan data demo yang konsisten. Daftar memiliki nomor, pemohon, unit, objek, usia tugas, status, dan langkah berikutnya.
C. Detail Persetujuan Pinjaman: pemohon, tujuan, daftar barang, tanggal, hasil pemeriksaan ketersediaan, dokumen, catatan, dan riwayat. Panel keputusan berada dekat informasi pendukung. Aksi “Setujui Pengajuan”, “Minta Revisi”, dan “Tolak” memiliki konfirmasi tengah. Revisi/penolakan meminta alasan jelas.
D. Detail Penutupan Pengembalian: kondisi hasil PJ, item sudah/belum kembali, masalah yang masih terbuka, kelengkapan bukti, dan tahap diketahui/pengesahan. Jangan menutup seluruh transaksi bila ada item belum selesai.

Kasus wajib: barang/jadwal berbenturan ketika persetujuan hendak dikirim; transaksi sudah diputuskan petugas lain; permohonan milik Koordinator sendiri; data di luar kewenangan; dokumen wajib belum lengkap. Tampilkan alasan dan tindakan untuk membuka data terbaru, menyerahkan ke petugas berwenang, atau melengkapi bukti. Jangan menyediakan tombol bypass persetujuan sendiri.

Keluaran: Dashboard Koordinator, Antrean Persetujuan, Detail Keputusan, dan Penutupan Pengembalian. Sertakan mobile untuk review satu permohonan dan contoh dialog setuju/revisi/tolak.
```

## Prompt 09 — Master aset dan penetapan pemegang jangka panjang

```text
Lanjutkan SIMON untuk Koordinator pada menu Aset dan Transaksi. Gunakan seluruh aturan visual dan role dari prompt utama.

Buat register aset dengan pencarian, filter unit/ruangan/kategori/kondisi/pemegang, pilihan kolom, pagination, serta “Tambah Aset”, “Impor Data”, dan “Cetak Label” sesuai izin. Tampilkan kode dan NUP sebagai teks, nilai dalam format rupiah, serta kondisi dan status penggunaan pada kolom terpisah.

Detail aset memakai tab Ringkasan, Kondisi & Foto, Pemegang/Lokasi, Riwayat, Dokumen. Data meliputi identitas satker/kode/NUP, merek/tipe, sumber/tanggal/nilai perolehan, pemegang aktif, kelengkapan paket, dan referensi dokumen. Nilai yang belum tersedia ditulis “Belum diisi”, bukan nol. Foto dan riwayat memiliki waktu/pelaksana. Identitas lama setelah transfer terlihat sebagai riwayat.

Form master dikelompokkan ke Identitas, Perolehan, Lokasi & Penanggung Jawab, Kondisi & Kelengkapan, dan Dokumen. Gunakan bantuan singkat dan progressive disclosure. Perubahan kode/NUP/nilai meminta alasan; PJ hanya dapat mengusulkan koreksi, bukan melihat tombol ubah master yang sama.

Penetapan pemegang jangka panjang:
- pilih aset dan pegawai;
- tanggal mulai dan tanggal review/akhir bila relevan;
- surat penunjukan/draf BAST;
- kelengkapan pengesahan;
- serah-terima dan pemegang aktif.
Tampilkan HP Omnibook milik penugasan Nabila Putri sebagai contoh. Ada “Ganti Pemegang” dan “Serah Balik” melalui alur tercatat. Pemegang lama tetap di riwayat. Jangan menampilkan keterlambatan harian untuk penugasan tanpa tanggal akhir.

Sertakan keadaan NUP/identitas diduga duplikat, aset sedang dipinjam/dirawat sehingga belum dapat dialihkan, dokumen belum ditandatangani, dan perubahan data belum disimpan. Konfirmasi penting berada di tengah.

Keluaran: Register Aset, Detail Aset, Form Aset, dan Penetapan Pemegang dengan variasi pergantian pemegang. Mobile detail memakai tab yang terbaca dan informasi penting lebih dahulu.
```

## Prompt 10 — Penerimaan, distribusi, mutasi, dan ASP/PSP

```text
Lanjutkan SIMON untuk Koordinator dan PJ penerima dengan gaya konsisten. Fokus pada perjalanan barang antarunit dan arsip peralihan.

Desain:
1. Daftar Penerimaan/Distribusi: referensi BAST, satker asal, unit tujuan, jumlah item, kondisi proses, penanggung jawab, dan tanggal.
2. Form Penerimaan: data BAST sumber, daftar aset, kode/NUP, kondisi, pengadaan, tujuan ruangan, serta dokumen. Tampilkan hasil review Koordinator dan tugas penyerahan/pelabelan.
3. Detail Mutasi: asal dan tujuan, alasan, daftar barang, tanggal rencana, tanggal pengiriman fisik, penerima, bukti, serta timeline. Status “Dalam Transfer” berlangsung sampai PJ penerima memeriksa dan mengonfirmasi. Aset yang masih dipinjam atau berkonflik diberi alasan belum dapat dipindah.
4. Konfirmasi penerimaan PJ: cocokkan item, kondisi, kelengkapan, dan foto; tampilkan perbedaan sebagai temuan, bukan penerimaan penuh otomatis.
5. Register ASP/PSP: nama proses, satker asal/tujuan, identitas lama/baru, status sementara, referensi tiket, dokumen penggunaan sementara/BAST, catatan dan tindak lanjut. Tampilkan waktu pembaruan dan asal data.

Gunakan unit Balai Jambi, SW I Medan, dan SW II Palembang. NUP sama pada konteks berbeda tidak ditandai otomatis sebagai barang yang pasti sama; tampilkan kebutuhan pencocokan dan bukti.

Gunakan label “Referensi pencatatan” atau “Menunggu konfirmasi pencatatan” untuk status aplikasi pemerintah. Jangan menggambar tombol sinkronisasi langsung, koneksi aktif, atau keberhasilan transfer pada sistem pemerintah yang belum terintegrasi.

Tampilkan status barang dan status surat sebagai dua informasi berbeda. Draf BAST dan arsip bertanda tangan dapat dibuka sesuai izin. Dialog tengah untuk kirim barang, terima barang, dan hasil tindakan.

Keluaran: daftar/detail distribusi atau mutasi, form penerimaan, serta daftar/detail ASP/PSP. Sertakan mobile konfirmasi penerimaan dan keadaan dokumen kurang, jumlah item berbeda, serta transfer belum diterima.
```

## Prompt 11 — Laporan masalah, perawatan, dan penghapusan

```text
Lanjutkan SIMON dengan aturan visual dan kewenangan yang sama. Desain alur dari laporan kondisi sampai penyelesaian atau usulan penghapusan.

A. Kerusakan & Perawatan: daftar insiden, aset, lokasi, pelapor, jenis masalah, prioritas, penanggung jawab, jadwal, dan status. Detail berisi kronologi, foto, kondisi sebelum/sesudah, komentar tindak lanjut, biaya bila sudah dicatat, lampiran pekerjaan, serta pemeriksaan ulang. Gunakan Scanner Epson DS-570W II sebagai contoh sedang dirawat.
B. Pegawai melihat laporan yang terkait dirinya; PJ melihat ruangan penugasan dan mengusulkan tindak lanjut; Koordinator mereview penanganan. Pisahkan observasi kondisi fisik B/RR/RB dari kejadian kehilangan. Jangan menghitung kewajiban ganti rugi otomatis.
C. Penghapusan: daftar usulan dan detail tahap “Draf → Opname → Penelitian → Usulan → Menunggu Pengesahan/SK → Selesai”. Form berisi aset, alasan, hasil cek, foto, hasil penilaian/harga limit dari petugas, nomor/tanggal SK, dan bukti pengesahan.
D. Pratinjau keputusan: checklist dokumen wajib, pejabat pengesah, item terdampak, dan ringkasan hasil yang akan dicatat. Aksi “Finalisasi Penghapusan” hanya tersedia setelah syarat terpenuhi, dengan konfirmasi tengah yang tegas. Bila belum lengkap, tampilkan alasan serta tombol melengkapi, bukan jalur bypass.

Aset yang sudah dihapus tampil dalam arsip dengan riwayat serta dokumen; pengguna tidak melihatnya sebagai barang tersedia. Jangan membuat finalisasi penghapusan menjadi tombol hapus permanen seluruh riwayat.

Keluaran: daftar/detail Perawatan, form/ringkasan Usulan Penghapusan, dan keadaan Arsip Aset. Sertakan variasi bukti belum lengkap, kasus dikembalikan untuk revisi, perbaikan menunggu pemeriksaan, dan finalisasi berhasil. Tombol penolakan/pembatalan meminta alasan; semua konfirmasi berada di tengah.
```

## Prompt 12 — Laporan periodik dan SPIP BMN

```text
Lanjutkan SIMON pada Laporan & SPIP, dengan akses Koordinator sesuai unit dan tampilan ringkas laporan ruangan untuk PJ.

Desain:
1. Pusat Laporan: pilihan periode, unit, jenis laporan, kondisi, dan status. Jenis laporan mencakup aset/DBR, pemegang, peminjaman, pengembalian, perawatan, mutasi, inventarisasi, penghapusan, dan ASP/PSP. Tampilkan preview ringkasan sebelum ekspor.
2. Detail Laporan Periode: judul, periode, unit, waktu data diambil, pembuat/pemeriksa, versi, angka/tabel ringkasan, bukti, status, serta timeline. Alur draf unit → review/koreksi → gabungan → pengesahan → pengiriman → arsip.
3. Permintaan ekspor PDF/Excel: pengguna mendapat status “Sedang disiapkan”, dapat melanjutkan pekerjaan, lalu membuka hasil dari pusat notifikasi. Tampilkan gagal proses dan “Coba Lagi”; jangan menampilkan file selesai jika masih dibuat.
4. Matriks SPIP BMN: risiko, pengendalian, penanggung jawab, hasil pemantauan, efektivitas yang dinilai petugas, tindak lanjut, tenggat, dan bukti. Contoh risiko demo kerusakan/hilang; pengendalian cek fisik dan perawatan. Gunakan detail baris yang dapat dibuka supaya tabel tidak penuh teks panjang.
5. Review SPIP: temuan belum ada bukti, tindak lanjut terlambat, dan daftar tugas untuk melengkapi. Penilaian efektivitas tidak otomatis diputuskan oleh grafik.

Laporan final memiliki periode/versi yang jelas dan tampilan terkunci dengan opsi membuat revisi beralasan. Total data harus konsisten antarfilter dan grafik. Jangan menghitung aset ganda dari berbagai sheet atau mengklaim angka demo sebagai saldo resmi.

Gunakan tabel dan maksimal grafik yang relevan; sediakan angka/label sehingga pengguna tidak bergantung pada warna. Dokumen resmi preview bertanda CONTOH. SPIP aktif berfokus pada BMN; dokumen bidang lain dapat diarsipkan tanpa menambahkan modul bisnis keuangan/kepegawaian baru.

Keluaran: Pusat Laporan, Detail Laporan dan Review, Matriks SPIP serta detail tindak lanjut. Sertakan adaptasi mobile detail tindak lanjut dan state ekspor diproses/gagal/selesai.
```

## Prompt 13 — Surat, arsip, template, dan label QR

```text
Lanjutkan SIMON untuk dokumen dan arsip, memakai palet serta komponen yang sama. Gunakan seluruh data surat fiktif, placeholder logo, dan watermark CONTOH; area tanda tangan tetap kosong.

Buat:
A. Pusat Dokumen: filter jenis, unit, periode, status, nomor, dan transaksi. Jenis: formulir pinjam/kembali, BAST personal, BAST kolektif, surat penunjukan, kertas kerja, DBR, laporan, dan usulan penghapusan.
B. Detail dokumen: nomor, versi, transaksi/aset terkait, pembuat, pejabat pengesah, kelengkapan, timeline, status tanda tangan, dan daftar salinan. Status Draf, Direview, Siap Ditandatangani, Diunggah, Diverifikasi, Arsip Final, Dibatalkan/Diganti.
C. Preview surat: halaman A4 dengan kop, tanggal, pihak, narasi, tabel barang, lampiran beberapa halaman, nomor halaman, dan blok tanda tangan kosong. Area preview memiliki zoom, pilihan halaman, “Unduh Draf”, “Cetak”, serta akses unggah salinan. Controls aplikasi tidak masuk isi surat ketika dicetak.
D. Unggah/verifikasi salinan: pilih file, preview, siapa yang menandatangani, tanggal, kelengkapan, dan catatan pemeriksa. Tampilkan scan belum jelas, file gagal/ditolak, pengesahan belum lengkap, dan hasil verifikasi.
E. Template editor terkendali: jenis surat, versi, field variabel, format nomor, pejabat/masa jabatan, lampiran, dan preview. Perubahan template membuat versi baru; surat final lama tetap terlihat sesuai versi semula.
F. Cetak Label: pilih aset, informasi kode/NUP dan satker, ukuran/lembar, preview label berisi QR placeholder, serta cetak. Pindai membuka detail hanya setelah login dan izin; QR tidak menampilkan NIP atau tanda tangan.

Persetujuan internal di aplikasi tidak otomatis mengubah surat menjadi bertanda tangan. Jangan menyediakan pengambil tanda tangan/cap dari arsip untuk ditempel ke surat baru. Nomor surat contoh memakai penanda DEMO; nomor batal tetap terlihat di riwayat dan tidak direcycle melalui UI.

Pegawai hanya melihat dokumen miliknya, PJ dokumen tugas/ruangan, Koordinator sesuai mandat. Dokumen terlarang tidak ditampilkan sebagai preview terbuka.

Keluaran: Arsip Dokumen, Detail & Preview A4, Unggah Salinan, Editor Template, dan Preview Label. Sertakan mobile arsip/preview serta dialog tengah finalisasi, batal, dan revisi.
```

## Prompt 14 — Administrasi, mandat, impor, dan audit

```text
Lanjutkan SIMON untuk Koordinator yang diberi mandat administrasi. Gunakan desain yang sama, tampilkan cakupan kewenangan, dan tetap hanya tiga role aplikasi.

Buat halaman:
1. Pengguna dan aktivasi: tab pendaftar, aktif, perlu perbaikan, ditangguhkan; pencarian, unit, role, dan detail verifikasi. Tindakan “Aktifkan Akun”, “Minta Perbaikan”, atau “Tangguhkan” memakai alasan dan konfirmasi tengah. Registrasi menjadi Pegawai; peningkatan peran melalui mandat, bukan pilihan pemohon.
2. Unit, Ruangan, dan Penugasan: hierarki Jambi/Medan/Palembang, ruangan, PJ aktif, masa berlaku, pengganti, serta pekerjaan terbuka saat pergantian. Tampilkan “Alihkan Tugas” sebelum perubahan penugasan menimbulkan tugas tanpa petugas.
3. Role dan mandat: hanya Pegawai, Penanggung Jawab Ruangan, Koordinator. Jabatan Subkoordinator/Kepala TU dan cakupan/izin terperinci disimpan sebagai penugasan. Ada larangan perubahan hak sendiri atau persetujuan sendiri; tampilkan keterangan bila pengguna tidak punya mandat global.
4. Impor Data: langkah Unggah → Pemetaan Kolom → Preview & Validasi → Pencocokan → Ringkasan → Komit. Tampilkan jumlah valid/bermasalah/duplikat, sumber sheet, kode/NUP/NIP tetap teks, dan baris kosong yang tidak dihitung. Data master belum berubah saat preview.
5. Detail masalah impor: “Identitas perlu dicocokkan”, “Unit belum dikenali”, “Koordinat perlu diperiksa”, dan “Berkas pernah diimpor”. Gunakan contoh nama sumber SW 1 PALEMBANG yang perlu diverifikasi ke SW II Palembang. Jangan memperbaiki otomatis tanpa keputusan tercatat.
6. Audit dan pengaturan: log waktu, pelaku, objek, tindakan, alasan, ringkasan sebelum/sesudah, dan filter. Log tampil baca-saja. Pengaturan operasional mencakup aturan pinjam, kalender kerja, reminder, serta ringkasan backup/job/email terakhir bila mandat sesuai.

Tidak ada tombol menampilkan password pengguna, menonaktifkan audit, atau menghapus riwayat massal. Data sensitif disamarkan pada contoh desain. Konfirmasi komit impor menyebut jumlah dan perubahan secara konkret.

Keluaran: Pengguna, Penugasan, Wizard Impor dengan tabel koreksi, dan Audit. Tambahkan keadaan input salah, impor berjalan/gagal/selesai, akun tanpa mandat, serta adaptasi mobile review pendaftar.
```

## Prompt 15 — Pusat notifikasi, bantuan, dan keadaan umum

```text
Lanjutkan SIMON dengan komponen bersama yang konsisten untuk semua role. Desain perilaku notifikasi terpusat dan keadaan umum aplikasi.

Pusat Notifikasi:
- daftar dibaca/belum dibaca, jenis, waktu, ringkasan, transaksi terkait, dan langkah berikutnya;
- badge jumlah pada ikon header;
- filter yang sederhana dan “Tandai Sudah Dibaca”;
- memilih item membuka detail putih di tengah layar, dengan tombol menuju transaksi;
- notifikasi masuk dari pekerjaan orang lain tidak mengambil fokus atau mengganggu pengetikan.

Buat keluarga dialog tengah:
1. Sukses: “Pengajuan berhasil dikirim”, nomor PIN-DEMO-0267, penjelasan, “Lihat Pengajuan”.
2. Konfirmasi: “Batalkan pengajuan ini?”, ringkasan barang/tanggal, alasan bila diperlukan, “Kembali” dan “Batalkan Pengajuan”.
3. Gagal: “Pemeriksaan belum tersimpan”, sebab yang dapat dipahami, “Periksa Isian” atau “Coba Lagi”.
4. Konflik jadwal/data: jelaskan perubahan dan arahkan ke tanggal baru atau versi terbaru tanpa menghilangkan draf.
5. Unggahan gagal: nama file contoh, sebab, dan opsi ulang/ganti file.
6. Sesi berakhir: jelaskan perlunya login ulang dan status penyimpanan draf secara jujur; jangan menjanjikan draf tersimpan bila belum disimpan.
7. Aksi penghapusan administratif: objek dan konsekuensi jelas; tombol tegas; tetap membutuhkan dokumen/pengesahan.

Semua dialog berada di tengah, bukan toast atas/pojok atau bottom sheet, termasuk di mobile. Hanya satu dialog terbuka; isi dapat scroll; keyboard/fokus terjaga; tidak menutup otomatis. Error kolom tetap tampil di bawah kolom.

Bantuan dan SOP: pencarian panduan, kategori sesuai role, instruksi tugas singkat, versi SOP aktif, unduh jika diizinkan, dan kontak bantuan placeholder. Hindari kontak pribadi nyata.
Keadaan umum: loading skeleton, belum ada data dengan satu CTA, pencarian nihil, tidak berwenang, halaman tidak ditemukan, gangguan layanan, dan koneksi terputus dengan status isian yang jelas.

Keluaran: Pusat Notifikasi, detail notifikasi, pustaka dialog, Bantuan/SOP, serta state kosong/error. Buat contoh desktop dan mobile untuk modal panjang dengan fokus tombol yang benar. Jika prototype belum mendukung perilaku fokus, catat spesifikasinya di luar mockup.
```

## Prompt 16 — Pemeriksaan konsistensi dan adaptasi mobile

```text
Tinjau seluruh desain SIMON yang telah dibuat dalam proyek ini berdasarkan brief dan prompt sebelumnya. Pertahankan fitur yang sudah disetujui dan perbaiki konsistensi antarlayar.

Periksa:
- putih #FFFFFF tetap menjadi background utama; semua warna mengikuti #015850, #F77A04, #0A7DEF, #1E1935, #739ABB;
- tombol hijau memakai teks putih; tombol oranye memakai teks gelap; teks normal, ikon interaktif, dan fokus memiliki kontras terbaca;
- ikon mata tersedia pada semua kolom password login/register/ganti/reset, termasuk konfirmasi;
- semua konfirmasi dan notifikasi hasil tindakan berada di tengah; tidak ada toast atas/pojok atau alert native;
- hanya tiga role; tidak ada pengalih role publik, daftar sebagai Koordinator, atau tombol menyetujui pengajuan sendiri;
- urutan persetujuan Koordinator, pemeriksaan PJ, penandatanganan/formulir, dan serah-terima tidak tertukar;
- pengembalian sebagian menjaga item lain tetap aktif; barang belum tersedia sebelum pemeriksaan/penutupan;
- aset penugasan jangka panjang berbeda dari pinjaman sementara;
- surat final, draf, dan status tanda tangan berbeda jelas; tidak ada cap/tanda tangan resmi palsu;
- menu, ukuran kontrol, istilah, badge, tabel, dan status konsisten;
- data contoh fiktif serta relasi angka/status/dokumen konsisten;
- tindakan utama terlihat, error berada dekat input, fokus jelas, kontrol ikon bernama, dan target sentuh minimal 44×44 px;
- GSAP/Lottie memiliki versi reduced motion; animasi tidak menahan akses ke konten.

Adaptasi desktop 1440 px dan mobile 390 px, dengan pemeriksaan layout tablet 768 px. Utamakan jalur mobile: Login/Reset → Katalog → Ajukan → Detail Status → Scan/Cek Fisik PJ → Pemeriksaan Kembali → Review Koordinator.
Di mobile, gunakan card untuk daftar operasional, tabel panjang scroll pada area tabel, navigasi ringkas, safe area, dan modal tengah yang muat viewport. Kontrol tidak bertabrakan dengan keyboard atau bagian bawah layar.

Tampilkan daftar temuan dan layar yang diperbaiki, lalu visual sebelum/sesudah hanya untuk perubahan yang membantu review. Jika layar dari kelompok tertentu belum dibuat, sebutkan nama/ID layar tersebut sebagai belum tersedia dan lanjutkan dengan prompt kelompok terkait; jangan mengklaim cakupan lengkap.

Berikan inventaris layar per role, state penting, komponen reusable, token warna/spacing, dan catatan interaksi di luar UI sebagai bahan handoff implementasi. Jangan menambahkan modul baru atau mengganti identitas visual.
```

## Prompt 17 — Prompt revisi terarah bila hasil mulai melenceng

```text
Revisi layar SIMON yang sedang dipilih sambil mempertahankan seluruh fungsi dan alur dari brief yang disetujui.

Fokus perbaikan:
1. Pastikan background halaman, sidebar, card, form, dan dialog putih #FFFFFF.
2. Gunakan #015850 sebagai aksi utama dengan teks putih, #F77A04 dengan teks gelap sebagai aksen, #1E1935 untuk teks, serta #0A7DEF dan #739ABB sesuai fungsi pendukung dan kontras.
3. Rapikan hirarki: judul, ringkasan konteks, tindakan utama, pekerjaan yang perlu dilakukan, kemudian data pendukung.
4. Kurangi kepadatan dengan jarak konsisten, form berlabel, dan ringkasan sebelum detail; pertahankan informasi penting.
5. Ubah notifikasi hasil aksi/konfirmasi menjadi dialog di tengah pada desktop dan mobile. Pertahankan validasi dekat kolom dan pusat notifikasi yang tidak menginterupsi.
6. Lengkapi ikon mata pada setiap kolom password yang ada pada layar.
7. Periksa bahwa role dan status transaksi hanya menampilkan tindakan yang diizinkan.
8. Gunakan data fiktif, istilah Indonesia yang konsisten, serta state loading, kosong, error, dan sukses yang relevan.
9. Pertahankan interaksi keyboard, fokus, target sentuh 44 px, dan alternatif reduced motion.
10. Perubahan berfokus pada layar yang dipilih; komponen bersama tetap selaras dengan desain SIMON yang sudah disepakati.

Tampilkan versi perbaikan dan jelaskan singkat perubahan di luar mockup. Jika ada informasi yang belum tersedia, tandai pada catatan desain tanpa mengarang angka resmi, tanda tangan, izin, atau keberhasilan proses.
```

## Pemetaan cakupan fitur yang disetujui

| Fitur rencana | Prompt yang mencakup |
|---|---|
| F01 Akun dan akses | 00, 01, 02, 14, 15 |
| F02 Struktur organisasi/ruangan | 05, 07, 14 |
| F03 Register aset | 03, 07, 09 |
| F04 Katalog | 03 |
| F05 Peminjaman | 03, 04, 05, 08 |
| F06 Pengembalian | 04, 06, 08 |
| F07 Penetapan pemegang | 04, 09, 13 |
| F08 Distribusi/mutasi | 10 |
| F09 Inventarisasi/DBR | 07, 12, 13 |
| F10 Kerusakan/perawatan | 04, 06, 11 |
| F11 Penghapusan | 11, 13 |
| F12 Laporan/SPIP | 12 |
| F13 Surat/arsip | 05, 09, 10, 13 |
| F14 Label NUP/QR | 05, 07, 13 |
| F15 ASP/PSP | 10 |
| F16 Notifikasi/pusat tugas | 00, 04, 05, 08, 15 |
| F17 Impor/kualitas data | 09, 14 |
| F18 Keamanan/audit | 00, 01, 02, 08, 11, 13, 14, 15 |

Prompt 16–17 berlaku lintas modul untuk pemeriksaan dan revisi.

## Catatan penyerahan ke pengembang

Desain perlu menunjukkan ruang untuk kontrol keamanan: status akun, mandat, batas akses, validasi, syarat dokumen, penanganan konflik, dan audit. Implementasi Laravel tetap harus menegakkan seluruh aturan itu di server; menyembunyikan tombol di mockup tidak memberikan perlindungan dengan sendirinya.

Fitur opsi lanjutan dalam rencana—integrasi TTE, WhatsApp, koneksi langsung aplikasi pemerintah, peta rinci, OCR penuh, dan offline—tidak dianggap tersedia hanya karena desain menampilkan dokumen atau QR. Paket prompt berfokus pada rilis yang disetujui dan pencatatan referensi/bukti.

Hasil setelah paket dijalankan di Stitch yang perlu dikumpulkan: daftar layar dan state yang benar-benar selesai, referensi komponen/token, alur antarlayar, adaptasi mobile, serta catatan interaksi. Paket ini sendiri hanya menyiapkan prompt; belum ada layar Stitch yang dihasilkan atau diverifikasi pada tahap penulisan ini.
