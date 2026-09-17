<?php

namespace App\Console\Commands;

use App\Models\AssetStaging;
use Illuminate\Console\Command;

class ImportAssetStaging extends Command
{
    protected $signature = 'bmn:import-staging {file : Path to CSV file} {--batch= : Batch ID}';

    protected $description = 'Import raw data to asset staging table for review';

    public function handle()
    {
        $filePath = $this->argument('file');
        $batchId = $this->option('batch') ?? uniqid('batch_');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        $this->info("Starting import to staging for batch: {$batchId}");

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            $count = 0;
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                if (count($headers) == count($data)) {
                    $row = array_combine($headers, $data);
                    AssetStaging::create([
                        'import_batch_id' => $batchId,
                        'raw_data' => $row,
                        'status' => 'pending',
                    ]);
                    $count++;
                }
            }
            fclose($handle);
            $this->info("Successfully imported {$count} records to staging.");
        } else {
            $this->error('Failed to read file.');

            return 1;
        }

        return 0;
    }
}
