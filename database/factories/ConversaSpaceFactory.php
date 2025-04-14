<?php

namespace VendorName\Conversa\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VendorName\Conversa\Models\ConversaSpace;
use Illuminate\Support\Str;

class ConversaSpaceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaSpace::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);
        
        return [
            'workspace_id' => 1, // Default workspace ID
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['public', 'private']),
            'icon_url' => $this->faker->imageUrl(100, 100),
            'created_by' => 1, // Default user ID
        ];
    }

    /**
     * Indicate that the space is public.
     */
    public function public(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'public',
            ];
        });
    }

    /**
     * Indicate that the space is private.
     */
    public function private(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'private',
            ];
        });
    }

    /**
     * Set the workspace ID for the space.
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
     * Set the creator ID for the space.
     */
    public function createdBy(int $userId): Factory
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'created_by' => $userId,
            ];
        });
    }
}
