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

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message instance.
     *
     * @var \VendorName\Conversa\Models\ConversaMessage
     */
    protected $message;

    /**
     * The message ID.
     *
     * @var int
     */
    public $messageId;

    /**
     * The space ID.
     *
     * @var int|null
     */
    public $spaceId;

    /**
     * The thread ID.
     *
     * @var int|null
     */
    public $threadId;

    /**
     * The workspace ID.
     *
     * @var int
     */
    public $workspaceId;

    /**
     * Create a new event instance.
     */
    public function __construct(ConversaMessage $message)
    {
        $this->message = $message;
        $this->messageId = $message->id;
        $this->spaceId = $message->space_id;
        $this->threadId = $message->thread_id;
        $this->workspaceId = $message->workspace_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        if ($this->spaceId) {
            return [
                new PrivateChannel('space.' . $this->spaceId),
            ];
        }

        return [
            new PrivateChannel('thread.' . $this->threadId),
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
            'message_id' => $this->messageId,
            'deleted_by' => [
                'id' => auth()->id(),
                'name' => auth()->user()->name,
            ],
            'deleted_at' => now()->toISOString(),
            'workspace_id' => $this->workspaceId,
            'space_id' => $this->spaceId,
            'thread_id' => $this->threadId,
            // Include parent_id if this was a reply
            'parent_id' => $this->message->parent_id,
            // Include child_count if this message had replies
            'child_count' => $this->message->replies()->count(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if the message exists and the user has permission to delete it
        return !is_null($this->message) && 
               auth()->check() && 
               (auth()->id() === $this->message->user_id || 
                auth()->user()->can('delete', $this->message));
    }

    /**
     * Get the message that was deleted.
     *
     * @return \VendorName\Conversa\Models\ConversaMessage
     */
    public function getMessage(): ConversaMessage
    {
        return $this->message;
    }

    /**
     * Get the channels that should be excluded from broadcasting.
     *
     * @return array<int, string>
     */
    public function dontBroadcastToCurrentUser(): array
    {
        return [auth()->id()];
    }
}
