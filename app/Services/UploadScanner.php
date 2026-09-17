<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use Symfony\Component\Process\Process;

class UploadScanner
{
    public function assertClean(string $path): void
    {
        if (config('simon.scanner') === 'development' && app()->environment(['local', 'testing'])) {
            return;
        }
        if (config('simon.scanner') !== 'clamav') {
            throw ValidationException::withMessages(['file' => 'Pemindai keamanan belum tersedia. Unggahan ditahan. Hubungi pengelola.']);
        }
        try {
            $process = new Process([config('simon.scanner_binary'), '--no-summary', '--infected', '--', $path]);
            $process->setTimeout(45);
            $process->run();
            $exit = $process->getExitCode();
        } catch (\Throwable) {
            $exit = 2;
        }
        if ($exit !== 0) {
            throw ValidationException::withMessages(['file' => $exit === 1 ? 'Berkas ditolak oleh pemindai keamanan.' : 'Pemindai tidak dapat memeriksa berkas. Coba lagi setelah layanan pulih.']);
        }
    }
}
