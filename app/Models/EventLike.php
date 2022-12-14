<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\EventLike
 *
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventLike whereUserId($value)
 */
class EventLike extends Model
{
    use HasFactory;
}
