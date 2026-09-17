# Implementation Decisions SIMON

## Stack dan Versi
- **Backend terpasang (16 September 2026)**: Laravel 13.31, PHP 8.3; gunakan API sesuai composer.lock, bukan asumsi versi terbaru.
- **Frontend**: React 18+ dengan TypeScript dan Inertia.js
- **Database**: MySQL 8.0+
- **Styling**: Tailwind CSS 3 dan komponen Headless UI yang sudah dipakai aplikasi; tidak menambahkan shadcn/Radix.
- **Animasi**: GSAP & Lottie

## Pilihan Driver/Paket
- **Queue**: Database (MySQL) untuk kemudahan operasional awal sesuai ADR-04.
- **Media/Image**: Intervention Image 4.3 dengan GD/WebP, sumber privat, antrean media, dan pemeriksaan akses ulang saat pemrosesan.
- **PDF Renderer**: barryvdh/laravel-dompdf 3.1; remote resource dan eksekusi PHP dinonaktifkan.
- **Excel**: PhpSpreadsheet 5.9; XLSX/CSV dibaca dengan batas ukuran ekstraksi/kolom/baris, formula dan konten aktif ditolak.
- **Autentikasi**: Fortify 1.39 untuk MFA TOTP; alur akun/aktivasi/scope tetap mengikuti aplikasi.
- **Build**: Vite 8, plugin React 6, tipe Node 24; React aplikasi tetap 18. Lottie Light dan GSAP dimuat dinamis.
- **Mail**: Mailtrap/log lokal untuk sandbox (pengaturan SMTP produksi menunggu instansi).

## ADR (Architecture Decision Records)
- **ADR-01**: Monolit modular Laravel agar satu transaksi BMN mudah dijaga.
- **ADR-02**: Sesi server + Inertia satu domain.
- **ADR-03**: Tiga role (Pegawai, PJ Ruangan, Koordinator) + cakupan + mandat.
- **ADR-04**: Database queue dan polling ringan.
- **ADR-05**: WebP server-side melalui satu layanan media.
- **ADR-06**: Bukti asli privat + turunan WebP.
- **ADR-07**: Surat berbasis template terkendali dan snapshot.

## Default/Config Terpilih
- Jadwal pengingat menggunakan Asia/Jakarta (08.00 WIB). Normalisasi tampilan waktu seluruh halaman masih perlu diuji; jangan mengasumsikan semua tanggal UI sudah dikonversi.
- Password min: 15 karakter.
- Upload Foto: Max 10MB per file, konversi ke WebP untuk display, aslinya di-keep untuk bukti (Storage private).

## Keputusan Belum Final (Pertanyaan Produksi)
- Konfirmasi versi SOP BMN final dan pejabat berwenang penandatangan.
- Konfigurasi mail server (SMTP) production instansi.
- Ketersediaan plugin OCR (jika dibutuhkan di masa depan) dan TTE resmi.
- Operasi worker/scheduler, instalasi ClamAV, retensi bukti dan backup/restore. Mode scanner development tidak setara dengan pemeriksaan antivirus.
