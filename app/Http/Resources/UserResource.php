<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use JsonSerializable;

/**
 * @property Collection $events
 * @property string $firstname
 * @property string $lastname
 * @property float $organizer_commission
 */
class UserResource extends JsonResource
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
                "firstname" =>
                    $this->show_pseudo_only && $this->id != Auth::id()
                        ? null
                        : $this->firstname,
                "lastname" =>
                    $this->show_pseudo_only && $this->id != Auth::id()
                        ? null
                        : $this->lastname,
                "avatar" => $this->avatar,
                "show_pseudo_only" => $this->show_pseudo_only,
                "selfie" => $this->selfie,
                "email" => $this->email,
                "pseudo" => $this->pseudo,
                "description" => $this->description,
                "organizer_commission" => $this->organizer_commission,
                "phone_number" => $this->phone_number,
                "location" => $this->whenAppended("location"),
                "address" => $this->address,
                "gender" => $this->gender,
                "birth_date" => $this->birth_date,
                "is_verified" => $this->is_verified,
                "is_visible" => $this->is_visible,
                "follows" => UserResource::collection(
                    $this->whenLoaded("follows")
                ),
                "follow_ids" => $this->whenLoaded("follows", function () {
                    return $this->followers->map->id;
                }),
                "followers" => UserResource::collection(
                    $this->whenLoaded("followers")
                ),
                "followers_ids" => $this->whenLoaded("followers", function () {
                    return $this->followers->map->id;
                }),
                "like_events" => EventResource::collection(
                    $this->whenLoaded("likeEvents")
                ),
                "like_eventts_ids" => $this->whenLoaded(
                    "likeEvents",
                    function () {
                        return $this->likeEvents->map->id;
                    }
                ),
                "joint_events" => EventResource::collection(
                    $this->whenLoaded("jointEvents")
                ),
                "events_count" => $this->events->count(),
                "followers_count" => $this->followers_count,
                "follows_count" => $this->follows_count,
                "fcm_token" => $this->fcm_token,
                "unread_notifications_count" =>
                    $this->unread_notifications_count,
                "blocked_event" => $this->blocked_event ?? [],
                "blocked_user" => $this->blocked_user ?? [],
                "blocked_by" => $this->blocked_by ?? [],
                "preferred_radius" => $this->preferred_radius,
                "stripe_account_status" => $this->stripe_account_status,
                "stripe_merchant_country" => $this->stripe_merchant_country,
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
            fn($value) => $value !== null,
            0
        );
    }
}
