<?php

namespace VendorName\Conversa\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VendorName\Conversa\Models\ConversaMessage;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Models\ConversaThread;

class ConversaMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => 1, // Default workspace ID
            'space_id' => null,
            'thread_id' => null,
            'parent_id' => null,
            'user_id' => 1, // Default user ID
            'content' => $this->faker->paragraph(),
            'type' => 'text',
            'metadata' => null,
            'edited_at' => null,
        ];
    }

    /**
     * Indicate that the message is in a space.
     */
    public function inSpace(?ConversaSpace $space = null): Factory
    {
        return $this->state(function (array $attributes) use ($space) {
            return [
                'space_id' => $space?->id ?? ConversaSpace::factory(),
                'thread_id' => null,
            ];
        });
    }

    /**
     * Indicate that the message is in a thread.
     */
    public function inThread(?ConversaThread $thread = null): Factory
    {
        return $this->state(function (array $attributes) use ($thread) {
            return [
                'thread_id' => $thread?->id ?? ConversaThread::factory(),
                'space_id' => null,
            ];
        });
    }

    /**
     * Indicate that the message is a reply to another message.
     */
    public function asReplyTo(?ConversaMessage $parent = null): Factory
    {
        return $this->state(function (array $attributes) use ($parent) {
            $parent = $parent ?? ConversaMessage::factory()->create();
            return [
                'parent_id' => $parent->id,
                'space_id' => $parent->space_id,
                'thread_id' => $parent->thread_id,
                'workspace_id' => $parent->workspace_id,
            ];
        });
    }

    /**
     * Indicate that the message is a system message.
     */
    public function system(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'system',
                'content' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Indicate that the message has been edited.
     */
    public function edited(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'edited_at' => now(),
            ];
        });
    }

    /**
     * Set the workspace ID for the message.
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
     * Set the sender ID for the message.
     */
    public function from(int $userId): Factory
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    /**
     * Add mentions to the message.
     */
    public function withMentions(array $userIds): Factory
    {
        return $this->state(function (array $attributes) use ($userIds) {
            return [
                'metadata' => [
                    'mentions' => $userIds,
                ],
            ];
        });
    }

    /**
     * Indicate that the message has attachments.
     */
    public function withAttachments(int $count = 1): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'file',
            ];
        })->has(
            ConversaAttachment::factory()->count($count),
            'attachments'
        );
    }
}
