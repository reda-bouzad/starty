<?php

namespace App\Notifications;

use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Models\Party;
use App\Models\User;
use App\Services\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class JoinEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private Party $event;
    private array $data;
    private array $title;
    private array $content;
    private User $user;

    /**
     * Create a new notification instance.
     *
     * @param Event $event
     */
    public function __construct(Party $event, $user)
    {
        $this->event = $event;
        $this->user = $user;


        $this->title = [
            "fr" => $event->type === "private" ? 'Nouvelle demande' : 'Nouveau participant',
            "en" => $event->type === "private" ? 'New request' : 'New participant'
        ];
        $this->content = [
            "fr" => $event->type === "private" ? "Un utilisateur demande à rejoindre votre soirée '{$event->label}'" : "Un nouveau participant à rejoint votre soirée '{$event->label}'",
            "en" => $event->type === "private" ? "A new user request to participate to your party '{$event->label}'" : "A new participant joint the party '{$event->label}"
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
        $eventData = json_decode((new EventResource($this->event))->toJson(), 1);
        $eventData['join_user'] = json_decode((new UserResource($this->user))->toJson(), 1);
        $this->data = [
            "type" => "join.events",
            "data" => $eventData
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
        $eventData = json_decode((new EventResource($this->event))->toJson(), 1);
        $eventData['join_user'] = json_decode((new UserResource($this->user))->toJson(), 1);
        $this->data = [
            "type" => "join.events",
            "data" => $eventData
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}
