<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
//إشعار للطالب عند قبول طلب الاشتراك من قبل المعلم
class SubscriptionRequestStatusNotification extends Notification
{
    use Queueable;

    private $status;
    private $subjectName;

    public function __construct($status, $subjectName)
    {
        $this->status = $status;
        $this->subjectName = $subjectName;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        $title = $this->status === 'accepted' ? 'تم قبول طلبك' : 'تم رفض طلبك';
        $body  = "طلب الاشتراك في مادة {$this->subjectName} {$this->status}";

        return FcmMessage::create()
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($title)
                ->setBody($body)
            )
            ->setData([
                'type' => 'subscription_response',
                'status' => $this->status,
                'subject' => $this->subjectName,
            ]);
    }
}
