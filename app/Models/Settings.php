<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Stepanenko3\NovaSettings\Events\SettingsDeleted;
use Stepanenko3\NovaSettings\Events\SettingsUpdated;

class Settings extends Model
{


    protected $casts = [
        'fields' => 'array',
        'settings' => 'array',
    ];

    protected $fillable = [
        'slug',
        'env',
        'type',
        'fields',
        'settings',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'saved' => SettingsUpdated::class,
        'deleted' => SettingsDeleted::class,
    ];


}

