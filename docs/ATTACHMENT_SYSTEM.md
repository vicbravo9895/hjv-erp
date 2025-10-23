# Sistema de Adjuntos (Attachment System) - ACTUALIZADO

Este documento describe el sistema de adjuntos implementado para la aplicación de gestión de flota.

## ⚠️ PROBLEMAS RESUELTOS

### Configuración de Livewire
- **Problema**: Los archivos no se subían a MinIO debido a configuración incorrecta de Livewire
- **Solución**: Configurado Livewire para usar MinIO como disco temporal en `config/livewire.php`

### Implementación en TravelExpenseResource
- **Problema**: Método `getMimeTypeFromExtension()` no existía
- **Solución**: Implementado directamente en el callback usando `match()` expression

### Configuración de Archivos Temporales
- **Problema**: Archivos temporales usando disco local por defecto
- **Solución**: Configurado `temporary_file_upload.disk = 'minio'` en Livewire

## Componentes Principales

### 1. AttachmentFileUpload Component

Componente personalizado de Filament que extiende el FileUpload base con funcionalidades adicionales:

- **Ubicación**: `app/Filament/Forms/Components/AttachmentFileUpload.php`
- **Vista**: `resources/views/filament/forms/components/attachment-file-upload.blade.php`

#### Características:
- Soporte para múltiples archivos
- Validación de tipos de archivo (PDF, JPG, PNG, GIF)
- Validación de tamaño (máximo 10MB por defecto)
- Previsualización de imágenes
- Verificaciones de seguridad
- Interfaz drag-and-drop
- Indicadores de progreso de carga

#### Uso Básico:
```php
AttachmentFileUpload::make('attachments')
    ->label('Archivos adjuntos')
    ->setAcceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
    ->setMaxFileSize(10240) // 10MB
    ->setMultiple(true)
    ->required();
```

### 2. AttachmentService

Servicio para manejar operaciones de archivos y almacenamiento:

- **Ubicación**: `app/Services/AttachmentService.php`

#### Funcionalidades:
- Almacenamiento seguro de archivos en MinIO
- Validación de tipos MIME y extensiones
- Verificaciones de seguridad (detección de ejecutables)
- Generación de nombres únicos
- Limpieza de archivos huérfanos
- Control de acceso a archivos

#### Métodos Principales:
```php
// Almacenar un archivo
$attachment = $attachmentService->storeFile($file, $model, $userId);

// Almacenar múltiples archivos
$attachments = $attachmentService->storeMultipleFiles($files, $model, $userId);

// Validar archivo
$errors = $attachmentService->validateFile($file);

// Eliminar adjunto
$success = $attachmentService->deleteAttachment($attachment);

// Limpiar archivos huérfanos
$cleaned = $attachmentService->cleanupOrphanedFiles();
```

### 3. HasAttachments Trait

Trait para modelos que pueden tener archivos adjuntos:

- **Ubicación**: `app/Traits/HasAttachments.php`

#### Uso:
```php
class TravelExpense extends Model
{
    use HasAttachments;
    
    // El trait automáticamente añade:
    // - Relación attachments()
    // - Métodos para agregar/remover adjuntos
    // - Limpieza automática al eliminar el modelo
}
```

#### Métodos Disponibles:
```php
// Obtener adjuntos
$model->attachments();
$model->imageAttachments();
$model->pdfAttachments();

// Verificar si tiene adjuntos
$model->hasAttachments();

// Agregar adjuntos
$model->addAttachment($file, $userId);
$model->addAttachments($files, $userId);

// Remover adjuntos
$model->removeAttachment($attachment);
$model->removeAllAttachments();

// Obtener tamaño total
$model->getTotalAttachmentSize();
$model->human_total_attachment_size;
```

### 4. Attachment Model

Modelo para almacenar metadatos de archivos:

- **Ubicación**: `app/Models/Attachment.php`

#### Relaciones:
- `attachable()`: Relación polimórfica al modelo propietario
- `uploadedBy()`: Usuario que subió el archivo

#### Métodos Útiles:
```php
$attachment->isImage();
$attachment->isPdf();
$attachment->human_file_size;
$attachment->url; // URL para descarga
```

### 5. AttachmentController

Controlador para manejar descargas y visualización:

- **Ubicación**: `app/Http/Controllers/AttachmentController.php`

#### Rutas:
- `GET /attachments/{attachment}/download` - Descargar archivo
- `GET /attachments/{attachment}/view` - Ver archivo en línea
- `POST /filament/temp-upload` - Carga temporal para componentes

### 6. AttachmentPolicy

Política de autorización para controlar acceso:

- **Ubicación**: `app/Policies/AttachmentPolicy.php`

