<?php

namespace App\Http\Resources;

use App\Models\Chat;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class ChatResource
 * @package App\Http\Resources
 *
 * @mixin Chat
 */
class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return array_filter([
            "id" =>$this->id,
            "type" => $this->type,
            "name" => $this->name,
            "state" =>$this->state,
            "members_count" => $this->members_count,
            "last_message" => new ChatMessageResource($this->whenLoaded("lastMessage")),
            'other_members' => UserResource::collection($this->whenLoaded('members')),
            "unread" => optional($this->resource)->getUnread(\Auth::id()),
            "created_by" => $this->created_by,
            "avatar" => $this->getFirstMediaUrl('avatar'),
            "created_at" => $this->created_at,
            "event" =>  EventResource::collection($this->whenLoaded('event'))
        ],fn($value) => $value !==null);
    }
}
