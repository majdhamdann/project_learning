<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
//إشعار لجميع الطلاب عند إنشاء تحدي من قبل معلم
class NewChallengeNotification extends Notification
{
    use Queueable;

    private $teacherName;
    private $subjectName;
    private $challengeTitle;

    public function __construct($teacherName, $subjectName, $challengeTitle)
    {
        $this->teacherName = $teacherName;
        $this->subjectName = $subjectName;
        $this->challengeTitle = $challengeTitle;
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
                    ->setTitle('تحدي جديد')
                    ->setBody("المعلم {$this->teacherName} أنشأ تحدي '{$this->challengeTitle}' في مادة {$this->subjectName}")
            )
            ->setData([
                'type' => 'new_challenge',
                'subject' => $this->subjectName,
                'challenge' => $this->challengeTitle
            ]);
    }
}
