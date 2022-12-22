<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function getNotifications(Request $request): AnonymousResourceCollection
    {
         $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('read_at')
            ->latest()
            ->take(max($request->input('limit',15),$user->unreadNotifications()->count()))
            ->paginate(20);
        return NotificationResource::collection($notifications);

    }

    public function markAllAsRead(){
        Auth::user()->notifications()->update([
            "read_at"  => now()
        ]);
    }

    public function markAsRead($notification_id){
        Auth::user()->notifications()
            ->where('id',$notification_id)
            ->whereNull('read_at')
            ->update(["read_at" => now()]);
    }

    public function deleteNotification($notification_id){
        Auth::user()->notifications()->where('id',$notification_id)->delete();
    }
}
