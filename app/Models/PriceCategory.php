<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use PhpParser\Builder;

/**
 * App\Models\PriceCategory
 *
 * @property int $id
 * @property string $devise
 * @property float $price
 * @mixin Builder
 * @property int $event_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Party $event
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory whereDevise($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PriceCategory whereUpdatedAt($value)
 * @mixin Eloquent
 */
class PriceCategory extends Model
{
    protected $fillable = ['devise', 'price', 'name', 'event_id'];
    use HasFactory;

    public function event(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }
}
