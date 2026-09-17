<?php

namespace App\Console\Commands;

use App\Models\LoanRequest;
use App\Models\SpipRecord;
use App\Models\User;
use App\Services\AccessScope;
use App\Services\WorkflowService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('simon:reminders')]
#[Description('Pengingat jatuh tempo dan tindak lanjut, dideduplikasi per hari')]
class SimonReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = now(config('simon.reminder_timezone'))->toDateString();
        LoanRequest::whereIn('status', ['active', 'returning'])->whereDate('end_date', '<=', now(config('simon.reminder_timezone'))->addDay()->toDateString())->with(['user', 'items'])->chunkById(100, function ($loans) use ($date) {
            foreach ($loans as $loan) {
                if ($loan->user->status !== 'active' || ! $loan->items->contains(fn ($item) => in_array($item->status, ['active', 'handed_over', 'return_requested']) && ! $item->physically_received_at)) {
                    continue;
                }
                $this->send($loan->user, 'loan-'.$loan->id.'-'.$date, 'Pengingat pengembalian', 'Peminjaman #'.$loan->id.' jatuh tempo '.$loan->end_date.'. Ajukan pengembalian atau perpanjangan sesuai kebutuhan.', route('loans.show', $loan));
            }
        });
        SpipRecord::whereNotIn('status', ['resolved'])->whereNotNull('due_date')->whereDate('due_date', '<=', $date)->with('asset')->chunkById(100, function ($records) use ($date) {
            foreach ($records as $record) {
                $user = User::find($record->assigned_to ?? $record->assessor_id);
                if ($user && $user->status === 'active' && $record->asset && app(AccessScope::class)->assets($user, true)->whereKey($record->asset_id)->exists()) {
                    $this->send($user, 'spip-'.$record->id.'-'.$date, 'Tindak lanjut SPIP jatuh tempo', 'Periksa pengendalian BMN periode '.$record->period.'.', route('spip.index'));
                }
            }
        });
        $this->info('Pengingat disimpan di inbox; duplikasi harian dicegah. Tidak mengirim email.');

        return self::SUCCESS;
    }

    private function send(User $user, string $key, string $title, string $message, string $url): void
    {
        DB::transaction(function () use ($user, $key, $title, $message, $url) {
            if (DB::table('notification_receipts')->insertOrIgnore(['key' => hash('sha256', $user->id.'-'.$key), 'created_at' => now()]) === 1) {
                app(WorkflowService::class)->notify($user, $title, $message, $url);
            }
        });
    }
}
