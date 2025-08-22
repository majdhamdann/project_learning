<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
//إشعار للمعلم عند قبول طلبه من قبل الـ Admin
class AdminSubscriptionApprovalNotification extends Notification
{
    use Queueable;

    private $subjectName;

    public function __construct($subjectName)
    {
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
                    ->setTitle('تم قبول طلبك من قبل الإدارة')
                    ->setBody("تمت الموافقة على طلبك للاشتراك في مادة {$this->subjectName}")
            )
            ->setData([
                'type' => 'admin_subscription_approval',
                'subject' => $this->subjectName
            ]);
    }
}
