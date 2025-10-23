<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttachmentController;

Route::get('/', function () {
    return view('welcome');
});

// Attachment routes
Route::middleware(['auth'])->group(function () {
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');
    Route::get('/attachments/{attachment}/view', [AttachmentController::class, 'view'])
        ->name('attachments.view');
    Route::get('/attachments/temp/{path}', [AttachmentController::class, 'tempView'])
        ->name('attachments.temp-view');
    Route::post('/filament/temp-upload', [AttachmentController::class, 'tempUpload'])
        ->name('filament.temp-upload');
});


