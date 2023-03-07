<?php


namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Stepanenko3\NovaSettings\Events\SettingsDeleted;
use Stepanenko3\NovaSettings\Events\SettingsUpdated;

/**
 * App\Models\Settings
 *
 * @property int $id
 * @property string|null $slug
 * @property string|null $env
 * @property string|null $type
 * @property array|null $settings
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static Builder|Settings newModelQuery()
 * @method static Builder|Settings newQuery()
 * @method static Builder|Settings query()
 * @method static Builder|Settings whereCreatedAt($value)
 * @method static Builder|Settings whereEnv($value)
 * @method static Builder|Settings whereId($value)
 * @method static Builder|Settings whereSettings($value)
 * @method static Builder|Settings whereSlug($value)
 * @method static Builder|Settings whereType($value)
 * @method static Builder|Settings whereUpdatedAt($value)
 * @mixin Eloquent
 */
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

