<?php

namespace SwellSystems\Conversa\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaReadReceipt;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The read receipt instance.
     *
     * @var ConversaReadReceipt
     */
    public $readReceipt;

    /**
     * The message instance.
     *
     * @var ConversaMessage
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ConversaReadReceipt $readReceipt)
    {
        $this->readReceipt = $readReceipt;
        $this->message = $readReceipt->message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];

        // Broadcast to space channel if message is in a space
        if ($this->message->space_id) {
            $channels[] = new PresenceChannel(
                "presence.space.{$this->message->space_id}"
            );
        }

        // Broadcast to thread channel if message is in a thread
        if ($this->message->thread_id) {
            $channels[] = new PresenceChannel(
                "presence.thread.{$this->message->thread_id}"
            );
        }

        // Broadcast to workspace channel
        $channels[] = new PresenceChannel(
            "presence.workspace.{$this->message->workspace_id}"
        );

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
            'message_id' => $this->message->id,
            'user_id' => $this->readReceipt->user_id,
            'read_at' => $this->readReceipt->read_at->toISOString(),
            'workspace_id' => $this->message->workspace_id,
            'space_id' => $this->message->space_id,
            'thread_id' => $this->message->thread_id,
            'read_count' => $this->message->read_count,
            'unread_count' => $this->message->unread_count,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.read';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return config('conversa.broadcasting.enabled', true) &&
               $this->readReceipt->read_at !== null;
    }
}
