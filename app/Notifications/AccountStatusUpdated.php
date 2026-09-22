<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountStatusUpdated extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $status,
        public string $reason,
        public ?string $role = null,
        public ?string $unitName = null,
        public ?string $updatedBy = null,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $subject = match ($this->status) {
            'active' => 'Akun SIMON BMN Anda Telah Diaktifkan',
            'suspended' => 'Pemberitahuan Penangguhan Akun SIMON BMN',
            default => 'Pembaruan Status Akun SIMON BMN',
        };

        $rootUrl = config('app.url', 'http://bmn-gakkum-jambi.test');
        $loginUrl = rtrim($rootUrl, '/').'/login';

        return (new MailMessage)
            ->subject($subject.' - Balai Gakkum Sumatera')
            ->view('emails.account-status-updated', [
                'user' => $notifiable,
                'status' => $this->status,
                'reason' => $this->reason,
                'role' => $this->role,
                'unitName' => $this->unitName,
                'updatedBy' => $this->updatedBy,
                'loginUrl' => $loginUrl,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'reason' => $this->reason,
            'role' => $this->role,
            'unit_name' => $this->unitName,
            'updated_by' => $this->updatedBy,
        ];
    }
}
