<?php

namespace SwellSystems\Conversa\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use SwellSystems\Conversa\Models\ConversaReminder;
use SwellSystems\Conversa\Models\ConversaMessage;

class ReminderDue implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The reminder instance.
     *
     * @var ConversaReminder
     */
    public $reminder;

    /**
     * The message instance.
     *
     * @var ConversaMessage
     */
    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(ConversaReminder $reminder)
    {
        $this->reminder = $reminder;
        $this->message = $reminder->message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            // Private channel for the user who set the reminder
            new PresenceChannel(
                "presence.user.{$this->reminder->user_id}"
            ),
        ];

        // Add workspace channel if message is in a workspace
        if ($this->message->workspace_id) {
            $channels[] = new PresenceChannel(
                "presence.workspace.{$this->message->workspace_id}"
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
            'reminder_id' => $this->reminder->id,
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
            'note' => $this->reminder->note,
            'remind_at' => $this->reminder->remind_at->toISOString(),
            'workspace_id' => $this->reminder->workspace_id,
            'created_at' => $this->reminder->created_at->toISOString(),
            'snooze_options' => [
                ['value' => '1h', 'label' => 'In 1 hour'],
                ['value' => '3h', 'label' => 'In 3 hours'],
                ['value' => 'tomorrow', 'label' => 'Tomorrow morning'],
                ['value' => 'nextweek', 'label' => 'Next week'],
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'reminder.due';
    }

    /**
     * Determine if this event should broadcast.
     */
    public function broadcastWhen(): bool
    {
        return $this->reminder->status === 'pending' &&
               $this->reminder->remind_at <= now() &&
               !$this->reminder->reminded_at;
    }
}
