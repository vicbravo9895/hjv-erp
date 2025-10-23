<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;

/**
 * Simple attachment upload component that works with MinIO.
 * Use this instead of the complex AttachmentFileUpload for now.
 */
class SimpleAttachmentUpload
{
    public static function make(string $name = 'attachments'): FileUpload
    {
        return FileUpload::make($name)
            ->label('Archivos adjuntos')
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])
            ->maxSize(10240) // 10MB
            ->multiple(true)
            ->disk('minio')
            ->directory('attachments')
            ->visibility('private')
            ->helperText('Formatos permitidos: PDF, JPG, PNG, GIF. Tamaño máximo: 10MB por archivo.')
            ->uploadingMessage('Subiendo archivo...')
            ->panelLayout('grid')
            ->imagePreviewHeight('150')
            ->openable()
            ->downloadable()
            ->deletable()
            ->reorderable();
    }
    
    /**
     * For single file uploads.
     */
    public static function single(string $name = 'attachment'): FileUpload
    {
        return self::make($name)
            ->multiple(false)
            ->label('Archivo adjunto');
    }
    
    /**
     * For images only.
     */
    public static function images(string $name = 'images'): FileUpload
    {
        return self::make($name)
            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif'])
            ->label('Imágenes')
            ->helperText('Formatos permitidos: JPG, PNG, GIF. Tamaño máximo: 10MB por archivo.');
    }
    
    /**
     * For PDFs only.
     */
    public static function pdfs(string $name = 'documents'): FileUpload
    {
        return self::make($name)
            ->acceptedFileTypes(['application/pdf'])
            ->label('Documentos PDF')
            ->helperText('Solo archivos PDF. Tamaño máximo: 10MB por archivo.');
    }
}