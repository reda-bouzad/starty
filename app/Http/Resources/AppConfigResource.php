<?php

namespace App\Http\Resources;

use App\Models\AppConfig;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

/**
 * Class AppConfigResource
 * @property float $commission_attendee
 * @package App\Http\Resources
 *
 * @mixin AppConfig
 */
class AppConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     *
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            "id" => $this->id,
            "active_facebook_login" => $this->active_facebook_login,
            "active_apple_login" => $this->active_apple_login,
            "active_google_login" => $this->active_google_login,
            "active_phone_number_login" => $this->active_phone_number_login,
            "commission" => $this->commission,
            "commission_attendee" => $this->commission_attendee,
            "stripe_pk" => $this->stripe_pk,
            "android_build" => $this->android_build,
            "ios_build" => $this->ios_build,
            "sliders" => $this->getMedia('sliders')->map->getUrl()
        ];
    }
}
