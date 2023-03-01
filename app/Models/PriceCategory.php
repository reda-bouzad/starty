<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PhpParser\Builder;

/**
 * @property int $id
 * @property string $devise
 * @property float $price
 * @mixin Eloquent
 * @mixin Builder
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
