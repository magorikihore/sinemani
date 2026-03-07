<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CoinReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected int $amount,
        protected string $reason
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'coins_received',
            'title' => 'Coins Received!',
            'message' => "You received {$this->amount} coins. Reason: {$this->reason}",
            'amount' => $this->amount,
        ];
    }
}
