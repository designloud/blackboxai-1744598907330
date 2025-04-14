<?php

namespace VendorName\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ConversaSpace extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversa_spaces';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'workspace_id',
        'name',
        'slug',
        'description',
        'type',
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
        static::creating(function ($space) {
            if (empty($space->slug)) {
                $space->slug = Str::slug($space->name);
            }
        });

        // Apply workspace scope if configured
        if (config('conversa.workspace.model')) {
            static::addGlobalScope('workspace', function ($query) {
                $query->where('workspace_id', auth()->user()->workspace_id);
            });
        }
    }

    /**
     * Get the workspace that owns the space.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(config('conversa.workspace.model'));
    }

    /**
     * Get the user who created the space.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user.model'), 'created_by');
    }

    /**
     * Get the messages for the space.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversaMessage::class, 'space_id');
    }

    /**
     * Get the members of the space.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(
            config('conversa.user.model'),
            'conversa_space_members',
            'space_id',
            'user_id'
        )
        ->withPivot('role', 'last_read_at')
        ->withTimestamps();
    }

    /**
     * Get the admins of the space.
     */
    public function admins(): BelongsToMany
    {
        return $this->members()->wherePivot('role', 'admin');
    }

    /**
     * Scope a query to only include public spaces.
     */
    public function scopePublic($query)
    {
        return $query->where('type', 'public');
    }

    /**
     * Scope a query to only include private spaces.
     */
    public function scopePrivate($query)
    {
        return $query->where('type', 'private');
    }

    /**
     * Check if the space is public.
     */
    public function isPublic(): bool
    {
        return $this->type === 'public';
    }

    /**
     * Check if the space is private.
     */
    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    /**
     * Check if a user is a member of the space.
     */
    public function hasMember($userId): bool
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    /**
     * Check if a user is an admin of the space.
     */
    public function isAdmin($userId): bool
    {
        return $this->admins()->where('user_id', $userId)->exists();
    }

    /**
     * Add a member to the space.
     */
    public function addMember($userId, $role = 'member'): void
    {
        if (!$this->hasMember($userId)) {
            $this->members()->attach($userId, ['role' => $role]);
        }
    }

    /**
     * Remove a member from the space.
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
}
