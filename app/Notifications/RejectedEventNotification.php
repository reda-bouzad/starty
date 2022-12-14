<?php

namespace App\Notifications;

use App\Http\Resources\EventResource;
use App\Models\Party;
use App\Services\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class RejectedEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Party $event;
    private array $data;
    private array $title;
    private array $content;

    /**
     * Create a new notification instance.
     *
     * @param Party $event
     */
    public function __construct(Party $event)
    {
        $this->event = $event;


        $this->title =[
            "fr" =>  'Requête rejectée',
            "en" => 'Access denied'
        ];
        $this->content = [
            "fr" => "La requête de participation à la soirée '{$event->label}' a été rejectée",
            "en" => "The request to participate to the party '{$event->label}' has been rejected"
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
        $this->data = [
            "type" => "join.events",
            "data" => json_decode((new EventResource($this->event))->toJson())
        ];
        return   FcmMessage::create()
            ->setData(["data" => json_encode($this->data)])
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
            "type" => "join.events",
            "data" => json_decode((new EventResource($this->event))->toJson())
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}