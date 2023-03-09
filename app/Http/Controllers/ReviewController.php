<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Models\Party;
use App\Models\Review;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{

    /**
     * @throws ValidationException
     */
    public function create(Request $request, Party $event)
    {
        $this->validate($request, [
            "content" => "required|string",
            "note" => "required|integer|between:1,5"
        ]);
        $review = Review::updateOrCreate([
            "user_id" => \Auth::id(),
            "event_id" => $event->id,
        ], [
            "content" => $request->input('content'),
            "note" => $request->note
        ]);
        $event->rating = Review::query()->where('event_id', $event->id)->avg('note');
        $event->save();
        return $review;
    }

    public function eventReviews(Request $request, Party $event): LengthAwarePaginator
    {
        $per_page = $request->input('per_page', 20);
        return Review::with('user:id,lastname,firstname')
            ->latest()
            ->where('event_id', $event->id)
            ->paginate($per_page);
    }

    public function reviewsList(Request $request): AnonymousResourceCollection
    {
        $without = json_decode($request->input('without', '[]'));
        $events = Party::query()
            ->whereNotIn('id', $without)
            ->whereDoesntHave('reviews', function ($query) {
                $query->where('reviews.user_id', Auth::id());
            })
            ->whereHas('scannedParticipants', function (Builder $query) {
                return $query->where('users.id', Auth::id());
            })->get();
        return EventResource::collection($events);
    }
}
