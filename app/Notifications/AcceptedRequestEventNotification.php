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

class AcceptedRequestEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Party $event;
    private array $data;
    private array $title;
    private array $content;

    /**
     * Create a new notification instance.
     *
     * @param Event $event
     */
    public function __construct(Party $event)
    {
        $this->event = $event;


        $this->title = [
            "fr" => 'Participation acceptée',
            "en" => 'Party joined'
        ];
        $this->content = [
            "fr" => ($event->type == "public" ? "Felicitation vous avez rejoint la soirée '{$event->label}'. " : "Felicitation votre de demande participation a été accepté '{$event->label}'.") . " Fait scanner ton QRcode à l'entrée grace au bouton TICKET dans les détails de la soirée pour accéder!",
            "en" => ($event->type === "public" ? "Congratulation, you joined the party '{$event->label}. " : "Congratulation, Your request to join the party '{$event->label}' has been accepted.") . " Scan your QRcode  at the entrance with the button TICKET in the party detail to participate!"
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
        $this->data = [
            "type" => "join.events",
            "data" => json_decode((new EventResource($this->event))->toJson())
        ];
        return FcmMessage::create()
            ->setData(["data" => json_encode($this->data)])
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
            "type" => "join.events",
            "data" => json_decode((new EventResource($this->event))->toJson())
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}
