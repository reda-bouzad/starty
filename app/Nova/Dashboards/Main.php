<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\EventTrend;
use App\Nova\Metrics\NbUsers;
use App\Nova\Metrics\UserTypes;
use Laravel\Nova\Cards\Help;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{

    public function name()
    {
        return 'Tableau de bord';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            new UserTypes,
            new NbUsers,
            new EventTrend()
        ];
    }
}
