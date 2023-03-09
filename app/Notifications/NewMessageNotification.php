<?php

namespace App\Notifications;

use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Services\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @var ChatMessage
     */
    protected ChatMessage $message;
    private array $data;
    private array $content;
    private array $title;

    /**
     * Create a new notification instance.
     *
     * @param ChatMessage $message
     */
    public function __construct(ChatMessage $message)
    {
        $this->message = $message;
        $sender_name = $this->message->userSender->lastname;


        $this->title = [
            "fr" => 'Nouveau message',
            "en" => 'New message'
        ];
        $this->content = [
            "fr" => "Vous avez reçu nouveau message de $sender_name",
            "en" => "You received a new message from $sender_name"
        ];

    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable)
    {
        \Log::info("fcm message");
        $this->message->load('chat:id,type,name');
        $this->message->load('userSender:id,firstname,lastname');
        $this->data = [
            "type" => "new.message",
            "data" => json_decode((new ChatMessageResource($this->message))->toJson(), 1)
        ];

        return FcmMessage::create()
            ->setData(["data" => json_encode($this->data)])
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($this->title[$notifiable->lang ?? App::getLocale()])
                ->setBody($this->content[$notifiable->lang ?? App::getLocale()])
            );
    }

    public function toArray($notifiable)
    {
        $this->message->load('chat:id,type,name');
        $this->message->load('userSender:id,firstname,lastname');
        $this->data = [
            "type" => "new.message",
            "data" => json_decode((new ChatMessageResource($this->message))->toJson(), 1)
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}
