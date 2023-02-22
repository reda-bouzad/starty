<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $devise
 * @property float $price
 */
class PriceCategory extends Model
{
    protected $fillable = ['devise', 'price', 'name', 'event_id'];
    use HasFactory;

    public function event()
    {
        return $this->belongsTo(Party::class);
    }
}
