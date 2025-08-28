<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification
{
    use Queueable;

    private $userName;
    private $lessonTitle;
    private $commentContent;

    public function __construct($userName, $lessonTitle, $commentContent)
    {
        $this->userName       = $userName;
        $this->lessonTitle    = $lessonTitle;
        $this->commentContent = $commentContent;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'user_name'    => $this->userName,
            'lesson_title' => $this->lessonTitle,
            'comment'      => $this->commentContent,
            'message'      => "{$this->userName} أضاف تعليقًا على درس '{$this->lessonTitle}': {$this->commentContent}"
        ];
    }
}
