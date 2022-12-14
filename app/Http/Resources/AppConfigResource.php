<?php

namespace App\Http\Resources;

use App\Models\AppConfig;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class AppConfigResource
 * @package App\Http\Resources
 *
 * @mixin AppConfig
 */
class AppConfigResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     *
     */
    public function toArray($request)
    {
        return [
            "id" => $this->id,
            "active_facebook_login" => $this->active_facebook_login,
            "active_apple_login" => $this->active_apple_login,
            "active_google_login" => $this->active_google_login,
            "active_phone_number_login" => $this->active_phone_number_login,
            "revolut_pk" => $this->revolut_pk,
            "commission" => $this->commision,
            "stripe_pk" => $this->stripe_pk,
            "android_build" => $this->android_build,
            "ios_build" => $this->ios_build,
            "sliders" => $this->getMedia('sliders')->map->getUrl()
        ];
    }
}
