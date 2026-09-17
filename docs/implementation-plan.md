# Implementation Plan SIMON

Diperbarui 17 September 2026. Rincian bukti dan backlog terdapat pada [audit perbaikan](AUDIT_PERBAIKAN_SIMON.md) dan [traceability](traceability.md).

## Yang sudah diterapkan dan diperiksa

- [x] Baseline runtime, kode, PRD, dan SOP relevan.
- [x] Stack Laravel 13, React 18, TypeScript, Inertia, Tailwind.
- [x] Login/register/reset, verifikasi, aktivasi, MFA PJ/Koordinator, sesi perangkat.
- [x] Scope unit/ruangan, mandat admin, Policy aset, audit dan penyimpanan privat.
- [x] Aset/katalog/QR, penempatan awal eksplisit tanpa memindahkan data otomatis.
- [x] Draf/revisi pinjaman, persetujuan, reservasi, penyerahan, kembali per item dan pemeriksaan BAST.
- [x] Temuan otomatis pengembalian rusak, perawatan terkait, pemeriksaan PJ dan review Koordinator; peminjam tidak dapat memproses temuannya sendiri.
- [x] Halaman/endpoint dasar penetapan, mutasi, inventarisasi, perawatan, penghapusan, SPIP, ASP/PSP.
- [x] Antrean foto, WebP/thumbnail, retry, status UI dan pemeriksaan ulang akses.
- [x] XLSX/CSV staging, sheet dan pemetaan kolom, validasi serta commit atomik.
- [x] Pusat dokumen dengan snapshot, versi dan arsip bukti SPIP.
- [x] Dialog tengah, tombol mata, animasi ringan/reduced motion dan pengingat inbox.
- [x] Inventarisasi: disposisi, kertas kerja PJ, review Subkoordinator/Koordinator, pengesahan eksternal Kepala TU, revisi dan snapshot final.
- [x] Bukti foto privat/WebP pada item pinjaman dan inventarisasi; checklist kelengkapan kembali dan gate pemrosesan bukti.
- [x] Koreksi baris staging dengan checksum versi, audit dan validasi ulang seluruh batch.
- [x] Pencabutan mandat, sesi dan riwayat penugasan; admin global + konfirmasi password untuk penugasan Koordinator.
- [x] Wizard pinjaman tiga tahap dan pratinjau bentrok jadwal tanpa identitas peminjam lain.
- [x] Snapshot laporan aset berfilter dengan ekspor PDF/XLSX, checksum dan pemeriksaan cakupan saat unduh.
- [x] Suite 90 tes / 774 assertions, build TypeScript/Vite, Pint dan pemeriksaan diff.
- [x] Pemeriksaan browser terbatas pada login, tombol mata dan dialog lupa sandi.

## Pekerjaan kode berikutnya

1. Rekonsiliasi temuan/barang tambahan inventarisasi, pengembalian penetapan pemegang dan penanganan kehilangan menyeluruh. Bukti review bertingkat inventarisasi kini dicatat sebagai pengesahan eksternal, bukan role tambahan.
2. Petakan layout resmi seluruh template sumber; selesaikan penerimaan/distribusi multi-item.
3. Lengkapi transisi identitas ASP/PSP, lifecycle master dan alih tugas terbuka saat mandat berganti. Pencabutan/riwayat penugasan sudah tersedia.
4. Kalender visual, interval/buffer jam kerja, review/gabungan/pengesahan laporan lintas modul dan eskalasi tugas. Wizard, pratinjau bentrok per hari dan snapshot aset bukan keseluruhan kebutuhan laporan.
5. Pemilihan sheet dari daftar otomatis, batch besar/provenance foto, retensi media, dan penggantian bukti gagal saat akses pengunggah dicabut.
6. UAT tiga role dan responsive/keyboard/accessibility; konkurensi MySQL dan pengujian keamanan produksi.

## Prasyarat operasional yang belum selesai

- SMTP/provider email dan verifikasi kirim sungguhan.
- Data ruangan, cakupan penugasan, enrollment MFA dan penempatan aset berdasarkan keputusan instansi.
- ClamAV/signature, worker antrean dan scheduler dengan pengelola proses.
- Pengesahan format/penandatangan SOP, HTTPS, backup/restore dan persetujuan pilot.

Status: implementasi dan perbaikan lintas P1–P5 sedang berlangsung. Bukan lagi discovery saja; seluruh PRD dan kesiapan produksi belum selesai.
