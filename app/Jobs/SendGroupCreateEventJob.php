<?php

namespace App\Jobs;

use App\Events\NewChat;
use App\Models\Chat;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class SendGroupCreateEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Chat
     */
    private Chat $chat;
    private  Collection $otherMember;

    /**
     * Create a new job instance.
     *
     * @param Chat $chat
     * @param Collection<int> $otherMember
     */
    public function __construct(Chat $chat, Collection $otherMember)
    {
        $this->chat = $chat;
        $this->otherMember = $otherMember;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->otherMember->each(fn($el) => event(new NewChat($this->chat,$el)));
    }
}
