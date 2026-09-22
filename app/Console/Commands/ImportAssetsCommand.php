<?php

namespace App\Console\Commands;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetIdentifier;
use App\Models\OrganizationUnit;
use App\Models\Room;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

#[Signature('import:assets {file? : Path file Excel} {--fresh : Hapus data aset dummy lama sebelum import} {--dry-run : Uji coba validasi tanpa menyimpan ke database}')]
#[Description('Import data aset BMN dari file Excel ke dalam database SIMON')]
class ImportAssetsCommand extends Command
{
    /** @var array<string, AssetCategory> */
    protected array $categoryCache = [];

    /** @var array<string, Room> */
    protected array $roomCache = [];

    /** @var array<string, int> */
    protected array $unitMap = [];

    protected int $defaultUnitId = 1;

    public function handle(): int
    {
        $filePath = $this->argument('file') ?? base_path('Daftar Aset BMN Balai Sumatera.xlsx');
        if (! file_exists($filePath)) {
            $this->error("File Excel tidak ditemukan: {$filePath}");

            return self::FAILURE;
        }

        $isFresh = (bool) $this->option('fresh');
        $isDryRun = (bool) $this->option('dry-run');

        $this->info('=== MEMULAI IMPORT DATA ASET BMN ===');
        $this->line("File: <comment>{$filePath}</comment>");
        if ($isDryRun) {
            $this->warn('Mode Dry-Run aktif: Tidak ada data yang akan disimpan ke database.');
        }

        $this->initCaches();

        $this->line('Membaca file Excel...');
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $this->line("Menganalisis baris data (Total baris di sheet: {$highestRow})...");

        // Kumpulkan baris yang valid (melewati header baris 1)
        $rowsToProcess = [];
        $skippedLocationsCount = 0;
        for ($r = 2; $r <= $highestRow; $r++) {
            $row = $sheet->rangeToArray("A{$r}:O{$r}", null, true, false)[0];
            // Lewati jika kolom Kode Barang dan Detail sama-sama kosong
            if (empty($row[1]) && empty($row[4])) {
                continue;
            }

            // Filter: Hanya izinkan tujuan transfer yang memuat 'jambi', 'medan', atau 'palembang'
            $tujuan = strtolower(trim((string) ($row[8] ?? '')));
            $isTargetLocation = str_contains($tujuan, 'jambi')
                || str_contains($tujuan, 'medan')
                || str_contains($tujuan, 'palembang');

            if (! $isTargetLocation) {
                $skippedLocationsCount++;

                continue;
            }

            $rowsToProcess[] = [
                'excel_row' => $r,
                'data' => $row,
            ];
        }

        $totalRows = count($rowsToProcess);
        $this->info("Ditemukan {$totalRows} baris aset valid untuk diimpor ({$skippedLocationsCount} baris lokasi lain dilewati).");

        if ($totalRows === 0) {
            $this->warn('Tidak ada data aset valid yang ditemukan.');

            return self::SUCCESS;
        }

        if ($isFresh && ! $isDryRun) {
            $this->warn('Menghapus data aset, identifier lama, dan lokasi di luar wilayah kerja (--fresh)...');
            DB::statement('PRAGMA foreign_keys = OFF');
            AssetIdentifier::query()->delete();
            Asset::query()->delete();
            Room::where(function ($q) {
                $q->where('name', 'not like', '%jambi%')
                    ->where('name', 'not like', '%medan%')
                    ->where('name', 'not like', '%palembang%');
            })->delete();
            DB::statement('PRAGMA foreign_keys = ON');
            $this->initCaches();
            $this->info('Data aset lama berhasil dibersihkan.');
        }

        $importedAssetsCount = 0;
        $createdIdentifiersCount = 0;
        $errors = [];

        $progressBar = $this->output->createProgressBar($totalRows);
        $progressBar->start();

        if (! $isDryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($rowsToProcess as $item) {
                $r = $item['excel_row'];
                $row = $item['data'];

                $itemCode = trim((string) ($row[1] ?? ''));
                $jenis = trim((string) ($row[2] ?? ''));
                $brandType = trim((string) ($row[3] ?? ''));
                $detail = trim((string) ($row[4] ?? ''));
                $stickerBmn = trim((string) ($row[5] ?? ''));
                $nup = trim((string) ($row[6] ?? ''));
                $pengadaan = trim((string) ($row[7] ?? ''));
                $tujuanTransfer = trim((string) ($row[8] ?? ''));
                // Wilayah Kerja ($row[9]) diabaikan sesuai instruksi pengguna
                $rawNilai = $row[10] ?? null;
                $penyedia = trim((string) ($row[11] ?? ''));
                $rawTanggal = $row[12] ?? null;
                $sakti = trim((string) ($row[13] ?? ''));
                $rawKondisi = trim((string) ($row[14] ?? 'Baik'));

                // 1. Nama Aset (User review: "detail saja")
                $name = $detail !== '' ? $detail : ($brandType !== '' ? $brandType : $jenis);
                if ($name === '') {
                    $name = 'Aset BMN No. '.$itemCode;
                }

                // 2. Kategori Aset
                $categoryId = $this->getOrCreateCategory($jenis, $isDryRun);

                // 3. Ruangan / Tujuan Transfer
                $roomId = $this->getOrCreateRoom($tujuanTransfer, $isDryRun);

                // 4. Tanggal Pembelian (User review: seragamkan)
                $acquisitionDate = $this->parseAcquisitionDate($rawTanggal);

                // 5. Nilai Perolehan
                $value = $this->parseValue($rawNilai);

                // 6. Kondisi Eksisting
                $condition = match (strtolower($rawKondisi)) {
                    'rusak ringan' => 'Rusak Ringan',
                    'rusak berat' => 'Rusak Berat',
                    default => 'Baik',
                };

                // 7. Spesifikasi tambahan (Penyedia, Pengadaan, SAKTI)
                $specParts = [];
                if ($pengadaan !== '') {
                    $specParts[] = 'Pengadaan: '.$pengadaan;
                }
                if ($penyedia !== '') {
                    $specParts[] = 'Penyedia: '.$penyedia;
                }
                if ($sakti !== '') {
                    $specParts[] = 'Periode SAKTI: '.$sakti;
                }
                $specification = ! empty($specParts) ? implode(' | ', $specParts) : null;

                if (! $isDryRun) {
                    $asset = Asset::create([
                        'item_code' => $itemCode !== '' ? $itemCode : null,
                        'nup' => $nup !== '' ? $nup : null,
                        'category_id' => $categoryId,
                        'name' => $name,
                        'brand_type' => $brandType !== '' ? $brandType : null,
                        'specification' => $specification,
                        'acquisition_date' => $acquisitionDate,
                        'acquisition_source' => $pengadaan !== '' ? $pengadaan : null,
                        'value' => $value,
                        'condition' => $condition,
                        'is_loanable' => true,
                        'room_id' => $roomId,
                        'status' => 'active',
                    ]);

                    if ($stickerBmn !== '') {
                        AssetIdentifier::create([
                            'asset_id' => $asset->id,
                            'identifier_type' => 'sticker',
                            'identifier_value' => $stickerBmn,
                            'assigned_date' => $acquisitionDate ?? now()->toDateString(),
                        ]);
                        $createdIdentifiersCount++;
                    }
                }

                $importedAssetsCount++;
                $progressBar->advance();
            }

            if (! $isDryRun) {
                DB::commit();
            }
            $progressBar->finish();
            $this->newLine(2);

        } catch (\Throwable $e) {
            if (! $isDryRun) {
                DB::rollBack();
            }
            $progressBar->finish();
            $this->newLine(2);
            $this->error("Terjadi kesalahan saat proses import: {$e->getMessage()}");
            $this->error("Di baris: {$e->getFile()}:{$e->getLine()}");

            return self::FAILURE;
        }

        $this->info('Import data aset BMN selesai!');
        $this->table(
            ['Metrik', 'Jumlah'],
            [
                ['Total Baris Diproses', $totalRows],
                ['Baris Wilayah Lain Dilewati', $skippedLocationsCount],
                ['Aset Berhasil Diimpor', $importedAssetsCount],
                ['Identifier / Sticker Dibuat', $createdIdentifiersCount],
                ['Total Kategori Terdaftar', count($this->categoryCache)],
                ['Total Ruangan / Lokasi Terdaftar', count($this->roomCache)],
            ]
        );

        return self::SUCCESS;
    }

