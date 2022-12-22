<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            "id" => $this->id,
            "firstname" => $this->firstname,
            "lastname" => $this->lastname,
            "avatar" => $this->avatar,
            "selfie" => $this->selfie,
            "email" => $this->email,
            "description" => $this->description,
            "phone_number" => $this->phone_number,
            "location" => $this->whenAppended('location'),
            "address" => $this->address,
            "gender" => $this->gender,
            "birth_date" => $this->birth_date,
            "is_verified" => $this->is_verified,
            "follows" =>  UserResource::collection($this->whenLoaded('follows')),
            "follow_ids" => $this->whenLoaded('follows',function(){
                return $this->followers->map->id;
            }),
            "followers" =>  UserResource::collection($this->whenLoaded('followers')),
            "followers_ids" => $this->whenLoaded('followers',function(){
                return $this->followers->map->id;
            }),
            "like_events" =>  EventResource::collection($this->whenLoaded('likeEvents')),
            "like_eventts_ids" => $this->whenLoaded('likeEvents',function(){
                return $this->likeEvents->map->id;
            }),
            "joint_events" => EventResource::collection( $this->whenLoaded('jointEvents')),
            "events" =>  EventResource::collection($this->whenLoaded('events')),
            "followers_count" => $this->followers_count,
            "follows_count" => $this->follows_count,
            "fcm_token" => $this->fcm_token,
            "unread_notifications_count" => $this->unread_notifications_count,
            "blocked_event" => $this->blocked_event ?? [],
            "blocked_user" => $this->blocked_user ?? [],
            "blocked_by" => $this->blocked_by ?? [],
            "preferred_radius" => $this->preferred_radius,
            "stripe_account_status" => $this->stripe_account_status,
            'stripe_merchant_country' => $this->stripe_merchant_country,
            "pivot" => $this->whenPivotLoaded('event_participants',function(){
                return [
                    "scanned" => $this->pivot->scanned,
                    "accepted" =>$this->pivot->accepted,
                    "rejected" => $this->pivot->rejected === true,
                    'paid' => $this->pivot->payment_intent_id !==null && !$this->pivot->payment_processing
                ];
            }),


        ],fn($value) => $value !==null,0);
    }
}
