# Traceability Matrix SIMON

Diperbarui 17 September 2026 berdasarkan kode dan route aktual. “Dasar teruji” bukan klaim seluruh acceptance criteria PRD terpenuhi. Status lengkap dan bukti verifikasi: [audit perbaikan](AUDIT_PERBAIKAN_SIMON.md).

| Kebutuhan | Halaman / route aktual | Implementasi / batas utama | Tes terkait |
| --- | --- | --- | --- |
| F01 Auth & akun | /login, /register, /verify-email, /forgot-password, /security/two-factor, /security/sessions | Aktivasi, verifikasi, MFA, sesi; SMTP nyata belum dikonfigurasi | Auth/*, TwoFactorAuthenticationTest, SimonCompletionTest |
| F02 Organisasi | /administration, /administration/placement | Unit/ruangan, mandat admin, penempatan awal, pencabutan/riwayat mandat, password penugasan Koordinator; lifecycle master/alih tugas belum penuh | SetupSimonTest, AssetPlacementTest, SimonWorkflowTest, SimonCompletionTest |
| F03 Register aset | /assets, /assets/{asset} | AssetPolicy + AccessScope, validasi/versi, riwayat | SimonWorkflowTest, SimonOperationsTest |
| F04 Katalog | /assets | Pencarian dan cakupan role; kalender belum ada | SimonWorkflowTest |
| F05 Pinjaman | /loans, /loans/create, /loans/{loan}/edit, /loans/availability | Wizard tiga tahap, draf/revisi, submission key, pratinjau bentrok per hari, approval dan serah-terima; kalender/interval jam kerja belum penuh | SimonWorkflowTest, SimonCompletionTest |
| F06 Pengembalian | /loans/{loan}, /evidence/photos | BAST per item, foto privat, checklist kelengkapan, temuan otomatis, perawatan dan gate bukti sebelum penutupan | SimonWorkflowTest, SimonCompletionTest |
| F07 Penetapan | /workspace/custody | Penetapan, serah-terima, penerimaan, penarikan; SOP/dokumen lanjutan belum penuh | SimonOperationsTest |
| F08 Mutasi | /workspace/transfers | Persetujuan, kirim/terima, jejak lokasi; penerimaan/distribusi multi-item belum penuh | SimonOperationsTest |
| F09 Inventarisasi/DBR | /workspace/inventory, /inventory/sessions/{session} | Disposisi, foto, kertas kerja, review bertingkat/bukti pengesahan eksternal, revisi dan snapshot final; rekonsiliasi/barang tambahan belum penuh | SimonWorkflowTest, SimonCompletionTest |
| F10 Insiden/perawatan | /workspace/incidents, /workspace/maintenance | Bukti foto, review, perawatan pengembalian rusak tanpa okupansi ganda, pemeriksaan PJ/Koordinator independen; kehilangan menyeluruh belum penuh | SimonWorkflowTest, SimonOperationsTest, SimonCompletionTest |
| F11 Penghapusan | /workspace/disposals | Usulan, persetujuan independen dengan bukti SK; tidak hard-delete | SimonOperationsTest |
| F12 SPIP/laporan | /spip, /workspace/reports, /reports/snapshots | SPIP berversi, CSV terkini, snapshot aset berfilter PDF/XLSX; pengesahan/gabungan dan laporan lintas modul belum penuh | SimonOperationsTest, SimonCompletionTest |
| F13 Dokumen | /documents, /basts/{bast}/print | Snapshot, PDF privat, verifikasi, versi pengganti; layout resmi belum penuh | SimonWorkflowTest, SimonCompletionTest |
| F14 QR | /assets/{asset}/qrcode | Label QR menuju detail terlindungi; izin tetap diperiksa | SimonWorkflowTest |
| F15 ASP/PSP | /workspace/registers | Catatan/review dasar; transisi identitas belum penuh | SimonOperationsTest |
| F16 Notifikasi | /workspace/notifications | Inbox, polling badge, pengingat harian terdeduplikasi; belum eskalasi lengkap/email | SimonOperationsTest, SimonCompletionTest |
| F17 Impor | /imports | XLSX/CSV, sheet, pemetaan, koreksi staging beraudit dan checksum, preview/commit atomik; maksimal 1.000 baris | SimonOperationsTest, SimonCompletionTest |
| F18 Audit/keamanan | /workspace/audit, /security/sessions | Scope, audit, MFA, pencabutan sesi, private storage; audit produksi belum selesai | SimonWorkflowTest, TwoFactorAuthenticationTest, SimonCompletionTest |
| MED media | Form aset/insiden, /media/{media}, /media/{media}/retry | Karantina privat, job, WebP/thumbnail, retry, akses ulang; ClamAV operasional/retensi/HEIC belum selesai | SimonOperationsTest, SimonCompletionTest |

Tabel memakai model yang sudah ada (antara lain loan_items, basts, work_records, media), bukan nama tabel konseptual lama dalam rancangan. Tidak ada tabel returns/attachments/document_templates baru yang diklaim sudah tersedia.
