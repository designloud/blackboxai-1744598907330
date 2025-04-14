<?php

namespace SwellSystems\Conversa\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use SwellSystems\Conversa\Events\UserPresence;
use Carbon\Carbon;

class UserPresenceManager
{
    /**
     * Cache prefix for presence data.
     */
    protected const CACHE_PREFIX = 'conversa:presence:';

    /**
     * Default presence timeout in minutes.
     */
    protected const PRESENCE_TIMEOUT = 5;

    /**
     * Update a user's presence status.
     *
     * @param mixed $user
     * @param int $workspaceId
     * @param string $status
     * @param string|null $statusMessage
     * @return void
     */
    public function updatePresence($user, int $workspaceId, string $status = 'online', ?string $statusMessage = null): void
    {
        $presenceData = [
            'user_id' => $user->id,
            'workspace_id' => $workspaceId,
            'status' => $status,
            'status_message' => $statusMessage,
            'last_seen_at' => now()->toISOString(),
        ];

        // Store presence data in cache
        Cache::put(
            $this->getCacheKey($user->id, $workspaceId),
            $presenceData,
            now()->addMinutes(static::PRESENCE_TIMEOUT)
        );

        // Broadcast presence update
        Event::dispatch(new UserPresence($user, $workspaceId, $status, $statusMessage));
    }

    /**
     * Mark a user as online.
     *
     * @param mixed $user
     * @param int $workspaceId
     * @param string|null $statusMessage
     * @return void
     */
    public function markOnline($user, int $workspaceId, ?string $statusMessage = null): void
    {
        $this->updatePresence($user, $workspaceId, 'online', $statusMessage);
    }

    /**
     * Mark a user as away.
     *
     * @param mixed $user
     * @param int $workspaceId
     * @param string|null $statusMessage
     * @return void
     */
    public function markAway($user, int $workspaceId, ?string $statusMessage = null): void
    {
        $this->updatePresence($user, $workspaceId, 'away', $statusMessage);
    }

    /**
     * Mark a user as offline.
     *
     * @param mixed $user
     * @param int $workspaceId
     * @return void
     */
    public function markOffline($user, int $workspaceId): void
    {
        $this->updatePresence($user, $workspaceId, 'offline');
        
        // Remove presence data from cache
        Cache::forget($this->getCacheKey($user->id, $workspaceId));
    }

    /**
     * Set a custom status for a user.
     *
     * @param mixed $user
     * @param int $workspaceId
     * @param string $status
     * @param string $statusMessage
     * @return void
     */
    public function setCustomStatus($user, int $workspaceId, string $status, string $statusMessage): void
    {
        $this->updatePresence($user, $workspaceId, $status, $statusMessage);
    }

    /**
     * Get a user's current presence data.
     *
     * @param int $userId
     * @param int $workspaceId
     * @return array|null
     */
    public function getPresence(int $userId, int $workspaceId): ?array
    {
        return Cache::get($this->getCacheKey($userId, $workspaceId));
    }

    /**
     * Get all online users in a workspace.
     *
     * @param int $workspaceId
     * @return array
     */
    public function getOnlineUsers(int $workspaceId): array
    {
        $pattern = static::CACHE_PREFIX . "*:workspace:{$workspaceId}";
        $keys = Cache::get(Cache::get($pattern) ?? []);

        $onlineUsers = [];
        foreach ($keys as $key) {
            $presenceData = Cache::get($key);
            if ($presenceData && $presenceData['status'] !== 'offline') {
                $onlineUsers[] = $presenceData;
            }
        }

        return $onlineUsers;
    }

    /**
     * Check if a user is online.
     *
     * @param int $userId
     * @param int $workspaceId
     * @return bool
     */
    public function isOnline(int $userId, int $workspaceId): bool
    {
        $presenceData = $this->getPresence($userId, $workspaceId);
        
        if (!$presenceData) {
            return false;
        }

        // Check if the last seen timestamp is within the timeout period
        $lastSeen = Carbon::parse($presenceData['last_seen_at']);
        return $lastSeen->diffInMinutes(now()) < static::PRESENCE_TIMEOUT &&
               $presenceData['status'] !== 'offline';
    }

    /**
     * Get the cache key for a user's presence data.
     *
     * @param int $userId
     * @param int $workspaceId
     * @return string
     */
    protected function getCacheKey(int $userId, int $workspaceId): string
    {
        return static::CACHE_PREFIX . "user:{$userId}:workspace:{$workspaceId}";
    }

    /**
     * Clean up expired presence data.
     *
     * @return void
     */
    public function cleanup(): void
    {
        $pattern = static::CACHE_PREFIX . '*';
        $keys = Cache::get(Cache::get($pattern) ?? []);

        foreach ($keys as $key) {
            $presenceData = Cache::get($key);
            if ($presenceData) {
                $lastSeen = Carbon::parse($presenceData['last_seen_at']);
                if ($lastSeen->diffInMinutes(now()) >= static::PRESENCE_TIMEOUT) {
                    Cache::forget($key);
                }
            }
        }
    }

    /**
     * Get the presence timeout value in minutes.
     *
     * @return int
     */
    public static function getTimeout(): int
    {
        return static::PRESENCE_TIMEOUT;
    }
}
