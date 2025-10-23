# Design Document

## Overview

El sistema de paneles especializados extiende la aplicación Laravel/Filament existente para proporcionar interfaces específicas por rol. Se implementarán tres paneles principales: Workshop (Taller), Operator (Operador), y se mejorará el panel Admin existente. Cada panel tendrá recursos y funcionalidades específicas según las necesidades del rol.

## Architecture

### Panel Structure
```
Filament Multi-Panel Architecture:
├── AdminPanel (existing - enhanced)
├── WorkshopPanel (new)
└── OperatorPanel (new)
```

### Authentication & Authorization
- Utilizar el sistema de roles existente en el modelo `User` (operador, supervisor, administrador, etc.)
- Extender middleware existente (`CheckRole`) para nuevos paneles
- Aprovechar métodos existentes como `isOperator()`, `hasAdminAccess()` del modelo User
- Redirección automática según rol del usuario usando el patrón ya establecido

### File Management
- Integración con Laravel Storage
- Componente Filament FileUpload personalizado
- Validación de tipos y tamaños de archivo

## Components and Interfaces

### 1. Workshop Panel Components

#### WorkshopPanelProvider
```php
// app/Providers/Filament/WorkshopPanelProvider.php
- Panel ID: 'workshop'
- Path: '/workshop'
- Middleware: ['auth', 'workshop.access'] (siguiendo patrón existente)
- Resources: ProductUsage, ProductRequest, MaintenanceRecord (existente), SparePart (existente)
- Integración con recursos existentes de mantenimiento
```

#### ProductUsageResource
```php
// Gestión de productos utilizados
Fields:
- spare_part_id (Select)
- maintenance_record_id (Select)
- quantity_used (Number)
- date_used (DatePicker)
- notes (Textarea)
- attachments (FileUpload - multiple)
```

#### ProductRequestResource
```php
// Solicitudes de productos necesarios
Fields:
- spare_part_id (Select)
- quantity_requested (Number)
- priority (Select: low, medium, high, urgent)
- justification (Textarea)
- status (Select: pending, approved, ordered, received)
- requested_by (Hidden - current user)
- requested_at (Hidden - current timestamp)
```

### 2. Operator Panel Components

#### OperatorPanelProvider
```php
// app/Providers/Filament/OperatorPanelProvider.php
- Panel ID: 'operator'
- Path: '/operator'
- Middleware: ['auth', 'operator.access'] (siguiendo patrón existente)
- Resources: TravelExpense, Trip (read-only, existente), Vehicle (read-only, existente)
- Integración con modelo Operator existente y relaciones con Trip
```

#### TravelExpenseResource
```php
// Gestión de gastos de viaje
Fields:
- trip_id (Select - only active trips for current operator)
- expense_type (Select: fuel, tolls, food, accommodation, other)
- amount (Number with currency)
- date (DatePicker)
- location (TextInput)
- description (Textarea)
- fuel_liters (Number - conditional on fuel type)
- fuel_price_per_liter (Number - conditional on fuel type)
- odometer_reading (Number - conditional on fuel type)
- attachments (FileUpload - multiple, required)
- status (Select: pending, approved, reimbursed)
```

### 3. Enhanced File Upload Component

#### CustomFileUpload
```php
// Extensión del FileUpload de Filament
Features:
- Múltiples archivos
- Previsualización de imágenes
- Validación de tipos: PDF, JPG, PNG, JPEG
- Tamaño máximo: 10MB por archivo
- Almacenamiento en storage/app/attachments/{model}/{id}/
- Nombres únicos con timestamp
```

### 4. New Models

#### ProductUsage Model
```php
// app/Models/ProductUsage.php
// Extiende el sistema existente de maintenance_spares
Relationships:
- belongsTo(SparePart) // Usa modelo existente
- belongsTo(MaintenanceRecord) // Usa modelo existente
- belongsTo(User, 'used_by')
- morphMany(Attachment, 'attachable')

Attributes:
- spare_part_id, maintenance_record_id, quantity_used
- date_used, notes, used_by, created_at, updated_at
// Integra con sistema existente de inventory tracking en SparePart
```

#### ProductRequest Model
```php
// app/Models/ProductRequest.php
Relationships:
- belongsTo(SparePart)
- belongsTo(User, 'requested_by')
- belongsTo(User, 'approved_by')

Attributes:
- spare_part_id, quantity_requested, priority, justification
- status, requested_by, approved_by, requested_at, approved_at
```

