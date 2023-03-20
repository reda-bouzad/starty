<?php

namespace App\Http\Resources;

use App\Models\ChatMessage;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class ChatMessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return array_filter([
            "id" => $this->id,
            "chat_id" => $this->chat_id,
            "sender_id" => $this->sender,
            "receiver_id" => $this->receiver,
            "sender" => new UserResource($this->whenLoaded('userSender')),
            "receiver" => new UserResource($this->whenLoaded('userReceiver')),
            "chat" => new ChatResource($this->whenLoaded('chat')),
            "response_to_id" => $this->response_to,
            "response_to_message" => new ChatMessageResource($this->whenLoaded('responseToMessage')),
            "read" => $this->read,
            "content" => $this->content,
            "files" => $this->files,
            "created_at" => $this->created_at,
        ], fn($value) => $value !== null);
    }
}
