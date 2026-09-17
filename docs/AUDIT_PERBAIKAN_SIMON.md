# Audit dan perbaikan SIMON

Status: pengerjaan aktif. Daftar ini mencatat bukti kode dan hasil pengujian, bukan klaim semua PRD telah selesai.

## Baseline

- Build frontend awal berhasil, dengan peringatan kompatibilitas plugin React/Vite.
- Tes awal: 25 tes, 23 lulus, 2 gagal (registrasi dan redirect halaman awal). Tes bisnis BMN belum tersedia.
- Ada perubahan pengguna yang belum di-commit pada controller aset/QR, layout, login/register, dashboard, halaman aset dan pinjaman. Perubahan ini menjadi dasar perbaikan.

## Temuan terkonfirmasi

1. Controller BMN tidak menerapkan Policy/izin; query daftar dan dashboard membaca semua unit.
2. Role di sidebar ditentukan dari alamat email, bukan penugasan database. Tautan menu berupa `#`, statistik tugas berupa angka tetap.
3. User belum menerapkan MustVerifyEmail sehingga middleware verified tidak menegakkan verifikasi.
4. Pengembalian mengambil LoanItem global tanpa memeriksa hubungan dengan loan, langsung memperbarui aset dan menutup pinjaman.
5. Persetujuan tidak mencegah self-approval dan konflik jadwal malah mengubah pengajuan menjadi rejected tanpa keputusan pengguna.
6. BAST penetapan/pengembalian ditandai signed tanpa bukti; nomor memakai uniqid, tanpa sequence.
7. Media memakai API Intervention v3 sedangkan paket terpasang v4; media disimpan di disk public.
8. Aset dan akun dapat dihapus permanen beserta relasi historis; penghapusan administratif hanya memerlukan teks nomor SK.
9. Inventarisasi dapat ditutup tanpa pemeriksaan item; perawatan mengubah kondisi ke Baik hanya karena end_date terisi.
10. Komponen Spip/Index belum ada; halaman operasional, organisasi, dokumen, pelaporan, notifikasi, dan impor belum lengkap.
11. Tombol konfirmasi memakai confirm/alert native; flash session belum dibagikan melalui Inertia.
12. Seeder menimpa password akun tetap dan memicu impor sumber saat seeding biasa.

## Implementasi yang sudah dikerjakan — 15 September 2026

- Boost terpasang; pedoman Laravel, pengujian, dan Inertia dipakai untuk implementasi serta tes regresi.
- Akses mengikuti role yang masih berlaku, unit/ruangan, Policy aset, dan mandat administrasi terpisah. Admin unit tidak dapat mengelola akun lintas unit/global; audit PJ dibatasi ke cakupan ruangan.
- Registrasi menyimpan akun Pegawai berstatus pending dengan unit valid. Verifikasi email diwajibkan sebelum operasional dan aktivasi admin. Kata sandi baru minimal 15 karakter. Respons lupa sandi tidak membocorkan keberadaan email.
- Laravel Fortify dipasang dengan persetujuan pengguna; paket PHP simple-qrcode yang tidak digunakan dihapus dengan persetujuan. QR React tetap digunakan. MFA TOTP wajib untuk PJ/Koordinator, memerlukan konfirmasi saat setup, memiliki recovery code sekali pakai, pembatasan percobaan, dan pemeriksaan sesi. Rahasia tidak disertakan dalam data pengguna biasa.
- Penangguhan akun membatalkan sesi dan token ingat-saya. Nonaktifkan akun mempertahankan riwayat, bukan menghapus pengguna permanen. Reset/ganti kata sandi mencabut sesi lama.
- Pinjaman memiliki draf/pengajuan, persetujuan independen, reservasi jadwal, pemeriksaan konflik atomik, persiapan, penyerahan, konfirmasi peminjam, perpanjangan, permintaan kembali, pemeriksaan fisik, dan penutupan per item. Kirim ulang dengan submission key yang sama tidak membuat transaksi baru.
- Nomor BAST memakai sequence; snapshot dibuat saat penerbitan. Draf tidak ditandai bertanda tangan. Unggahan PDF privat dan verifikasi terpisah sudah tersedia untuk BAST pinjaman.
- Penetapan pemegang, inventarisasi, perawatan, penghapusan dengan SK/PDF, mutasi ruangan, laporan kejadian, register ASP/PSP, SPIP, dokumen, laporan CSV, audit, notifikasi, administrasi, serta staging impor CSV memiliki halaman dan endpoint nyata. Kedalaman beberapa modul masih terbatas (lihat pekerjaan tersisa).
- Foto aset dan bukti kejadian diproses memakai API Intervention v4: validasi tipe/ukuran/dimensi, resize, konversi WebP, thumbnail, checksum, penyimpanan sumber asli privat, pembersihan file jika transaksi gagal. Akses foto memeriksa izin; tidak ada media lama disk public pada database yang diperiksa.
- Tampilan memakai navigasi nyata berdasarkan izin dan angka database. Dialog konfirmasi/notifikasi di tengah, input kata sandi dengan tombol mata, pilihan banyak barang berbentuk checklist, foto bukti bisa dibuka, label QR mengarah ke detail aset yang tetap memerlukan otorisasi.
- Seeder tidak lagi menimpa password akun tetap atau otomatis mengimpor aset. Halaman penempatan awal memerlukan admin global, pilihan ruangan eksplisit, pemeriksaan versi, serta jejak lokasi.

