# Implementation Decisions SIMON

## Stack dan Versi
- **Backend**: Laravel 11.x (versi stabil terbaru yang didukung PHP 8.2+)
- **Frontend**: React 18+ dengan TypeScript dan Inertia.js
- **Database**: MySQL 8.0+
- **Styling**: Tailwind CSS + shadcn/ui (radix-ui)
- **Animasi**: GSAP & Lottie

## Pilihan Driver/Paket
- **Queue**: Database (MySQL) untuk kemudahan operasional awal sesuai ADR-04.
- **Media/Image**: Intervention Image v3 (dengan GD atau Imagick yang mendukung WebP).
- **PDF Renderer**: Laravel Snappy atau dompdf (akan dievaluasi lebih lanjut pada P3/P4).
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
- Waktu dan Zona: Asia/Jakarta (WIB) pada UI, UTC di server database.
- Password min: 15 karakter.
- Upload Foto: Max 10MB per file, konversi ke WebP untuk display, aslinya di-keep untuk bukti (Storage private).

## Keputusan Belum Final (Pertanyaan Produksi)
- Konfirmasi versi SOP BMN final dan pejabat berwenang penandatangan.
- Konfigurasi mail server (SMTP) production instansi.
- Ketersediaan plugin OCR (jika dibutuhkan di masa depan) dan TTE resmi.
