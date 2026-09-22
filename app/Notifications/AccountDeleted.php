<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountDeleted extends Notification
{
    use Queueable;

    public function __construct(
        public string $userName,
        public string $userEmail,
        public string $reason,
        public ?string $deletedBy = null,
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
        return (new MailMessage)
            ->subject('Penghapusan Akun Pengguna SIMON BMN - Balai Gakkum Sumatera')
            ->view('emails.account-deleted', [
                'userName' => $this->userName,
                'userEmail' => $this->userEmail,
                'reason' => $this->reason,
                'deletedBy' => $this->deletedBy,
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
            'user_name' => $this->userName,
            'user_email' => $this->userEmail,
            'reason' => $this->reason,
            'deleted_by' => $this->deletedBy,
        ];
    }
}
