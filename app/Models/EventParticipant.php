<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * App\Models\EventParticipant
 *
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant query()
 * @property int $id
 * @property int $user_id
 * @property int $event_id
 * @property int $ticket_id
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
 * @property bool $rejected
 * @property string|null $payment_intent_id
 * @property bool $payment_processing
 * @property string|null $status
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant wherePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant wherePaymentProcessing($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|EventParticipant whereTicketId($value)
 * @mixin \Eloquent
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

    public static function getElement(int $event_id, int $user_id, int $ticket_id)
    {
        return EventParticipant::query()->where('event_id', $event_id)
            ->where('ticket_id', $ticket_id)
            ->where('user_id', $user_id)->first();
    }
}
