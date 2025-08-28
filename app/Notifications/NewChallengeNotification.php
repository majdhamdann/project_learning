<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewChallengeNotification extends Notification
{
    use Queueable;

    private $teacherName;
    private $challengeTitle;
    private $startTime;

    public function __construct($teacherName, $challengeTitle, $startTime)
    {
        $this->teacherName   = $teacherName;
        $this->challengeTitle = $challengeTitle;
        $this->startTime      = $startTime;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'teacher_name'   => $this->teacherName,
            'challenge_title'=> $this->challengeTitle,
            'start_time'     => $this->startTime,
            'message'        => "المعلم {$this->teacherName} أنشأ تحديًا جديدًا بعنوان '{$this->challengeTitle}' يبدأ في {$this->startTime}",
        ];
    }
}
