<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\Chat
 *
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\ChatMessage[] $messages
 * @property-read int|null $messages_count
 * @property-read \App\Models\User|null $user1
 * @property-read \App\Models\User|null $user2
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Chat query()
 * @mixin \Eloquent
 * @property int $id
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUser1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereUser2($value)
 * @property int $sender_id
 * @property string $receiver_type
 * @property int $receiver_id
 * @property mixed $unread
 * @property-read Model|\Eloquent $initiator
 * @property-read \App\Models\ChatMessage|null $lastMessage
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereReceiverType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereSenderId($value)
 * @property string $type
 * @property string|null $name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\User[] $members
 * @property-read int|null $members_count
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Chat whereType($value)
 * @method static Builder|Chat withLastMessageId()
 * @property string $state
 * @property int|null $created_by
 * @property-read \App\Models\User|null $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Party[] $event
 * @property-read int|null $event_count
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @method static Builder|Chat whereCreatedBy($value)
 * @method static Builder|Chat whereState($value)
 */
class Chat extends Model implements  HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $guarded = [];
    protected $appends = ['state'];
    protected $with = ['media'];
    protected $hidden = ['media'];


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
    }

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(function($query){
           $query->withLastMessageId()
                ->when(\Auth::user(),function(Builder $query){
                    $query->whereNotIn('id',\Auth::user()->deleted_chats ?? []);
                });
        });

        static::creating(function($model){
            if($model->created_by=== null)
            $model->created_by = \Auth::id();
        });
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'chat_id');
    }

    public function lastMessage(){
        return $this->belongsTo(ChatMessage::class,'last_message_id');
    }

    public function getUnread($user_id){
        if(!$user_id) return null;
        return ChatMessage::where('read',false)
            ->where('chat_id',$this->id)
            ->where('receiver', $user_id)
            ->count();
    }

    public function members() {
        return $this->belongsToMany(User::class,'chat_users')
            ->using(ChatUser::class)
            ->withPivot('state')
            ->select(['users.id','firstname','lastname']);
    }
    public function directMembers() {
        return $this->belongsToMany(User::class,'chat_users')
            ->using(ChatUser::class)
            ->wherePivot('state','direct')
            ->select(['users.id','firstname','lastname']);
    }
    public function requestMembers() {
        return $this->belongsToMany(User::class,'chat_users')
            ->using(ChatUser::class)
            ->wherePivot('state','request')
            ->select(['users.id','firstname','lastname']);
    }

    public function scopeWithLastMessageId(Builder $query){
        $query->addSelect([
            "last_message_id" => ChatMessage::whereColumn('chats.id','chat_id')
                ->latest()
                ->select('id')
                ->limit(1)
        ]);
    }

    public function isGroup()
    {
        return $this->type === "group";
    }

    public function createdBy(){
        return $this->belongsTo(User::class,'created_by');
    }

    public function getStateAttribute(){
        return optional(ChatUser::where([
            "chat_id" => $this->id,
            "user_id" => \Auth::id()
        ])->first(['state']))->state ?? "request";

    }

    public function event(){
        return $this->hasMany(Party::class);
    }
}
