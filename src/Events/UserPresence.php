<?php

namespace SwellSystems\Conversa\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserPresence implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The user instance.
     *
     * @var \Illuminate\Contracts\Auth\Authenticatable
     */
    protected $user;

    /**
     * The workspace ID.
     *
     * @var int
     */
    protected $workspaceId;

    /**
     * The user's status.
     *
     * @var string
     */
    public $status;

    /**
     * The user's custom status message.
     *
     * @var string|null
     */
    public $statusMessage;

    /**
     * Create a new event instance.
     */
    public function __construct($user, int $workspaceId, string $status = 'online', ?string $statusMessage = null)
    {
        $this->user = $user;
        $this->workspaceId = $workspaceId;
        $this->status = $status;
        $this->statusMessage = $statusMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("presence.workspace.{$this->workspaceId}"),
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
                'email' => $this->user->email,
            ],
            'status' => $this->status,
            'status_message' => $this->statusMessage,
            'workspace_id' => $this->workspaceId,
            'last_seen_at' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.presence';
    }

    /**
     * Create a new online presence event.
     */
    public static function online($user, int $workspaceId, ?string $statusMessage = null): self
    {
        return new static($user, $workspaceId, 'online', $statusMessage);
    }

    /**
     * Create a new away presence event.
     */
    public static function away($user, int $workspaceId, ?string $statusMessage = null): self
    {
        return new static($user, $workspaceId, 'away', $statusMessage);
    }

    /**
     * Create a new offline presence event.
     */
    public static function offline($user, int $workspaceId): self
    {
        return new static($user, $workspaceId, 'offline');
    }

    /**
     * Create a new custom status presence event.
     */
    public static function customStatus($user, int $workspaceId, string $status, string $statusMessage): self
    {
        return new static($user, $workspaceId, $status, $statusMessage);
    }
}
