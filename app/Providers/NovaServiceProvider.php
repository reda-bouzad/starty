<?php

namespace App\Providers;

use App\Nova\Administrator;
use App\Nova\AppConfig;
use App\Nova\Dashboards\Statistics;
use App\Nova\Event;
use App\Nova\EventReport;
use App\Nova\Organizer;
use App\Nova\User;
use App\Nova\Report;
use App\Nova\ModelReport;
use App\Nova\UserReport;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;
use Laravel\Nova\Dashboards\Main;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;
use Stepanenko3\NovaSettings\NovaSettingsTool;
use Stepanenko3\NovaSettings\Resources\Settings;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        Nova::footer(function ($request) {
            return Blade::render('<p class="mt-8 text-center text-xs text-80">
                <a href="#" class="text-primary dim no-underline">Starty</a>
                <span class="px-1">&middot;</span>
                &copy; ' . date('Y') . '<span class="px-1">&middot;</span>
                v1</p>');
        });

        Nova::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard(Main::class)->icon('home'),

                MenuSection::make('Utilisateurs', [
                    MenuItem::resource(Administrator::class),
                    MenuItem::resource(User::class),
                ])->icon('user')->collapsable(),

                MenuSection::make('Evènements', [
                    MenuItem::resource(Event::class)
                ])
                    ->icon('event')->collapsable(),
                MenuSection::make('Signalements', [
                    MenuItem::resource(Report::class),
                    MenuItem::resource(UserReport::class),
                    MenuItem::resource(EventReport::class)
                ]),
                MenuSection::make('Paramètres', [
                    MenuItem::resource(AppConfig::class),
                    MenuItem::resource(  Organizer::class),


                ])->icon('settings')->collapsable(),
            ];
        });
    }

    /**
     * Register the Nova routes.
     *
     * @return void
     */
    protected function routes()
    {
        Nova::routes()
            ->withAuthenticationRoutes()
            ->withPasswordResetRoutes()
            ->register();
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     *
     * @return void
     */
    protected function gate()
    {
        Gate::define('viewNova', function ($user) {
            return $user->user_type === "administrator";
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array
     */
    protected function dashboards()
    {
        return [
            new \App\Nova\Dashboards\Main,
            //new Statistics
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array
     */
    public function tools()
    {
        return [
            new \Stepanenko3\NovaSettings\NovaSettingsTool(),
        ];
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
