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


        $this->title = [
            "fr" => "Relance!!",
            "en" => "Reminder!!"
        ];
        $this->content = [
            "fr" => "Votre selfie de securité n’est pas conforme pour la certification de votre compte.Rendez vous dans vos paramètres afin de la modifier.",
            "en" => "Your security selfie is not compliant for your account certification. Please go to your settings to change it."
        ];
//        dd($this->title, $this->content, $this->data);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannel::class, 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     */
    public function toFcm($notifiable)
    {

        return FcmMessage::create()
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($this->title[$notifiable->lang ?? App::getLocale()])
                ->setBody($this->content[$notifiable->lang ?? App::getLocale()])
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
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