    protected function initCaches(): void
    {
        $this->categoryCache = [];
        foreach (AssetCategory::all() as $cat) {
            $this->categoryCache[strtolower(trim($cat->name))] = $cat;
        }

        $this->roomCache = [];
        foreach (Room::all() as $room) {
            $this->roomCache[strtolower(trim($room->name))] = $room;
        }

        $this->unitMap = [];
        foreach (OrganizationUnit::all() as $unit) {
            $this->unitMap[strtolower($unit->name)] = $unit->id;
            $this->unitMap[strtolower($unit->code)] = $unit->id;
        }

        $defaultUnit = OrganizationUnit::where('code', 'BALAI-JAMBI')->first() ?? OrganizationUnit::first();
        $this->defaultUnitId = $defaultUnit ? $defaultUnit->id : 1;
    }

    protected function getOrCreateCategory(string $jenis, bool $dryRun): ?int
    {
        $cleanName = trim($jenis);
        if ($cleanName === '') {
            return null;
        }

        $key = strtolower($cleanName);
        if (isset($this->categoryCache[$key])) {
            return $this->categoryCache[$key]->id;
        }

        if ($dryRun) {
            return 1;
        }

        $code = $this->generateCategoryCode($cleanName);
        $category = AssetCategory::firstOrCreate(
            ['name' => $cleanName],
            ['code' => $code, 'is_active' => true]
        );

        $this->categoryCache[$key] = $category;

        return $category->id;
    }

