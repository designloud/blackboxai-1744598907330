<?php

namespace SwellSystems\Conversa\Database\Factories;

use SwellSystems\Conversa\Models\ConversaSpace;
use SwellSystems\Conversa\Models\ConversaThread;
use SwellSystems\Conversa\Models\ConversaScheduledMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversaScheduledMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaScheduledMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => fake()->numberBetween(1, 10),
            'space_id' => ConversaSpace::factory(),
            'thread_id' => ConversaThread::factory(),
            'user_id' => fake()->numberBetween(1, 100),
            'content' => fake()->paragraph(),
            'metadata' => [
                'type' => 'text',
                'scheduled' => true,
                'mentions' => [],
                'attachments' => [],
            ],
            'scheduled_for' => fake()->dateTimeBetween('now', '+1 week'),
            'sent_at' => null,
            'status' => 'pending',
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the message has been sent.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the message has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the message is due for sending.
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_for' => fake()->dateTimeBetween('-1 hour', 'now'),
            'status' => 'pending',
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the message is scheduled for the future.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'scheduled_for' => fake()->dateTimeBetween('now', '+1 week'),
            'status' => 'pending',
            'sent_at' => null,
        ]);
    }

    /**
     * Indicate that the message is for a specific space.
     */
    public function forSpace(int $spaceId): static
    {
        return $this->state(fn (array $attributes) => [
            'space_id' => $spaceId,
            'thread_id' => null,
        ]);
    }

    /**
     * Indicate that the message is for a specific thread.
     */
    public function forThread(int $threadId): static
    {
        return $this->state(fn (array $attributes) => [
            'thread_id' => $threadId,
        ]);
    }

    /**
     * Add mentions to the message metadata.
     */
    public function withMentions(array $mentions): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'mentions' => $mentions,
            ]),
        ]);
    }

    /**
     * Add attachments to the message metadata.
     */
    public function withAttachments(array $attachments): static
    {
        return $this->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'attachments' => $attachments,
            ]),
        ]);
    }
}
