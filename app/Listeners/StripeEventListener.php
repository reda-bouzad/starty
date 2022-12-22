<?php

namespace App\Listeners;

use App\Events\AccountUpdate;
use App\Models\EventParticipant;
use App\Models\User;
use App\Notifications\AccountRequiredActionNotification;
use App\Notifications\AccountValidateNotification;
use App\Notifications\StripeAccountSubmitted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class StripeEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {

        \Log::info("stripe webhook: ".$event->payload['type']);
        if($event->payload['type'] === "account.updated"){
            $data = $event->payload['data']['object'];
            $metadata = $data['metadata'];

            if(Arr::get($metadata,'starty_user_id')){
                $user = User::find($metadata['starty_user_id']);
                $user->stripe_merchant_country = $data['country'];

                if($data['payouts_enabled']) {
                    if($user->stripe_account_status === "enable") return;
                    $user->stripe_account_status = "enable";
                    $user->notify(new AccountValidateNotification($user->stripe_account_status));
                }elseif(count($data['requirements']['errors'])>0 || count($data['requirements']['eventually_due'])>0){
                    $previous = $user->stripe_account_stats;
                    $user->stripe_account_status = "action_required";
                    if($previous !==  null){
                        $user->notify(new AccountRequiredActionNotification($user->stripe_account_status));
                    }
                } elseif ($data['details_submitted']){
                    if($user->stripe_account_status === "processed") return;
                    $user->stripe_account_status = "processed";
                    $user->notify(new StripeAccountSubmitted($user->stripe_account_status));
                }else{
                    $user->stripe_account_status = "processing";
                }

            \Log::info('c\'est save');
                $user->save();
                event(new AccountUpdate($user->id));
            }
        }
        if($event->payload['type'] === 'checkout.session.completed'){
            Log::info('');
            $data = $event->payload['data']['object'];
            $metadata = $data['metadata'];
            $event_participant = EventParticipant::firstOrCreate([
                "event_id" => $metadata['stripe_event_id'],
                "user_id" => $metadata['stripe_user_id']
            ]);
            $event_participant->payment_intent_id = $data['payment_intent'];
            $event_participant->payment_processing = false;
            Log::info('update payment');
            $event_participant->save();
        }

    }


}
