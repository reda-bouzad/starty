<?php

namespace App\Nova\Actions;

use App\Notifications\SelfieVerificationReminderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class SelfieVerificationReminder extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = "Relance Image Selfie";
    /**
     * Perform the action on the given models.
     *
     * @param ActionFields $fields
     * @param Collection $models
     * @return string[]
     */
    public function handle(ActionFields $fields, Collection $models): array
    {
        \Notification::send($models, new SelfieVerificationReminderNotification());
        return Action::message('La notification a bien été envoyée');
    }

    /**
     * Get the fields available on the action.
     *
     * @param NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request): array
    {
        return [];
    }
}
