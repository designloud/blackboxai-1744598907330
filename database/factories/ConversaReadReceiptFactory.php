<?php

namespace SwellSystems\Conversa\Database\Factories;

use SwellSystems\Conversa\Models\ConversaMessage;
use SwellSystems\Conversa\Models\ConversaReadReceipt;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversaReadReceiptFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaReadReceipt::class;

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
            'read_at' => fake()->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'created_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'updated_at' => function (array $attributes) {
                return fake()->dateTimeBetween($attributes['created_at'], 'now');
            },
        ];
    }

    /**
     * Indicate that the receipt is read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    /**
     * Indicate that the receipt is unread.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Indicate that the receipt was read recently.
     */
    public function readRecently(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the receipt was read a while ago.
     */
    public function readLongAgo(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
        ]);
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (ConversaReadReceipt $receipt) {
            // Any post-making configurations
        })->afterCreating(function (ConversaReadReceipt $receipt) {
            // Update message read/unread counts
            $message = $receipt->message;
            $readCount = $message->readReceipts()->whereNotNull('read_at')->count();
            $unreadCount = $message->readReceipts()->whereNull('read_at')->count();
            
            $message->update([
                'read_count' => $readCount,
                'unread_count' => $unreadCount,
            ]);
        });
    }
}
