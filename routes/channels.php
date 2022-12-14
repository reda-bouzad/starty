<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('chat.{receiver_id}', function ($user, $receiver_id) {

    return (int) $receiver_id === $user->id;
});

Broadcast::channel('message.{chatId}', function ($user, $chatId) {
    Log::info("chatid $chatId");
    $chat = Chat::withoutGlobalScopes()->find($chatId);

    return $chat->members()->whereIn('users.id',[Auth::id()])->exists();

});
Broadcast::channel("profile.updated.{userId}", function ($user,int  $userId ) {
    return $userId === $user->id;
});
