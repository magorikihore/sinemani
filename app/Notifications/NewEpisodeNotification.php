<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewEpisodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $dramaTitle,
        protected int $episodeNumber,
        protected int $dramaId
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'new_episode',
            'title' => 'New Episode Available!',
            'message' => "Episode {$this->episodeNumber} of \"{$this->dramaTitle}\" is now available.",
            'drama_id' => $this->dramaId,
            'episode_number' => $this->episodeNumber,
        ];
    }
}
