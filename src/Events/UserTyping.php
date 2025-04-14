<?php

namespace VendorName\Conversa\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user who is typing.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * The space ID.
     *
     * @var int|null
     */
    protected $spaceId;

    /**
     * The thread ID.
     *
     * @var int|null
     */
    protected $threadId;

    /**
     * Create a new event instance.
     */
    public function __construct($user, ?int $spaceId = null, ?int $threadId = null)
    {
        $this->user = $user;
        $this->spaceId = $spaceId;
        $this->threadId = $threadId;
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
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar_url' => $this->user->avatar_url,
            ],
            'space_id' => $this->spaceId,
            'thread_id' => $this->threadId,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if we have either a space ID or thread ID
        return !is_null($this->spaceId) || !is_null($this->threadId);
    }

    /**
     * Get the channels that should be excluded from broadcasting.
     *
     * @return array<int, string>
     */
    public function dontBroadcastToCurrentUser(): array
    {
        return [$this->user->id];
    }

    /**
     * Create a new typing event for a space.
     */
    public static function inSpace($user, int $spaceId): self
    {
        return new static($user, $spaceId);
    }

    /**
     * Create a new typing event for a thread.
     */
    public static function inThread($user, int $threadId): self
    {
        return new static($user, null, $threadId);
    }

    /**
     * Get the user who is typing.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Get the space ID.
     */
    public function getSpaceId(): ?int
    {
        return $this->spaceId;
    }

    /**
     * Get the thread ID.
     */
    public function getThreadId(): ?int
    {
        return $this->threadId;
    }
}
