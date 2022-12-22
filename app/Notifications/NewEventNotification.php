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

class NewEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var Event
     */
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


        $this->title =[
            "fr" =>  'Nouvelle soirée prêt de chez vous',
            "en" => 'New party around you'
        ];
        $this->content = [
            "fr" => "Une nouvelle soirée ({$event->label}) prêt de chez vous a été ajouté",
            "en" => "A party ({$event->label}) around you has been added"
        ];

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database',FcmChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     */
    public function toFcm($notifiable)
    {
        $this->data = [
            "type" => "new.events",
            "data" => json_decode((new EventResource($this->event))->toJson(),1)
        ];
        return     FcmMessage::create()
        ->setData(["data" => json_encode($this->data)])
        ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
            ->setTitle($this->title[$notifiable->lang ?? App::getLocale()])
            ->setImage($this->event->thumb)
            ->setBody($this->content[$notifiable->lang ?? App::getLocale()])
        );

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
            "type" => "new.events",
            "data" => json_decode((new EventResource($this->event))->toJson(),1)
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
            ], $this->data);
    }
}