## Kelanjutan implementasi — 16 September 2026

- Dependensi yang disetujui sudah terpasang: PhpSpreadsheet 5.9, GSAP, Lottie; plugin React 6 dan tipe Node 24 diselaraskan dengan Vite 8 tanpa menaikkan React aplikasi ke versi 19.
- Editor draf/revisi pinjaman memakai pemeriksaan versi; submission key dengan isi berbeda ditolak. Pembatalan item sebelum serah-terima tidak menghapus riwayat.
- Pengembalian kini membuat BAST per item setelah pemeriksaan fisik. Koordinator tidak dapat menutup item sebelum BAST pengembalian terverifikasi dan insiden terbuka diselesaikan.
- Pengembalian dengan kondisi rusak otomatis mencatat insiden terkait item, kondisi awal/akhir, peminjam, dan waktu pemeriksaan. Temuan wajib ditindaklanjuti; penolakan bukan jalan pintas penutupan. Peminjam dapat memantau temuan miliknya, tetapi tidak dapat menyetujui/memeriksa/menutupnya meskipun memiliki role operasional tambahan.
- Perawatan terkait pengembalian dapat dimulai setelah persetujuan insiden dan penerimaan fisik PJ, tanpa menggandakan okupansi. Perawatan ganda, barang belum diterima, insiden milik aset lain, serta penutupan sebelum perawatan selesai ditolak. Hasil PJ dan review Koordinator terpisah; kondisi tidak otomatis menjadi Baik, dan biaya perawatan bukan tagihan otomatis peminjam.
- Laporan insiden baru diperiksa ulang saat penyerahan pinjaman, penetapan pemegang, dan serah-terima mutasi. Foto bukti yang belum siap menahan penutupan insiden.
- Impor XLSX/CSV memiliki pratinjau, nama sheet, form pemetaan judul kolom, validasi identitas, sumber privat dan checksum. Kode panjang bertipe teks serta nol di depan dipertahankan. Formula/makro/tautan eksternal/objek tertanam dan ukuran ekstraksi berlebihan ditolak. Lokasi berkas sumber tidak dikirim ke browser.
- Media memiliki antrean privat, pemrosesan WebP/thumbnail, status antre/gagal/siap, retry pengunggah, pemeriksaan ulang akses saat job dijalankan, dan rekonsiliasi antrean. Job berulang tidak menggandakan turunan. Mode development bukan antivirus.
- Pusat dokumen menampilkan snapshot, unduhan asli privat, verifikasi/rejeksi independen, dan versi pengganti tanpa menimpa berkas lama. Pengganti BAST pengembalian mempertahankan cakupan item dan pihak terkait. PDF belum diperiksa tidak dapat diunduh/verifikasi.
- SPIP memiliki tenggat, unggah bukti PJ, review Koordinator, permintaan revisi dan penilaian efektivitas. Bukti setiap revisi diarsipkan berversi serta dapat dibuka hanya dalam cakupan petugas.
- Halaman sesi perangkat dapat mencabut sesi perangkat lain setelah konfirmasi password. Pengingat inbox terjadwal dideduplikasi per hari dan pengingat peminjam berhenti setelah penerimaan fisik.
- Animasi GSAP/Lottie dimuat sesuai kebutuhan dan menghormati reduced motion. Lottie Light tidak memakai mesin expression/eval. Branding autentikasi diperbaiki menjadi SIMON, latar putih, serta klaim pengesahan/kepatuhan otomatis yang belum terbukti dihapus.
- Panduan worker, scheduler, mail, scanner, dan pengujian ada pada README. Worker/scheduler layanan permanen belum dipasang pada sistem operasi.

