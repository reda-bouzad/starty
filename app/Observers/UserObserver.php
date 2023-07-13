<?php

namespace App\Observers;

use App\Models\User;
use Laravel\Nova\Notifications\NovaNotification;

class UserObserver
{
    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        $this->getNovaNotification($user,'Nouveau user: ', 'success');
    }

    /**
     * Handle the User "updated" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function updated(User $user)
    {
        $this->getNovaNotification($user,'Update user: ', 'success');
    }

    /**
     * Handle the User "deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function deleted(User $user)
    {
        $this->getNovaNotification($user,'Deleted user: ', 'error');
    }

    /**
     * Handle the User "restored" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function restored(User $user)
    {
        $this->getNovaNotification($user,'Restored user: ', 'success');
    }

    /**
     * Handle the User "force deleted" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function forceDeleted(User $user)
    {
        $this->getNovaNotification($user,'Force Deleted user: ', 'error');
    }

    public function getNovaNotification($user, $message, $type):void{
        foreach (User::all()as $u){
            $u-> notify(NovaNotification::make()->message($message.' '.$u->username.' '.$u->lastname)
                ->icon('user')->type($type));
        }
    }
}
