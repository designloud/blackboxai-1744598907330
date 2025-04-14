<?php

namespace SwellSystems\Conversa\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SwellSystems\Conversa\Models\ConversaMention;
use SwellSystems\Conversa\Models\ConversaMessage;

class UserMentioned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The mention instance.
     *
     * @var ConversaMention
     */
    public $mention;

    /**
     * The message instance.
     *
     * @var ConversaMessage
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ConversaMention $mention)
    {
        $this->mention = $mention;
        $this->message = $mention->message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            // Private channel for the mentioned user
            new PresenceChannel(
                "presence.user.{$this->mention->mentionable_id}"
            ),
        ];

        // Add workspace channel if message is in a workspace
        if ($this->message->workspace_id) {
            $channels[] = new PresenceChannel(
                "presence.workspace.{$this->message->workspace_id}"
            );
        }

        // Add space channel if message is in a space
        if ($this->message->space_id) {
            $channels[] = new PresenceChannel(
                "presence.space.{$this->message->space_id}"
            );
        }

        // Add thread channel if message is in a thread
        if ($this->message->thread_id) {
            $channels[] = new PresenceChannel(
                "presence.thread.{$this->message->thread_id}"
            );
        }

        return $channels;
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'mention_id' => $this->mention->id,
            'message' => [
                'id' => $this->message->id,
                'content' => $this->message->content,
                'user' => [
                    'id' => $this->message->user_id,
                    'name' => $this->message->user->name,
                    'avatar_url' => $this->message->user->avatar_url,
                ],
                'workspace_id' => $this->message->workspace_id,
                'space_id' => $this->message->space_id,
                'thread_id' => $this->message->thread_id,
                'created_at' => $this->message->created_at->toISOString(),
            ],
            'mentionable' => [
                'id' => $this->mention->mentionable_id,
                'type' => $this->mention->mentionable_type,
            ],
            'workspace_id' => $this->mention->workspace_id,
            'created_at' => $this->mention->created_at->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.mentioned';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        // Only broadcast if notifications are enabled for the mentioned user
        if ($this->mention->mentionable_type === config('conversa.user_model', 'App\\Models\\User')) {
            $threadSettings = $this->message->thread?->settings()
                ->where('user_id', $this->mention->mentionable_id)
                ->first();

            return !$threadSettings || $threadSettings->mention_notifications_enabled;
        }

        return true;
    }
}
