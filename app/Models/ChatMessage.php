<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\ChatMessage
 *
 * @property-read \App\Models\Chat|null $chat
 * @property-read mixed $files
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage query()
 * @property int $id
 * @property int $chat_id
 * @property int $sender
 * @property int $receiver
 * @property int $read
 * @property string|null $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereReceiver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereSender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereUpdatedAt($value)
 * @property-read \App\Models\User|null $userReceiver
 * @property-read \App\Models\User $userSender
 * @property int|null $response_to
 * @property-read ChatMessage|null $responseToMessage
 * @method static \Illuminate\Database\Eloquent\Builder|ChatMessage whereResponseTo($value)
 * @mixin \Eloquent
 */
class ChatMessage extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $appends = ['files'];

    protected $guarded = [];
    protected $hidden = ['media'];
    protected $casts = [
        "receiver" => "int",
        "read" => "boolean",
        "response_to" => "int"
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(function($query){
            $query->when(\Auth::user(),function(Builder $query){
                    $query->whereNotIn('id',\Auth::user()->deleted_messages ?? []);
                });
        });

    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file');
    }

    public function getFilesAttribute()
    {
        return $this->getMedia('files')->map->getUrl();
    }

    public function chat()
    {
        return $this->belongsTo(Chat::class, 'chat_id');
    }

    public function userSender(){
        return $this->belongsTo(User::class,'sender');
    }
    public function userReceiver(){
        return $this->belongsTo(User::class,'receiver');
    }

    public function responseToMessage(){
        return $this->belongsTo(ChatMessage::class,'response_to');
    }
}

