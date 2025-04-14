<?php

namespace VendorName\Conversa\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VendorName\Conversa\Models\ConversaThread;

class ConversaThreadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaThread::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => 1, // Default workspace ID
            'type' => 'direct',
            'name' => null,
            'icon_url' => null,
            'created_by' => 1, // Default user ID
        ];
    }

    /**
     * Indicate that the thread is a direct message.
     */
    public function direct(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'direct',
                'name' => null,
            ];
        });
    }

    /**
     * Indicate that the thread is a group.
     */
    public function group(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'group',
                'name' => $this->faker->words(3, true),
                'icon_url' => $this->faker->imageUrl(100, 100),
            ];
        });
    }

    /**
     * Set the workspace ID for the thread.
     */
    public function forWorkspace(int $workspaceId): Factory
    {
        return $this->state(function (array $attributes) use ($workspaceId) {
            return [
                'workspace_id' => $workspaceId,
            ];
        });
    }

    /**
     * Set the creator ID for the thread.
     */
    public function createdBy(int $userId): Factory
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
            ];
        });
    }

    /**
     * Add members to the thread.
     */
    public function withMembers(array $userIds, string $role = 'member'): Factory
    {
        return $this->afterCreating(function (ConversaThread $thread) use ($userIds, $role) {
            foreach ($userIds as $userId) {
                $thread->addMember($userId, $role);
            }
        });
    }

    /**
     * Add messages to the thread.
     */
    public function withMessages(int $count = 1): Factory
    {
        return $this->has(
            ConversaMessage::factory()
                ->count($count)
                ->state(function (array $attributes, ConversaThread $thread) {
                    return [
                        'workspace_id' => $thread->workspace_id,
                        'thread_id' => $thread->id,
                    ];
                }),
            'messages'
        );
    }

    /**
     * Create a direct message thread between two users.
     */
    public function between(int $user1Id, int $user2Id): Factory
    {
        return $this->direct()
            ->createdBy($user1Id)
            ->afterCreating(function (ConversaThread $thread) use ($user1Id, $user2Id) {
                $thread->addMember($user1Id);
                $thread->addMember($user2Id);
            });
    }

    /**
     * Create a group thread with the specified members.
     */
    public function groupWith(array $memberIds, int $creatorId = null): Factory
    {
        return $this->group()
            ->createdBy($creatorId ?? $memberIds[0])
            ->afterCreating(function (ConversaThread $thread) use ($memberIds) {
                foreach ($memberIds as $memberId) {
                    $thread->addMember($memberId);
                }
            });
    }

    /**
     * Add thread settings for members.
     */
    public function withSettings(array $settings = []): Factory
    {
        return $this->afterCreating(function (ConversaThread $thread) use ($settings) {
            foreach ($settings as $userId => $userSettings) {
                $thread->settings()->create([
                    'user_id' => $userId,
                    'settings' => $userSettings,
                ]);
            }
        });
    }
}
