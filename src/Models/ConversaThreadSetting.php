<?php

namespace VendorName\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConversaThreadSetting extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversa_thread_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'thread_id',
        'user_id',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the thread that owns the setting.
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(ConversaThread::class, 'thread_id');
    }

    /**
     * Get the user that owns the setting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('conversa.user.model'), 'user_id');
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting($key, $default = null)
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Update a specific setting value.
     */
    public function updateSetting($key, $value): void
    {
        $this->settings[$key] = $value;
        $this->save();
    }

    /**
     * Check if a specific setting exists.
     */
    public function hasSetting($key): bool
    {
        return array_key_exists($key, $this->settings);
    }
}
