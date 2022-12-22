<?php

namespace App\Nova\Metrics;

use App\Models\Party;
use DateInterval;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Value;
use Laravel\Nova\Metrics\ValueResult;

class EventTrend extends Value
{
    /**
     * Calculate the value of the metric.
     *
     * @param NovaRequest $request
     * @return ValueResult
     */
    public function calculate(NovaRequest $request): ValueResult
    {
        return $this->count($request, Party::class);
    }

    /**
     * Get the ranges available for the metric.
     *
     * @return array
     */
    public function ranges(): array
    {
        return [
            30 => __('30 Days'),
            60 => __('60 Days'),
            365 => __('365 Days'),
            'TODAY' => __('Today'),
            'MTD' => __('Month To Date'),
            'QTD' => __('Quarter To Date'),
            'YTD' => __('Year To Date'),
        ];
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return DateInterval|float|DateTimeInterface|int|null
     */
    public function cacheFor(): DateInterval|float|DateTimeInterface|int|null
    {
        // return now()->addMinutes(5);
        return null;
    }

    /**
     * Get the URI key for the metric.
     *
     * @return string
     */
    public function uriKey(): string
    {
        return 'event-trend';
    }

    public function name(): string
    {
        return "Nbre de fêtes";
    }
}
