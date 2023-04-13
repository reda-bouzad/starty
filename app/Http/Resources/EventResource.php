<?php

namespace App\Http\Resources;

use App\Models\Party;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use JsonSerializable;

/**
 * @mixin Party
 */
class EventResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return array_filter(
            [
                "id" => $this->id,
                "label" => $this->label,
                "type" => $this->type,
                "is_visible" => $this->is_visible ? "visible" : "hidden",
                "pricy" => $this->pricy,
                "price" => $this->price,
                "nb_participants" => $this->nb_participants,
                "remaining_participants" =>
                    $this->nb_participants - $this->participants()->count(),
                "contact" => $this->contact,
                "start_at" => $this->start_at,
                "end_at" => $this->end_at,
                "description" => $this->description,
                "user_id" => $this->user_id,
                "tickets" => $this->eventParticipants()
                    ->where("user_id", "=", \Auth::id())
                    ->where("event_id", "=", $this->id)
                    ->select("id", "user_id", "event_id")
                    ->get(),
                "user" => new UserResource($this->whenLoaded("user")),
                "participants" =>
                    $this->is_visible || Auth::id() == $this->user_id
                        ? collect(
                        UserResource::collection(
                            $this->participants->filter(
                                function ($e) {
                                    $e["is_visible"] = $e->pivot->is_visible;
                                    error_log($e["is_visible"]);
                                    if (Auth::id() == $this->user_id) return true;
                                    if (Auth::id() == $e->pivot->user_id) return true;
                                    return $e->pivot->is_visible == "visible";
                                }
                            )
                        )
                    )
                        : collect([]),
                "first_participants" =>
                    $this->is_visible || Auth::id() == $this->user_id
                        ? collect(
                        UserResource::collection(
                            $this->first_participants->filter(
                                function ($e) {
                                    $e["is_visible"] = $e->pivot->is_visible;
                                    if (Auth::id() == $this->user_id) return true;
                                    if (Auth::id() == $e->pivot->user_id) return true;
                                    return $e->pivot->is_visible == "visible";
                                }
                            )
                        )
                    )
                        : collect([]),
                "participants_count" => $this->participants_count,
                "accepted_participants_count" =>
                    $this->accepted_participants_count,
                "requested_participants_count" =>
                    $this->requested_participants_count,
                "location" => [
                    "address" => $this->address,
                    "lat" => $this->location?->latitude,
                    "long" => $this->location?->longitude,
                    "distance" => $this->distance,
                ],
                "thumb" => $this->whenAppended("thumb"),
                "price_categories" => $this->price_categories,
                "first_image" => $this->whenAppended("first_image"),
                "images" => $this->whenAppended("images"),
                "qr_code" => $this->whenAppended("qr_code"),
                "share_link" => $this->share_link,
                "chat_id" => $this->chat_id,
                "chat" => new ChatResource($this->whenLoaded("eventChat")),
                "blocked_by" => $this->blocked_by ?? [],
                "rating" => $this->rating,
                "devise" => $this->devise,
                "phone_number" => $this->phone_number,
                "pivot" => $this->whenPivotLoaded(
                    "event_participants",
                    function () {
                        return [
                            "scanned" => $this->pivot->scanned,
                            "accepted" => $this->pivot->accepted,
                            "rejected" => $this->pivot->rejected === true,
                            "paid" =>
                                $this->pivot->payment_intent_id !== null &&
                                !$this->pivot->payment_processing,
                        ];
                    }
                ),
            ],
            fn($value) => $value !== null
        );
    }
}
