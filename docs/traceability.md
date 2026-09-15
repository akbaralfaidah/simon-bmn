# Traceability Matrix SIMON

Matrix ini memetakan kebutuhan F01-F18 dan MED-01-MED-12 ke implementasinya.

| Fitur / Kebutuhan | Halaman / Route | Policy / Middleware | Model / Migration | Tes Terkait | Status |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **F01** - Autentikasi dan akun | `/login`, `/register`, `/verify`, `/forgot-password` | `guest`, `auth`, `verified` | `users`, `employee_profiles` | AC-01 s.d AC-05 | Todo |
| **F02** - Organisasi & Ruangan | `/admin/units`, `/admin/rooms` | `can:manage-organization` | `organization_units`, `buildings`, `rooms` | AC-06 | Todo |
| **F03** - Register aset | `/assets`, `/assets/{id}` | `can:view-assets` | `assets`, `asset_categories`, `asset_identifiers` | AC-07, AC-08 | Todo |
| **F04** - Katalog & pencarian | `/catalog` | `auth` | `assets` | AC-07 | Todo |
| **F05** - Peminjaman | `/loans/create`, `/loans` | `can:create-loan` | `loan_requests`, `loan_items`, `reservations` | AC-09 s.d AC-14 | Todo |
| **F06** - Pengembalian | `/returns`, `/returns/{id}` | `can:return-loan` | `returns`, `return_items` | AC-15, AC-16 | Todo |
| **F07** - Penetapan pemegang | `/custody` | `can:manage-custody` | `custody_assignments`, `custody_events` | AC-17 | Todo |
| **F08** - Penerimaan & mutasi | `/transfers` | `can:manage-transfers` | `asset_receipts`, `asset_transfers`, `transfer_items`| AC-18 | Todo |
| **F09** - Inventarisasi & DBR | `/inventory` | `can:manage-inventory` | `inventory_sessions`, `inventory_items`, `findings` | AC-19 | Todo |
| **F10** - Insiden & perawatan | `/maintenance`, `/incidents` | `auth` | `incidents`, `maintenance_orders` | AC-20 | Todo |
| **F11** - Usulan penghapusan | `/disposals` | `can:manage-disposals` | `disposal_cases`, `disposal_items` | AC-21 | Todo |
| **F12** - Laporan dan SPIP | `/reports`, `/spip` | `can:view-reports` | `report_periods`, `spip_records` | AC-22, AC-23 | Todo |
| **F13** - Surat & arsip | `/documents` | `auth` | `document_templates`, `documents` | AC-24 | Todo |
| **F14** - Label NUP & QR | `/assets/{id}/qr` | `auth` | (virtual/generator) | AC-25 | Todo |
| **F15** - Transisi ASP/PSP | `/transitions` | `can:manage-transitions`| `ownership_transition_cases` | AC-26 | Todo |
| **F16** - Notifikasi & tugas | `/notifications` | `auth` | `notifications` | AC-27 | Todo |
| **F17** - Impor & kualitas data | `/import` | `can:import-data` | `import_batches`, `source_files` | AC-28, AC-29 | Todo |
| **F18** - Audit & keamanan | `/audit` | `can:view-audit` | `audit_logs` | AC-30 | Todo |
| **MED-01 s.d MED-12** - Media | `/media/upload` | `can:upload-media` | `attachments`, `media_variants` | MT-01 s.d MT-14 | Todo |
