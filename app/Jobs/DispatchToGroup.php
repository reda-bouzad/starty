<?php

namespace App\Jobs;

use App\Events\NewChat;
use App\Models\Chat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchToGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var User
     */
    private User $sender;
    /**
     * @var Chat
     */
    private Chat $chat;

    /**
     * Create a new job instance.
     *
     * @param Chat $chat
     * @param User $sender
     */
    public function __construct(Chat $chat, User $sender)
    {
        $this->chat = $chat;
        $this->sender = $sender;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $otherMembers = $this
            ->chat
            ->members()
//            ->where('users.id','!=',$this->sender->id)
            ->get(['users.id'])
            ->each(fn($el) =>   event(new NewChat($this->chat,$el->id )));

    }
}