## Kelanjutan implementasi — 17 September 2026

- Inventarisasi dibuat Koordinator per ruangan. Tahap disposisi → pemeriksaan PJ → kertas kerja → review Subkoordinator → review Koordinator independen → pengesahan Kepala TU → arsip final ditegakkan server. Pejabat eksternal dibuktikan dengan nama, nomor, tanggal dan PDF privat; aplikasi tidak menerbitkan mandat atau menandatangani otomatis.
- Review memiliki versi dan riwayat append-only; pengembalian untuk koreksi tidak menimpa bukti sebelumnya. Draft temuan tidak mengubah master. Snapshot final mempertahankan kondisi awal dan hasil pemeriksaan terpisah; DBR berikutnya memakai snapshot tersebut, bukan master yang telah berubah. Path/checksum bukti tidak dibagikan melalui Inertia.
- Foto bukti tersedia pada item pinjaman dan inventarisasi melalui pipeline karantina/WebP yang sama. Hanya PJ terkait dapat mengunggah; peminjam dapat melihat bukti pinjamannya. Penyerahan/penutupan pinjaman dan pengiriman kertas kerja menunggu pemrosesan bukti selesai. Checklist pengembalian tidak lengkap otomatis memunculkan tindak lanjut meskipun kondisi fisik Baik.
- Baris staging bisa dikoreksi dengan alasan, checksum versi dan jejak sebelum/sesudah. Seluruh batch diperiksa ulang setelah koreksi; data sumber tidak ditimpa dan batch committed tidak bisa diedit.
- Mandat dapat dicabut dengan konfirmasi password, alasan dan otorisasi cakupan. Sesi/token ingat-saya dicabut; row penugasan lama dipertahankan ketika mandat baru dibuat. Penugasan Koordinator memerlukan admin global dan konfirmasi password; pemberian mandat ke diri sendiri tetap ditolak.
- Wizard pinjaman tiga langkah dilengkapi ringkasan dan dialog konfirmasi tengah. Pratinjau ketersediaan (maks. 30 aset / 93 hari per pemeriksaan) menampilkan reservasi, okupansi dan insiden tanpa identitas peminjam lain. Ini bukan reservasi dan masih memakai tanggal harian, bukan interval jam kerja PRD.
- Snapshot laporan aset berfilter dapat diunduh PDF/XLSX. Snapshot/checksum tetap sama setelah master berubah; checksum tidak bergantung urutan key JSON MySQL. Kode/NUP dan teks berawalan formula diekspor sebagai string. Unduhan memeriksa ulang cakupan saat ini. Label periode bukan rekonstruksi saldo historis dan laporan tetap draf internal.
- Dialog ActionForm memakai initial data/versi terbaru setiap dibuka sehingga tidak mengirim versi usang setelah aksi sebelumnya.

## Perubahan database nyata yang disetujui

- Tiga migrasi tambahan telah dijalankan: perluasan workflow, kolom MFA, dan kelengkapan operasional (versi dokumen/draf, status media, SPIP, serta deduplikasi pengingat). Tidak menjalankan migrate:fresh, rollback, atau penghapusan data lama.
- Migrasi tambahan `2026_09_16_144947_link_return_followup_to_maintenance` telah dijalankan: relasi item/insiden pada perawatan, pemeriksa dan catatan hasil, serta timestamp pemeriksaan pengembalian. Migrasi hanya menambah struktur; tidak memindahkan atau menghapus aset lama.
- Migrasi `2026_09_16_153638_add_review_snapshot_to_inventory_sessions` telah dijalankan untuk versi, riwayat review dan snapshot final. Tidak mengubah data aset atau mandat pengguna nyata.
- Perintah `php artisan simon:setup 1 --global-admin --units --no-interaction` berhasil dijalankan setelah persetujuan pengguna.
- Unit awal: Balai Jambi, Seksi Wilayah I Medan, Seksi Wilayah II Palembang.
- Penugasan Koordinator akun ID 1 kini memiliki mandat global dan administrasi. Password, verifikasi email, serta enrollment MFA akun tidak diubah otomatis.
- Sesudah setup: 212 aset tetap ada; semuanya tetap tanpa ruangan; jumlah ruangan 0. Admin perlu membuat ruangan, lalu menempatkan aset secara manual melalui Administrasi → Penempatan awal.

