<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;
use JsonSerializable;

/**
 * Class NotificationResource
 * @package App\Http\Resources
 * @mixin  DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return array_merge(['id' => $this->id, 'read_at' => $this->read_at, 'created_at' => $this->created_at], $this->data);
    }
}
