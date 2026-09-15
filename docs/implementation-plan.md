# Implementation Plan SIMON

## Tahapan Implementasi

- [ ] **P0 — Discovery dan baseline** (Sedang berjalan)
  - [x] Baca PRD/rencana/SOP relevan
  - [x] Inventaris project/runtime
  - [x] Cek aturan workspace
  - [x] Buat `docs/implementation-plan.md`, `docs/implementation-decisions.md`, `docs/traceability.md`
- [ ] **P1 — Fondasi aman**
  - [ ] Scaffold stack (Laravel + React + Inertia + Tailwind)
  - [ ] Setup DB, design tokens, dialog/form
  - [ ] Auth lengkap (Login, Register, MFA)
  - [ ] Scope/Policy, unit/ruangan, audit, private storage
  - [ ] Queue dan mail sandbox
  - [ ] Build/typecheck/lint + tes auth/role negatif
- [ ] **P2 — Aset dan media**
  - [ ] F03/F04 Register Aset & Katalog
  - [ ] Upload service MED-01–12, gallery/WebP, QR
  - [ ] Import staging dasar
  - [ ] Tes media MT-01–11/MT-13
- [ ] **P3 — Siklus pinjaman**
  - [ ] F05/F06/F10 dasar
  - [ ] Persetujuan/reservasi
  - [ ] Checklist/bukti, surat dasar, serah-terima
  - [ ] Kembali sebagian, penutupan, notifikasi
  - [ ] E2E tiga role lengkap, concurrency MySQL
- [ ] **P4 — Penetapan dan kelengkapan SOP**
  - [ ] F07–F15 lanjutan (penetapan, distribusi, inventarisasi, perawatan, penghapusan, laporan/SPIP, arsip/template, ASP/PSP)
- [ ] **P5 — Impor tervalidasi dan hardening**
  - [ ] F17 lengkap, rekonsiliasi, provenance foto
  - [ ] UAT mobile/aksesibilitas/performa
  - [ ] Security audit, dokumentasi operasi
- [ ] **P6 — Pilot dan produksi terotorisasi**
  - [ ] Verifikasi SOP/mandat/template/SMTP/storage
  - [ ] Deployment dan pelatihan

## Status Aktual dan Next Step
- **Status Aktual**: Tahap P0 (Discovery) hampir selesai. Rencana implementasi disusun.
- **Next Step**: Memulai P1 (Scaffolding dan Fondasi Aman).
