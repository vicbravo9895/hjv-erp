<?php

namespace App\Traits;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAttachments
{
    /**
     * Get all attachments for this model.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get attachments of a specific type.
     */
    public function attachmentsByType(string $mimeType): MorphMany
    {
        return $this->attachments()->where('mime_type', $mimeType);
    }

    /**
     * Get image attachments only.
     */
    public function imageAttachments(): MorphMany
    {
        return $this->attachments()->where('mime_type', 'like', 'image/%');
    }

    /**
     * Get PDF attachments only.
     */
    public function pdfAttachments(): MorphMany
    {
        return $this->attachments()->where('mime_type', 'application/pdf');
    }

    /**
     * Check if model has any attachments.
     */
    public function hasAttachments(): bool
    {
        return $this->attachments()->exists();
    }

    /**
     * Get the total size of all attachments.
     */
    public function getTotalAttachmentSize(): int
    {
        return $this->attachments()->sum('file_size');
    }

    /**
     * Get human readable total attachment size.
     */
    public function getHumanTotalAttachmentSizeAttribute(): string
    {
        $bytes = $this->getTotalAttachmentSize();
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $bytes;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Clean up attachments when model is deleted.
     */
    protected static function bootHasAttachments(): void
    {
        static::deleting(function ($model) {
            $attachmentService = app(AttachmentService::class);
            $attachmentService->cleanupModelAttachments(
                get_class($model),
                $model->id
            );
        });
    }

    /**
     * Add attachment to this model.
     */
    public function addAttachment(
        \Illuminate\Http\UploadedFile|\Livewire\Features\SupportFileUploads\TemporaryUploadedFile $file,
        ?int $uploadedBy = null
    ): ?Attachment {
        $attachmentService = app(AttachmentService::class);
        return $attachmentService->storeFile($file, $this, $uploadedBy);
    }

    /**
     * Add multiple attachments to this model.
     */
    public function addAttachments(array $files, ?int $uploadedBy = null): array
    {
        $attachmentService = app(AttachmentService::class);
        return $attachmentService->storeMultipleFiles($files, $this, $uploadedBy);
    }

    /**
     * Remove an attachment from this model.
     */
    public function removeAttachment(Attachment $attachment): bool
    {
        if ($attachment->attachable_id !== $this->id || $attachment->attachable_type !== get_class($this)) {
            return false;
        }

        $attachmentService = app(AttachmentService::class);
        return $attachmentService->deleteAttachment($attachment);
    }

    /**
     * Remove all attachments from this model.
     */
    public function removeAllAttachments(): int
    {
        $removed = 0;
        $attachmentService = app(AttachmentService::class);

        foreach ($this->attachments as $attachment) {
            if ($attachmentService->deleteAttachment($attachment)) {
                $removed++;
            }
        }

        return $removed;
    }
}