#### TravelExpense Model
```php
// app/Models/TravelExpense.php
// Complementa el sistema existente de TripCost y Expense
Relationships:
- belongsTo(Trip) // Usa modelo existente
- belongsTo(User, 'operator_id') // Integra con sistema de roles existente
- morphMany(Attachment, 'attachable')

Attributes:
- trip_id, operator_id, expense_type, amount, date, location
- description, fuel_liters, fuel_price_per_liter, odometer_reading, status
// Se diferencia de TripCost existente por incluir attachments y ser específico para operadores
```

#### Attachment Model (Polymorphic)
```php
// app/Models/Attachment.php
Relationships:
- morphTo('attachable') // Can attach to any model

Attributes:
- attachable_type, attachable_id, file_name, file_path
- file_size, mime_type, uploaded_by, created_at
```

## Data Models

### Database Schema

#### product_usages table
```sql
CREATE TABLE product_usages (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    spare_part_id BIGINT NOT NULL,
    maintenance_record_id BIGINT NOT NULL,
    quantity_used DECIMAL(10,2) NOT NULL,
    date_used DATE NOT NULL,
    notes TEXT NULL,
    used_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
    FOREIGN KEY (maintenance_record_id) REFERENCES maintenance_records(id),
    FOREIGN KEY (used_by) REFERENCES users(id)
);
```

#### product_requests table
```sql
CREATE TABLE product_requests (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    spare_part_id BIGINT NOT NULL,
    quantity_requested DECIMAL(10,2) NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    justification TEXT NOT NULL,
    status ENUM('pending', 'approved', 'ordered', 'received') DEFAULT 'pending',
    requested_by BIGINT NOT NULL,
    approved_by BIGINT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);
```

#### travel_expenses table
```sql
CREATE TABLE travel_expenses (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    trip_id BIGINT NOT NULL,
    operator_id BIGINT NOT NULL,
    expense_type ENUM('fuel', 'tolls', 'food', 'accommodation', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    date DATE NOT NULL,
    location VARCHAR(255) NULL,
    description TEXT NULL,
    fuel_liters DECIMAL(8,2) NULL,
    fuel_price_per_liter DECIMAL(6,3) NULL,
    odometer_reading INT NULL,
    status ENUM('pending', 'approved', 'reimbursed') DEFAULT 'pending',
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (operator_id) REFERENCES users(id)
);
```

#### attachments table (Polymorphic)
```sql
CREATE TABLE attachments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    attachable_type VARCHAR(255) NOT NULL,
    attachable_id BIGINT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by BIGINT NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    INDEX attachable_index (attachable_type, attachable_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);
```

## Error Handling

### Validation Rules
- File uploads: tipos permitidos, tamaño máximo
- Cantidad de productos: no exceder stock disponible
- Gastos: montos positivos, fechas válidas
- Roles: verificación de permisos por panel

### Error Messages
- Mensajes en español para mejor UX
- Validación en tiempo real donde sea posible
- Notificaciones toast para acciones exitosas

### Exception Handling
- Manejo de errores de carga de archivos
- Rollback de transacciones en operaciones complejas
- Logging de errores críticos

## Testing Strategy

### Unit Tests
- Modelos: relaciones y validaciones
- Servicios: lógica de negocio
- Componentes: funcionalidad de upload

### Feature Tests
- Autenticación por panel
- CRUD operations por recurso
- File upload functionality
- Role-based access control

### Integration Tests
- Flujo completo de registro de gastos
- Proceso de solicitud de productos
- Actualización automática de inventario

## Security Considerations

### File Upload Security
- Validación de tipos MIME
- Escaneo de archivos maliciosos
- Almacenamiento fuera del web root
- Nombres de archivo únicos

### Access Control
- Middleware específico por panel
- Verificación de ownership de datos
- Políticas de autorización granulares

### Data Protection
- Encriptación de archivos sensibles
- Audit trail de cambios importantes
- Backup automático de attachments

## Performance Considerations

### File Storage
- Organización jerárquica de archivos
- Compresión automática de imágenes
- CDN para archivos estáticos (futuro)

### Database Optimization
- Índices en campos de búsqueda frecuente
- Paginación en listados grandes
- Eager loading de relaciones

### Caching Strategy
- Cache de configuraciones de panel
- Cache de listas de productos frecuentes
- Session-based cache para datos de usuario