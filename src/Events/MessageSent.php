<?php

namespace VendorName\Conversa\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use VendorName\Conversa\Models\ConversaMessage;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message instance.
     *
     * @var \VendorName\Conversa\Models\ConversaMessage
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ConversaMessage $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->message->space_id) {
            return [
                new PrivateChannel('space.' . $this->message->space_id),
            ];
        }

        return [
            new PrivateChannel('thread.' . $this->message->thread_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'content' => $this->message->content,
            'type' => $this->message->type,
            'user' => [
                'id' => $this->message->user_id,
                'name' => $this->message->user->name,
                'avatar_url' => $this->message->user->avatar_url,
            ],
            'attachments' => $this->message->attachments->map(function ($attachment) {
                return [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_type' => $attachment->file_type,
                    'url' => $attachment->url,
                ];
            }),
            'created_at' => $this->message->created_at->toISOString(),
            'workspace_id' => $this->message->workspace_id,
            'space_id' => $this->message->space_id,
            'thread_id' => $this->message->thread_id,
            'parent_id' => $this->message->parent_id,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
