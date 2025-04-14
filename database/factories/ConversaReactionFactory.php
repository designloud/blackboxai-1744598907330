<?php

namespace VendorName\Conversa\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VendorName\Conversa\Models\ConversaReaction;
use VendorName\Conversa\Models\ConversaMessage;

class ConversaReactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaReaction::class;

    /**
     * Common emoji reactions.
     *
     * @var array
     */
    protected $commonReactions = [
        '👍', '❤️', '😂', '🎉', '😍',
        '👏', '🙌', '✨', '🔥', '💯',
        '😊', '🤔', '👀', '💪', '🙏',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'message_id' => ConversaMessage::factory(),
            'user_id' => 1, // Default user ID
            'reaction' => $this->faker->randomElement($this->commonReactions),
        ];
    }

    /**
     * Set a specific reaction.
     */
    public function withReaction(string $reaction): Factory
    {
        return $this->state(function (array $attributes) use ($reaction) {
            return [
                'reaction' => $reaction,
            ];
        });
    }

    /**
     * Set the user who reacted.
     */
    public function byUser(int $userId): Factory
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    /**
     * Set the message that was reacted to.
     */
    public function forMessage(ConversaMessage $message): Factory
    {
        return $this->state(function (array $attributes) use ($message) {
            return [
                'message_id' => $message->id,
            ];
        });
    }

    /**
     * Create multiple reactions for a message.
     */
    public static function createMultiple(ConversaMessage $message, array $userIds, ?string $reaction = null): void
    {
        foreach ($userIds as $userId) {
            static::new()->forMessage($message)
                ->byUser($userId)
                ->when($reaction, fn($factory) => $factory->withReaction($reaction))
                ->create();
        }
    }

    /**
     * Create a random set of reactions for a message.
     */
    public static function createRandom(ConversaMessage $message, int $count = 3): void
    {
        $factory = static::new();
        
        for ($i = 0; $i < $count; $i++) {
            $factory->forMessage($message)
                ->byUser($factory->faker->numberBetween(1, 10))
                ->create();
        }
    }

    /**
     * Create a specific set of reactions with counts.
     */
    public static function createWithCounts(ConversaMessage $message, array $reactionCounts): void
    {
        $factory = static::new();
        $userId = 1;

        foreach ($reactionCounts as $reaction => $count) {
            for ($i = 0; $i < $count; $i++) {
                $factory->forMessage($message)
                    ->byUser($userId++)
                    ->withReaction($reaction)
                    ->create();
            }
        }
    }
}
