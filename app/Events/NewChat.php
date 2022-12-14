<?php

namespace App\Events;

use App\Http\Resources\ChatResource;
use App\Models\Chat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class NewChat implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $chat;
    private $receiver_id;

    public function __construct(Chat $chat, $receiver_id)
    {
        $this->chat = $chat;
        $this->receiver_id = $receiver_id;
    }
    public function broadcastAs()
    {
        return 'update.chat';
    }

    public function broadcastWith()
    {

        $chat = Chat::query()
            ->withLastMessageId()
            ->with([
                'lastMessage',
                'members' => function($query){
                    $query->select('users.id','firstname','lastname');
                }
            ])
            ->withCount('members')
            ->find($this->chat->id);

        return  array_merge(json_decode((new ChatResource($chat))->toJson(),true),["unread" => $chat->getUnread($this->receiver_id)]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.'.$this->receiver_id);
    }

}
