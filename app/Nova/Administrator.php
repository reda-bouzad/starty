<?php

namespace App\Nova;

use Ebess\AdvancedNovaMediaLibrary\Fields\Images;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Hidden;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Password;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Administrator extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = \App\Models\User::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'fullname';

    public static function label()
    {
        return "Administrateurs";
    }

    public static $clickAction = 'preview';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'firstname','id','username'
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make()->sortable(),

            Images::make('Avatar')
                ->showOnPreview()
                ->textAlign('left')
            ,

            Text::make('Prénom', 'firstname')
                ->sortable()
                ->rules('max:255')
                ->textAlign('left')
                ->placeholder('Entrer le prénom')
                ->showOnPreview(),

            Text::make('Nom', 'lastname')
                ->sortable()
                ->rules('max:255')
                ->textAlign('left')
                ->placeholder('Entrer le nom')
                ->showOnPreview(),

            Text::make('Email')
                ->sortable()
                ->rules('required', 'email', 'max:254')
                ->creationRules('unique:users,email')
                ->updateRules('unique:users,email,{{resourceId}}')
                ->placeholder("Entrer l'email")
                ->textAlign('left')
                ->showOnPreview(),

            Hidden::make('user_type')->default('administrator')
                ->showOnPreview(),

            Password::make('Password')
                ->onlyOnForms()
                ->creationRules('required', 'string', 'min:8')
                ->placeholder('Entrer le mot de passe')
                ->updateRules('nullable', 'string', 'min:8'),
        ];
    }

    /**
     * Get the cards available for the request.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function cards(Request $request)
    {
        return [];
    }

    /**
     * Get the filters available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function filters(Request $request)
    {
        return [];
    }

    /**
     * Get the lenses available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function lenses(Request $request)
    {
        return [];
    }

    /**
     * Get the actions available for the resource.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function actions(Request $request)
    {
        return [];
    }

    public static function indexQuery(NovaRequest $request, $query)
    {
        return $query->where('user_type', 'administrator');
    }
}
