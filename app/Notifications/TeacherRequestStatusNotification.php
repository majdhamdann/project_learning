<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeacherRequestStatusNotification extends Notification
{
    use Queueable;

    private $subjectName;
    private $status;

    public function __construct($subjectName, $status)
    {
        $this->subjectName = $subjectName;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'subject_name' => $this->subjectName,
            'status' => $this->status,
            'message' => "Your request to join subject {$this->subjectName} has been {$this->status}"
        ];
    }
}
