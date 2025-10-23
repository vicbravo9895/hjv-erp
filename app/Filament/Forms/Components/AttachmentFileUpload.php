<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\FileUpload;

class AttachmentFileUpload extends FileUpload
{
    public static function make(string $name): static
    {
        $static = parent::make($name);
        
        return $static
            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 'image/gif'])
            ->maxSize(10240) // 10MB
            ->multiple(true)
            ->disk('minio')
            ->directory('attachments')
            ->visibility('private')
            ->rules([
                'max:10240',
                'mimes:pdf,jpeg,jpg,png,gif',
            ])
            ->helperText('Formatos permitidos: PDF, JPG, PNG, GIF. Tamaño máximo: 10MB por archivo.')
            ->uploadingMessage('Subiendo archivo...')
            ->removeUploadedFileButtonPosition('right')
            ->uploadButtonPosition('left')
            ->panelLayout('grid')
            ->imagePreviewHeight('150')
            ->loadingIndicatorPosition('center')
            ->panelAspectRatio('16:9')
            ->imageResizeMode('cover')
            ->imageCropAspectRatio('16:9')
            ->imageResizeTargetWidth('1920')
            ->imageResizeTargetHeight('1080')
            ->openable()
            ->downloadable()
            ->deletable()
            ->reorderable()
            ->appendFiles();
    }
}