#### Reglas de Acceso:
- Usuarios pueden ver/editar sus propios adjuntos
- Operadores pueden ver adjuntos de sus gastos de viaje
- Administradores tienen acceso completo
- Contadores pueden ver adjuntos de gastos

## Configuración de Almacenamiento

El sistema utiliza MinIO como almacenamiento por defecto. Los archivos se organizan de la siguiente manera:

```
attachments/
├── travel-expense/
│   ├── 1/
│   │   ├── 1634567890_abc123.pdf
│   │   └── 1634567891_def456.jpg
│   └── 2/
└── product-usage/
    └── 1/
```

## Validaciones de Seguridad

### Tipos de Archivo Permitidos:
- PDF: `application/pdf`
- Imágenes: `image/jpeg`, `image/jpg`, `image/png`, `image/gif`

### Verificaciones de Seguridad:
1. Validación de tipo MIME
2. Verificación de extensión de archivo
3. Detección de firmas ejecutables
4. Verificación de JavaScript en PDFs
5. Límite de tamaño de archivo (10MB por defecto)

### Nombres de Archivo Seguros:
- Timestamp + ID único + extensión original
- Ejemplo: `1634567890_abc123def456.pdf`

## Comandos de Consola

### Limpieza de Archivos Huérfanos:
```bash
# Limpiar archivos huérfanos
php artisan attachments:cleanup

# Modo de prueba (no elimina archivos)
php artisan attachments:cleanup --dry-run

# Limpiar archivos temporales más antiguos de X horas
php artisan attachments:cleanup --older-than=48
```

## Uso en Recursos de Filament

### Ejemplo en ProductUsageResource:
```php
public static function form(Form $form): Form
{
    return $form->schema([
        // Otros campos...
        
        AttachmentFileUpload::make('attachments')
            ->label('Comprobantes')
            ->setAcceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
            ->setMaxFileSize(10240)
            ->setMultiple(true)
            ->helperText('Sube facturas o comprobantes (PDF, JPG, PNG - máx. 10MB)'),
    ]);
}

protected function handleRecordCreation(array $data): Model
{
    $record = parent::handleRecordCreation($data);
    
    // Los adjuntos se procesan automáticamente por el componente
    // No se requiere código adicional
    
    return $record;
}
```

## Consideraciones de Rendimiento

1. **Almacenamiento**: Los archivos se almacenan en MinIO, no en la base de datos
2. **Metadatos**: Solo los metadatos se almacenan en la tabla `attachments`
3. **Limpieza**: Comando programado para limpiar archivos huérfanos
4. **Caché**: Las URLs de descarga no se cachean por seguridad

## Troubleshooting

### Problemas Comunes:

1. **Error "Failed to load resource: livewire_temp_RESOLVED"**:
   - **Causa**: Configuración incorrecta de Livewire para archivos temporales
   - **Solución**: Verificar que `config/livewire.php` tenga `'disk' => 'minio'`
   - **Comando de prueba**: `php artisan livewire:test-upload`

2. **Archivos no se suben a MinIO**:
   - **Causa**: Disco temporal no configurado correctamente
   - **Solución**: Ejecutar `php artisan config:clear` después de cambiar configuración
   - **Verificación**: `php artisan minio:test`

3. **Error de MIME type**:
   - **Causa**: Método `getMimeTypeFromExtension()` no encontrado
   - **Solución**: Usar implementación directa con `match()` expression

4. **Permisos de acceso**:
   - **Causa**: Políticas de autorización restrictivas
   - **Solución**: Verificar `AttachmentPolicy` y permisos de usuario

### Comandos de Diagnóstico:

```bash
# Probar conexión MinIO
php artisan minio:test

# Probar configuración Livewire
php artisan livewire:test-upload

# Limpiar configuración
php artisan config:clear

# Limpiar archivos huérfanos
php artisan attachments:cleanup
```

### Logs:
Los errores se registran en `storage/logs/laravel.log` con contexto detallado.

### Configuración Requerida:

1. **config/livewire.php**:
```php
'temporary_file_upload' => [
    'disk' => 'minio',
    'rules' => ['required', 'file', 'max:10240', 'mimes:pdf,jpeg,jpg,png,gif'],
    'directory' => 'livewire-tmp',
],
```

2. **config/filesystems.php**:
```php
'minio' => [
    'driver' => 's3',
    'key' => env('MINIO_ACCESS_KEY', 'sail'),
    'secret' => env('MINIO_SECRET_KEY', 'password'),
    'region' => env('MINIO_DEFAULT_REGION', 'us-east-1'),
    'bucket' => env('MINIO_BUCKET', 'hjv-erp'),
    'endpoint' => env('MINIO_ENDPOINT', 'http://minio:9000'),
    'use_path_style_endpoint' => true,
],
```