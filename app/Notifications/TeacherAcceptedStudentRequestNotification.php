<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TeacherAcceptedStudentRequestNotification extends Notification
{
    use Queueable;

    private $teacherName;
    private $subjectName;

    public function __construct($teacherName, $subjectName)
    {
        $this->teacherName = $teacherName;
        $this->subjectName = $subjectName;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'teacher_name' => $this->teacherName,
            'subject_name' => $this->subjectName,
            'message' => "المعلم {$this->teacherName} وافق على طلبك للاشتراك في مادة {$this->subjectName}",
        ];
    }
}
