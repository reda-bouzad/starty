<?php

namespace App\Notifications;

use App\Http\Resources\EventResource;
use App\Models\Party;
use App\Services\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class SelfieVerificationReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    use Queueable;

    private array $data;
    private array $title;
    private array $content;

    /**
     * Create a new notification instance.
     *
     * @param Party $event
     */
    public function __construct()
    {


        $this->title =[
            "fr" =>  "Relance!!",
            "en" => "Reminder!!"
        ];
        $this->content = [
            "fr" => "Votre image selfie n'est pas adéquate pour la validation de votre compte, aller dans votre profil et mettez une  image selfie claire nous permettant de valider votre compte svp!",
            "en" => "Your selfie image is not adequate for the validation of your account, go to your profile and put a clear selfie image allowing us to validate your account please!"
        ];
//        dd($this->title, $this->content, $this->data);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannel::class,'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toFcm($notifiable)
    {

        return   FcmMessage::create()
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($this->title[$notifiable->lang ?? App::getLocale()])
                ->setBody($this->content[$notifiable->lang ?? App::getLocale()])
            )->setApns(\NotificationChannels\Fcm\Resources\ApnsConfig::create()
                ->setPayload([
                    "aps" =>[
                        "contentAvailable" => true
                    ]
                ])
                ->setHeaders([
                    "apns-push-type"=> "background",
                    "apns-priority"=> "5", // Must be `5` when `contentAvailable` is set to true.
                    "apns-topic"=> "io.flutter.plugins.firebase.messaging", // bundle identifier
                ]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $this->data = [
            "type" => "users.selfie",
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}
