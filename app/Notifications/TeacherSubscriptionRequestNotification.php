<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
//إشعار للـ Admin عند طلب اشتراك بمادة من قبل معلم
class TeacherSubscriptionRequestNotification extends Notification
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
        return [FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setNotification(
                \NotificationChannels\Fcm\Resources\Notification::create()
                    ->setTitle('طلب اشتراك معلم جديد')
                    ->setBody("المعلم {$this->teacherName} طلب الانضمام لمادة {$this->subjectName}")
            )
            ->setData([
                'type' => 'teacher_subscription_request',
                'subject' => $this->subjectName
            ]);
    }
}
