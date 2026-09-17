<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use ZipArchive;

class SpreadsheetReader
{
    public function read(UploadedFile $file, ?string $sheet = null): array
    {
        app(UploadScanner::class)->assertClean($file->getRealPath());
        if (strtolower($file->getClientOriginalExtension()) !== 'xlsx') {
            $stream = fopen($file->getRealPath(), 'r');
            $rows = [];
            try {
                while (($row = fgetcsv($stream, 100000, ',', '"', '')) !== false) {
                    if ($row === [null]) {
                        continue;
                    }
                    $rows[] = $row;
                    $this->limit(count($rows) <= 1001 && count($row) <= 100, 'Maksimal 1.000 baris data dan 100 kolom.');
                }
            } finally {
                fclose($stream);
            }

            return $rows;
        }
        $zip = new ZipArchive;
        $this->limit($zip->open($file->getRealPath()) === true, 'Workbook tidak valid atau terenkripsi.');
        $expanded = 0;
        try {
            $this->limit($zip->numFiles <= 10000, 'Workbook memiliki terlalu banyak entri.');
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $entry = $zip->statIndex($index);
                $expanded += $entry['size'];
                $this->limit($expanded <= 50 * 1024 * 1024 && $entry['size'] <= 20 * 1024 * 1024, 'Ukuran hasil ekstraksi workbook melebihi batas aman.');
                $this->limit(! preg_match('#vbaProject|externalLinks/|embeddings/|\.bin$|\.\.[/\\\\]#i', $entry['name']), 'Makro, tautan eksternal, atau objek tertanam tidak diizinkan.');
            }
        } finally {
            $zip->close();
        }
        $reader = new Xlsx;
        $sheets = $reader->listWorksheetInfo($file->getRealPath());
        $selected = collect($sheets)->firstWhere('worksheetName', $sheet ?: ($sheets[0]['worksheetName'] ?? ''));
        $this->limit($selected !== null, 'Sheet tidak ditemukan. Isi nama sheet dengan tepat.');
        $this->limit($selected['totalRows'] <= 1001 && $selected['totalColumns'] <= 100, 'Sheet maksimal 1.000 baris data dan 100 kolom.');
        $reader->setLoadSheetsOnly($selected['worksheetName']);
        $reader->setReadDataOnly(false);
        $book = $reader->load($file->getRealPath());
        try {
            $rows = [];
            foreach ($book->getActiveSheet()->getRowIterator() as $row) {
                $values = [];
                foreach ($row->getCellIterator('A', $selected['lastColumnLetter']) as $cell) {
                    $this->limit($cell->getDataType() !== DataType::TYPE_FORMULA, 'Formula tidak diizinkan. Salin sebagai nilai pada workbook impor.');
                    $raw = $cell->getValue();
                    $this->limit($cell->getDataType() !== DataType::TYPE_NUMERIC || strlen(preg_replace('/[^0-9]/', '', (string) $raw)) <= 15, 'Nomor panjang harus disimpan sebagai teks di Excel.');
                    $value = $raw === null ? '' : (string) $cell->getFormattedValue();
                    $this->limit(mb_strlen($value) <= 5000, 'Isi sel terlalu panjang.');
                    $values[] = $value;
                }
                if (array_filter($values, fn ($value) => $value !== '') !== []) {
                    $rows[] = $values;
                }
            }

            return $rows;
        } finally {
            $book->disconnectWorksheets();
        }
    }

    private function limit(bool $allowed, string $message): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages(['file' => $message]);
        }
    }
}