    protected function generateCategoryCode(string $name): string
    {
        // Cari akronim dalam kurung, misal "(AC)", "(HT)", dll
        if (preg_match('/\(([A-Za-z0-9]+)\)/', $name, $matches)) {
            $candidate = strtoupper($matches[1]);
            if (! AssetCategory::where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Ambil huruf depan dari setiap kata
        $words = preg_split('/[\s\-\/\(\)]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach ($words as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }

        if (strlen($initials) >= 2 && strlen($initials) <= 5 && ! AssetCategory::where('code', $initials)->exists()) {
            return $initials;
        }

        // Fallback: 3 huruf pertama yang bersih
        $clean = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name));
        $baseCode = substr($clean, 0, 3);
        if (strlen($baseCode) < 3) {
            $baseCode = str_pad($baseCode, 3, 'X');
        }

        $code = $baseCode;
        $counter = 1;
        while (AssetCategory::where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 2).$counter;
            $counter++;
        }

        return $code;
    }

    protected function getOrCreateRoom(string $roomName, bool $dryRun): ?int
    {
        $cleanName = trim($roomName);
        if ($cleanName === '') {
            return null;
        }

        $key = strtolower($cleanName);
        if (isset($this->roomCache[$key])) {
            return $this->roomCache[$key]->id;
        }

        if ($dryRun) {
            return 1;
        }

        $unitId = $this->determineUnitId($cleanName);

        $words = preg_split('/[\s\-\/\(\)]+/', $cleanName, -1, PREG_SPLIT_NO_EMPTY);
        $initials = '';
        foreach ($words as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }

        $baseCode = 'RM-'.(strlen($initials) >= 2 ? substr($initials, 0, 8) : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $cleanName), 0, 6)));
        $code = $baseCode;
        $counter = 1;
        while (Room::where('code', $code)->exists()) {
            $code = $baseCode.'-'.$counter;
            $counter++;
        }

        $room = Room::firstOrCreate(
            ['name' => $cleanName],
            ['code' => $code, 'unit_id' => $unitId, 'is_active' => true]
        );

        $this->roomCache[$key] = $room;

        return $room->id;
    }

    protected function determineUnitId(string $roomName): int
    {
        $lower = strtolower($roomName);
        if (str_contains($lower, 'medan')) {
            return $this->unitMap['sw-i-medan'] ?? $this->unitMap['seksi wilayah i medan'] ?? $this->defaultUnitId;
        }
        if (str_contains($lower, 'palembang')) {
            return $this->unitMap['sw-ii-palembang'] ?? $this->unitMap['seksi wilayah ii palembang'] ?? $this->defaultUnitId;
        }

        return $this->defaultUnitId;
    }

    protected function parseAcquisitionDate(mixed $rawDate): ?string
    {
        if (empty($rawDate)) {
            return null;
        }

        if (is_numeric($rawDate)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $rawDate)->format('Y-m-d');
            } catch (\Throwable) {
                // fall through
            }
        }

        try {
            return Carbon::parse(trim((string) $rawDate))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function parseValue(mixed $rawValue): ?float
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        if (is_numeric($rawValue)) {
            return (float) $rawValue;
        }

        // Hilangkan simbol selain angka, titik, koma
        $cleaned = preg_replace('/[^\d.,]/', '', (string) $rawValue);
        $cleaned = str_replace(',', '.', str_replace('.', '', $cleaned));

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }
}
