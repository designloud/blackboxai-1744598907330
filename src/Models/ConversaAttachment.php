<?php

namespace VendorName\Conversa\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Storage;

class ConversaAttachment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'conversa_attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'message_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'url',
        'formatted_size',
        'is_image',
        'thumbnail_url',
    ];

    /**
     * Get the message that owns the attachment.
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversaMessage::class, 'message_id');
    }

    /**
     * Get the URL for the attachment.
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk(config('conversa.attachments.storage_disk'))
            ->url($this->file_path);
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Check if the attachment is an image.
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get the thumbnail URL for the attachment.
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->is_image) {
            return null;
        }

        // If using intervention/image package, you could generate thumbnails
        // For now, return the original image URL
        return $this->url;
    }

    /**
     * Get the icon class based on file type.
     */
    public function getIconClass(): string
    {
        return match (true) {
            $this->is_image => 'fa-image',
            str_starts_with($this->mime_type, 'video/') => 'fa-video',
            str_starts_with($this->mime_type, 'audio/') => 'fa-music',
            $this->mime_type === 'application/pdf' => 'fa-file-pdf',
            str_contains($this->mime_type, 'word') => 'fa-file-word',
            str_contains($this->mime_type, 'excel') => 'fa-file-excel',
            str_contains($this->mime_type, 'powerpoint') => 'fa-file-powerpoint',
            str_contains($this->mime_type, 'zip') => 'fa-file-archive',
            default => 'fa-file',
        };
    }

    /**
     * Store a new file attachment.
     */
    public static function storeFile($file, $messageId): self
    {
        $path = $file->store(
            config('conversa.attachments.storage_path'),
            config('conversa.attachments.storage_disk')
        );

        $metadata = [];
        
        // If it's an image, get dimensions
        if (str_starts_with($file->getMimeType(), 'image/')) {
            $dimensions = getimagesize($file->path());
            if ($dimensions) {
                $metadata['dimensions'] = [
                    'width' => $dimensions[0],
                    'height' => $dimensions[1],
                ];
            }
        }

        return self::create([
            'message_id' => $messageId,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'file_type' => pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Delete the file from storage when the model is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function ($attachment) {
            Storage::disk(config('conversa.attachments.storage_disk'))
                ->delete($attachment->file_path);
        });
    }

    /**
     * Check if the file type is allowed.
     */
    public static function isAllowedFileType($mimeType): bool
    {
        return in_array($mimeType, config('conversa.attachments.allowed_types', []));
    }

    /**
     * Check if the file size is within limits.
     */
    public static function isAllowedFileSize($size): bool
    {
        $maxSize = config('conversa.attachments.max_size', 10240); // Default 10MB
        return $size <= ($maxSize * 1024); // Convert KB to bytes
    }

    /**
     * Validate a file upload.
     */
    public static function validateFile($file): array
    {
        $errors = [];

        if (!self::isAllowedFileType($file->getMimeType())) {
            $errors[] = 'File type not allowed.';
        }

        if (!self::isAllowedFileSize($file->getSize())) {
            $errors[] = 'File size exceeds maximum limit.';
        }

        return $errors;
    }
}
