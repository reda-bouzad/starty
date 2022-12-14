<?php

namespace App\Nova\Dashboards;

use App\Nova\Metrics\NbUsers;
use App\Nova\Metrics\UserTypes;
use Laravel\Nova\Dashboard;

class Statistics extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards()
    {
        return [
            //
        ];
    }

    /**
     * Get the URI key for the dashboard.
     *
     * @return string
     */
    public function uriKey()
    {
        return 'statistics';
    }

    public function name()
    {
        return 'Tableau de bord';
    }
}
