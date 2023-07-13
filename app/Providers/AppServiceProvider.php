<?php

namespace App\Providers;

use App\Models\Party;
use App\Models\User;
use App\Observers\EventObserver;
use App\Observers\UserObserver;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        ResourceCollection::withoutWrapping();
        $this->Observers();
        $this->Partys();


    }

    private function Observers():void
    {
        User::Observe(UserObserver::class);
    }
    private function Partys():void
    {
        Party::Observe(EventObserver::class);
    }
}
