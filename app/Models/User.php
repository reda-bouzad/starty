<?php

namespace App\Models;

use Auth;
use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Subscription;
use Laravel\Nova\Nova;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use MatanYadaev\EloquentSpatial\Objects\Geometry;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;


/**
 * App\Models\User
 *
 * @property int $id
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $phone_number
 * @property string|null $email
 * @property Carbon|null $email_verified_at
 * @property string|null $password
 * @property string|null $pseudo
 * @property string|null $address
 * @property float|null $preferred_radius
 * @property string|null $lang
 * @property point|null $last_location
 * @property string|null $revolut_customer_id
 * @property string $firebase_uuid
 * @property string|null $gender
 * @property string|null $birth_date
 * @property string|null $description
 * @property string $user_type
 * @property boolean show_pseudo_only
 * @property int $allow_location_access
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read mixed $avatar
 * @property-read mixed $fullname
 * @property-read MediaCollection|Media[] $media
 * @property-read int|null $media_count
 * @property-read DatabaseNotificationCollection|DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection|PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static UserFactory factory(...$parameters)
 * @method static Builder|User newModelQuery()
 * @method static Builder|User newQuery()
 * @method static Builder|User query()
 * @method static Builder|User whereAllowLocationAccess($value)
 * @method static Builder|User whereBirthDate($value)
 * @method static Builder|User whereCreatedAt($value)
 * @method static Builder|User whereDescription($value)
 * @method static Builder|User whereEmail($value)
 * @method static Builder|User whereEmailVerifiedAt($value)
 * @method static Builder|User whereFirebaseUuid($value)
 * @method static Builder|User whereFirstname($value)
 * @method static Builder|User whereGender($value)
 * @method static Builder|User whereId($value)
 * @method static Builder|User whereLastname($value)
 * @method static Builder|User wherePassword($value)
 * @method static Builder|User wherePhoneNumber($value)
 * @method static Builder|User whereUpdatedAt($value)
 * @method static Builder|User whereUserType($value)
 * @property string|null $fcm_token
 * @property-read Collection|EventParticipant[] $participants
 * @property-read int|null $participants_count
 * @method static Builder|User whereFcmToken($value)
 * @property-read Collection|Party[] $jointEvents
 * @property-read int|null $joint_events_count
 * @property-read Collection|Party[] $likeEvents
 * @property-read int|null $like_events_count
 * @property-read Collection|Party[] $events
 * @property-read int|null $events_count
 * @property string|null $stripe_id
 * @property string|null $pm_type
 * @property string|null $pm_last_four
 * @property string|null $trial_ends_at
 * @property bool $is_verified
 * @property string|null $deleted_at
 * @property array|null $blocked_by
 * @property array|null $blocked_user
 * @property array|null $blocked_event
 * @property array|null $deleted_chats
 * @property array|null $deleted_messages
 * @property string|null $stripe_account
 * @property string|null $stripe_account_status
 * @property array|null $archive_chats
 * @property string|null $stripe_merchant_country
 * @property string|null $stripe_customer_id
 * @property string|null $remember_token
 * @property-read Collection<int, User> $followers
 * @property-read int|null $followers_count
 * @property-read Collection<int, User> $follows
 * @property-read int|null $follows_count
 * @property-read mixed $location
 * @property-read mixed $selfie
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, Subscription> $subscriptions
 * @property-read int|null $subscriptions_count
 * @method static SpatialBuilder|User inRadius($lat, $long, $radius)
 * @method static SpatialBuilder|User orderByDistance(string $column, Geometry|string $geometryOrColumn, string $direction = 'asc')
 * @method static SpatialBuilder|User orderByDistanceSphere(string $column, Geometry|string $geometryOrColumn, string $direction = 'asc')
 * @method static SpatialBuilder|User regular()
 * @method static SpatialBuilder|User whereAddress($value)
 * @method static SpatialBuilder|User whereArchiveChats($value)
 * @method static SpatialBuilder|User whereBlockedBy($value)
 * @method static SpatialBuilder|User whereBlockedEvent($value)
 * @method static SpatialBuilder|User whereBlockedUser($value)
 * @method static SpatialBuilder|User whereContains(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User whereCrosses(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User whereDeletedAt($value)
 * @method static SpatialBuilder|User whereDeletedChats($value)
 * @method static SpatialBuilder|User whereDeletedMessages($value)
 * @method static SpatialBuilder|User whereDisjoint(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User whereDistance(string $column, Geometry|string $geometryOrColumn, string $operator, int|float $value)
 * @method static SpatialBuilder|User whereDistanceSphere(string $column, Geometry|string $geometryOrColumn, string $operator, int|float $value)
 * @method static SpatialBuilder|User whereEquals(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User whereIntersects(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User whereIsVerified($value)
 * @method static SpatialBuilder|User whereLang($value)
 * @method static SpatialBuilder|User whereLastLocation($value)
 * @method static SpatialBuilder|User whereOverlaps(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User wherePmLastFour($value)
 * @method static SpatialBuilder|User wherePmType($value)
 * @method static SpatialBuilder|User wherePreferredRadius($value)
 * @method static SpatialBuilder|User wherePseudo($value)
 * @method static SpatialBuilder|User whereRememberToken($value)
 * @method static SpatialBuilder|User whereRevolutCustomerId($value)
 * @method static SpatialBuilder|User whereShowPseudoOnly($value)
 * @method static SpatialBuilder|User whereStripeAccount($value)
 * @method static SpatialBuilder|User whereStripeAccountStatus($value)
 * @method static SpatialBuilder|User whereStripeCustomerId($value)
 * @method static SpatialBuilder|User whereStripeId($value)
 * @method static SpatialBuilder|User whereStripeMerchantCountry($value)
 * @method static SpatialBuilder|User whereTouches(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User whereTrialEndsAt($value)
 * @method static SpatialBuilder|User whereWithin(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|User withDistance(string $column, Geometry|string $geometryOrColumn, string $alias = 'distance')
 * @method static SpatialBuilder|User withDistanceSphere(string $column, Geometry|string $geometryOrColumn, string $alias = 'distance')
 * @mixin Eloquent
 */
