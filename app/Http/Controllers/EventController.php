<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeUserVisibilityRequest;
use App\Http\Requests\EventRequest;
use App\Http\Requests\EventSearchRequest;
use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Models\AppConfig;
use App\Models\ChatUser;
use App\Models\EventParticipant;
use App\Models\ModelReport;
use App\Models\Party;
use App\Models\PriceCategory;
use App\Models\User;
use App\Notifications\AcceptedRequestEventNotification;
use App\Notifications\CancelEventNotification;
use App\Notifications\JoinEventNotification;
use App\Notifications\NewEventNotification;
use App\Notifications\RejectedEventNotification;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Cashier;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\SpatialBuilder;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Stripe\Exception\ApiErrorException;

class EventController extends Controller
{
    /**
     * @param EventRequest $eventRequest
     * @return JsonResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function createEvent(EventRequest $eventRequest): JsonResponse
    {
        if ($eventRequest->pricy) {
            $this->validate($eventRequest, [
                "price_categories.*.price" => "required|numeric",
                "price_categories.*.devise" =>
                    "required|string|in:AUD,BGN,CAD,CHF,CZK,DKK,EUR,GBP,HKD,HRK,HUF,JPY,NOK,PLN,RON,SEK,USD,ZAR",
                "price_categories.*.name" => "required|string|max:200",
            ]);
        }
        $data = $eventRequest->except(["lat", "long"]);
        $data = array_merge($data, [
            "user_id" => Auth::user()->id ,
            "location" => new Point($eventRequest->lat, $eventRequest->long),
            "remaining_participants" => $eventRequest->nb_participants,
        ]);

        unset($data["price_categories"]);

        $event = Party::create($data);
        $event->price_categories()->createMany(
            collect($eventRequest->price_categories)
                ->map(fn($e) => [...$e, "event_id" => $event->id])
                ->toArray()
        );

        if ($eventRequest->hasFile("images")) {
            $event
                ->addMultipleMediaFromRequest(["images"])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection("image");
                });
        }

        $event->generateQrcode();
        $event->append("image", "qr_code");

        $users = User::inRadius(
            $event->location->latitude,
            $event->location->longitude,
            50000
        )
            ->where("id", "!=", Auth::id())
            ->get(["id"]);
        try {
            Notification::send(
                $users,
                (new NewEventNotification($event))->delay(now()->addMinutes(10))
            );
        } catch (Exception $e) {
            Log::error($e);
        }

        return response()->json(new EventResource($event));
    }

    /**
     * @param Party $event
     * @param Request $request
     * @return JsonResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function addImagesToEvent(
        Party   $event,
        Request $request
    ): JsonResponse
    {
        if ($request->hasFile("images")) {
            $event
                ->addMultipleMediaFromRequest(["images"])
                ->each(function ($fileAdder, $key) {
                    $fileAdder
                        ->toMediaCollection("image")
                        ->withCustomProperties([
                            "order" => intval($key) + 1,
                        ]);
                });
        }

        return response()->json($event);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws ValidationException
     */
    public function addSingleImageToEvent(
        Party   $event,
        Request $request
    ): JsonResponse
    {
        $this->validate($request, [
            "image" => "required|image",
            "order" => "required|numeric|between:1,10",
        ]);
        if ($request->hasFile("image")) {
            if ($request->order == 1) {
                $event
                    ->addMediaFromRequest("image")
                    ->toMediaCollection("first_image");
            } else {
                $event
                    ->addMediaFromRequest("image")
                    ->withCustomProperties([
                        "order" => $request->order ?? 1,
                    ])
                    ->toMediaCollection("image");
            }
        }

        $event->append("images");
        return response()->json(new EventResource($event));
    }

    public function getEvent(Party $event): JsonResponse
    {
        $event->append("images", "qr_code");
        $event->load("user", "eventChat.members:id");
        $event->loadCount([
            "participants",
            "acceptedParticipants",
            "requestedParticipants",
        ]);
        $event->load([
            "participants" => function ($query) {
                $query->select("users.id");
            },
        ]);
        return response()->json(new EventResource($event));
    }

