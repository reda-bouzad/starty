<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\EventParticipant
 *
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Party $event
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $user
 * @property-read int|null $user_count
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereUserId($value)
 * @property bool $scanned
 * @property bool $accepted
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereAccepted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereScanned($value)
 */
class EventParticipant extends Pivot
{
    use HasFactory;
    protected $table = "event_participants";
    protected $casts = [
        "scanned" => "boolean",
        "accepted" => "boolean",
        "rejected" => "boolean",
        'payment_processing' => 'boolean'
    ];
    protected $guarded = [];

    public function event()
    {
        return $this->belongsTo(Party::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function  getElement( int $event_id, int $user_id){
        return EventParticipant::query()->where('event_id',$event_id)
            ->where('user_id',$user_id)
            ->first();
    }
}
