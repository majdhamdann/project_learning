<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
//إشعار للمعلم عند طلب اشتراك بمادة من قبل طالب
class NewSubscriptionRequestNotification extends Notification
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
        return [FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        return FcmMessage::create()
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle("طلب اشتراك جديد")
                ->setBody("الطالب {$this->studentName} طلب الانضمام لمادة {$this->subjectName}")
            )
            ->setData([
                'type' => 'subscription_request',
                'subject' => $this->subjectName,
            ]);
    }
}
