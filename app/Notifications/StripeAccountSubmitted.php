<?php

namespace App\Notifications;

use App\Services\FcmMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;
use NotificationChannels\Fcm\FcmChannel;

class StripeAccountSubmitted extends Notification implements  ShouldQueue
{
    use Queueable;

    /**
     * @var string[]
     */
    private array $title;
    private array $content;
    private $state;

    public function __construct($state)
    {
        $this->state = $state;

        $this->title =[
            "fr" =>  'Demande envoyé',
            "en" => 'Application submitted'
        ];
        $this->content = [
            "fr" => "Fecilitation vos informations ont été soumis à Stripe notre partenaire de paiement sécurisé, et votre compte de paiement est en attente de validation.",
            "en" =>"Congratualation, your application has been submitted to Stripe our secure payment partner, and your payment account will be review for validation"
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
                "stripe_account_status" => $this->state
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
                "stripe_account_status" => $this->state
            ]
        ];
        return array_merge([
            "title" => $this->title,
            "content" => $this->content,
        ], $this->data);
    }
}
