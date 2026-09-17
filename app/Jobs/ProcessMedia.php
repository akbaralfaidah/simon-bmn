<?php

namespace App\Jobs;

use App\Services\MediaService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMedia implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $mediaId)
    {
        $this->afterCommit();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        app(MediaService::class)->processQueued($this->mediaId);
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }
}
