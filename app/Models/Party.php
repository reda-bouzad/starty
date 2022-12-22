<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;
use MatanYadaev\EloquentSpatial\SpatialBuilder;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Objects\Polygon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\Event
 * @property  int id
 * @property  string label
 * @property \MatanYadaev\EloquentSpatial\Objects\Point|null $location
 * @property \MatanYadaev\EloquentSpatial\Objects\Geometry|null $area
 * @method static SpatialBuilder|Event newModelQuery()
 * @method static SpatialBuilder|Event newQuery()
 * @method static SpatialBuilder|Event orderByDistance(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn, string $direction = 'asc')
 * @method static SpatialBuilder|Event orderByDistanceSphere(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn, string $direction = 'asc')
 * @method static SpatialBuilder|Event query()
 * @method static SpatialBuilder|Event whereContains(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereCrosses(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereDisjoint(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereDistance(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn, string $operator, int|float $value)
 * @method static SpatialBuilder|Event whereDistanceSphere(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn, string $operator, int|float $value)
 * @method static SpatialBuilder|Event whereEquals(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereIntersects(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereOverlaps(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereTouches(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereWithin(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event withDistance(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn, string $alias = 'distance')
 * @method static SpatialBuilder|Event withDistanceSphere(string $column, \MatanYadaev\EloquentSpatial\Objects\Geometry|string $geometryOrColumn, string $alias = 'distance')
 * @mixin \Eloquent
 * @property-read mixed $images
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @property-read \App\Models\User|null $user
 */
class Party extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;
    protected $table="events";
    protected $appends = ['thumb','first_image','first_participants'];
    protected  $with = ['media'];

    protected $guarded = [];
    protected $hidden = ['media'];
    protected $withCount = ['participants'];

    protected $casts = [
        'location' => Point::class,
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'pricy' => 'boolean',
        'blocked_by' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(function($query){
          $query
              ->whereHas('user')
              ->when(!Nova::check(request()) && \Auth::user(),function(Builder $query){
                $query->whereNotIn('events.id',\Auth::user()->blocked_event ?? []);
                $query->whereNotIn('events.user_id',\Auth::user()->blocked_user ?? []);
          });
        });
        self::saving(function($model){
            if($model->uuid === null){
                $model->uuid = Str::uuid();
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->onlyKeepLatest(3);
        $this->addMediaCollection('first_image')->singleFile();
        $this->addMediaCollection('qr_code')->singleFile();
    }
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(360)
            ->optimize()
            ->performOnCollections('first_image');
    }

    public function getImagesAttribute()
    {
        return $this->getMedia('image')->map(fn($media) => [
            "url" => $media->getUrl(),
            "order" => $media->getCustomProperty('order') ?? 0
        ])->all();
    }
    public function getThumbAttribute()
    {
        return $this->getMedia('first_image')->map->getUrl('thumb')->first();
    }
    public function getFirstImageAttribute()
    {
        return $this->getFirstMediaUrl('first_image');
    }

    public function getQrCodeAttribute()
    {
        return $this->getFirstMediaUrl('qr_code');
    }


    public function newEloquentBuilder($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')
            ->select(['id','firstname','lastname'])
            ->withCount('followers','follows');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class,'event_participants', 'event_id','user_id')
            ->using(EventParticipant::class)
            ->select('users.id','lastname','firstname')
            ->withCount('followers')
            ->orderByPivot('accepted','desc')
            ->wherePivot('payment_processing',false)
            ->withPivot(['scanned','accepted','rejected','payment_intent_id','payment_processing']);
    }

    public function acceptedParticipants()
    {
        return $this->belongsToMany(User::class,'event_participants','event_id','user_id')
            ->using(EventParticipant::class)
            ->select('users.id','lastname','firstname')
            ->withCount('followers')
            ->wherePivot('accepted',true)
            ->wherePivot('payment_processing',false)
            ->withPivot(['scanned','accepted','rejected','payment_intent_id','payment_processing']);
    }
    public function rejectedParticipants()
    {
        return $this->belongsToMany(User::class,'event_participants','event_id','user_id')
            ->using(EventParticipant::class)
            ->select('users.id','lastname','firstname')
            ->wherePivot('payment_processing',false)
            ->wherePivot('rejected',true)
            ->withPivot(['scanned','accepted','rejected','payment_intent_id','payment_processing']);
    }
    public function requestedParticipants()
    {
        return $this->belongsToMany(User::class,'event_participants','event_id','user_id')
            ->using(EventParticipant::class)
            ->select('users.id','lastname','firstname')
            ->wherePivot('payment_processing',false)
            ->wherePivot('accepted',false);
    }

    public function scannedParticipants()
    {
        return $this->belongsToMany(User::class,'event_participants','event_id','user_id')
            ->using(EventParticipant::class)
            ->wherePivot('scanned',true)
            ->withPivot(['scanned','accepted','rejected']);
//            ->wherePivot('accepted',true);
    }

    public function likes(){
        return $this->belongsToMany(User::class,'event_likes','event_id','user_id');
    }

    public function generateQrcode(){
        $this->addMediaFromBase64(
            base64_encode(QrCode::format('png')
                ->style('square')
                ->size(512)
                ->generate(Str::substr($this->uuid,0,10))
            ),
            'image/png'
            )
            ->usingFileName($this->uuid.".png")
            ->toMediaCollection('qr_code');
    }

    public function getFirstParticipantsAttribute(){
        return  $this->acceptedParticipants()
            ->limit(3)
            ->select('users.id')
            ->get();
    }

    public function eventChat(){
        return $this->belongsTo(Chat::class,'chat_id');
    }

     public function reports()
    {
        return $this->morphToMany(Report::class, 'model','model_report');
    }

    public function reviews(){
        return $this->hasMany(Review::class,'event_id');

    }

    public function eventParticipants(){
        return $this->hasMany(EventParticipant::class,'event_id');
    }
}
