<?php

namespace App\Traits;

use App\Models\Attachment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

trait ProcessesAttachments
{
    /**
     * Process uploaded files and create attachment records.
     */
    protected function processAttachments(array $data, $record, string $fieldName = 'attachments'): void
    {
        if (!isset($data[$fieldName]) || empty($data[$fieldName])) {
            return;
        }

        $filePaths = is_array($data[$fieldName]) ? $data[$fieldName] : [$data[$fieldName]];

        foreach ($filePaths as $filePath) {
            if (is_string($filePath)) {
                $this->createAttachmentRecord($filePath, $record);
            }
        }
    }

    /**
     * Create an attachment record from a file path.
     */
    protected function createAttachmentRecord(string $filePath, $record): void
    {
        try {
            $disk = Storage::disk('minio');
            
            if (!$disk->exists($filePath)) {
                return;
            }

            // Get file information
            $fileName = basename($filePath);
            $fileSize = $disk->size($filePath);
            
            // Try to determine MIME type from file extension
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $mimeType = $this->getMimeTypeFromExtension($extension);

            // Create attachment record
            Attachment::create([
                'attachable_type' => get_class($record),
                'attachable_id' => $record->id,
                'file_name' => $fileName,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'uploaded_by' => Auth::id(),
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to create attachment record', [
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get MIME type from file extension.
     */
    protected function getMimeTypeFromExtension(string $extension): string
    {
        return match ($extension) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };
    }
}