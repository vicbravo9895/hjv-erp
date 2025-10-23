<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    /**
     * Get the owning attachable model.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this attachment.
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full storage path for the file.
     */
    public function getFullPathAttribute(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Get the file URL for download.
     */
    public function getUrlAttribute(): string
    {
        return route('attachments.download', $this->id);
    }

    /**
     * Check if the file is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Check if the file is a PDF.
     */
    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    /**
     * Get human readable file size.
     */
    public function getHumanFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Generate a unique file path for storage.
     */
    public static function generateStoragePath(string $modelType, int $modelId, string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $extension;
        
        return "attachments/{$modelType}/{$modelId}/{$filename}";
    }

    /**
     * Validate file type and size.
     */
    public static function validateFile(array $file): array
    {
        $errors = [];
        
        // Allowed MIME types
        $allowedTypes = [
            'application/pdf',
            'image/jpeg',
            'image/jpg', 
            'image/png',
            'image/gif',
        ];
        
        // Check MIME type
        if (!in_array($file['mime_type'], $allowedTypes)) {
            $errors[] = 'Tipo de archivo no permitido. Solo se permiten PDF, JPG, PNG y GIF.';
        }
        
        // Check file size (10MB max)
        $maxSize = 10 * 1024 * 1024; // 10MB in bytes
        if ($file['size'] > $maxSize) {
            $errors[] = 'El archivo es demasiado grande. Tamaño máximo permitido: 10MB.';
        }
        
        return $errors;
    }

    /**
     * Clean up file when attachment is deleted.
     */
    protected static function booted(): void
    {
        static::deleting(function (Attachment $attachment) {
            if (Storage::disk('minio')->exists($attachment->file_path)) {
                Storage::disk('minio')->delete($attachment->file_path);
            }
        });
    }
}
