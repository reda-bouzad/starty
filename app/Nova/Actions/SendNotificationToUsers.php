<?php

namespace App\Nova\Actions;

use App\Models\User;
use App\Notifications\SendNotificationToUsersNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class SendNotificationToUsers extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = "Notifer les utilisateurs";

    /**
     * Perform the action on the given models.
     *
     * @param \Laravel\Nova\Fields\ActionFields $fields
     * @param \Illuminate\Support\Collection<int, User> $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $title = [
            "fr" => $fields->title_fr,
            "en" => $fields->title_en,
        ];
        $content = [
            "fr" => $fields->content_fr,
            "en" => $fields->content_en,
        ];

        \Notification::send($models, (new SendNotificationToUsersNotification($title, $content))->allOnQueue('notification'));
        return Action::message('Notification en cours d\'envoie');
    }

    /**
     * Get the fields available on the action.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Titre en français', 'title_fr')->rules(['required', 'max:50']),
            Text::make('Titre en Anglais', 'title_en')->rules(['required', 'max:50']),
            Textarea::make('Contenu en français', 'content_fr')->rules(['required', 'max:300']),
            Textarea::make('Contenu en Anglais', 'content_en')->rules(['required', 'max:300']),
        ];
    }
}
