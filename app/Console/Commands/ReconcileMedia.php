<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMedia;
use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('simon:media-reconcile')]
#[Description('Mengirim ulang foto pending yang belum diproses worker')]
class ReconcileMedia extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Media::where('processing_status', 'queued')->where('updated_at', '<=', now()->subMinutes(2))->orderBy('id')->chunkById(50, function ($items) {
            foreach ($items as $media) {
                ProcessMedia::dispatch($media->id)->onQueue('media');
                $media->touch();
            }
        });
        $this->info('Antrean foto pending direkonsiliasi.');

        return self::SUCCESS;
    }
}
