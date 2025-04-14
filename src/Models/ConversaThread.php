<?php

namespace VendorName\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConversaThread extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversa_threads';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'workspace_id',
        'type',
        'name',
        'icon_url',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Apply workspace scope if configured
        if (config('conversa.workspace.model')) {
            static::addGlobalScope('workspace', function ($query) {
                $query->where('workspace_id', auth()->user()->workspace_id);
            });
        }
    }

    /**
     * Get the workspace that owns the thread.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(config('conversa.workspace.model'));
    }

    /**
     * Get the user who created the thread.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user.model'), 'created_by');
    }

    /**
     * Get the messages in the thread.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversaMessage::class, 'thread_id');
    }

    /**
     * Get the members of the thread.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('conversa.user.model'),
            'conversa_thread_members',
            'thread_id',
            'user_id'
        )
        ->withPivot('role', 'last_read_at', 'is_muted')
        ->withTimestamps();
    }

    /**
     * Get the thread settings for all members.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(ConversaThreadSetting::class, 'thread_id');
    }

    /**
     * Get the admins of the thread.
     */
    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    /**
     * Scope a query to only include direct message threads.
     */
    public function scopeDirect($query)
    {
        return $query->where('type', 'direct');
    }

    /**
     * Scope a query to only include group threads.
     */
    public function scopeGroup($query)
    {
        return $query->where('type', 'group');
    }

    /**
     * Check if the thread is a direct message.
     */
    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    /**
     * Check if the thread is a group.
     */
    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Check if a user is a member of the thread.
     */
    public function hasMember($userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a user is an admin of the thread.
     */
    public function isAdmin($userId): bool
    {
        return $this->admins()->where('user_id', $userId)->exists();
    }

    /**
     * Add a member to the thread.
     */
    public function addMember($userId, $role = 'member'): void
    {
        if (!$this->hasMember($userId)) {
            $this->members()->attach($userId, [
                'role' => $role,
                'last_read_at' => now()
            ]);
        }
    }

    /**
     * Remove a member from the thread.
     */
    public function removeMember($userId): void
    {
        $this->members()->detach($userId);
    }

    /**
     * Update member's role.
     */
    public function updateMemberRole($userId, $role): void
    {
        if ($this->hasMember($userId)) {
            $this->members()->updateExistingPivot($userId, ['role' => $role]);
        }
    }

    /**
     * Update last read timestamp for a member.
     */
    public function markAsReadFor($userId): void
    {
        if ($this->hasMember($userId)) {
            $this->members()->updateExistingPivot($userId, [
                'last_read_at' => now()
            ]);
        }
    }

    /**
     * Toggle mute status for a member.
     */
    public function toggleMuteFor($userId): void
    {
        if ($this->hasMember($userId)) {
            $member = $this->members()->where('user_id', $userId)->first();
            $this->members()->updateExistingPivot($userId, [
                'is_muted' => !$member->pivot->is_muted
            ]);
        }
    }

    /**
     * Get the display name for the thread.
     */
    public function getDisplayName(): string
    {
        if ($this->name) {
            return $this->name;
        }

        // For direct messages, show the other user's name
        if ($this->isDirect()) {
            $otherMember = $this->members()
                ->where('user_id', '!=', auth()->id())
                ->first();
            
            return $otherMember ? $otherMember->name : 'Deleted User';
        }

        // For groups without a name, list first few members
        return $this->members()
            ->where('user_id', '!=', auth()->id())
            ->take(3)
            ->pluck('name')
            ->join(', ');
    }

    /**
     * Get unread messages count for a user.
     */
    public function getUnreadCountFor($userId): int
    {
        $lastRead = $this->members()
            ->where('user_id', $userId)
            ->first()
            ?->pivot
            ->last_read_at;

        if (!$lastRead) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('created_at', '>', $lastRead)
            ->where('user_id', '!=', $userId)
            ->count();
    }
}
