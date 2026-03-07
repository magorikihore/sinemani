<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentReplyNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected string $replierName,
        protected int $episodeId,
        protected int $commentId
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'comment_reply',
            'title' => 'New Reply',
            'message' => "{$this->replierName} replied to your comment.",
            'episode_id' => $this->episodeId,
            'comment_id' => $this->commentId,
        ];
    }
}
