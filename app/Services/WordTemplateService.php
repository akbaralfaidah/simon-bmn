<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Bast;
use App\Models\LoanRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use ZipArchive;

class WordTemplateService
{
    /**
     * Path to official SOP Word document.
     */
    protected string $sourcePath;

    /**
     * Path to prepared template with placeholders.
     */
    protected string $templatePath;

    public function __construct()
    {
        $this->sourcePath = base_path('SOP BMN/Formulir Pinjam Pakai Barang Milik Negara.docx');
        $this->templatePath = resource_path('templates/Formulir_Pinjam_Pakai_Template.docx');
    }

    /**
     * Generate filled Word document for a BAST and return the temporary file path.
     */
    public function generateBastDocument(Bast $bast): string
    {
        Settings::setOutputEscapingEnabled(true);
        $this->ensureTemplateExists();

        $processor = new TemplateProcessor($this->templatePath);
        $variables = $this->buildVariables($bast);

        // Apply digital signatures FIRST (before text replacement which may escape placeholders)
        $signatures = data_get($bast->snapshot, 'signatures', []);

        // 1. Peminjam signature
        $peminjamSigned = ! empty(data_get($signatures, 'peminjam'));
        $peminjamUserId = data_get($signatures, 'peminjam.user_id') ?? ($variables['peminjam_user_id'] ?? null);
        $peminjamSigPath = $this->resolveSignaturePath($peminjamUserId, $peminjamSigned);

        if ($peminjamSigPath && file_exists($peminjamSigPath)) {
            $processor->setImageValue('sig_peminjam', [
                'path' => $peminjamSigPath,
                'width' => 120,
                'height' => 55,
                'ratio' => false,
            ]);
        } else {
            $processor->setValue('sig_peminjam', '');
        }

        // 2. PJ Ruangan signature
        $pjSigned = ! empty(data_get($signatures, 'pj'));
        $pjUserId = data_get($signatures, 'pj.user_id') ?? ($variables['pj_user_id'] ?? null);
        $pjSigPath = $this->resolveSignaturePath($pjUserId, $pjSigned);

        if ($pjSigPath && file_exists($pjSigPath)) {
            $processor->setImageValue('sig_pj', [
                'path' => $pjSigPath,
                'width' => 90,
                'height' => 55,
                'ratio' => false,
            ]);
        } else {
            $processor->setValue('sig_pj', '');
        }

        // 3. Koordinator BMN signature
        $koorSigned = ! empty(data_get($signatures, 'koordinator'));
        $koorUserId = data_get($signatures, 'koordinator.user_id') ?? ($variables['coordinator_user_id'] ?? null);
        $koorSigPath = $this->resolveSignaturePath($koorUserId, $koorSigned);

        if ($koorSigPath && file_exists($koorSigPath)) {
            $processor->setImageValue('sig_koor', [
                'path' => $koorSigPath,
                'width' => 90,
                'height' => 55,
                'ratio' => false,
            ]);
        } else {
            $processor->setValue('sig_koor', '');
        }

        // 4. Koordinator signature below "Mengetahui" (largest, centered over line)
        if ($koorSigPath && file_exists($koorSigPath)) {
            $processor->setImageValue('sig_koor_bawah', [
                'path' => $koorSigPath,
                'width' => 170,
                'height' => 75,
                'ratio' => false,
            ]);
        } else {
            $processor->setValue('sig_koor_bawah', '');
        }

        // 5. Pengembalian signatures (always render if signed)
        if ($peminjamSigPath && file_exists($peminjamSigPath)) {
            $processor->setImageValue('sig_peminjam_bawah', [
                'path' => $peminjamSigPath,
                'width' => 100,
                'height' => 45,
                'ratio' => false,
            ]);
        } else {
            $processor->setValue('sig_peminjam_bawah', '');
        }

        if ($pjSigPath && file_exists($pjSigPath)) {
            $processor->setImageValue('sig_pj_bawah', [
                'path' => $pjSigPath,
                'width' => 80,
                'height' => 45,
                'ratio' => false,
            ]);
        } else {
            $processor->setValue('sig_pj_bawah', '');
        }

        // Now apply text values
        $processor->setValues($variables);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $filename = 'BAST-'.Str::slug($bast->bast_number, '-').'-'.time().'.docx';
        $outputPath = $tempDir.DIRECTORY_SEPARATOR.$filename;

        $processor->saveAs($outputPath);

        // 6. Post-process to make Koordinator signature "In Front of Text", centered over the line
        $zip = new ZipArchive;
        if ($zip->open($outputPath) === true) {
            $xml = $zip->getFromName('word/document.xml');
            if ($xml !== false) {
                $xml = str_replace(
                    'style="width:170px;height:75px"',
                    'style="position:absolute;z-index:251660300;mso-wrap-style:none;mso-position-horizontal:center;mso-position-horizontal-relative:text;mso-position-vertical:absolute;mso-position-vertical-relative:text;margin-top:6pt;width:170px;height:75px"',
                    $xml
                );
                $zip->addFromString('word/document.xml', $xml);
            }
            $zip->close();
        }

        return $outputPath;
    }

