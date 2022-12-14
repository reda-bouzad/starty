<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Http\Resources\UserResource;
use App\Models\AppConfig;
use App\Models\Review;
use App\Nova\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\Models\Party;
use Illuminate\Support\Facades\Auth;
use Laravel\Nova\Nova;

class ReviewController extends Controller
{

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

    public function eventReviews(Request $request, Party $event)
    {
        $per_page = $request->input('per_page', 20);
        $reviews = Review::with('user:id,lastname,firstname')
            ->latest()
            ->where('event_id', $event->id)
            ->paginate($per_page);
        return $reviews;
    }

    public function reviewsList(Request $request)
    {
        $withoud = json_decode($request->input('withoud', '[]'));
        $events = Party::query()
            ->whereNotIn('id', $withoud)
            ->whereDoesntHave('reviews', function ($query) {
                $query->where('reviews.user_id', Auth::id());
            })
            ->whereHas('scannedParticipants', function (Builder $query) {
                return $query->where('users.id', Auth::id());
            })->get();
        return EventResource::collection($events);
    }
}
