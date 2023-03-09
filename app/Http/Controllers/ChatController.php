<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Http\Requests\ChatRequest;
use App\Http\Requests\MessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ChatResource;
use App\Http\Resources\UserResource;
use App\Jobs\DispatchToGroup;
use App\Jobs\SendGroupCreateEventJob;
use App\Models\Chat;
use App\Models\ChatMessage;
use App\Models\ChatUser;
use App\Models\Follow;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Notification;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;

class ChatController extends Controller
{
    public function createChat(ChatRequest $request): ChatResource
    {
        $room = $request->with;
        $room[] = Auth::id();
        $chat = null;
        if ($request->input('type', 'single') === "single") {
            $chat = Chat::whereType($request->input('type', 'single'))
                ->whereHas('members', function ($query) use ($room) {
                    $query->whereIn('users.id', $room);
                }, count($room))
                ->first();
        }
        $ids = [];
        foreach ($room as $id_user) {
            if ($id_user === Auth::id()) {
                $state = "direct";
            } else {
                $state = Follow::where('user_id', Auth::id())->where('follower_id', $id_user)->exists() ? "direct" : "request";
            }
            $ids[$id_user] = ['state' => $state];
        }
        if (!$chat) {

            $chat = Chat::create([
                "created_by" => Auth::id(),
                "type" => $request->input('type', 'single'),
                "name" => $request->input('name'),
            ]);
            $chat->members()->sync($ids);


        }
        SendGroupCreateEventJob::dispatch($chat, collect($room)->filter(fn($el) => $el !== Auth::id()));
        return new ChatResource($chat->load('members'));
    }

    public function createMessage(MessageRequest $request, Chat $chat): ChatMessageResource
    {

        $content = $request->input('content', "");
        $from_message = null;
        if ($request->from_message_id !== null) {
            $from_message = ChatMessage::findOrFail($request->from_message_id);
            $content = $from_message->content;
        }
        $message = ChatMessage::create([
            "sender" => Auth::id(),
            "receiver" => $chat->type === "group" ? 0 : $request->to,
            "content" => $content,
            "chat_id" => $chat->id,
            "response_to" => $request->response_to
        ]);
        ChatUser::updateOrCreate([
            "user_id" => Auth::id(),
            "chat_id" => $chat->id,
        ], [
            "state" => "direct"
        ]);

        if ($chat->type === "single") {
            $to = User::find($request->to);
            $to->deleted_chats = array_filter($to->deleted_chats ?? [], fn($el) => $el !== $chat->id);
            $to->save();
        }

        $from_message?->getMedia('files')
            ->each(/**
             * @throws FileIsTooBig
             * @throws FileDoesNotExist
             */ fn($el) => $message->addMediaFromStream($el->stream())
                ->usingFileName(Str::uuid() . "." . $el->extension)
                ->toMediaCollection('files'));
        if ($request->hasFile('files')) {
            $message->addMultipleMediaFromRequest(['files'])
                ->each(function ($fileAdder) {
                    $fileAdder->toMediaCollection('files');
                });
        }

        DispatchToGroup::dispatch($chat, Auth::user());
        event(new MessageSent($message));

        $otherMembers = $chat->members()
            ->where('users.id', '!=', Auth::id())
            ->get(['users.id']);
        Notification::send($otherMembers, new NewMessageNotification($message));

        return new ChatMessageResource($message);
    }

