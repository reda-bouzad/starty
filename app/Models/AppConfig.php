<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * App\Models\AppConfig
 *
 * @property int $id
 * @property int $active_facebook_login
 * @property int $active_google_login
 * @property int $active_apple_login
 * @property int $active_phone_number_login
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection|\Spatie\MediaLibrary\MediaCollections\Models\Media[] $media
 * @property-read int|null $media_count
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig query()
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereActiveAppleLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereActiveFacebookLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereActiveGoogleLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereActivePhoneNumberLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AppConfig whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AppConfig extends Model implements  HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    protected $hidden = ['media'];


    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('sliders');
    }
}
