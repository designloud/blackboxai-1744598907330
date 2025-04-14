<?php

namespace VendorName\Conversa\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VendorName\Conversa\Models\ConversaThreadSetting;
use VendorName\Conversa\Models\ConversaThread;

class ConversaThreadSettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ConversaThreadSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'thread_id' => ConversaThread::factory(),
            'user_id' => 1, // Default user ID
            'settings' => [
                'notifications' => true,
                'sound' => true,
                'desktop_notifications' => true,
                'email_notifications' => false,
                'theme' => 'light',
                'custom_background' => null,
            ],
        ];
    }

    /**
     * Set the thread for the settings.
     */
    public function forThread(ConversaThread $thread): Factory
    {
        return $this->state(function (array $attributes) use ($thread) {
            return [
                'thread_id' => $thread->id,
            ];
        });
    }

    /**
     * Set the user for the settings.
     */
    public function forUser(int $userId): Factory
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });
    }

    /**
     * Disable all notifications.
     */
    public function notificationsDisabled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'settings' => array_merge($attributes['settings'] ?? [], [
                    'notifications' => false,
                    'sound' => false,
                    'desktop_notifications' => false,
                    'email_notifications' => false,
                ]),
            ];
        });
    }

    /**
     * Enable all notifications.
     */
    public function notificationsEnabled(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'settings' => array_merge($attributes['settings'] ?? [], [
                    'notifications' => true,
                    'sound' => true,
                    'desktop_notifications' => true,
                    'email_notifications' => true,
                ]),
            ];
        });
    }

    /**
     * Set dark theme.
     */
    public function darkTheme(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'settings' => array_merge($attributes['settings'] ?? [], [
                    'theme' => 'dark',
                ]),
            ];
        });
    }

    /**
     * Set light theme.
     */
    public function lightTheme(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'settings' => array_merge($attributes['settings'] ?? [], [
                    'theme' => 'light',
                ]),
            ];
        });
    }

    /**
     * Set custom background.
     */
    public function withCustomBackground(string $backgroundUrl): Factory
    {
        return $this->state(function (array $attributes) use ($backgroundUrl) {
            return [
                'settings' => array_merge($attributes['settings'] ?? [], [
                    'custom_background' => $backgroundUrl,
                ]),
            ];
        });
    }

    /**
     * Set custom settings.
     */
    public function withSettings(array $settings): Factory
    {
        return $this->state(function (array $attributes) use ($settings) {
            return [
                'settings' => array_merge($attributes['settings'] ?? [], $settings),
            ];
        });
    }

    /**
     * Create settings for multiple users in a thread.
     */
    public static function createForUsers(ConversaThread $thread, array $userIds, ?array $settings = null): void
    {
        foreach ($userIds as $userId) {
            static::new()
                ->forThread($thread)
                ->forUser($userId)
                ->when($settings, fn($factory) => $factory->withSettings($settings))
                ->create();
        }
    }
}