    public function getChats(Request $request): AnonymousResourceCollection
    {
        $q = $request->search;
        $type = $request->type;

        $chats = Chat::query()
            ->with([
                'lastMessage',
                'event:id,label,chat_id',
                'members' => function ($query) {
                    $query->select('users.id', 'firstname', 'lastname')
                        ->where('users.id', '!=', Auth::id());
                }
            ])
            ->withCount('members')
            ->whereHas('members', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->where(function (Builder $query) {
                $query->where(fn($query) => $query->where('type', 'single')->has('messages'))
                    ->orWhere('type', 'group');
            })
            ->whereNotIn('id', \Auth::user()->archive_chats ?? [])
            ->when($q, function ($query) use ($q) {
                $query
                    ->where(fn($query) => $query->where('type', 'group')->where('name', 'like', "%$q%"))
                    ->orWhere(function ($query) use ($q) {
                        $query
                            ->where('type', 'single')
                            ->whereHas('members', function ($query) use ($q) {
                                $query
                                    ->where('users.id', '!=', Auth::id())
                                    ->where(fn($query) => $query->where('lastname', 'like', "%$q%")->orWhere('firstname', 'like', "%$q%"));
                            });
                    });
            })
            ->when($type, function ($query) use ($type) {
                if ($type === "direct") {

                    $query->whereHas('directMembers', function ($query) {
                        $query->where('users.id', Auth::id());
                    });
                } else if ($type === "request") {
                    $query->whereHas('requestMembers', function ($query) {
                        $query->where('users.id', Auth::id());
                    });
                }
            })
            ->latest()
            ->paginate($request->input('per_page'));
        return ChatResource::collection($chats);
    }

    public function getChatMessages(Request $request, Chat $chat): AnonymousResourceCollection
    {

        return ChatMessageResource::collection(
            ChatMessage::with('responseToMessage:id,content,sender')
                ->where('chat_id', $chat->id)
                ->latest()
                ->paginate($request->input('per_page', 20))
        );
    }

    public function getLastMessagesAfterId(Request $request): AnonymousResourceCollection
    {
        return ChatMessageResource::collection(
            ChatMessage::when($request->last_id, function ($query) use ($request) {
                $query->where('id', '>', $request->last_id);
            }
            )->latest()->get());

    }


    public function markAsRead(ChatMessage $message)
    {
        if ($message->receiver === Auth::id()) {
            $message->read = true;
            $message->save();
        }
    }

    public function markChatAsRead(Chat $chat)
    {
        $chat->messages()->where('receiver', Auth::id())
            ->where('read', false)
            ->update(['read' => true]);

    }

    /**
     * @throws ValidationException
     */
    public function addToGroup(Request $request, Chat $chat)
    {
        if ($chat->isGroup()) {
            $this->validate($request, [
                "list" => "required|array"
            ]);
            $users = User::whereIn('id', $request->list)
                ->whereJsonDoesntContain('deleted_chats', $chat->id)
                ->get(['id'])->map->id;
            $chat->members()->attach($users);
        }

    }

    /**
     * @throws ValidationException
     */
    public function removeToGroup(Request $request, Chat $chat)
    {
        $this->validate($request, [
            "list" => "required|array"
        ]);
        $chat->members()->detach($request->list);
    }

    /**
     * @throws ValidationException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function addImageToGroup(Request $request, Chat $chat): ChatResource
    {
        $this->validate($request, [
            "avatar" => "required|image"
        ]);

        $chat->addMediaFromRequest('avatar')->toMediaCollection('avatar');
        return new ChatResource($chat);
    }

    public function deleteGroup(Chat $chat)
    {
        $user = Auth::user();

        if (Auth::id() === $chat->created_by) {
            $chat->delete();
        } else {
            $user->deleted_chats = array_merge($user->deleted_chats ?? [], [$chat->id]);
            $user->deleted_messages = array_merge($user->deleted_messages ?? [], $chat->messages()->get(['id'])->map->id->toArray());
            $chat->members()->detach($user->id);
            $user->save();
        }
    }

    public function deleteMessage(Request $request, ChatMessage $message)
    {
        $user = Auth::user();

        if ($request->boolean('for_all') && $message->sender === Auth::id()) {
            $message->delete();
        } else {
            $user->deleted_messages = array_merge($user->deleted_messages ?? [], [$message->id]);
            $user->save();
        }
    }

    /**
     * @throws ValidationException
     */
    public function updateName(Request $request, Chat $chat)
    {
        $this->validate($request, [
            "name" => "required|string"
        ]);
        $chat->name = $request->name;
        $chat->save();
    }

    public function getChatMembers(Request $request, Chat $chat): AnonymousResourceCollection
    {
        return UserResource::collection(
            $chat->members()->paginate($request->input("per_page", 30))
        );
    }

    public function archiveChat(Chat $chat)
    {
        $user = Auth::user();
        $user->archive_chats = array_merge($user->archive_chats ?? [], [$chat->id]);
        $user->save();
    }

    public function unArchiveChat(int $chat)
    {
        $user = Auth::user();

        if ($user->archive_chats) {
            $user->archive_chats = array_filter($user->archive_chats, fn($val) => $val !== $chat);
        }
        $user->save();
    }

    public function archiveList(): AnonymousResourceCollection
    {
        $chats = Chat::withoutGlobalScope('archive')->with([
            'lastMessage',
            'event:id,label,chat_id',
            'members' => function ($query) {
                $query->select('users.id', 'firstname', 'lastname')
                    ->where('users.id', '!=', Auth::id());
            }
        ])
            ->withCount('members')
            ->whereHas('members', function ($query) {
                $query->where('users.id', Auth::id());
            })
            ->whereIn('id', Auth::user()->archive_chats ?? [])
            ->latest()
            ->get();

        return ChatResource::collection($chats);
    }

}
