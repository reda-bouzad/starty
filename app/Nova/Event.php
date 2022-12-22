<?php

namespace App\Nova;

use DKulyk\Nova\Tabs;
use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Ebess\AdvancedNovaMediaLibrary\Fields\Media;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\Heading;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;
use MatanYadaev\EloquentSpatial\Objects\Point;
use Stepanenko3\NovaJson\JSON;

class Event extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\Party::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'label';

    public static function label()
    {
        return "Fêtes";
    }


    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Heading::make('<p class="text-danger">* Tous les champs sont requis.</p>')->asHtml(),
            ID::make()->sortable(),

            BelongsTo::make('Utilisateur', 'user', User::class)->searchable(),

            Text::make('Libellé', 'label'),

            Select::make('Type')->options(array('public' => 'Public', 'private' => 'Privé'))->nullable(),

            Number::make('Nombre de participants', 'nb_participants'),

            Number::make('Nombre de place restantes', 'remaining_participants'),

            Number::make('Contact', 'contact'),

            Boolean::make('Payant', 'pricy'),

            Currency::make('Prix', 'price')->default(fn() => 0),

            DateTime::make('Début', 'start_at'),

            DateTime::make('Fin', 'end_at'),

            Textarea::make('Adresse', 'address'),

            Textarea::make('Description'),
            Text::make('latitude','lat')->rules(['required','numeric', 'between:-90,90'])
                ->default(fn () => optional($this->location)->latitude),
            Text::make('longitude','long')->rules(['required','numeric', 'between:-180,180'])
                ->default(fn () => optional($this->location)->longitude),
            Images::make('QrCode ticket','qr_code')->readonly(),
            BelongsToMany::make('Participants', 'participants', User::class),
            BelongsToMany::make('Signalement','reports',Report::class)


        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function cards(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function filters(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function lenses(NovaRequest $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Laravel\Nova\Http\Requests\NovaRequest $request
     * @return array
     */
    public function actions(NovaRequest $request)
    {
        return [];
    }
}
