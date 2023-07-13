<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\EventPerDay;
use App\Nova\Metrics\EventTrend;
use App\Nova\Metrics\UserCount;
use App\Nova\Metrics\UserPerRole;
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
            new EventTrend(),
            new EventPerDay(),
            new UserPerRole()
        ];
    }
}
