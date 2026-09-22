<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetIdentifier;
use App\Models\OrganizationUnit;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportAssetsTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_fails_if_file_does_not_exist(): void
    {
        $this->artisan('import:assets', ['file' => 'non_existent_file.xlsx'])
            ->expectsOutputToContain('File Excel tidak ditemukan')
            ->assertFailed();
    }

    public function test_import_assets_from_spreadsheet_with_proper_mapping_and_standardized_dates(): void
    {
        $unit = OrganizationUnit::create([
            'name' => 'Balai Jambi',
            'code' => 'BALAI-JAMBI',
        ]);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('All');

        // Header
        $headers = [
            'No', 'Kode Barang', 'Jenis', 'Merk/Tipe', 'Detail',
            'Sticker BMN', 'NUP', 'Pengadaan', 'Tujuan Transfer BMN', 'Wilayah Kerja',
            'Nilai Perolehan', 'Penyedia', 'Tanggal Pembelian', 'Periode Penginputan SAKTI', 'Kondisi Eksisting',
        ];
        $sheet->fromArray([$headers], null, 'A1');

        // Row 2: string date e.g. "1 October 2025"
        $row2 = [
            1, '3050105015', 'Alat Penghancur Kertas', 'Krisbow Micro Cut', 'Krisbow Mesin Penghancur Kertas Micro Cut S8106',
            '1', '6', 'Direktorat Pengaduan', 'Balai Penegakan Hukum Jambi', 'Sumatera',
            '6604500', 'PT Rekadaya', '1 October 2025', '45931', 'Baik',
        ];
        // Row 3: Excel serial date e.g. 45930 (which is 2025-09-30)
        $row3 = [
            2, '3050105016', 'Drone', 'DJI Mavic 3', 'DJI Mavic 3 Enterprise Thermal',
            'STK-002', '12', 'Direktorat Pengawasan', 'Seksi Wilayah I Medan', 'Sumatera',
            '75000000', 'PT Drone Indo', 45930, '45932', 'Baik',
        ];
        // Row 4: Non-target location (Surabaya) - should be skipped
        $row4 = [
            3, '3050105099', 'Laptop', 'Lenovo Thinkpad', 'Thinkpad T14',
            'STK-099', '99', 'Direktorat Lain', 'Balai Penegakan Hukum Lingkungan Hidup di Kota Surabaya', 'Jawa',
            '20000000', 'PT Mitra', 45930, '45932', 'Baik',
        ];

        $sheet->fromArray([$row2, $row3, $row4], null, 'A2');

        $tempFile = tempnam(sys_get_temp_dir(), 'test_bmn_').'.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFile);

        try {
            // Test Dry Run first
            $this->artisan('import:assets', ['file' => $tempFile, '--dry-run' => true])
                ->expectsOutputToContain('Mode Dry-Run aktif')
                ->assertSuccessful();

            $this->assertSame(0, Asset::count());

            // Run actual import
            $this->artisan('import:assets', ['file' => $tempFile])
                ->assertSuccessful();

            $this->assertSame(2, Asset::count());
            $this->assertSame(2, AssetIdentifier::count());

            // Verify first asset (Row 2)
            $asset1 = Asset::where('item_code', '3050105015')->firstOrFail();
            $this->assertSame('Krisbow Mesin Penghancur Kertas Micro Cut S8106', $asset1->name);
            $this->assertSame('6', $asset1->nup);
            $this->assertSame('2025-10-01', $asset1->acquisition_date);
            $this->assertEquals(6604500, $asset1->value);
            $this->assertSame('Baik', $asset1->condition);
            $this->assertStringContainsString('Penyedia: PT Rekadaya', $asset1->specification);
            $this->assertStringContainsString('Pengadaan: Direktorat Pengaduan', $asset1->specification);

            // Verify second asset (Row 3 with Excel serial date)
            $asset2 = Asset::where('item_code', '3050105016')->firstOrFail();
            $this->assertSame('DJI Mavic 3 Enterprise Thermal', $asset2->name);
            $this->assertSame('12', $asset2->nup);
            $this->assertSame('2025-09-30', $asset2->acquisition_date);
            $this->assertEquals(75000000, $asset2->value);

            // Verify category creation
            $droneCategory = AssetCategory::where('name', 'Drone')->first();
            $this->assertNotNull($droneCategory);
            $this->assertSame($droneCategory->id, $asset2->category_id);

            // Verify room creation
            $room1 = Room::where('name', 'Balai Penegakan Hukum Jambi')->first();
            $this->assertNotNull($room1);
            $this->assertSame($room1->id, $asset1->room_id);

            // Verify sticker identifier
            $identifier1 = AssetIdentifier::where('asset_id', $asset1->id)->first();
            $this->assertNotNull($identifier1);
            $this->assertSame('sticker', $identifier1->identifier_type);
            $this->assertSame('1', $identifier1->identifier_value);

        } finally {
            if (file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }
    }
}