class User extends Authenticatable implements HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, InteractsWithMedia, Billable, SoftDeletes;

    protected $appends = ['avatar', 'location', 'selfie'];
    protected $with = ['media'];
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
        'deleted_messages',
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
        'show_pseudo_only' => 'boolean',
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

        static::addGlobalScope(function ($query) {
            $query->when(!Nova::check(request()) && Auth::user(), function (Builder $query) {
                $query->whereNotIn('users.id', Auth::user()->blocked_by ?? []);
                $query->where('user_type', '!=', 'administrator');
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
        return ($this->firstname && $this->lastname) ? $this->firstname . ' ' . $this->lastname : $this->email;
    }

    public function participants()
    {
        return $this->hasMany(EventParticipant::class, 'user_id');
    }

    public function jointEvents()
    {
        return $this->belongsToMany(Party::class, 'event_participants', 'user_id', 'event_id')
            ->using(EventParticipant::class)
            ->wherePivot('payment_processing', false)
            ->where('end_at', '>', now()->subDay())
            ->withPivot(['accepted', 'scanned', 'rejected', 'payment_processing', 'payment_intent_id']);
    }

    public function likeEvents()
    {
        return $this->belongsToMany(Party::class, 'event_likes', 'user_id', 'event_id');
    }

    public function events()
    {
        return $this->hasMany(Party::class, 'user_id');
    }

    public function scopeInRadius(SpatialBuilder $query, $lat, $long, $radius)
    {
        $query->whereNotNull('last_location')
            ->whereDistanceSphere('last_location', new Point($lat, $long), '<=', $radius);
    }

    public function followers()
    {
        return $this->belongsToMany(User::class, 'follows', 'user_id', 'follower_id')
            ->select('users.id', 'users.firstname', 'users.lastname')
            ->using(Follow::class);
    }

    public function follows()
    {
        return $this
            ->belongsToMany(User::class, 'follows', 'follower_id', 'user_id')
            ->select('users.id', 'users.firstname', 'users.lastname')
            ->using(Follow::class);
    }


    public function getLocationAttribute()
    {
        return [
            "lat" => optional($this->last_location)->latitude,
            "long" => optional($this->last_location)->longitude
        ];
    }

    public function reports()
    {
        return $this->morphToMany(Report::class, 'model', 'model_report');
    }

    public function createAccount()
    {
        $params = [
            'type' => 'express',
            /*'capabilities' => [
                'card_payments' => ['requested' => false],
                'transfers' => ['requested' => false],
            ],*/
//            'email' => $this->email,
            'business_type' => 'individual',
            'metadata' => [
                "starty_user_id" => $this->id,
                "email" => $this->email,
                "phone_number" => $this->phone_number
            ]
        ];
        if ($this->email) {
            $params['email'] = $this->email;
        }
        $data = Cashier::stripe()->accounts->create($params);

        $this->stripe_account = $data['id'];
        $this->save();
    }


    public function getAccount()
    {
        return Cashier::stripe()->accounts->retrieve($this->stripe_account);
    }

    public function scopeRegular(Builder $query)
    {
        $query->whereNotNull('lastname')
            ->whereNotNull('firstname');
    }


}