    public function updateEvent(Party $event, Request $request): JsonResponse
    {
        $event->price_categories()->delete();
        $event->update([
            "label" => $request->label ?? $event->label,
            "type" => $request->type ?? $event->type,
            "pricy" => $request->pricy ?? $event->pricy,
            "price" => $request->price ?? $event->price,
            "nb_participants" =>
                    $request->nb_participants ?? $event->nb_participants,
            "remaining_participants" =>
                    $request->remaining_participants ??
                    $event->remaining_participants,
            "contact" => $request->contact ?? $event->contact,
            "start_at" => $request->start_at ?? $event->start_at,
            "end_at" => $request->end_at ?? $event->end_at,
            "location" =>
                $request->lat && $request->long
                    ? new Point($request->lat, $request->long)
                    : $event->location,
            "description" => $request->description ?? $event->description,
            "address" => $request->address ?? $event->address,
            "share_link" => $request->share_link ?? $event->share_link,
            "devise" => $request->devise ?? $event->devise,
            "event_visible" => $request->event_visible ?? $event->event_visible,
        ]);

        $event->price_categories()->createMany($request->price_categories);

        if ($request->hasFile("images")) {
            $event
                ->addMultipleMediaFromRequest(["images"])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection("image");
                });
        }

        return response()->json(new EventResource($event));
    }

    /**
     * @throws ApiErrorException
     */
    public function deleteEvent(Party $event): JsonResponse
    {
        $chat = $event->eventChat;
        if ($event->pricy) {
            foreach ($event->eventParticipants as $eventParticipant) {
                if (
                    $eventParticipant->accepted &&
                    $eventParticipant->payment_intent_id
                ) {
                    Cashier::stripe()->refunds->create([
                        "payment_intent" =>
                            $eventParticipant->payment_intent_id,
                    ]);
                }
            }
        }
        $event->delete();
        $chat->delete();
        try {

            Notification::send(
                $event->participant,
                new CancelEventNotification($event)
            );
        } catch (Exception) {

        }
        return response()->json([
            "message" => "Evènement supprimé",
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function myParties(Request $request): AnonymousResourceCollection
    {
        //all, organise, attend
        $this->validate($request, [
            "organise" => "sometimes|in:0,1",
            "attend" => "sometimes|in:0,1",
            "attended" => "sometimes|in:0,1",
            "start" => "sometimes|date",
            "end" => "sometimes|date",
            "pricy" => "sometimes|in:0,1",
            "type" => ["sometimes", Rule::in(["private", "public"])],
        ]);

        $data = Party::query()
            ->orderByDesc("start_at")
            ->withCount(["participants", "acceptedParticipants"])
            ->where(function ($query) use ($request) {
                $query
                    ->when($request->organise, function (Builder $query) use (
                        $request
                    ) {
                        return $query->where(
                            "user_id",
                            $request->organise === "1" ? "=" : "!=",
                            Auth::id()
                        );
                    })
                    ->when($request->attend, function (Builder $query) use (
                        $request
                    ) {
                        return $query->orWhereHas(
                            "acceptedParticipants",
                            function ($query) use ($request) {
                                return $query->where(
                                    "users.id",
                                    $request->attend === "1" ? "=" : "!=",
                                    Auth::id()
                                );
                            }
                        );
                    })
                    ->when($request->attended, function ($query) use (
                        $request
                    ) {
                        return $query->orWhereHas(
                            "scannedParticipants",
                            function (Builder $query) use ($request) {
                                return $query->where(
                                    "users.id",
                                    $request->attended === "1" ? "=" : "!=",
                                    Auth::id()
                                );
                            }
                        );
                    });
            })
            ->when($request->start, function (Builder $query) use ($request) {
                return $query->where("start_at", ">=", $request->start);
            })
            ->when($request->end, function ($query) use ($request) {
                return $query->where("start_at", "<=", $request->end);
            })
            ->when($request->pricy, function ($query) use ($request) {
                return $query->where("pricy", $request->pricy === "1");
            })
            ->when($request->type, function ($query) use ($request) {
                return $query->where("type", $request->type);
            })
            ->when(
                $request->lat && $request->long && $request->radius,
                function (SpatialBuilder $query) use ($request) {
                    $center = new Point(
                        $request->get("lat", 0.0),
                        $request->get("long", 0.0)
                    );
                    return $query
                        ->whereDistanceSphere(
                            "location",
                            $center,
                            "<=",
                            $request->radius
                        )
                        ->withDistanceSphere("location", $center)
                        ->orderByDistanceSphere("location", $center);
                }
            )
            ->paginate($request->input("per_page", 20));

        return EventResource::collection($data);
    }

    public function getUpcomingEvents(): AnonymousResourceCollection
    {
        $data = Party::whereBetween("start_at", [
            now(),
            now()->addDays(10),
        ])->paginate(20);

        return EventResource::collection($data);
    }

    public function joinEvent(Request $request, Party $event): JsonResponse
    {
        $request->validate([
            "payment_intent_id" => [
                Rule::requiredIf(fn() => $event->pricy),
                Rule::exists("event_participants", "payment_intent_id")
                    ->where("user_id", Auth::id())
                    ->where("event_id", $event->id)
                    ->where("is_visible", true),
            ],
        ]);
        if ($event->nb_participants <= $event->participants->count()) {
            return response()->json(
                [
                    "message" => "Evènement complet",
                ],
                422
            );
        }
        if (
            $event
                ->participants()
                ->where("users.id", Auth::id())
                ->exists()
        ) {
            return response()->json(
                [
                    "message" => "Existe déjà",
                ],
                422
            );
        }
        if ($event->user_id == Auth::id()) {
            return response()->json(
                [
                    "message" => "Impossible de joindre sa soirré",
                ],
                422
            );
        }

        EventParticipant::updateOrCreate([
            "user_id" => \Auth::id(),
            "event_id" => $event->id,
            "is_visible" => true,
        ]);
        $eventParticipant = EventParticipant::firstWhere([
            "user_id" => \Auth::id(),
            "event_id" => $event->id,
        ]);

        EventParticipant::whereId($eventParticipant->id)->update([
            "payment_processing" => false,
            "accepted" => $event->type === "public",
            "rejected" => false,
        ]);
        if (
            $event->chat_id &&
            $event->type === "public" &&
            !ChatUser::where("chat_id", $event->chat_id)
                ->where("user_id", Auth::id())
                ->exists()
        ) {
            ChatUser::updateOrCreate(
                [
                    "chat_id" => $event->chat_id,
                    "user_id" => Auth::id(),
                ],
                ["state" => "direct"]
            );
            $event->remaining_participants -= 1;
            $event->save();
        }

        $event->user->notify(new JoinEventNotification($event, Auth::user()));
        if ($event->type === "public") {
            Auth::user()->notify(new AcceptedRequestEventNotification($event));
        }

        return response()->json([
            "message" =>
                $event->type === "private"
                    ? "requête envoyée avec succès"
                    : 'Vous avez rejoins l\'évènement avec succès',
        ]);
    }

    public function deleteEventImage(Media $media): JsonResponse
    {
        $media->delete();
        return response()->json(["message" => "Image supprimée"]);
    }

    public function searchEvent(
        EventSearchRequest $request
    ): AnonymousResourceCollection
    {
        $center = new Point(
            $request->get("lat", 0.0),
            $request->get("long", 0.0)
        );
        $start_at = explode(",", $request->input("start_at", ","));
        $end_at = explode(",", $request->input("end_at", ","));
        $data = Party::query()
            ->withCount(["participants", "acceptedParticipants"])
            ->orderBy("start_at")
            ->where('event_visible', "=", "visible")
            ->when(
                $request->lat && $request->long && $request->radius,
                function (SpatialBuilder $query) use ($request, $center) {
                    return $query
                        ->whereDistanceSphere(
                            "location",
                            $center,
                            "<=",
                            $request->radius
                        )
                        ->withDistanceSphere("location", $center)
                        ->orderByDistanceSphere("location", $center);
                }
            )
            ->when($request->type, function ($query) use ($request) {
                return $query->where("type", $request->type);
            })
            ->when($request->pricy, function ($query) use ($request) {
                return $query->where("pricy", $request->pricy);
            })
            ->when($request->start, function (Builder $query) use ($request) {
                return $query->where("start_at", ">=", $request->start);
            })
            ->when($request->end, function ($query) use ($request) {
                return $query->where("start_at", "<=", $request->end);
            })
            ->when($start_at[0], function (Builder $query) use ($start_at) {
                return $query->where("start_at", ">=", $start_at[0]);
            })
            ->when($start_at[1], function (Builder $query) use ($start_at) {
                return $query->where("start_at", "<=", $start_at[1]);
            })
            ->when($end_at[0], function (Builder $query) use ($end_at) {
                return $query->where("end_at", ">=", $end_at[0]);
            })
            ->when($end_at[1], function (Builder $query) use ($end_at) {
                return $query->where("end_at", "<=", $end_at[1]);
            })
            ->when($request->search, function (Builder $query) use ($request) {
                $lower = Str::lower($request->search);
                $upper = Str::upper($request->search);
                return $query->where(function ($query) use ($lower, $upper) {
                    $query
                        ->where("label", "like", "%$lower%")
                        ->orWhere("label", "like", "%$upper%");
                });
            })
            ->paginate($request->input("per_page", 20));

        return EventResource::collection($data);
    }

    public function toggleLikeEvent(Party $event): JsonResponse
    {
        if (
            $event
                ->likes()
                ->where("users.id", Auth::id())
                ->exists()
        ) {
            $event->likes()->detach(Auth::id());
            return response()->json(["like" => false]);
        } else {
            $event->likes()->attach(Auth::id());
            return response()->json(["like" => true]);
        }
    }

    public function scannedQrcode(int $event, string $uuid): JsonResponse
    {
        $event2 = Party::where("uuid", "like", "$uuid%")
            ->whereHas("acceptedParticipants", function ($query) {
                $query->where("users.id", Auth::id());
            })
            ->first(["id"]);
        if ($event2 && $event === $event2->id) {
            EventParticipant::whereEventId($event2->id)
                ->whereUserId(Auth::id())
                ->update([
                    "scanned" => true,
                ]);
            return response()->json([
                "is_invited" => true,
            ]);
        }

        return response()->json(
            [
                "is_invited" => false,
            ],
            422
        );
    }

    public function getMyFavoriteEvents(): AnonymousResourceCollection
    {
        $events = Party::whereHas("likes", function ($query) {
            $query->where("users.id", Auth::id());
        })
            ->paginate()
            ->appends("first_participants");

        return EventResource::collection($events);
    }

    public function participants(
        Request $request,
        Party   $event
    ): AnonymousResourceCollection
    {
        $query =
            $request->only_accepted === true || $event->user_id !== Auth::id()
                ? $event->acceptedParticipants()
                : $event->participants();
        return UserResource::collection(
            $query->paginate($request->input("per_page", 30))
        );
    }

    public function leaveTheEvent(Party $event): Response|JsonResponse
    {
        $event_participant = EventParticipant::where([
            "event_id" => $event->id,
            "user_id" => Auth::id(),
        ])->first();
        if ($event->pricy && $event_participant->payment_intent_id) {
            if ($event_participant->accepted) {
                $res = Http::withtoken(AppConfig::first()->revolut_pk)->post(
                    env("REVOLUT_BASE_URL") .
                    "orders/" .
                    $event_participant->payment_intent_id .
                    "/refund"
                );
                if (!$res->ok()) {
                    return response()->json(
                        ["message" => "refund_contact_admin"],
                        403
                    );
                }
            }
            Http::withtoken(AppConfig::first()->revolut_pk)->post(
                env("REVOLUT_BASE_URL") .
                "orders/" .
                $event_participant->payment_intent_id .
                "/cancel"
            );
        }
        $event->participants()->detach(Auth::id());

        ChatUser::where("chat_id", $event->chat_id)
            ->where("user_id", Auth::id())
            ->delete();
        $event->remaining_participants += 1;
        $event->save();
        return response()->noContent();
    }

    public function acceptRequest(
        Party $event,
        int   $user
    ): Response|JsonResponse
    {
        if (
            !EventParticipant::where([
                "event_id" => $event->id,
                "user_id" => $user,
            ])->exists()
        ) {
            return response()->noContent();
        }
        $event_participant = EventParticipant::where([
            "event_id" => $event->id,
            "user_id" => $user,
        ])->first();
        error_log($event_participant->id);
        $ticket = PriceCategory::where([
            "id" => $event_participant->ticket_id,
        ])->first();

        if ($event_participant->payment_intent_id) {
            $res = Http::withtoken(AppConfig::first()->revolut_pk)->post(
                env("REVOLUT_BASE_URL") .
                "orders/" .
                $event_participant->payment_intent_id .
                "/capture",
                ["amount" => $ticket->price]
            );
            Log::channel("stderr")->error(
                env("REVOLUT_BASE_URL") .
                "orders/" .
                $event_participant->payment_intent_id .
                "/capture"
            );
            if (!$res->ok()) {
                return response()->json(["message" => "payment_failed"], 403);
            }
        }
        $event_participant->update([
            "event_id" => $event->id,
            "status" => "COMPLETED",
            "user_id" => $user,
            "accepted" => true,
            "payment_processing" => false,
            "rejected" => false,
        ]);

        $event->remaining_participants -= 1;
        $event->save();
        ChatUser::create([
            "chat_id" => $event->chat_id,
            "user_id" => $user,
        ]);
        User::find($user)->notify(new AcceptedRequestEventNotification($event));
        return response()->noContent();
    }

    public function rejectRequest(
        Party $event,
        int   $user
    ): Response|JsonResponse
    {
        if (
            !EventParticipant::where([
                "event_id" => $event->id,
                "user_id" => $user,
            ])->exists()
        ) {
            return response()->noContent();
        }
        $event_participant = EventParticipant::where([
            "event_id" => $event->id,
            "user_id" => $user,
        ])->first();
        if ($event->pricy && $event_participant->payment_intent_id) {
            if ($event_participant->accepted) {
                $res = Http::withtoken(AppConfig::first()->revolut_pk)->post(
                    env("REVOLUT_BASE_URL") .
                    "orders/" .
                    $event_participant->payment_intent_id .
                    "/refund"
                );
                if (!$res->ok()) {
                    return response()->json(
                        ["message" => "refund_contact_admin"],
                        403
                    );
                }

                $res = Http::withtoken(AppConfig::first()->revolut_pk)->post(
                    env("REVOLUT_BASE_URL") .
                    "orders/" .
                    $event_participant->payment_intent_id .
                    "/cancel"
                );
                if (!$res->ok()) {
                    return response()->json(
                        ["message" => "refund_contact_admin"],
                        403
                    );
                }
            }
        }
        if ($event_participant->accepted) {
            $event->remaining_participants -= 1;
        }
        $event_participant->update([
            "accepted" => false,
            "rejected" => true,
        ]);

        $event->save();
        ChatUser::where([
            "chat_id" => $event->chat_id,
            "user_id" => $user,
        ])->delete();

        User::find($user)->notify(new RejectedEventNotification($event));
        return response()->noContent();
    }

    /**
     * @throws ValidationException
     */
    public function report(Request $request, Party $event)
    {
        $this->validate($request, [
            "report_id" => ["required", "exists:reports,id"],
        ]);

        ModelReport::updateOrCreate(
            [
                "user_id" => Auth::id(),
                "model_type" => Party::class,
                "model_id" => $event->id,
                "report_id" => $request->report_id,
            ],
            []
        );
    }

    public function toggleBlock(int $event_id): array|JsonResponse
    {
        $event = Party::withoutGlobalScopes()->find($event_id);
        if ($event->user_id === Auth::id()) {
            return response()->json(
                ["message" => "impossible de bloquer son évènement"],
                401
            );
        }
        if ($event->blocked_by) {
            if (collect($event->blocked_by)->contains(Auth::id())) {
                $event->blocked_by = collect($event->blocked_by)
                    ->reject(fn($el) => $el === Auth::id())
                    ->values()
                    ->all();
                Auth::user()->blocked_event = collect(
                    Auth::user()->blocked_event
                )
                    ->reject(fn($el) => $el === $event->id)
                    ->values()
                    ->all();
                Auth::user()->save();
                $event->save();
                return [
                    "blocked" => false,
                ];
            } else {
                $event->blocked_by = array_merge(
                    [Auth::id()],
                    $event->blocked_by
                );
                Auth::user()->blocked_event = array_merge(
                    [$event->id],
                    Auth::user()->blocked_event
                );
            }
        } else {
            $event->blocked_by = [Auth::id()];
            Auth::user()->blocked_event = [$event->id];
        }
        $event->save();
        Auth::user()->save();
        return [
            "blocked" => true,
        ];
    }

    public function scanned2Qrcode(int $event, int $user): JsonResponse
    {
        $event_user = EventParticipant::whereEventId($event)
            ->whereUserId($user)
            ->first();
        $user = User::find($user);
        if (!$event_user || !$event_user->accepted) {
            return response()->json(
                [
                    "is_invited" => false,
                    "already_scanned" => false,
                    "user" => new UserResource($user),
                ],
                422
            );
        }

        if ($event_user->scanned) {
            return response()->json(
                [
                    "is_invited" => true,
                    "already_scanned" => true,
                    "user" => new UserResource($user),
                ],
                422
            );
        }

        $event_user->scanned = true;
        $event_user->save();
        $ticket = PriceCategory::where(
            "id",
            "=",
            $event_user->ticket_id
        )->get();
        Log::channel("stderr")->error($ticket->toJson());
        return response()->json([
            "is_invited" => true,
            "ticket" => $ticket,
            "user" => new UserResource($user),
        ]);
    }

    /**
     * Define if Authenticated User Participant will be visible or not
     *
     * @param Party $party
     * @return EventResource
     */
    public function toggleUserVisibility(Party $party): EventResource
    {

        $participant = EventParticipant::where(["user_id" => Auth::id(), "event_id" => $party->id])->first();
        $participant->update([
            "is_visible" =>
                $participant->is_visible == "hidden"
                    ? "visible"
                    : "hidden",
        ]);
        return new EventResource($party);
    }

    /**
     * Define if Event Participants will be visible or not
     *
     * @param Party $party
     * @return EventResource
     */
    public function toggleParticipantsVisibility(Party $party)
    {
        error_log($party->id);
        $party->update([
            "is_visible" =>
                !$party->is_visible
        ]);
        return new EventResource($party);
    }

    /**
     * Define if the event will be visible or not
     *
     * @param Party $party
     * @return JsonResponse
     */
    public function toggleEvent(Party $party)
    {
        $party->update([
            "event_visible" =>
                $party->first()->event_visible === "hidden"
                    ? "visible"
                    : "hidden",
        ]);
        return response()->json([
            "status" =>
                $party->first()->event_visible === "visible"
                    ? "Visible"
                    : "Hidden",
        ]);
    }
}
