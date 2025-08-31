<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentSubjectRequestNotification extends Notification
{
    use Queueable;

    private $studentName;
    private $subjectName;

    public function __construct($studentName, $subjectName)
    {
        $this->studentName = $studentName;
        $this->subjectName = $subjectName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'student_name' => $this->studentName,
            'subject_name' => $this->subjectName,
           'message' => "Student {$this->studentName} requested to enroll in subject {$this->subjectName}"
        ];
    }
}
