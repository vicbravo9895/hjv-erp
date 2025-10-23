<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Download an attachment file.
     */
    public function download(Attachment $attachment): StreamedResponse
    {
        // Check if user has permission to download this attachment
        $this->authorize('view', $attachment);

        // Check if file exists in MinIO
        if (!Storage::disk('minio')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        // Get file content and stream it
        return response()->streamDownload(function () use ($attachment) {
            echo Storage::disk('minio')->get($attachment->file_path);
        }, $attachment->file_name, [
            'Content-Type' => $attachment->mime_type,
        ]);
    }

    /**
     * View an attachment file (for images/PDFs).
     */
    public function view(Attachment $attachment)
    {
        // Check if user has permission to view this attachment
        $this->authorize('view', $attachment);

        // Check if file exists in MinIO
        if (!Storage::disk('minio')->exists($attachment->file_path)) {
            abort(404, 'File not found');
        }

        // Get file content and return as response
        $fileContent = Storage::disk('minio')->get($attachment->file_path);
        
        return response($fileContent)
            ->header('Content-Type', $attachment->mime_type)
            ->header('Content-Disposition', "inline; filename=\"{$attachment->file_name}\"");
    }

    /**
     * Handle temporary file upload for Filament components.
     */
    public function tempUpload(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240|mimes:pdf,jpeg,jpg,png,gif',
            ]);

            $file = $request->file('file');
            $attachmentService = app(AttachmentService::class);

            // Validate file using service
            $validationErrors = $attachmentService->validateFile($file);
            if (!empty($validationErrors)) {
                return response()->json([
                    'success' => false,
                    'message' => implode(', ', $validationErrors)
                ], 422);
            }

            // Store file temporarily
            $tempPath = $file->store('temp-uploads', 'minio');
            
            if (!$tempPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al subir el archivo'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'id' => $tempPath,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'type' => $file->getMimeType(),
                'url' => route('attachments.temp-view', ['path' => base64_encode($tempPath)]),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * View a temporary uploaded file.
     */
    public function tempView(string $path)
    {
        try {
            $filePath = base64_decode($path);
            
            if (!Storage::disk('minio')->exists($filePath)) {
                abort(404, 'File not found');
            }
            
            $fileContent = Storage::disk('minio')->get($filePath);
            $mimeType = Storage::disk('minio')->mimeType($filePath);
            
            return response($fileContent)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'inline');
                
        } catch (\Exception $e) {
            abort(404, 'File not found');
        }
    }
}