## Pemeriksaan dan batas hasil — 17 September 2026

- Suite penuh: **90 tes lulus, 774 assertions**. Mencakup auth, scope, workflow, inventarisasi/revisi/snapshot, bukti foto/gate proses, koreksi staging, pencabutan mandat, jadwal privat, laporan snapshot/Excel dan perawatan pengembalian.
- TypeScript dan build Vite lulus; konflik plugin React/Vite serta peringatan eval Lottie telah diatasi.
- Pint lulus, `composer validate` lulus, `git diff --check` lulus. Semua migrasi berstatus Ran; dua perintah terjadwal tampil pada `schedule:list`.
- Pengujian backend memakai SQLite in-memory, bukan database aset nyata. Hasil ini bukan bukti uji konkurensi/beban MySQL.
- Browser lokal berhasil membuka login dan lupa sandi. Tombol mata mengubah keadaan tampil/sembunyi; pengiriman email fiktif tanpa akun menampilkan pesan netral dalam dialog tengah yang dapat ditutup. Judul halaman dan latar putih hasil build terbaru sudah dilihat.
- Pengujian visual tersebut belum meliputi semua halaman terautentikasi, seluruh ukuran layar, ataupun perjalanan lengkap tiga role. Tidak membuat akun nyata atau mengubah kredensial pengguna untuk pengujian.
- Percobaan browser pada 17 September terhalang koneksi sesi alat (tab dinyatakan bukan bagian sesi). Tidak mengklaim wizard/modal/foto terbaru telah melewati UAT browser. Pengguna diminta login memakai akun uji sendiri; tidak melewati MFA atau mengubah kredensial.

## Pekerjaan tersisa / belum layak diklaim selesai

1. Email masih memakai transport `log`; SMTP/provider dan uji verifikasi/reset ke inbox sungguhan belum dikonfigurasi.
2. Enrollment MFA, ruangan, mandat, dan penempatan 212 aset memerlukan data organisasi nyata; tidak boleh dibuat berdasarkan tebakan.
3. Template PDF masih generik. Kop, layout setiap dokumen sumber, pejabat, format nomor, lampiran, dan persetujuan format instansi belum lengkap. Flag persetujuan template harus tetap false sampai disahkan.
4. Rekonsiliasi temuan/barang tambahan inventarisasi, penerimaan/distribusi multi-item, pengembalian penetapan pemegang dan penanganan kehilangan menyeluruh masih perlu dilengkapi. Review hierarki dengan bukti eksternal, foto/checklist pengembalian pinjaman dan integrasi perawatannya sudah tersedia.
5. Register ASP/PSP masih catatan/review dasar, belum transisi identitas lengkap. Lifecycle master aktif/nonaktif, bukti SK mandat dan alih tugas terbuka belum penuh; pencabutan dan riwayat penugasan sudah tersedia.
6. Impor dibatasi 1.000 baris; koreksi staging sudah tersedia, tetapi pemilihan sheet dari hasil deteksi interaktif, batch besar dan provenance foto workbook belum ada.
7. Worker media, scheduler, ClamAV dengan signature terbarui dan pemantauan gagal harus dioperasikan. Mode lokal development tidak memindai malware; produksi gagal aman ketika scanner tidak tersedia. Retensi sumber, HEIC, dan optimasi memori/decode perlu diperluas.
8. Kalender visual/interval jam kerja, laporan lintas modul/gabungan/pengesahan dan eskalasi tugas petugas belum selesai. Wizard, pratinjau bentrok per hari serta snapshot aset PDF/XLSX sudah ada. Pengingat masih inbox, bukan email. Pemulihan bukti gagal ketika akses pengunggah dicabut perlu mekanisme penggantian teraudit; gate tetap gagal aman.
9. Perlu UAT tiga role, mobile/keyboard/accessibility, konkurensi MySQL, HTTPS/cookie/CSP, backup/restore, pengujian pemindai sungguhan, dan peninjauan keamanan produksi menyeluruh.

Status keseluruhan: peningkatan di atas sudah diimplementasikan dan diuji sesuai bukti yang dicatat; **seluruh PRD belum selesai dan aplikasi belum dinyatakan siap produksi**.
