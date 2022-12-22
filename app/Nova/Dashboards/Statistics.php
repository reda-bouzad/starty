<?php

namespace App\Nova\Dashboards;

use Laravel\Nova\Dashboard;

class Statistics extends Dashboard
{
    /**
     * Get the cards for the dashboard.
     *
     * @return array
     */
    public function cards(): array
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
    public function uriKey(): string
    {
        return 'statistics';
    }

    public function name(): string
    {
        return 'Tableau de bord';
    }
}