    /**
     * Crop transparent margins around a PNG signature so the ink fills the frame.
     */
    protected function cropSignature(string $sourcePath): string
    {
        $img = @imagecreatefrompng($sourcePath);
        if (! $img) {
            return $sourcePath;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        $minX = $w;
        $minY = $h;
        $maxX = 0;
        $maxY = 0;

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($img, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                if ($alpha < 120) {
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        if ($maxX < $minX || $maxY < $minY) {
            imagedestroy($img);

            return $sourcePath;
        }

        $pad = 10;
        $cropX = max(0, $minX - $pad);
        $cropY = max(0, $minY - $pad);
        $cropW = min($w - $cropX, ($maxX - $minX + 1) + ($pad * 2));
        $cropH = min($h - $cropY, ($maxY - $minY + 1) + ($pad * 2));

        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $transparent = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $transparent);

        imagecopy($cropped, $img, 0, 0, $cropX, $cropY, $cropW, $cropH);
        imagedestroy($img);

        $tempDir = storage_path('app/temp');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $tempPath = $tempDir.'/crop_'.basename($sourcePath);
        imagepng($cropped, $tempPath);
        imagedestroy($cropped);

        return $tempPath;
    }

    protected function resolveSignaturePath(?int $userId, bool $isSigned): ?string
    {
        if (! $isSigned || ! $userId) {
            return null;
        }

        $user = User::with('profile')->find($userId);
        $path = $user?->profile?->signature_path;

        if ($path && Storage::disk('local')->exists($path)) {
            $rawPath = Storage::disk('local')->path($path);

            return $this->cropSignature($rawPath);
        }

        return null;
    }

    /**
     * Build the associative array of values for the template variables.
     *
     * @return array<string, string>
     */
    public function buildVariables(Bast $bast): array
    {
        $loan = $bast->reference instanceof LoanRequest ? $bast->reference : null;
        $user = $loan?->user;
        $profile = $user?->profile;

        // Extract items
        $snapshotItems = data_get($bast->snapshot, 'items', []);
        $itemIds = data_get($bast->snapshot, 'item_ids', []);

        $assets = collect();
        if (! empty($snapshotItems)) {
            $assets = collect($snapshotItems);
        } elseif ($loan) {
            $assets = $loan->items()
                ->when(! empty($itemIds), fn ($query) => $query->whereIn('id', $itemIds))
                ->with('asset')
                ->get()
                ->pluck('asset')
                ->filter();
        }

        // Fetch assets from DB if snapshot items lack some attributes like brand_type
        if ($assets->isNotEmpty() && isset($assets->first()['id'])) {
            $dbAssets = Asset::whereIn('id', $assets->pluck('id'))->get()->keyBy('id');
            $assets = $assets->map(function ($item) use ($dbAssets) {
                $id = is_array($item) ? ($item['id'] ?? null) : $item->id;
                $dbAsset = $id ? $dbAssets->get($id) : null;

                return [
                    'id' => $id,
                    'name' => is_array($item) ? ($item['name'] ?? '-') : $item->name,
                    'item_code' => is_array($item) ? ($item['item_code'] ?? $dbAsset?->item_code ?? '-') : $item->item_code,
                    'nup' => is_array($item) ? ($item['nup'] ?? $dbAsset?->nup ?? '-') : $item->nup,
                    'satker_code' => is_array($item) ? ($item['satker_code'] ?? $dbAsset?->satker_code ?? '029.05.01.683416') : ($item->satker_code ?? '029.05.01.683416'),
                    'brand_type' => is_array($item) ? ($item['brand_type'] ?? $dbAsset?->brand_type ?? '-') : ($item->brand_type ?? '-'),
                    'condition' => is_array($item) ? ($item['condition'] ?? $dbAsset?->condition ?? 'Baik') : ($item->condition ?? 'Baik'),
                ];
            });
        }

        $namaBarang = $assets->pluck('name')->filter()->unique()->join(', ') ?: '-';
        $kodeBarang = $assets->pluck('item_code')->filter(fn ($v) => $v && $v !== '-')->unique()->join(', ') ?: '-';
        $nup = $assets->pluck('nup')->filter(fn ($v) => $v && $v !== '-')->unique()->join(', ') ?: '-';
        $merkType = $assets->pluck('brand_type')->filter(fn ($v) => $v && $v !== '-')->unique()->join(', ') ?: '-';
        $satkerCode = $assets->pluck('satker_code')->filter(fn ($v) => $v && $v !== '-')->unique()->first() ?: '029.05.01.683416';
        $kondisi = $assets->pluck('condition')->filter()->unique()->join(', ') ?: 'Baik';

        // Split purpose if long
        $purpose = (string) data_get($bast->snapshot, 'purpose', $loan?->purpose ?? '-');
        $purposeLine1 = $purpose;
        $purposeLine2 = '';
        if (mb_strlen($purpose) > 40) {
            $wrapped = wordwrap($purpose, 40, "\n", true);
            $lines = explode("\n", $wrapped, 2);
            $purposeLine1 = $lines[0] ?? $purpose;
            $purposeLine2 = $lines[1] ?? '';
        }

        $startDate = $loan?->start_date ? Carbon::parse($loan->start_date)->format('d/m/Y') : now()->format('d/m/Y');
        $isReturn = $bast->bast_type === 'return';

        // Peminjam details
        $peminjam = $user ?? ($loan ? $loan->user : null);
        $peminjamName = $peminjam?->name ?? '-';
        $peminjamNip = $profile?->nip ?? $peminjam?->profile?->nip ?? '-';
        $peminjamUnit = $profile?->unit?->name ?? $peminjam?->profile?->unit?->name ?? 'Balai Penegakan Hukum Lingkungan Hidup Kota Jambi';

        // Koordinator BMN details
        $coordinatorUser = $loan?->coordinator;
        if (! $coordinatorUser) {
            $coordinatorUser = User::whereHas('roleAssignments.role', fn ($q) => $q->whereIn('name', AccessScope::COORDINATORS))->first();
        }
        $approverName = $coordinatorUser?->name ?? data_get($bast->snapshot, 'approver', 'Koordinator BMN');
        $coordinatorNip = $coordinatorUser?->profile?->nip ?? '-';

        // PJ Ruangan details
        $pjUser = null;
        if ($loan) {
            $firstItem = $loan->items->first();
            if ($firstItem?->prepared_by) {
                $pjUser = User::find($firstItem->prepared_by);
            } elseif ($firstItem?->inspected_by) {
                $pjUser = User::find($firstItem->inspected_by);
            } elseif ($firstItem?->asset?->room_id) {
                $roomId = $firstItem->asset->room_id;
                $unitId = $firstItem->asset->room?->unit_id;
                $pjUser = User::whereHas('roleAssignments', function ($q) use ($roomId, $unitId) {
                    $q->whereHas('role', fn ($rq) => $rq->whereIn('name', AccessScope::KEEPERS))
                        ->where(fn ($sub) => $sub->where('room_id', $roomId)->orWhere('unit_id', $unitId));
                })->first();
            }
        }
        if (! $pjUser && $isReturn && $bast->issued_by && $bast->issued_by !== $loan?->user_id) {
            $pjUser = User::find($bast->issued_by);
        }
        $pjName = $pjUser?->name ?? ($isReturn ? data_get($bast->snapshot, 'receiver') : data_get($bast->snapshot, 'issuer')) ?? 'Penanggung Jawab Ruangan';
        if ($pjName === $approverName && $pjUser?->name) {
            $pjName = $pjUser->name;
        }

        return [
            'nama_peminjam' => $peminjamName,
            'nip_peminjam' => $peminjamNip,
            'unit_kerja' => $peminjamUnit,
            'keperluan_pinjam' => $purposeLine1,
            'keperluan_pinjam_lanjutan' => $purposeLine2,
            'kode_satker' => $satkerCode,
            'kode_barang' => $kodeBarang,
            'nup' => $nup,
            'nama_barang' => $namaBarang,
            'merk_type' => $merkType,
            'kondisi_pinjam' => $kondisi,
            'peminjam_nama' => $peminjamName,
            'tgl_peminjam' => $startDate,
            'pj_nama' => $pjName,
            'tgl_pj' => $startDate,
            'catatan_kendali' => 'Lengkap',
            'koordinator_nama' => $approverName,
            'tgl_koordinator' => $startDate,
            'pengembalian_keterangan' => $isReturn
                ? 'Barang telah diperiksa dan diserahkan kembali dalam kondisi '.$kondisi.' pada tanggal '.now()->format('d/m/Y').'.'
                : '',
            'peminjam_kembali' => $peminjamName,
            'pj_kembali' => $pjName,
            'peminjam_user_id' => $peminjam?->id,
            'pj_user_id' => $pjUser?->id,
            'coordinator_user_id' => $coordinatorUser?->id,
            'koordinator_bmn_nama' => $approverName,
            'koordinator_bmn_nip' => $coordinatorNip,
        ];
    }

    /**
     * Ensure the template with placeholders exists. If not, generate from source.
     */
    public function ensureTemplateExists(bool $force = false): void
    {
        if (! $force && file_exists($this->templatePath)) {
            $zip = new ZipArchive;
            if ($zip->open($this->templatePath) === true) {
                $docXml = $zip->getFromName('word/document.xml');
                $zip->close();
                if ($docXml && str_contains($docXml, '${sig_peminjam}') && str_contains($docXml, '${sig_koor_bawah}') && str_contains($docXml, '<w:gridCol w:w="2800"/>')) {
                    libxml_use_internal_errors(true);
                    $dom = new \DOMDocument;
                    if ($dom->loadXML($docXml)) {
                        libxml_clear_errors();

                        return;
                    }
                    libxml_clear_errors();
                }
            }
        }

        abort_unless(file_exists($this->sourcePath), 500, 'Berkas template SOP BMN tidak ditemukan di '.$this->sourcePath);

        $targetDir = dirname($this->templatePath);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $zip = new ZipArchive;
        abort_unless($zip->open($this->sourcePath) === true, 500, 'Gagal membuka berkas template SOP');

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        // 1. Unwrap AlternateContent to Fallback so all shapes are unified VML without DrawingML collisions
        $xml = preg_replace(
            '/<mc:AlternateContent>.*?<mc:Fallback>(.*?)<\/mc:Fallback>.*?<\/mc:AlternateContent>/s',
            '$1',
            $xml
        );

        // Ensure A4 Paper
        $xml = preg_replace('/<w:pgSz[^>]+>/', '<w:pgSz w:w="11906" w:h="16838" w:code="9"/>', $xml);

        preg_match_all('/<w:tr[^>]*>.*?<\/w:tr>/s', $xml, $matches);
        $rows = $matches[0];

        // Table 1
        $rows[0] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${nama_peminjam}</w:t>', $rows[0]);
        $rows[1] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${nip_peminjam}</w:t>', $rows[1]);
        $rows[2] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${unit_kerja}</w:t>', $rows[2]);
        $rows[3] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${keperluan_pinjam}</w:t>', $rows[3]);
        $rows[4] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${keperluan_pinjam_lanjutan}</w:t>', $rows[4]);
        $rows[5] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${kode_satker}</w:t>', $rows[5]);
        $rows[6] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${kode_barang}</w:t>', $rows[6]);
        $rows[7] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${nup}</w:t>', $rows[7]);
        $rows[8] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${nama_barang}</w:t>', $rows[8]);
        $rows[9] = preg_replace('/<w:t>\.{10,}<\/w:t>/', '<w:t>${merk_type}</w:t>', $rows[9]);

        $ttdCellTarget = '<w:tc><w:tcPr><w:tcW w:w="1984" w:type="dxa"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64" w:rsidP="00E21262"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr></w:p></w:tc>';
        $ttdCellPeminjam = '<w:tc><w:tcPr><w:tcW w:w="1984" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64" w:rsidP="00E21262"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${sig_peminjam}</w:t></w:r></w:p></w:tc>';
        $ttdCellPj = '<w:tc><w:tcPr><w:tcW w:w="1984" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64" w:rsidP="00E21262"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${sig_pj}</w:t></w:r></w:p></w:tc>';
        $ttdCellKoor = '<w:tc><w:tcPr><w:tcW w:w="1984" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64" w:rsidP="00E21262"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${sig_koor}</w:t></w:r></w:p></w:tc>';

        // Table 2
        // Row 12 (Kondisi & Peminjam)
        $rows[12] = preg_replace('/<w:t>\.{5,}<\/w:t>/', '<w:t>${kondisi_pinjam}</w:t>', $rows[12], 1);
        $rows[12] = preg_replace('/<w:t>\.{5,}<\/w:t>/', '<w:t></w:t>', $rows[12], 1);
        $rows[12] = str_replace('<w:t>Nama:..................................</w:t>', '<w:t>Nama: ${peminjam_nama}</w:t>', $rows[12]);
        $rows[12] = preg_replace('/(<w:tc><w:tcPr><w:tcW w:w="1843" w:type="dxa"\/><\/w:tcPr><w:p[^>]*>.*?)(<\/w:p><\/w:tc>)/s', '<w:tc><w:tcPr><w:tcW w:w="1843" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${tgl_peminjam}</w:t></w:r></w:p></w:tc>', $rows[12]);
        $rows[12] = str_replace($ttdCellTarget, $ttdCellPeminjam, $rows[12]);

        // Row 13 (PJ Ruangan)
        $rows[13] = str_replace('<w:t>Nama:..................................</w:t>', '<w:t>Nama: ${pj_nama}</w:t>', $rows[13]);
        $rows[13] = preg_replace('/(<w:tc><w:tcPr><w:tcW w:w="1843" w:type="dxa"\/><\/w:tcPr><w:p[^>]*>.*?)(<\/w:p><\/w:tc>)/s', '<w:tc><w:tcPr><w:tcW w:w="1843" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${tgl_pj}</w:t></w:r></w:p></w:tc>', $rows[13]);
        $rows[13] = str_replace($ttdCellTarget, $ttdCellPj, $rows[13]);

        // Row 14 (Catatan Kendali & Koordinator)
        $rows[14] = preg_replace('/<w:t>\.{5,}<\/w:t>/', '<w:t>${catatan_kendali}</w:t>', $rows[14], 1);
        $rows[14] = preg_replace('/<w:t>\.{5,}<\/w:t>/', '<w:t></w:t>', $rows[14], 1);
        $rows[14] = str_replace('<w:t>Nama:..................................</w:t>', '<w:t>Nama: ${koordinator_nama}</w:t>', $rows[14]);
        $rows[14] = preg_replace('/(<w:tc><w:tcPr><w:tcW w:w="1843" w:type="dxa"\/><\/w:tcPr><w:p[^>]*>.*?)(<\/w:p><\/w:tc>)/s', '<w:tc><w:tcPr><w:tcW w:w="1843" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p w:rsidR="00F81E64" w:rsidRDefault="00F81E64"><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${tgl_koordinator}</w:t></w:r></w:p></w:tc>', $rows[14]);
        $rows[14] = str_replace($ttdCellTarget, $ttdCellKoor, $rows[14]);

        foreach ($matches[0] as $idx => $origRow) {
            if (isset($rows[$idx])) {
                $xml = str_replace($origRow, $rows[$idx], $xml);
            }
        }

        // Pengembalian Textbox inside <w:txbxContent>: robust 2-column borderless table
        $pengembalianTable = '<w:p w:rsidR="002C2A00" w:rsidRPr="00F81E64" w:rsidRDefault="002C2A00"><w:pPr><w:rPr><w:b/></w:rPr></w:pPr><w:r w:rsidRPr="00F81E64"><w:rPr><w:b/></w:rPr><w:t>Pengembalian:</w:t></w:r></w:p>'
            .'<w:p w:rsidR="002C2A00" w:rsidRDefault="002C2A00"><w:r><w:t>${pengembalian_keterangan}</w:t></w:r></w:p>'
            .'<w:tbl>'
            .'  <w:tblPr><w:tblW w:w="5600" w:type="dxa"/><w:tblBorders><w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/><w:insideH w:val="none"/><w:insideV w:val="none"/></w:tblBorders></w:tblPr>'
            .'  <w:tblGrid><w:gridCol w:w="2800"/><w:gridCol w:w="2800"/></w:tblGrid>'
            .'  <w:tr>'
            .'    <w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr><w:t>Peminjam</w:t></w:r></w:p></w:tc>'
            .'    <w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr><w:t>Penanggung Jawab Ruangan</w:t></w:r></w:p></w:tc>'
            .'  </w:tr>'
            .'  <w:tr>'
            .'    <w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>${sig_peminjam_bawah}</w:t></w:r></w:p></w:tc>'
            .'    <w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:t>${sig_pj_bawah}</w:t></w:r></w:p></w:tc>'
            .'  </w:tr>'
            .'  <w:tr>'
            .'    <w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr><w:t>${peminjam_kembali}</w:t></w:r></w:p></w:tc>'
            .'    <w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/></w:tcPr><w:p><w:pPr><w:jc w:val="center"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:sz w:val="18"/></w:rPr><w:t>${pj_kembali}</w:t></w:r></w:p></w:tc>'
            .'  </w:tr>'
            .'</w:tbl>';

        $xml = preg_replace('/<w:txbxContent>.*?<\/w:txbxContent>/s', '<w:txbxContent>'.$pengembalianTable.'</w:txbxContent>', $xml);

        // Mengetahui section: place signature between title and line
        $mengetahuiTarget = '<w:p w:rsidR="004764CA" w:rsidRPr="004764CA" w:rsidRDefault="004764CA" w:rsidP="004764CA"><w:pPr><w:ind w:left="6096" w:right="140"/><w:jc w:val="left"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr></w:p><w:p w:rsidR="004764CA" w:rsidRDefault="004764CA" w:rsidP="004764CA"><w:pPr><w:ind w:left="6096" w:right="140"/><w:jc w:val="left"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr></w:p>';

        $mengetahuiReplacement = '<w:p w:rsidR="004764CA" w:rsidRPr="004764CA" w:rsidRDefault="004764CA" w:rsidP="004764CA"><w:pPr><w:ind w:left="6096" w:right="140"/><w:jc w:val="center"/><w:spacing w:before="60" w:after="60"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/></w:rPr><w:t>${sig_koor_bawah}</w:t></w:r></w:p>';

        $xml = str_replace($mengetahuiTarget, $mengetahuiReplacement, $xml);

        // Name and NIP below the line:
        $nipTarget = '<w:p w:rsidR="004764CA" w:rsidRPr="004764CA" w:rsidRDefault="004764CA" w:rsidP="004764CA"><w:pPr><w:ind w:left="6379" w:right="140"/><w:jc w:val="left"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr><w:t>NIP.</w:t></w:r></w:p>';

        $nipReplacement = '<w:p w:rsidR="004764CA" w:rsidRPr="004764CA" w:rsidRDefault="004764CA" w:rsidP="004764CA"><w:pPr><w:ind w:left="6096" w:right="140"/><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr><w:t>${koordinator_bmn_nama}</w:t></w:r></w:p><w:p w:rsidR="004764CA" w:rsidRPr="004764CA" w:rsidRDefault="004764CA" w:rsidP="004764CA"><w:pPr><w:ind w:left="6096" w:right="140"/><w:jc w:val="center"/><w:spacing w:before="0" w:after="0"/><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr></w:pPr><w:r><w:rPr><w:rFonts w:ascii="Bookman Old Style" w:hAnsi="Bookman Old Style"/><w:b/></w:rPr><w:t>NIP. ${koordinator_bmn_nip}</w:t></w:r></w:p>';

        $xml = str_replace($nipTarget, $nipReplacement, $xml);

        copy($this->sourcePath, $this->templatePath);
        $targetZip = new ZipArchive;
        abort_unless($targetZip->open($this->templatePath) === true, 500, 'Gagal menyimpan template');
        $targetZip->addFromString('word/document.xml', $xml);
        $targetZip->close();
    }
}
