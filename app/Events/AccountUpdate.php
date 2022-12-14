<?php

namespace App\Events;

use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountUpdate
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $user_id;

    /**
     * Create a new event instance.
     *
     * @param int $user_id
     * @param int $event_id
     */
    public function __construct(int $user_id)
    {
        $this->user_id = $user_id;
    }

    public function broadcastAs()
    {
        return 'account.updated';
    }

    public function broadcastWith()
    {

        return [
            "stripe_account_status" => User::select(['id','stripe_account_status'])->find($this->user_id)->stripe_account_status
        ];
    }
    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel("profile.updated.{$this->user_id}");
    }
}
