<?php

namespace App\Http\Resources;

use App\Models\Party;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Party
 */
class EventResource extends JsonResource
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
            "label" => $this->label,
            "type" => $this->type,
            "pricy" => $this->pricy,
            "price" => $this->price,
            "nb_participants" => $this->nb_participants,
            "remaining_participants" => $this->remaining_participants,
            "contact" => $this->contact,
            "start_at" => $this->start_at,
            "end_at" => $this->end_at,
            "description" => $this->description,
            "user_id" => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'participants' =>UserResource::collection($this->whenLoaded('participants')),
            'first_participants' => UserResource::collection( $this->whenAppended('first_participants')),
            "participants_count" => $this->participants_count,
            "accepted_participants_count" => $this->accepted_participants_count,
            "requested_participants_count" => $this->requested_participants_count,
            "location" => [
                "address" => $this->address,
                "lat" => $this->location?->latitude,
                "long" => $this->location?->longitude,
                "distance" => $this->distance,
            ],
            'thumb' => $this->whenAppended('thumb'),
            'price_categories'=>$this->price_categories,
            'first_image' => $this->whenAppended('first_image'),
            'images' => $this->whenAppended('images'),
            'qr_code' => $this->whenAppended('qr_code'),
            'share_link' => $this->share_link,
            'chat_id' => $this->chat_id,
            'chat' => new ChatResource($this->whenLoaded('eventChat')),
            'blocked_by' => $this->blocked_by  ?? [],
            'rating' => $this->rating,
            'devise' => $this->devise,
            'phone_number' => $this->phone_number,
            "pivot" => $this->whenPivotLoaded('event_participants',function(){
                return [
                    "scanned" => $this->pivot->scanned,
                    "accepted" =>$this->pivot->accepted,
                    "rejected" => $this->pivot->rejected === true,
                    'paid' => $this->pivot->payment_intent_id !==null && !$this->pivot->payment_processing
                ];
            }),

        ],fn($value) =>$value!==null);
    }
}
