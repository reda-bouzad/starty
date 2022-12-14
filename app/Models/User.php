<?php

namespace App\Models;

use App\Models\Scopes\SubscribeFinishScope;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;
use Laravel\Nova\Nova;
use Laravel\Sanctum\HasApiTokens;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


/**
 * App\Models\User
 *
 * @property int $id
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $phone_number
 * @property string|null $email
 * @property string|null $revolut_customer_id
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $password
 * @property string $firebase_uuid
 * @property string|null $gender
 * @property string|null $birth_date
 * @property string|null $description
 * @property string $user_type
 * @property int $allow_location_access
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $avatar
 * @property-read mixed $fullname
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory(...$parameters)
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAllowLocationAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBirthDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirebaseUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePhoneNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUserType($value)
 * @mixin \Eloquent
 * @property string|null $fcm_token
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\EventParticipant[] $participants
 * @property-read int|null $participants_count
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFcmToken($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Party[] $jointEvents
 * @property-read int|null $joint_events_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Party[] $likeEvents
 * @property-read int|null $like_events_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Party[] $events
 * @property-read int|null $events_count
 */
class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, Billable;

    protected $appends = ['avatar','location','selfie'];
    protected  $with = ['media'];
//    protected $withCount =['followers','follows'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
     protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'remember_token',
        'media',
        'password',
        'deleted_chats',
        'deleted_messages'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'last_location' => Point::class,
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'is_verified' => 'boolean',
        'blocked_by' => 'array',
        'blocked_user' => 'array',
        'blocked_event' => 'array',
        'deleted_chats' => 'array',
        'deleted_messages' => 'array',
        'archive_chats' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function($query){
            $query->when(!Nova::check(request()) && \Auth::user(),function(Builder $query){
                $query->whereNotIn('users.id',\Auth::user()->blocked_by ?? []);
                $query->where('type_user','!=','administrator');
            });
        });
       // static::addGlobalScope('finished',new SubscribeFinishScope());
    }

    public function newEloquentBuilder($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }

    public function getAvatarAttribute()
    {
        return $this->getFirstMediaUrl('avatar');
    }
    public function getSelfieAttribute()
    {

        return $this->getFirstMediaUrl('self_image');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile();
        $this->addMediaCollection('self_image')
            ->singleFile();
    }

    public function routeNotificationForFcm()
    {
        return $this->fcm_token;
    }


    public function getFullnameAttribute()
    {
        return ($this->firstname && $this->lastname) ? $this->firstname.' '.$this->lastname : $this->email;
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class, 'user_id');
    }
    public function jointEvents(){
        return $this->belongsToMany(Party::class,'event_participants','user_id','event_id')
            ->using(EventParticipant::class)
            ->wherePivot('payment_processing',false)
            ->withPivot(['accepted','scanned','rejected','payment_processing','payment_intent_id']);
    }

    public function likeEvents(){
        return $this->belongsToMany(Party::class,'event_likes','user_id','event_id');
    }

    public function events(){
        return $this->hasMany(Party::class,'user_id');
    }

    public function scopeInRadius(SpatialBuilder $query,$lat,$long, $radius){
        $query->whereNotNull('last_location')
            ->whereDistanceSphere('last_location', new Point($lat,$long), '<=', $radius);
    }

    public function followers(){
        return $this->belongsToMany(User::class, 'follows','user_id','follower_id')

            ->select('users.id','users.firstname','users.lastname')
            ->using(Follow::class);
    }
    public function follows(){
        return $this
            ->belongsToMany(User::class, 'follows','follower_id','user_id')
            ->select('users.id','users.firstname','users.lastname')
            ->using(Follow::class);
    }





    public function getLocationAttribute(){
        return [
            "lat" => optional($this->last_location)->latitude,
            "long" => optional($this->last_location)->longitude
        ];
    }

     public function reports()
    {
        return $this->morphToMany(Report::class, 'model','model_report');
    }

    public function createAccount(){
        $params = [
            'type' => 'express',
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
//            'email' => $this->email,
            'business_type' => 'individual',
            'metadata' => [
                "starty_user_id" => $this->id,
                "email" => $this->email,
                "phone_number" => $this->phone_number
            ]
        ];
        if($this->email){
            $params['email'] = $this->email;
        }
        $data = Cashier::stripe()->accounts->create($params);

        $this->stripe_account  = $data['id'];
        $this->save();
    }



    public function getAccount(){
        return Cashier::stripe()->accounts->retrieve($this->stripe_account);
    }


    public function scopeRegular(Builder $query){
        $query->whereNotNull('lastname')
            ->whereNotNull('firstname');
    }


}
