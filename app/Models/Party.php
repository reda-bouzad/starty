<?php

namespace App\Models;

use Auth;
use Carbon\Carbon;
use Eloquent;
use Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Nova\Nova;
use MatanYadaev\EloquentSpatial\Objects\Geometry;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * App\Models\Event
 *
 * @property int id
 * @property int|null price
 * @property string label
 * @property string devise
 * @property string type
 * @property bool|null pricy
 * @property Carbon start_at
 * @property Carbon end_at
 * @property Carbon|null $created_at
 * @property Point|null $location
 * @property Geometry|null $area
 * @method static SpatialBuilder|Event newModelQuery()
 * @method static SpatialBuilder|Event newQuery()
 * @method static SpatialBuilder|Event orderByDistance(string $column, Geometry|string $geometryOrColumn, string $direction = 'asc')
 * @method static SpatialBuilder|Event orderByDistanceSphere(string $column, Geometry|string $geometryOrColumn, string $direction = 'asc')
 * @method static SpatialBuilder|Event query()
 * @method static SpatialBuilder|Event whereContains(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereCrosses(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereDisjoint(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereDistance(string $column, Geometry|string $geometryOrColumn, string $operator, int|float $value)
 * @method static SpatialBuilder|Event whereDistanceSphere(string $column, Geometry|string $geometryOrColumn, string $operator, int|float $value)
 * @method static SpatialBuilder|Event whereEquals(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereIntersects(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereOverlaps(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereTouches(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event whereWithin(string $column, Geometry|string $geometryOrColumn)
 * @method static SpatialBuilder|Event withDistance(string $column, Geometry|string $geometryOrColumn, string $alias = 'distance')
 * @method static SpatialBuilder|Event withDistanceSphere(string $column, Geometry|string $geometryOrColumn, string $alias = 'distance')
 * @mixin Builder
 * @property-read mixed $images
 * @property-read MediaCollection|Media[] $media
 * @property-read int|null $media_count
 * @property-read User|null $user
 * @property int $user_id
 * @property int $nb_participants
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $remaining_participants
 * @property string|null $contact
 * @property string|null $address
 * @property string|null $uuid
 * @property string|null $share_link
 * @property int|null $chat_id
 * @property array|null $blocked_by
 * @property float $rating
 * @property string|null $phone_number
 * @property string|null $stripe_product_id
 * @property string|null $stripe_price_id
 * @property string|null $long
 * @property string|null $lat
 * @property string|null $is_visible
 * @property string|null $event_visible
 * @property-read Collection<int, User> $acceptedParticipants
 * @property-read int|null $accepted_participants_count
 * @property-read Chat|null $eventChat
 * @property-read Collection<int, EventParticipant> $eventParticipants
 * @property-read int|null $event_participants_count
 * @property-read mixed $first_image
 * @property-read mixed $first_participants
 * @property-read mixed $qr_code
 * @property-read mixed $thumb
 * @property-read Collection<int, User> $likes
 * @property-read int|null $likes_count
 * @property-read Collection<int, User> $participants
 * @property-read int|null $participants_count
 * @property-read Collection<int, PriceCategory> $price_categories
 * @property-read int|null $price_categories_count
 * @property-read Collection<int, User> $rejectedParticipants
 * @property-read int|null $rejected_participants_count
 * @property-read Collection<int, Report> $reports
 * @property-read int|null $reports_count
 * @property-read Collection<int, User> $requestedParticipants
 * @property-read int|null $requested_participants_count
 * @property-read Collection<int, Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read Collection<int, User> $scannedParticipants
 * @property-read int|null $scanned_participants_count
 * @method static SpatialBuilder|Party whereAddress($value)
 * @method static SpatialBuilder|Party whereBlockedBy($value)
 * @method static SpatialBuilder|Party whereChatId($value)
 * @method static SpatialBuilder|Party whereContact($value)
 * @method static SpatialBuilder|Party whereCreatedAt($value)
 * @method static SpatialBuilder|Party whereDescription($value)
 * @method static SpatialBuilder|Party whereDevise($value)
 * @method static SpatialBuilder|Party whereEndAt($value)
 * @method static SpatialBuilder|Party whereId($value)
 * @method static SpatialBuilder|Party whereLabel($value)
 * @method static SpatialBuilder|Party whereLat($value)
 * @method static SpatialBuilder|Party whereLocation($value)
 * @method static SpatialBuilder|Party whereLong($value)
 * @method static SpatialBuilder|Party whereNbParticipants($value)
 * @method static SpatialBuilder|Party wherePhoneNumber($value)
 * @method static SpatialBuilder|Party wherePrice($value)
 * @method static SpatialBuilder|Party wherePricy($value)
 * @method static SpatialBuilder|Party whereRating($value)
 * @method static SpatialBuilder|Party whereRemainingParticipants($value)
 * @method static SpatialBuilder|Party whereShareLink($value)
 * @method static SpatialBuilder|Party whereStartAt($value)
 * @method static SpatialBuilder|Party whereStripePriceId($value)
 * @method static SpatialBuilder|Party whereStripeProductId($value)
 * @method static SpatialBuilder|Party whereType($value)
 * @method static SpatialBuilder|Party whereUpdatedAt($value)
 * @method static SpatialBuilder|Party whereUserId($value)
 * @method static SpatialBuilder|Party whereUuid($value)
 * @mixin Eloquent
 */
class Party extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = "events";
    protected $appends = ["thumb", "first_image", "first_participants"];
    protected $with = ["media"];

    protected $guarded = [];
    protected $hidden = ["media"];
    protected $withCount = ["participants"];

    protected $casts = [
        "location" => Point::class,
        "start_at" => "datetime",
        "end_at" => "datetime",
        "pricy" => "boolean",
        "blocked_by" => "array",
    ];

    protected static function boot()
    {
        parent::boot();
        static::addGlobalScope(function ($query) {
            $query
                ->whereHas("user")
                ->when(!Nova::check(request()) && Auth::user(), function (
                    Builder $query
                ) {
                    $query->whereNotIn(
                        "events.id",
                        Auth::user()->blocked_event ?? []
                    );
                    $query->whereNotIn(
                        "events.user_id",
                        Auth::user()->blocked_user ?? []
                    );
                });
        });
        self::saving(function ($model) {
            if ($model->uuid === null) {
                $model->uuid = Str::uuid();
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection("image")->onlyKeepLatest(3);
        $this->addMediaCollection("first_image")->singleFile();
        $this->addMediaCollection("qr_code")->singleFile();
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion("thumb")
            ->width(360)
            ->optimize()
            ->performOnCollections("first_image");
    }

    public function getImagesAttribute()
    {
        return $this->getMedia("image")
            ->map(
                fn($media) => [
                    "url" => $media->getUrl(),
                    "order" => $media->getCustomProperty("order") ?? 0,
                ]
            )
            ->all();
    }

    public function price_categories()
    {
        return $this->hasMany(PriceCategory::class, "event_id");
    }

    public function getThumbAttribute()
    {
        return $this->getMedia("first_image")
            ->map->getUrl("thumb")
            ->first();
    }

    public function getFirstImageAttribute()
    {
        return $this->getFirstMediaUrl("first_image");
    }

    public function getQrCodeAttribute()
    {
        return $this->getFirstMediaUrl("qr_code");
    }

    public function newEloquentBuilder($query): SpatialBuilder
    {
        return new SpatialBuilder($query);
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id")
            ->select([
                "id",
                "firstname",
                "lastname",
                "show_pseudo_only",
                "pseudo",
            ])
            ->withCount("followers", "follows");
    }

    public function participants()
    {
        return $this->belongsToMany(
            User::class,
            "event_participants",
            "event_id",
            "user_id"
        )
            ->using(EventParticipant::class)
            ->select("users.id", "lastname", "firstname")
            ->withCount("followers")
            ->orderByPivot("accepted", "desc")
            ->wherePivot("payment_processing", false)
            ->withPivot([
                "scanned",
                "accepted",
                "rejected",
                "payment_intent_id",
                "payment_processing",
                "is_visible",
            ]);
    }

    public function acceptedParticipants()
    {
        return $this->belongsToMany(
            User::class,
            "event_participants",
            "event_id",
            "user_id"
        )
            ->using(EventParticipant::class)
            ->select("users.id", "lastname", "firstname")
            ->withCount("followers")
            ->wherePivot("accepted", true)
            ->wherePivot("payment_processing", false)
            ->withPivot([
                "scanned",
                "accepted",
                "rejected",
                "payment_intent_id",
                "payment_processing",
                "is_visible",
            ]);
    }

    public function rejectedParticipants()
    {
        return $this->belongsToMany(
            User::class,
            "event_participants",
            "event_id",
            "user_id"
        )
            ->using(EventParticipant::class)
            ->select("users.id", "lastname", "firstname")
            ->wherePivot("payment_processing", false)
            ->wherePivot("rejected", true)
            ->withPivot([
                "scanned",
                "accepted",
                "rejected",
                "payment_intent_id",
                "payment_processing",
            ]);
    }

    public function requestedParticipants()
    {
        return $this->belongsToMany(
            User::class,
            "event_participants",
            "event_id",
            "user_id"
        )
            ->using(EventParticipant::class)
            ->select("users.id", "lastname", "firstname")
            ->wherePivot("payment_processing", false)
            ->wherePivot("accepted", false);
    }

    public function scannedParticipants()
    {
        return $this->belongsToMany(
            User::class,
            "event_participants",
            "event_id",
            "user_id"
        )
            ->using(EventParticipant::class)
            ->wherePivot("scanned", true)
            ->withPivot(["scanned", "accepted", "rejected"]);
        //            ->wherePivot('accepted',true);
    }

    public function likes()
    {
        return $this->belongsToMany(
            User::class,
            "event_likes",
            "event_id",
            "user_id"
        );
    }

    public function generateQrcode()
    {
        $this->addMediaFromBase64(
            base64_encode(
                QrCode::format("png")
                    ->style("square")
                    ->size(512)
                    ->generate(Str::substr($this->uuid, 0, 10))
            ),
            "image/png"
        )
            ->usingFileName($this->uuid . ".png")
            ->toMediaCollection("qr_code");
    }

    public function getFirstParticipantsAttribute()
    {
        return $this->acceptedParticipants()
            ->limit(3)
            ->select("users.id")
            ->get();
    }

    public function eventChat()
    {
        return $this->belongsTo(Chat::class, "chat_id");
    }

    public function reports()
    {
        return $this->morphToMany(Report::class, "model", "model_report");
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, "event_id");
    }

    public function eventParticipants()
    {
        return $this->hasMany(EventParticipant::class, "event_id");
    }
}
