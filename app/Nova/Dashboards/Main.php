<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\EventTrend;
use App\Nova\Metrics\UserCount;
use Laravel\Nova\Dashboards\Main as Dashboard;

class Main extends Dashboard
{

    public function name(): string
    {
        return 'Tableau de bord';
    }

    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards(): array
    {
        return [
            new UserCount(),
            new EventTrend()
        ];
    }
}
