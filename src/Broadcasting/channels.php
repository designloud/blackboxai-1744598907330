<?php

use Illuminate\Support\Facades\Broadcast;
use VendorName\Conversa\Models\ConversaSpace;
use VendorName\Conversa\Models\ConversaThread;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Space Channel
Broadcast::channel('space.{spaceId}', function ($user, $spaceId) {
    $space = ConversaSpace::find($spaceId);
    
    if (!$space) {
        return false;
    }

    // Check if user belongs to the workspace
    if ($user->workspace_id !== $space->workspace_id) {
        return false;
    }

    // Public spaces are accessible to all workspace members
    if ($space->type === 'public') {
        return true;
    }

    // For private spaces, check if user is a member
    return $space->hasMember($user->id);
});

// Thread Channel
Broadcast::channel('thread.{threadId}', function ($user, $threadId) {
    $thread = ConversaThread::find($threadId);
    
    if (!$thread) {
        return false;
    }

    // Check if user belongs to the workspace
    if ($user->workspace_id !== $thread->workspace_id) {
        return false;
    }

    // Check if user is a member of the thread
    return $thread->hasMember($user->id);
});

// User Presence Channel
Broadcast::channel('presence.workspace.{workspaceId}', function ($user, $workspaceId) {
    if ($user->workspace_id != $workspaceId) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar_url' => $user->avatar_url,
        'email' => $user->email,
        'last_seen_at' => now()->toISOString(),
    ];
});

// Workspace Channel (for workspace-wide notifications)
Broadcast::channel('workspace.{workspaceId}', function ($user, $workspaceId) {
    return $user->workspace_id == $workspaceId;
});

// User Private Channel (for direct notifications)
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return $user->id == $userId;
});
