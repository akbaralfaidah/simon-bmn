<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssignmentUpdated extends Notification
{
    use Queueable;

    public function __construct(
        public string $action,
        public string $roleName,
        public string $reason,
        public ?string $unitName = null,
        public ?string $roomName = null,
        public ?string $endsAt = null,
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
        $subject = match ($this->action) {
            'revoked' => 'Pencabutan Mandat Penugasan SIMON BMN',
            default => 'Pembaruan Mandat Penugasan SIMON BMN',
        };

        return (new MailMessage)
            ->subject($subject.' - Balai Gakkum Sumatera')
            ->view('emails.assignment-updated', [
                'user' => $notifiable,
                'action' => $this->action,
                'roleName' => $this->roleName,
                'reason' => $this->reason,
                'unitName' => $this->unitName,
                'roomName' => $this->roomName,
                'endsAt' => $this->endsAt,
                'updatedBy' => $this->updatedBy,
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
            'action' => $this->action,
            'role_name' => $this->roleName,
            'reason' => $this->reason,
            'unit_name' => $this->unitName,
            'room_name' => $this->roomName,
            'ends_at' => $this->endsAt,
            'updated_by' => $this->updatedBy,
        ];
    }
}
