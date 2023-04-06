<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowOrHideEventParticipantRequest;
use App\Models\EventParticipant;
use App\Models\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventParticipantController extends Controller
{
    /**
     * Hide or make visible Event Participants
     *
     * @param Party $party
     * @param ShowOrHideEventParticipantRequest $request
     * @return JsonResponse
     */
    public function hideOrShowParticipants(
        Party $party,
        ShowOrHideEventParticipantRequest $request
    ): JsonResponse {
        $participants = EventParticipant::where("id", $party->id)->get();
        $participants->update([
            "is_visible" => $request->is_visible,
        ]);
        return response()->json([
            "message" => $request->is_visible
                ? "The participants will be visible"
                : "The participants will be hidden",
            "visible" => $request->is_visible,
        ]);
    }

    /**
     * Authenticated user or sho hides or makes his participation in an event
     *
     * @param ShowOrHideEventParticipantRequest $request
     * @return JsonResponse
     */
    public function hideOrShowAuthParticipant(
        ShowOrHideEventParticipantRequest $request
    ): JsonResponse {
        $participants = EventParticipant::where("user_id", auth()->user()->id);
        $participants->update([
            "is_visible" => $request->is_visible,
        ]);
        return response()->json([
            "message" => $request->is_visible
                ? "The participants will be visible"
                : "The participants will be hidden",
            "visible" => $request->is_visible,
        ]);
    }
}
