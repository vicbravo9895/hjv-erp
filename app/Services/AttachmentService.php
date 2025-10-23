<?php

namespace App\Services;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class AttachmentService
{
    protected string $disk = 'minio';
    
    protected array $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/jpg', 
        'image/png',
        'image/gif',
    ];
    
    protected int $maxFileSize = 10485760; // 10MB in bytes
    
    /**
     * Store an uploaded file and create an Attachment record.
     */
    public function storeFile(
        UploadedFile|TemporaryUploadedFile $file, 
        Model $attachable, 
        ?int $uploadedBy = null
    ): ?Attachment {
        try {
            DB::beginTransaction();
            
            // Validate the file
            $validationErrors = $this->validateFile($file);
            if (!empty($validationErrors)) {
                Log::warning('File validation failed', [
                    'file' => $file->getClientOriginalName(),
                    'errors' => $validationErrors
                ]);
                return null;
            }
            
            // Generate secure file path
            $filePath = $this->generateSecureFilePath(
                $attachable,
                $file->getClientOriginalName()
            );
            
            // Store file to MinIO
            $storedPath = Storage::disk($this->disk)->putFileAs(
                dirname($filePath),
                $file,
                basename($filePath),
                'private'
            );
            
            if (!$storedPath) {
                Log::error('Failed to store file to MinIO', [
                    'file' => $file->getClientOriginalName(),
                    'path' => $filePath
                ]);
                return null;
            }
            
            // Create Attachment record
            $attachment = Attachment::create([
                'attachable_type' => get_class($attachable),
                'attachable_id' => $attachable->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $storedPath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'uploaded_by' => $uploadedBy ?? Auth::id(),
            ]);
            
            DB::commit();
            
            Log::info('File stored successfully', [
                'attachment_id' => $attachment->id,
                'file' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);
            
            return $attachment;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up file if it was stored
            if (isset($storedPath) && Storage::disk($this->disk)->exists($storedPath)) {
                Storage::disk($this->disk)->delete($storedPath);
            }
            
            Log::error('Failed to store attachment', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }
    
    /**
     * Store multiple files for a model.
     */
    public function storeMultipleFiles(
        array $files, 
        Model $attachable, 
        ?int $uploadedBy = null
    ): array {
        $attachments = [];
        
        foreach ($files as $file) {
            if ($file instanceof UploadedFile || $file instanceof TemporaryUploadedFile) {
                $attachment = $this->storeFile($file, $attachable, $uploadedBy);
                if ($attachment) {
                    $attachments[] = $attachment;
                }
            }
        }
        
        return $attachments;
    }
    
    /**
     * Validate an uploaded file.
     */
    public function validateFile(UploadedFile|TemporaryUploadedFile $file): array
    {
        $errors = [];
        
        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $errors[] = "El archivo excede el tamaño máximo permitido de " . 
                       $this->formatFileSize($this->maxFileSize);
        }
        
        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            $errors[] = "Tipo de archivo no permitido: {$mimeType}";
        }
        
        // Check file extension
        $extension = strtolower($file->getClientOriginalExtension());
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = "Extensión de archivo no permitida: {$extension}";
        }
        
        // Security checks
        if ($this->hasSecurityRisks($file)) {
            $errors[] = "El archivo contiene contenido potencialmente peligroso";
        }
        
        return $errors;
    }
    
    /**
     * Check if file has security risks.
     */
    protected function hasSecurityRisks(UploadedFile|TemporaryUploadedFile $file): bool
    {
        try {
            // Read first few bytes to check for executable signatures
            $handle = fopen($file->getRealPath(), 'rb');
            if (!$handle) {
                return true; // Can't read file, consider it risky
            }
            
            $header = fread($handle, 1024);
            fclose($handle);
            
            // Check for executable signatures
            $dangerousSignatures = [
                'MZ',           // PE executable
                '#!/',          // Shell script
                '<?php',        // PHP script
                '<script',      // JavaScript
                'PK',           // ZIP/JAR (could contain executables)
                '%PDF-1.',      // PDF (but check for embedded JS)
            ];
            
            foreach ($dangerousSignatures as $signature) {
                if (str_starts_with($header, $signature)) {
                    // PDF is allowed, but check for JavaScript
                    if ($signature === '%PDF-1.') {
                        return $this->pdfContainsJavaScript($file);
                    }
                    // Other signatures are dangerous
                    if ($signature !== '%PDF-1.') {
                        return true;
                    }
                }
            }
            
            // Check for null bytes (could indicate binary executable)
            if (str_contains($header, "\x00")) {
                // Images and PDFs can have null bytes, so check MIME type
                $mimeType = $file->getMimeType();
                if (!str_starts_with($mimeType, 'image/') && $mimeType !== 'application/pdf') {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::warning('Security check failed for file', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return true; // If we can't check, consider it risky
        }
    }
    
    /**
     * Check if PDF contains JavaScript.
     */
    protected function pdfContainsJavaScript(UploadedFile|TemporaryUploadedFile $file): bool
    {
        try {
            $content = file_get_contents($file->getRealPath());
            
            // Look for JavaScript-related keywords in PDF
            $jsKeywords = [
                '/JavaScript',
                '/JS',
                'this.print',
                'app.alert',
                'eval(',
                'unescape(',
            ];
            
            foreach ($jsKeywords as $keyword) {
                if (str_contains($content, $keyword)) {
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::warning('PDF JavaScript check failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return true; // If we can't check, consider it risky
        }
    }
    
    /**
     * Generate a secure file path for storage.
     */
    protected function generateSecureFilePath(Model $attachable, string $originalName): string
    {
        $modelType = Str::kebab(class_basename($attachable));
        $modelId = $attachable->id;
        
        // Generate unique filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = time() . '_' . Str::random(16) . '.' . strtolower($extension);
        
        return "attachments/{$modelType}/{$modelId}/{$filename}";
    }
    
    /**
     * Delete an attachment and its file.
     */
    public function deleteAttachment(Attachment $attachment): bool
    {
        try {
            DB::beginTransaction();
            
            // Delete file from storage
            if (Storage::disk($this->disk)->exists($attachment->file_path)) {
                Storage::disk($this->disk)->delete($attachment->file_path);
            }
            
            // Delete attachment record
            $attachment->delete();
            
            DB::commit();
            
            Log::info('Attachment deleted successfully', [
                'attachment_id' => $attachment->id,
                'file_path' => $attachment->file_path
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Failed to delete attachment', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Clean up orphaned attachments (files without database records).
     */
    public function cleanupOrphanedFiles(): int
    {
        $cleaned = 0;
        
        try {
            $allFiles = Storage::disk($this->disk)->allFiles('attachments');
            
            foreach ($allFiles as $filePath) {
                $exists = Attachment::where('file_path', $filePath)->exists();
                
                if (!$exists) {
                    Storage::disk($this->disk)->delete($filePath);
                    $cleaned++;
                    
                    Log::info('Orphaned file cleaned up', ['file_path' => $filePath]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to cleanup orphaned files', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $cleaned;
    }
    
    /**
     * Clean up attachments for a deleted model.
     */
    public function cleanupModelAttachments(string $modelType, int $modelId): int
    {
        $cleaned = 0;
        
        try {
            $attachments = Attachment::where('attachable_type', $modelType)
                ->where('attachable_id', $modelId)
                ->get();
            
            foreach ($attachments as $attachment) {
                if ($this->deleteAttachment($attachment)) {
                    $cleaned++;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to cleanup model attachments', [
                'model_type' => $modelType,
                'model_id' => $modelId,
                'error' => $e->getMessage()
            ]);
        }
        
        return $cleaned;
    }
    
    /**
     * Get file content for download/viewing.
     */
    public function getFileContent(Attachment $attachment): ?string
    {
        try {
            if (!Storage::disk($this->disk)->exists($attachment->file_path)) {
                Log::warning('File not found for attachment', [
                    'attachment_id' => $attachment->id,
                    'file_path' => $attachment->file_path
                ]);
                return null;
            }
            
            return Storage::disk($this->disk)->get($attachment->file_path);
            
        } catch (\Exception $e) {
            Log::error('Failed to get file content', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Check if user has access to attachment.
     */
    public function userCanAccess(Attachment $attachment, ?int $userId = null): bool
    {
        $userId = $userId ?? Auth::id();
        
        if (!$userId) {
            return false;
        }
        
        // User can access if they uploaded it
        if ($attachment->uploaded_by === $userId) {
            return true;
        }
        
        // User can access if they own the attachable model
        $attachable = $attachment->attachable;
        if ($attachable && method_exists($attachable, 'user_id')) {
            return $attachable->user_id === $userId;
        }
        
        // Additional access control logic can be added here
        // For example, checking if user has admin role
        $user = Auth::user();
        if ($user && method_exists($user, 'hasAdminAccess') && $user->hasAdminAccess()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Format file size in human readable format.
     */
    protected function formatFileSize(int $bytes): string
    {
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
     * Set allowed MIME types.
     */
    public function setAllowedMimeTypes(array $mimeTypes): self
    {
        $this->allowedMimeTypes = $mimeTypes;
        return $this;
    }
    
    /**
     * Set maximum file size.
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }
    
    /**
     * Set storage disk.
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }
}