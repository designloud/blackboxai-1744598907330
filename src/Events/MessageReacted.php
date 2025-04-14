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
use VendorName\Conversa\Models\ConversaReaction;

class MessageReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The message instance.
     *
     * @var \VendorName\Conversa\Models\ConversaMessage
     */
    public $message;

    /**
     * The reaction instance.
     *
     * @var \VendorName\Conversa\Models\ConversaReaction
     */
    public $reaction;

    /**
     * Whether the reaction was added or removed.
     *
     * @var bool
     */
    public $added;

    /**
     * Create a new event instance.
     */
    public function __construct(ConversaMessage $message, ConversaReaction $reaction, bool $added)
    {
        $this->message = $message;
        $this->reaction = $reaction;
        $this->added = $added;
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
            'message_id' => $this->message->id,
            'reaction' => $this->reaction->reaction,
            'user' => [
                'id' => $this->reaction->user_id,
                'name' => $this->reaction->user->name,
                'avatar_url' => $this->reaction->user->avatar_url,
            ],
            'added' => $this->added,
            'reaction_counts' => $this->message->getReactionCounts(),
            'workspace_id' => $this->message->workspace_id,
            'space_id' => $this->message->space_id,
            'thread_id' => $this->message->thread_id,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.reacted';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if the message and reaction still exist
        return !is_null($this->message) && !is_null($this->reaction);
    }
}
