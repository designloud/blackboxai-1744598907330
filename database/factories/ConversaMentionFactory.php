<?php

namespace SwellSystems\Conversa\Database\Factories;

use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaMention;
use SwellSystems\Conversa\Models\ConversaSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversaMentionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaMention::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => ConversaMessage::factory(),
            'mentionable_id' => fake()->numberBetween(1, 100),
            'mentionable_type' => config('conversa.user_model', 'App\\Models\\User'),
            'workspace_id' => fake()->numberBetween(1, 10),
            'read_at' => fake()->optional(0.7)->dateTimeBetween('-1 week', 'now'),
            'notified_at' => fake()->optional(0.9)->dateTimeBetween('-1 week', 'now'),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the mention is for a user.
     */
    public function forUser(int $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'mentionable_id' => $userId ?? fake()->numberBetween(1, 100),
            'mentionable_type' => config('conversa.user_model', 'App\\Models\\User'),
        ]);
    }

    /**
     * Indicate that the mention is for a channel.
     */
    public function forChannel(int $channelId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'mentionable_id' => $channelId ?? ConversaSpace::factory(),
            'mentionable_type' => ConversaSpace::class,
        ]);
    }

    /**
     * Indicate that the mention is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the mention is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the mention is notified.
     */
    public function notified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the mention is not notified.
     */
    public function notNotified(): static
    {
        return $this->state(fn (array $attributes) => [
            'notified_at' => null,
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ConversaMention $mention) {
            // Any post-making configurations
        })->afterCreating(function (ConversaMention $mention) {
            // Update message mentions count
            $message = $mention->message;
            $mentionsCount = $message->mentions()->count();
            
            $message->update([
                'mentions_count' => $mentionsCount,
            ]);
        });
    }
}
