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

class AccountValidateNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $status;
    private array $title;
    private array $content;

    public function __construct($status)
    {
        $this->status = $status;

        $this->title = [
            "fr" =>  'Compte de paiement validé',
            "en" => 'Payment account validated'
        ];

        $this->content = [
            "fr" => "Felicitation vous pouvez maintenant créer des soirées payantes et percevoir votre argent.",
            "en" =>"Congratulation, you can now create payed parties and get your money."
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
            "type" => "account.updated",
            "data" => [
                "stripe_account_status" => $this->status
            ]
        ];
        return   FcmMessage::create()
            ->setData(["data" => json_encode($this->data)])
            ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
                ->setTitle($this->title[$notifiable->lang ?? App::getLocale()])
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
            "type" => "account.updated",
            "data" => [
                "stripe_account_status" => $this->status
            ]
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}
