<?php

namespace App\Nova\Metrics;

use App\Models\User;
use DateInterval;
use DateTimeInterface;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Metrics\Partition;
use Laravel\Nova\Metrics\PartitionResult;

class UserTypes extends Partition
{

    public $name = 'Utiliateurs par types';

    /**
     * Calculate the value of the metric.
     *
     * @param NovaRequest $request
     * @return PartitionResult
     */
    public function calculate(NovaRequest $request): PartitionResult
    {
        return $this->count($request, User::class, 'user_type');
    }

    /**
     * Determine the amount of time the results of the metric should be cached.
     *
     * @return DateTimeInterface|DateInterval|float|int|null
     */
    public function cacheFor():DateInterval|float|DateTimeInterface|int|null
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
        return 'user-types';
    }
}
