<?php

namespace SwellSystems\Conversa\Database\Factories;

use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaReminder;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversaReminderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaReminder::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => ConversaMessage::factory(),
            'user_id' => fake()->numberBetween(1, 100),
            'workspace_id' => fake()->numberBetween(1, 10),
            'remind_at' => fake()->dateTimeBetween('now', '+1 week'),
            'reminded_at' => null,
            'note' => fake()->optional()->sentence(),
            'status' => 'pending',
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the reminder is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'reminded_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the reminder is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Indicate that the reminder is due.
     */
    public function due(): static
    {
        return $this->state(fn (array $attributes) => [
            'remind_at' => fake()->dateTimeBetween('-1 hour', 'now'),
            'status' => 'pending',
            'reminded_at' => null,
        ]);
    }

    /**
     * Indicate that the reminder is upcoming.
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'remind_at' => fake()->dateTimeBetween('now', '+1 week'),
            'status' => 'pending',
            'reminded_at' => null,
        ]);
    }
}
