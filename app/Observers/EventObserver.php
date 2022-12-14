<?php

namespace App\Observers;

use App\Models\Chat;
use App\Models\Party;

class EventObserver
{
    /**
     * Handle the Party "created" event.
     *
     * @param  \App\Models\Party  $event
     * @return void
     */
    public function created(Party $event)
    {
        if($event->chat_id !== null) return;
       $chat =  Chat::create([
            "created_by" =>$event->user_id,
            "name" => $event->label,
            "type" => "group",
        ]);
       $event->chat_id = $chat->id;
       $event->saveQuietly();
       if($event->getFirstMediaUrl('first_image')!= null){
           $chat->addMediaFromUrl($event->getFirstMediaUrl('first_image'));
       }
       $chat->members()->attach($event->user_id, ["state" => "direct"]);
    }

    /**
     * Handle the Party "updated" event.
     *
     * @param  \App\Models\Party  $event
     * @return void
     */
    public function updated(Party $event)
    {
        //
    }

    /**
     * Handle the Party "deleted" event.
     *
     * @param  \App\Models\Party  $event
     * @return void
     */
    public function deleted(Party $event)
    {
        //
    }

    /**
     * Handle the Party "restored" event.
     *
     * @param  \App\Models\Party  $event
     * @return void
     */
    public function restored(Party $event)
    {
        //
    }

    /**
     * Handle the Party "force deleted" event.
     *
     * @param  \App\Models\Party  $event
     * @return void
     */
    public function forceDeleted(Party $event)
    {
        //
    }
}
