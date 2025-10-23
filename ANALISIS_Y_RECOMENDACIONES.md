# Análisis Completo y Recomendaciones de Mejora - Sistema ERP Multi-Rol

## Tabla de Contenidos
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Análisis de Base de Datos](#análisis-de-base-de-datos)
4. [Sistema de Roles y Permisos](#sistema-de-roles-y-permisos)
5. [Análisis de Resources por Módulo](#análisis-de-resources-por-módulo)
6. [Problemas Identificados](#problemas-identificados)
7. [Recomendaciones de Mejora](#recomendaciones-de-mejora)
8. [Plan de Implementación](#plan-de-implementación)

---

## Resumen Ejecutivo

### Estado Actual del Proyecto

**Tecnologías Utilizadas:**
- Laravel 12 + PHP 8.2
- Filament 3.0 (Admin Panel)
- MySQL/PostgreSQL
- AWS S3/MinIO para almacenamiento
- Redis para cache/queues
- Integración con Samsara API

**Módulos Implementados:**
- Gestión de Flota (Vehículos, Trailers, Operadores)
- Operaciones (Viajes, Costos de Viajes)
- Mantenimiento (Registros, Refacciones)
- Inventario (Solicitudes, Uso de Productos)
- Finanzas (Gastos, Nóminas, Gastos de Viaje, Proveedores)
- Sistema de Usuarios y Roles

**Roles del Sistema:**
1. Super Admin (acceso total)
2. Administrador (gestión general)
3. Supervisor (operaciones y mantenimiento)
4. Contador (finanzas)
5. Operador (viajes y gastos propios)

**Paneles Implementados:**
- Admin Panel (principal)
- Operator Panel (operadores)
- Accounting Panel (contadores)
- Workshop Panel (taller/mantenimiento)

### Puntuación General

| Categoría | Puntuación | Comentario |
|-----------|------------|------------|
| Arquitectura | 8/10 | Sólida estructura con separación de concerns |
| Base de Datos | 7/10 | Bien diseñada, falta normalización en algunos campos |
| UX/UI | 5/10 | Funcional pero poco amigable para usuarios no técnicos |
| Validaciones | 6/10 | Básicas implementadas, faltan validaciones de negocio |
| Permisos | 8/10 | Sistema robusto con servicios dedicados |
| Documentación | 4/10 | Falta documentación para usuarios finales |

---

## Arquitectura del Sistema

### Fortalezas

1. **Separación de Paneles por Rol**
   - Cada rol tiene un panel específico con recursos relevantes
   - Navegación simplificada por contexto
   - Colores distintivos por panel (Admin: Azul, Operador: Verde)

2. **Patrón Service/Repository**
   - `VehicleAssignmentService`: Lógica de asignación de recursos
   - `AutoAssignmentService`: Auto-asignación de campos
   - `PermissionService`: Servicios especializados por resource

3. **Traits Reutilizables**
   - `HasAutoAssignment`: Auto-asignación de mechanic_id, operator_id
   - `HasRoleBasedAccess`: Control de acceso por rol
   - `HasAttachments`: Gestión polimórfica de archivos
   - `ProcessesAttachments`: Procesamiento de archivos

4. **Contratos/Interfaces**
   - `FormFieldResolverInterface`: Resolución dinámica de campos
   - `EnhancedPermissionInterface`: Permisos extendidos
   - `ResourceClusterInterface`: Agrupación de resources

### Áreas de Mejora

1. **Código Duplicado**
   - Lógica similar en múltiples Resources
   - Formularios con patrones repetitivos
   - Falta un BaseFormBuilder para componentes comunes

2. **Falta de Abstracción**
   - Cálculos de costos esparcidos en múltiples lugares
   - Lógica de negocio mezclada con lógica de presentación

3. **Gestión de Archivos**
   - Sistema complejo con storage temporal
   - No hay servicio centralizado de uploads

---

## Análisis de Base de Datos

### Estructura General

**20 tablas principales + 3 tablas de sistema (users, cache, jobs)**

### Tablas Core

#### 1. **users** (Autenticación y Roles)
```
- id, name, email, password, role
- Roles: super_admin, administrador, supervisor, contador, operador
```

**✅ Fortalezas:**
- Sistema simple de roles (string)
- Métodos helper bien implementados (hasRole, hasAnyRole)
- Scopes útiles (operators, accountants, workshopUsers)

**⚠️ Problemas:**
- Rol como string sin tabla de referencia
- No hay tabla de permisos granulares
- No hay auditoría de cambios de rol
- Falta campos: phone, profile_photo, last_login_at

**💡 Recomendación:**
```sql
-- Agregar campos útiles
ALTER TABLE users ADD COLUMN phone VARCHAR(20);
ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255);
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP;
ALTER TABLE users ADD COLUMN is_active BOOLEAN DEFAULT true;
```

#### 2. **vehicles** (Flota - Tractocamiones)
```
- Campos: external_id (Samsara), vin, serial_number, name, unit_number, plate
- Telemetría: last_odometer_km, last_fuel_percent, last_engine_state, last_speed_mph
- Ubicación: last_lat, last_lng, formatted_location, last_location_at
- Estado: available, in_trip, maintenance, out_of_service
```

**✅ Fortalezas:**
- Integración bien diseñada con Samsara
- Campo raw_snapshot para debugging
- Índices adecuados

**⚠️ Problemas:**
- No hay histórico de estados
- No hay tabla de asignación temporal vehicle_operator
- Falta campo maintenance_due_date

**💡 Recomendación:**
```sql
-- Crear tabla de histórico de estados
CREATE TABLE vehicle_status_history (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    vehicle_id BIGINT UNSIGNED NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    reason TEXT,
    changed_by BIGINT UNSIGNED,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id),
    FOREIGN KEY (changed_by) REFERENCES users(id),
    INDEX (vehicle_id, changed_at)
);

-- Agregar campos de mantenimiento
ALTER TABLE vehicles ADD COLUMN next_maintenance_date DATE;
ALTER TABLE vehicles ADD COLUMN next_maintenance_km DECIMAL(10,2);
```

#### 3. **trailers** (Remolques)
```
- Similar a vehicles pero sin telemetría
- Campos: name, asset_number, plate, year, capacity
```

**✅ Fortalezas:**
- Estructura simple y clara

**⚠️ Problemas:**
- Sin tracking de ubicación
- Sin histórico de asignaciones

#### 4. **operators** (Operadores/Choferes)
```
- Campos: name, license_number, phone, email, hire_date, status
```

**⚠️ PROBLEMA CRÍTICO:**
- **Duplicación de datos:** Hay tabla `operators` Y modelo `User` con rol 'operador'
- **Inconsistencia:** Un operador debería ser un usuario, no una entidad separada

**💡 Recomendación URGENTE:**
```sql
-- OPCIÓN A: Eliminar tabla operators, usar solo users
-- Migrar datos de operators a users
INSERT INTO users (name, email, password, role, phone)
SELECT name, email, CONCAT('$2y$10$...default_hash...'), 'operador', phone
FROM operators;

-- Actualizar foreign keys
ALTER TABLE trips DROP FOREIGN KEY trips_operator_id_foreign;
ALTER TABLE trips ADD CONSTRAINT trips_operator_id_foreign
    FOREIGN KEY (operator_id) REFERENCES users(id);

-- OPCIÓN B: Convertir operators en "extended profile"
ALTER TABLE operators ADD COLUMN user_id BIGINT UNSIGNED UNIQUE;
ALTER TABLE operators ADD FOREIGN KEY (user_id) REFERENCES users(id);
-- operator.user_id apuntaría al registro en users
```

**Decisión Recomendada:** OPCIÓN A - Usar solo users
- Simplifica arquitectura
- Elimina inconsistencias
- Un operador ES un usuario

#### 5. **trips** (Viajes)
```
- Relaciones: truck_id, trailer_id, operator_id
- Estados: planned, in_progress, completed, cancelled
- Fechas: start_date, end_date, completed_at
```

**✅ Fortalezas:**
- Estados bien definidos
- Relaciones claras

**⚠️ Problemas:**
- No hay validación de solapamiento de viajes
- No hay tabla de ruta/waypoints
- Falta campo estimated_revenue

**💡 Recomendación:**
```sql
-- Agregar campos de negocio
ALTER TABLE trips ADD COLUMN client_name VARCHAR(255);
ALTER TABLE trips ADD COLUMN client_rate DECIMAL(10,2);
ALTER TABLE trips ADD COLUMN estimated_revenue DECIMAL(12,2);
ALTER TABLE trips ADD COLUMN actual_revenue DECIMAL(12,2);
ALTER TABLE trips ADD COLUMN notes TEXT;

-- Crear tabla de waypoints (puntos de la ruta)
CREATE TABLE trip_waypoints (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    trip_id BIGINT UNSIGNED NOT NULL,
    sequence_order INT NOT NULL,
    location VARCHAR(255) NOT NULL,
    waypoint_type ENUM('pickup', 'delivery', 'stop') NOT NULL,
    scheduled_time TIMESTAMP,
    actual_time TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    INDEX (trip_id, sequence_order)
);
```

#### 6. **trip_costs** (Costos de Viaje)
```
- Tipos: diesel, toll, maneuver
- Relación: trip_id
```

**✅ Fortalezas:**
- Simple y efectiva

**⚠️ Problemas:**
- Tipos hardcoded (diesel, toll, maneuver)
- No hay receipt_url o comprobantes
- Se solapa con travel_expenses

**🤔 Confusión Conceptual:**
- `trip_costs`: Costos operativos del viaje (diesel, peajes)
- `travel_expenses`: Gastos del operador (alimentación, hospedaje)

**💡 Recomendación:**
Consolidar en una sola tabla con tipo:
```sql
CREATE TABLE trip_transactions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    trip_id BIGINT UNSIGNED NOT NULL,
    transaction_type ENUM('cost', 'expense', 'income') NOT NULL,
    category VARCHAR(50) NOT NULL, -- diesel, toll, food, lodging, etc
    amount DECIMAL(10,2) NOT NULL,
    date DATETIME NOT NULL,
    operator_id BIGINT UNSIGNED,
    location VARCHAR(255),
    description TEXT,
    receipt_url VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    approved_by BIGINT UNSIGNED,
    approved_at TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (operator_id) REFERENCES users(id),
    INDEX (trip_id, transaction_type, category)
);
```

#### 7. **maintenance_records** (Mantenimiento)
```
- Polimórfico: vehicle_id + vehicle_type ('Vehicle' o 'Trailer')
- Tipos: preventivo, correctivo, emergencia, inspección
- Ya NO tiene campo cost (se calcula de product_usages)
```

**✅ Fortalezas:**
- Polimorfismo bien implementado
- Sistema de costo calculado es inteligente

**⚠️ Problemas:**
- No hay campo status (pending, in_progress, completed)
- No hay estimated_cost vs actual_cost
- No hay scheduled_date vs actual_date

**💡 Recomendación:**
```sql
ALTER TABLE maintenance_records ADD COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled';
ALTER TABLE maintenance_records ADD COLUMN scheduled_date DATE;
ALTER TABLE maintenance_records RENAME COLUMN date TO actual_date;
ALTER TABLE maintenance_records ADD COLUMN estimated_hours DECIMAL(5,2);
ALTER TABLE maintenance_records ADD COLUMN actual_hours DECIMAL(5,2);
ALTER TABLE maintenance_records ADD COLUMN labor_cost DECIMAL(10,2);
```

#### 8. **spare_parts** (Refacciones/Inventario)
```
- Campos: part_number, name, brand, stock_quantity, unit_cost, min_stock, location
```

**✅ Fortalezas:**
- Campos esenciales presentes
- min_stock para alertas

**⚠️ Problemas:**
- No hay histórico de movimientos de stock
- No hay multiple locations (un almacén)
- No hay campo supplier_id
- No hay categorías de productos

**💡 Recomendación:**
```sql
-- Crear tabla de categorías
CREATE TABLE spare_part_categories (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    parent_id BIGINT UNSIGNED,
    FOREIGN KEY (parent_id) REFERENCES spare_part_categories(id)
);

ALTER TABLE spare_parts ADD COLUMN category_id BIGINT UNSIGNED;
ALTER TABLE spare_parts ADD FOREIGN KEY (category_id) REFERENCES spare_part_categories(id);

-- Crear tabla de proveedores de refacciones
CREATE TABLE spare_part_suppliers (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    spare_part_id BIGINT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NOT NULL,
    supplier_part_number VARCHAR(255),
    lead_time_days INT,
    unit_cost DECIMAL(10,2),
    is_preferred BOOLEAN DEFAULT false,
    FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
    FOREIGN KEY (supplier_id) REFERENCES providers(id),
    UNIQUE (spare_part_id, supplier_id)
);

-- Tabla de movimientos de inventario (CRÍTICO)
CREATE TABLE inventory_movements (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    spare_part_id BIGINT UNSIGNED NOT NULL,
    movement_type ENUM('purchase', 'usage', 'adjustment', 'return', 'transfer') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2),
    reference_type VARCHAR(50), -- 'MaintenanceRecord', 'ProductRequest', etc
    reference_id BIGINT UNSIGNED,
    notes TEXT,
    created_by BIGINT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX (spare_part_id, created_at),
    INDEX (reference_type, reference_id)
);
```

#### 9. **product_usages** (Uso de Productos en Mantenimiento)
```
- Relación: spare_part_id, maintenance_record_id
- Campos: quantity_used, date_used, notes, used_by
```

**✅ Fortalezas:**
- Tracking detallado de uso
- Auditoría con used_by

**⚠️ Problemas:**
- Debería registrarse en inventory_movements (propuesta arriba)

#### 10. **product_requests** (Solicitudes de Productos)
```
- Estados: pending, approved, ordered, received
- Prioridades: low, medium, high, urgent
```

**✅ Fortalezas:**
- Workflow bien definido
- Sistema de aprobaciones

**⚠️ Problemas:**
- No hay fecha estimada de entrega
- No hay proveedor asignado
- No hay costo estimado vs real

**💡 Recomendación:**
```sql
ALTER TABLE product_requests ADD COLUMN estimated_cost DECIMAL(10,2);
ALTER TABLE product_requests ADD COLUMN actual_cost DECIMAL(10,2);
ALTER TABLE product_requests ADD COLUMN supplier_id BIGINT UNSIGNED;
ALTER TABLE product_requests ADD COLUMN estimated_delivery_date DATE;
ALTER TABLE product_requests ADD COLUMN actual_delivery_date DATE;
ALTER TABLE product_requests ADD COLUMN order_number VARCHAR(100);
ALTER TABLE product_requests ADD FOREIGN KEY (supplier_id) REFERENCES providers(id);
```

#### 11. **travel_expenses** (Gastos de Viaje)
```
- Tipos: desde constante TravelExpense::EXPENSE_TYPES
- Campos especiales para combustible: fuel_liters, fuel_price_per_liter, odometer_reading
```

**✅ Fortalezas:**
- Cálculos automáticos de combustible
- Sistema de aprobaciones

**⚠️ Problemas:**
- Ver comentario en trip_costs (posible consolidación)

#### 12. **expenses** (Gastos Generales)
```
- Relaciones: category_id, provider_id, cost_center_id
```

**✅ Fortalezas:**
- Clasificación completa

**⚠️ Problemas:**
- No hay workflow de aprobación
- No hay campo status

**💡 Recomendación:**
```sql
ALTER TABLE expenses ADD COLUMN status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending';
ALTER TABLE expenses ADD COLUMN approved_by BIGINT UNSIGNED;
ALTER TABLE expenses ADD COLUMN approved_at TIMESTAMP;
ALTER TABLE expenses ADD FOREIGN KEY (approved_by) REFERENCES users(id);
```

#### 13. **weekly_payrolls** (Nóminas Semanales)
```
- Cálculo: trips_count × payment_scale + adjustments
```

**⚠️ Problemas:**
- No hay desglose de deducciones
- No hay histórico de cambios
- Falta campo payment_method, payment_date, payment_reference

**💡 Recomendación:**
```sql
ALTER TABLE weekly_payrolls ADD COLUMN payment_method ENUM('cash', 'transfer', 'check');
ALTER TABLE weekly_payrolls ADD COLUMN payment_date DATE;
ALTER TABLE weekly_payrolls ADD COLUMN payment_reference VARCHAR(100);
ALTER TABLE weekly_payrolls ADD COLUMN notes TEXT;
ALTER TABLE weekly_payrolls ADD COLUMN status ENUM('draft', 'approved', 'paid') DEFAULT 'draft';

-- Desglose de deducciones
CREATE TABLE payroll_deductions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    weekly_payroll_id BIGINT UNSIGNED NOT NULL,
    deduction_type VARCHAR(50) NOT NULL, -- tax, insurance, advance, etc
    description VARCHAR(255),
    amount DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (weekly_payroll_id) REFERENCES weekly_payrolls(id) ON DELETE CASCADE
);
```

#### 14. **attachments** (Archivos Adjuntos - Polimórfico)
```
- Polimórfico: attachable_type, attachable_id
- Storage: S3/MinIO
```

**✅ Fortalezas:**
- Polimorfismo correcto
- Metadatos completos

**⚠️ Problemas:**
- No hay thumbnail_url para imágenes
- No hay virus_scan_status
- No hay expiration_date

**💡 Recomendación:**
```sql
ALTER TABLE attachments ADD COLUMN thumbnail_url VARCHAR(255);
ALTER TABLE attachments ADD COLUMN file_hash VARCHAR(64);
ALTER TABLE attachments ADD COLUMN virus_scan_status ENUM('pending', 'clean', 'infected') DEFAULT 'pending';
ALTER TABLE attachments ADD COLUMN expires_at TIMESTAMP;
```

### Tablas Catálogos

#### expense_categories, providers, cost_centers, payment_scales

**✅ Fortalezas:**
- Bien normalizadas

**⚠️ Problemas:**
- Falta soft deletes
- Falta is_active flag

**💡 Recomendación:**
```sql
-- Para todas las tablas de catálogos
ALTER TABLE expense_categories ADD COLUMN is_active BOOLEAN DEFAULT true;
ALTER TABLE expense_categories ADD COLUMN deleted_at TIMESTAMP NULL;

ALTER TABLE providers ADD COLUMN is_active BOOLEAN DEFAULT true;
ALTER TABLE providers ADD COLUMN deleted_at TIMESTAMP NULL;

ALTER TABLE cost_centers ADD COLUMN is_active BOOLEAN DEFAULT true;
ALTER TABLE cost_centers ADD COLUMN deleted_at TIMESTAMP NULL;

ALTER TABLE payment_scales ADD COLUMN is_active BOOLEAN DEFAULT true;
ALTER TABLE payment_scales ADD COLUMN deleted_at TIMESTAMP NULL;
```

### Tablas Faltantes (Críticas)

```sql
-- 1. Tabla de auditoría global
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED,
    action VARCHAR(50) NOT NULL, -- create, update, delete, login, etc
    auditable_type VARCHAR(100) NOT NULL,
    auditable_id BIGINT UNSIGNED NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX (auditable_type, auditable_id),
    INDEX (user_id, created_at),
    INDEX (action, created_at)
);

-- 2. Notificaciones en base de datos
CREATE TABLE notifications (
    id CHAR(36) PRIMARY KEY,
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id BIGINT UNSIGNED NOT NULL,
    data JSON NOT NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX (notifiable_type, notifiable_id),
    INDEX (read_at)
);

-- 3. Configuraciones del sistema
CREATE TABLE system_settings (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    type VARCHAR(20) DEFAULT 'string', -- string, int, bool, json
    description TEXT,
    is_public BOOLEAN DEFAULT false, -- si usuarios pueden ver
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Tabla de clientes (si es que facturan a clientes)
CREATE TABLE clients (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255),
    rfc VARCHAR(13),
    email VARCHAR(255),
    phone VARCHAR(20),
    address TEXT,
    credit_limit DECIMAL(12,2),
    credit_days INT DEFAULT 30,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Agregar client_id a trips
ALTER TABLE trips ADD COLUMN client_id BIGINT UNSIGNED;
ALTER TABLE trips ADD FOREIGN KEY (client_id) REFERENCES clients(id);
```

---

## Sistema de Roles y Permisos

### Roles Actuales

| Rol | Accesos | Panel Principal |
|-----|---------|-----------------|
| **super_admin** | Acceso total a todo | Admin |
| **administrador** | Gestión completa excepto sistema | Admin |
| **supervisor** | Operaciones, Mantenimiento, Inventario | Admin/Workshop |
| **contador** | Finanzas, Reportes | Accounting |
| **operador** | Solo sus viajes y gastos | Operator |

### Matriz de Permisos por Resource

| Resource | super_admin | administrador | supervisor | contador | operador |
|----------|-------------|---------------|------------|----------|----------|
| **Vehicles** | CRUD | CRUD | R | - | R (asignado) |
| **Trailers** | CRUD | CRUD | R | - | - |
| **Operators** | CRUD | CRUD | R | - | - |
| **Trips** | CRUD | CRUD | CRU | R | R (propios) |
| **TripCosts** | CRUD | CRUD | CRU | R | CR (propios) |
| **MaintenanceRecords** | CRUD | CRUD | CRUD | R | - |
| **SpareParts** | CRUD | CRUD | CRUD | R | R |
| **ProductRequests** | CRUD | CRUD | CRUD | R | CR |
| **ProductUsages** | CRUD | CRUD | CRUD | - | - |
| **TravelExpenses** | CRUD | CRUD | R | CRU (aprobar) | CR (propios) |
| **Expenses** | CRUD | CRUD | - | CRUD | - |
| **WeeklyPayrolls** | CRUD | CRUD | R | CRUD | R (propios) |
| **Providers** | CRUD | CRUD | R | CRU | - |
| **ExpenseCategories** | CRUD | CRUD | - | CRU | - |
| **CostCenters** | CRUD | CRUD | - | CRU | - |
| **PaymentScales** | CRUD | CRUD | - | R | R |
| **Users** | CRUD | CRU | - | - | - |

**Leyenda:** C=Create, R=Read, U=Update, D=Delete

### Servicios de Permisos Implementados

1. **MaintenanceRecordPermissionService**
   - Controla quién puede crear/editar registros
   - Controla asignación de mecánico

2. **ProductRequestPermissionService**
   - Workflow de aprobación
   - Permisos por estado de solicitud

3. **TravelExpensePermissionService**
   - Operadores solo ven sus gastos
   - Contadores aprueban gastos

4. **FormFieldResolverInterface**
   - Campos visibles/editables según rol
   - Valores por defecto según contexto

### Problemas del Sistema de Permisos

**❌ PROBLEMA 1: Código Duplicado**
```php
// En cada Resource se repite:
protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
{
    return $user->hasAnyRole(['super_admin', 'administrador', 'supervisor']);
}
```

**💡 Solución:** Crear un sistema centralizado de políticas

```php
// app/Policies/BasePolicy.php
abstract class BasePolicy
{
    protected array $rolePermissions = [];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole($this->rolePermissions['viewAny'] ?? []);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole($this->rolePermissions['create'] ?? []);
    }

    // ... demás métodos
}

// app/Policies/TripPolicy.php
class TripPolicy extends BasePolicy
{
    protected array $rolePermissions = [
        'viewAny' => ['super_admin', 'administrador', 'supervisor', 'contador'],
        'view' => ['super_admin', 'administrador', 'supervisor', 'contador', 'operador'],
        'create' => ['super_admin', 'administrador', 'supervisor'],
        'update' => ['super_admin', 'administrador', 'supervisor'],
        'delete' => ['super_admin', 'administrador'],
    ];

    public function view(User $user, Trip $trip): bool
    {
        if ($user->isOperator()) {
            return $trip->operator_id === $user->id;
        }
        return parent::view($user, $trip);
    }
}
```

**❌ PROBLEMA 2: Campos Condicionales Confusos**

Los formularios tienen campos que aparecen/desaparecen según rol, sin explicación.

**💡 Solución:** Agregar mensajes explicativos

```php
Forms\Components\Placeholder::make('field_hidden_notice')
    ->content('Este campo solo es visible para administradores.')
    ->visible(fn() => !$formFieldResolver->isFieldVisible($user, Model::class, 'field'))
```

**❌ PROBLEMA 3: No Hay Logs de Acciones**

No se auditan cambios importantes.

**💡 Solución:** Implementar audit logs (ver tabla propuesta arriba)

---

## Análisis de Resources por Módulo

### Módulo: Gestión de Flota

#### VehicleResource
**Estado:** Funcional
**Campos:** 20+ campos de formulario
**Problemas:**
- Formulario muy largo sin organización clara
- No hay wizard para registro inicial
- Campos de Samsara confusos para usuarios

**💡 Mejoras Sugeridas:**

**Wizard de 3 Pasos: "Registrar Nuevo Vehículo"**

```
Paso 1: Información Básica
- Tipo de vehículo (con iconos: Tractocamión, Trailer)
- Marca y Modelo
- Año
- Número Económico
- Placas

Paso 2: Identificación y Documentos
- VIN
- Número de Serie
- Subir foto del vehículo
- Subir tarjeta de circulación

Paso 3: Integración Samsara (Opcional)
- Toggle: "¿Tiene GPS Samsara?"
- Si Sí: External ID de Samsara
- Si No: Explicar que se puede agregar después
- Botón: "Sincronizar con Samsara"
```

**Mejoras de UX:**
```php
// Agregar tabs en lugar de sections largas
Forms\Components\Tabs::make('vehicle_info')
    ->tabs([
        Forms\Components\Tabs\Tab::make('Básico')
            ->icon('heroicon-o-truck')
            ->schema([...]),
        Forms\Components\Tabs\Tab::make('Documentos')
            ->icon('heroicon-o-document-text')
            ->schema([...]),
        Forms\Components\Tabs\Tab::make('Samsara')
            ->icon('heroicon-o-signal')
            ->schema([...]),
        Forms\Components\Tabs\Tab::make('Mantenimiento')
            ->icon('heroicon-o-wrench')
            ->schema([...]),
    ])
```

#### OperatorResource
**Estado:** Funcional pero con problema arquitectónico
**Problema Crítico:** Duplicación con tabla users

**💡 Solución:** Ver sección de Base de Datos - eliminar tabla operators

### Módulo: Operaciones

#### TripResource
**Estado:** Bueno, con validaciones de asignación
**Fortalezas:**
- Validación de disponibilidad de recursos
- Auto-completado inteligente de fechas
- Estados bien definidos

**Problemas:**
- No hay vista de ruta/mapa
- No hay estimación de costos antes de crear
- No hay templates de viajes recurrentes

**💡 Mejoras Sugeridas:**

**Feature 1: Calculadora de Costos Pre-Viaje**
```php
Forms\Components\Section::make('Estimación de Costos')
    ->schema([
        Forms\Components\Placeholder::make('estimated_distance')
            ->label('Distancia Estimada')
            ->content(fn (Get $get) => $this->calculateDistance($get('origin'), $get('destination'))),

        Forms\Components\Placeholder::make('estimated_fuel_cost')
            ->label('Costo Estimado de Combustible')
            ->content(fn (Get $get) => $this->estimateFuelCost($get('truck_id'), ...)),

        Forms\Components\Placeholder::make('estimated_tolls')
            ->label('Peajes Estimados')
            ->content(fn (Get $get) => $this->estimateTolls($get('origin'), $get('destination'))),

        Forms\Components\Placeholder::make('total_estimated_cost')
            ->label('Costo Total Estimado')
            ->content(fn (Get $get) => $this->getTotalEstimatedCost($get)),
    ])
    ->collapsible()
```

**Feature 2: Templates de Viajes**
```php
Forms\Components\Select::make('trip_template')
    ->label('¿Es un viaje recurrente?')
    ->options(TripTemplate::pluck('name', 'id'))
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        if ($state) {
            $template = TripTemplate::find($state);
            $set('origin', $template->origin);
            $set('destination', $template->destination);
            $set('client_id', $template->client_id);
            // ... más campos
        }
    })
    ->helperText('Selecciona un template para autocompletar datos comunes')
```

#### TripCostResource
**Estado:** Básico
**Problema:** Se solapa con TravelExpenseResource

**💡 Solución:** Ver recomendación en Base de Datos (consolidar en trip_transactions)

### Módulo: Mantenimiento

#### MaintenanceRecordResource
**Estado:** Complejo y poderoso, pero difícil de usar
**Análisis Detallado:** Ver reporte del agente (arriba)

**💡 Implementación de Wizard (Prioridad ALTA):**

```php
// app/Filament/Resources/MaintenanceRecordResource/Pages/CreateMaintenanceRecord.php
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Wizard;

class CreateMaintenanceRecord extends CreateRecord
{
    use CreateRecord\Concerns\HasWizard;

    protected static string $resource = MaintenanceRecordResource::class;

    protected function getSteps(): array
    {
        return [
            Wizard\Step::make('Identificación')
                ->icon('heroicon-o-truck')
                ->description('¿Qué vehículo necesita mantenimiento?')
                ->schema([
                    Forms\Components\Radio::make('vehicle_type')
                        ->label('Tipo de Vehículo')
                        ->options([
                            'App\Models\Vehicle' => 'Tractocamión',
                            'App\Models\Trailer' => 'Trailer',
                        ])
                        ->required()
                        ->inline()
                        ->live(),

                    Forms\Components\Select::make('vehicle_id')
                        ->label('Vehículo')
                        ->options(fn (Get $get) => $this->getVehicleOptions($get('vehicle_type')))
                        ->required()
                        ->searchable()
                        ->getOptionLabelFromRecordUsing(fn ($record) =>
                            $record->display_name . " - Odómetro: {$record->last_odometer_km} km"
                        )
                        ->helperText('Busca por número económico o placa'),

                    Forms\Components\Select::make('maintenance_type')
                        ->label('Tipo de Mantenimiento')
                        ->options([
                            'preventivo' => '🔧 Preventivo - Servicio programado',
                            'correctivo' => '⚙️ Correctivo - Reparación',
                            'emergencia' => '🚨 Emergencia - Atención inmediata',
                            'inspección' => '🔍 Inspección - Revisión general',
                        ])
                        ->required()
                        ->native(false),

                    Forms\Components\DatePicker::make('date')
                        ->label('Fecha del Servicio')
                        ->required()
                        ->default(now())
                        ->maxDate(now()),
                ])
                ->columns(1),

            Wizard\Step::make('Descripción')
                ->icon('heroicon-o-document-text')
                ->description('Describe el trabajo realizado')
                ->schema([
                    Forms\Components\MarkdownEditor::make('description')
                        ->label('Descripción del Trabajo')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'bulletList',
                            'orderedList',
                        ])
                        ->placeholder('Ej: Cambio de aceite y filtro. Se revisó nivel de refrigerante...'),

                    Forms\Components\Select::make('mechanic_id')
                        ->label('Mecánico Asignado')
                        ->relationship('mechanic', 'name', fn ($query) => $query->workshopUsers())
                        ->searchable()
                        ->preload()
                        ->helperText('Quien realizó el trabajo'),
                ])
                ->columns(1),

            Wizard\Step::make('Refacciones')
                ->icon('heroicon-o-cube')
                ->description('Agrega las piezas utilizadas')
                ->schema([
                    Forms\Components\Placeholder::make('parts_info')
                        ->content('Agrega cada refacción utilizada. El stock se actualizará automáticamente.')
                        ->columnSpanFull(),

                    Forms\Components\Repeater::make('products_used')
                        ->label('Refacciones Utilizadas')
                        ->schema([
                            Forms\Components\Grid::make(12)
                                ->schema([
                                    Forms\Components\Select::make('spare_part_id')
                                        ->label('Refacción')
                                        ->relationship('sparePart', 'name')
                                        ->searchable(['name', 'part_number', 'brand'])
                                        ->required()
                                        ->live()
                                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                                            $part = SparePart::find($state);
                                            if ($part) {
                                                $set('available_stock', $part->stock_quantity);
                                                $set('unit_cost', $part->unit_cost);
                                                $set('quantity_used', 1);
                                                $set('item_total', $part->unit_cost);
                                            }
                                        })
                                        ->columnSpan(5),

                                    Forms\Components\TextInput::make('quantity_used')
                                        ->label('Cantidad')
                                        ->numeric()
                                        ->required()
                                        ->minValue(0.01)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                            $stock = $get('available_stock') ?? 0;
                                            $cost = $get('unit_cost') ?? 0;

                                            if ($state > $stock) {
                                                Notification::make()
                                                    ->danger()
                                                    ->title('Stock insuficiente')
                                                    ->body("Solo hay {$stock} unidades disponibles")
                                                    ->send();
                                            }

                                            $set('item_total', $state * $cost);
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\Placeholder::make('stock_indicator')
                                        ->content(function (Forms\Get $get) {
                                            $stock = $get('available_stock') ?? 0;
                                            $color = $stock > 10 ? 'success' : ($stock > 0 ? 'warning' : 'danger');
                                            return new HtmlString("
                                                <div class='text-sm'>
                                                    <span class='font-medium text-{$color}-600'>
                                                        📦 Stock: {$stock}
                                                    </span>
                                                </div>
                                            ");
                                        })
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('unit_cost')
                                        ->label('Costo Unit.')
                                        ->disabled()
                                        ->prefix('$')
                                        ->columnSpan(2),

                                    Forms\Components\TextInput::make('item_total')
                                        ->label('Total')
                                        ->disabled()
                                        ->prefix('$')
                                        ->extraAttributes(['class' => 'font-bold'])
                                        ->columnSpan(3),
                                ]),

                            Forms\Components\Textarea::make('notes')
                                ->label('Notas adicionales')
                                ->rows(2)
                                ->placeholder('Ej: Presentaba fuga, se reemplazó completo'),
                        ])
                        ->addActionLabel('➕ Agregar Refacción')
                        ->reorderable(false)
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string =>
                            $state['spare_part_id']
                                ? SparePart::find($state['spare_part_id'])?->name
                                : 'Nueva refacción'
                        )
                        ->defaultItems(0)
                        ->columnSpanFull(),

                    // Total Cost Display - STICKY
                    Forms\Components\Placeholder::make('cost_summary')
                        ->content(function (Forms\Get $get) {
                            $products = $get('products_used') ?? [];
                            $total = collect($products)->sum('item_total');

                            return new HtmlString("
                                <div class='bg-primary-50 border-2 border-primary-500 rounded-lg p-4'>
                                    <div class='text-center'>
                                        <div class='text-sm text-primary-600 font-medium'>COSTO TOTAL DEL MANTENIMIENTO</div>
                                        <div class='text-3xl font-bold text-primary-700 mt-2'>
                                            $" . number_format($total, 2) . " MXN
                                        </div>
                                        <div class='text-xs text-primary-600 mt-1'>
                                            " . count($products) . " refacciones utilizadas
                                        </div>
                                    </div>
                                </div>
                            ");
                        })
                        ->columnSpanFull(),
                ])
                ->columns(1),

            Wizard\Step::make('Evidencias')
                ->icon('heroicon-o-camera')
                ->description('Sube fotos o comprobantes (opcional)')
                ->schema([
                    Forms\Components\Placeholder::make('evidence_info')
                        ->content(new HtmlString('
                            <div class="text-sm text-gray-600">
                                📸 Las evidencias fotográficas ayudan a documentar el estado del vehículo<br>
                                📄 Sube también facturas o comprobantes de compra
                            </div>
                        ')),

                    Forms\Components\FileUpload::make('new_attachments')
                        ->label('Archivos')
                        ->multiple()
                        ->acceptedFileTypes(['image/*', 'application/pdf'])
                        ->maxSize(10240)
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            null,
                            '16:9',
                            '4:3',
                            '1:1',
                        ])
                        ->directory('maintenance-records-temp')
                        ->visibility('private')
                        ->helperText('Formatos: JPG, PNG, PDF. Máximo 10MB por archivo'),
                ])
                ->columns(1),
        ];
    }

    protected function getSubmitFormAction(): Action
    {
        return parent::getSubmitFormAction()
            ->label('✅ Registrar Mantenimiento')
            ->requiresConfirmation()
            ->modalHeading('Confirmar Registro de Mantenimiento')
            ->modalDescription(fn (array $data) => new HtmlString("
                <div class='space-y-2 text-sm'>
                    <p><strong>Vehículo:</strong> " . $this->getVehicleName($data) . "</p>
                    <p><strong>Tipo:</strong> " . $data['maintenance_type'] . "</p>
                    <p><strong>Refacciones:</strong> " . count($data['products_used'] ?? []) . "</p>
                    <p><strong>Costo Total:</strong> $" . number_format($this->calculateTotal($data), 2) . " MXN</p>
                </div>
            "))
            ->modalSubmitActionLabel('Confirmar y Guardar');
    }
}
```

**Beneficios del Wizard:**
1. ✅ Reduce carga cognitiva (4 pasos simples vs 1 formulario gigante)
2. ✅ Validación temprana por paso
3. ✅ Indicador de progreso claro
4. ✅ Confirmación visual antes de guardar
5. ✅ Mejor en móviles (paso a paso)
6. ✅ Menos errores de captura

### Módulo: Inventario

#### SparePartResource
**Estado:** Básico
**Problemas:**
- No hay alertas de stock bajo
- No hay categorías
- No hay proveedores múltiples

**💡 Mejoras Sugeridas:**

**Feature 1: Dashboard de Inventario**
```php
// app/Filament/Widgets/InventoryStatsWidget.php
protected function getStats(): array
{
    return [
        Stat::make('Total Refacciones', SparePart::count())
            ->description('Productos en catálogo')
            ->icon('heroicon-o-cube'),

        Stat::make('Stock Bajo', SparePart::lowStock()->count())
            ->description('Requieren reorden')
            ->color('danger')
            ->icon('heroicon-o-exclamation-triangle'),

        Stat::make('Valor del Inventario', '$' . number_format(SparePart::sum(DB::raw('stock_quantity * unit_cost')), 2))
            ->description('Valor total en refacciones')
            ->icon('heroicon-o-currency-dollar'),
    ];
}
```

**Feature 2: Alertas Automáticas**
```php
// app/Observers/SparePartObserver.php
public function updated(SparePart $sparePart): void
{
    if ($sparePart->isDirty('stock_quantity') && $sparePart->stock_quantity <= $sparePart->min_stock) {
        // Crear notificación para supervisores
        Notification::make()
            ->warning()
            ->title('Stock Bajo')
            ->body("{$sparePart->name} tiene solo {$sparePart->stock_quantity} unidades")
            ->sendToDatabase(User::workshopUsers()->get());

        // Crear product request automático si está habilitado
        if (config('inventory.auto_request_on_low_stock')) {
            ProductRequest::create([
                'spare_part_id' => $sparePart->id,
                'quantity_requested' => $sparePart->min_stock * 2, // Pedir el doble del mínimo
                'priority' => 'high',
                'justification' => 'Auto-generada por stock bajo',
                'status' => 'pending',
                'requested_by' => User::where('role', 'super_admin')->first()->id,
            ]);
        }
    }
}
```

#### ProductRequestResource
**Estado:** Bien implementado con workflow
**Análisis:** Ver reporte del agente (arriba)

**💡 Mejoras Sugeridas:**

**Feature: Quick Request (Solicitud Rápida)**
```php
// En la tabla de SpareParts, agregar action
Tables\Actions\Action::make('quick_request')
    ->label('Solicitar')
    ->icon('heroicon-o-plus-circle')
    ->color('success')
    ->form([
        Forms\Components\TextInput::make('quantity')
            ->label('Cantidad')
            ->numeric()
            ->required()
            ->default(fn (SparePart $record) => $record->min_stock * 2),

        Forms\Components\Select::make('priority')
            ->label('Prioridad')
            ->options([
                'low' => 'Baja',
                'medium' => 'Media',
                'high' => 'Alta',
                'urgent' => 'Urgente',
            ])
            ->default('medium'),

        Forms\Components\Textarea::make('justification')
            ->label('Justificación')
            ->required()
            ->default('Stock bajo, reorden necesario'),
    ])
    ->action(function (SparePart $record, array $data) {
        ProductRequest::create([
            'spare_part_id' => $record->id,
            'quantity_requested' => $data['quantity'],
            'priority' => $data['priority'],
            'justification' => $data['justification'],
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        Notification::make()
            ->success()
            ->title('Solicitud creada')
            ->send();
    })
```

### Módulo: Finanzas

#### ExpenseResource
**Estado:** Funcional con buen sistema de categorización

**💡 Mejoras Sugeridas:**

**Feature 1: Presupuesto vs Real**
```php
// En CostCenterResource, agregar widget
protected function getHeaderWidgets(): array
{
    return [
        CostCenterBudgetWidget::class,
    ];
}

// app/Filament/Widgets/CostCenterBudgetWidget.php
class CostCenterBudgetWidget extends Widget
{
    public ?CostCenter $record = null;

    protected function getViewData(): array
    {
        $currentMonth = now()->format('Y-m');
        $spent = Expense::where('cost_center_id', $this->record->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$currentMonth])
            ->sum('amount');

        $budget = $this->record->budget;
        $percentage = $budget > 0 ? ($spent / $budget) * 100 : 0;

        return [
            'spent' => $spent,
            'budget' => $budget,
            'remaining' => $budget - $spent,
            'percentage' => $percentage,
            'status' => $percentage > 90 ? 'danger' : ($percentage > 70 ? 'warning' : 'success'),
        ];
    }
}
```

**Feature 2: OCR para Tickets**
```php
// Integración con servicio de OCR para extraer datos de tickets
Forms\Components\FileUpload::make('receipt_image')
    ->label('Foto del Ticket')
    ->image()
    ->imageEditor()
    ->afterStateUpdated(function ($state, Forms\Set $set) {
        if ($state) {
            // Usar servicio de OCR (Google Vision, AWS Textract, etc)
            $extractedData = app(OCRService::class)->extractFromReceipt($state);

            $set('amount', $extractedData['total'] ?? null);
            $set('date', $extractedData['date'] ?? null);
            $set('description', $extractedData['items'] ?? null);

            Notification::make()
                ->success()
                ->title('Datos extraídos del ticket')
                ->body('Verifica que los datos sean correctos')
                ->send();
        }
    })
```

#### TravelExpenseResource
**Estado:** Complejo pero bien implementado
**Análisis:** Ver reporte del agente (arriba)

**💡 Implementación de Wizard (Prioridad ALTA):**

Ver reporte detallado del agente - Wizard de 3 pasos ya diseñado.

#### WeeklyPayrollResource
**Estado:** Básico

**💡 Mejoras Sugeridas:**

**Feature: Generación Automática de Nóminas**
```php
// app/Filament/Pages/GeneratePayrolls.php
class GeneratePayrolls extends Page
{
    protected static string $view = 'filament.pages.generate-payrolls';

    public function mount(): void
    {
        $this->form->fill([
            'week_start' => now()->startOfWeek(),
            'week_end' => now()->endOfWeek(),
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\DatePicker::make('week_start')
                ->label('Inicio de Semana')
                ->required(),

            Forms\Components\DatePicker::make('week_end')
                ->label('Fin de Semana')
                ->required(),

            Forms\Components\CheckboxList::make('operators')
                ->label('Operadores')
                ->options(Operator::active()->pluck('name', 'id'))
                ->default(Operator::active()->pluck('id')->toArray()),
        ];
    }

    public function generate(): void
    {
        $data = $this->form->getState();
        $service = app(PayrollService::class);

        foreach ($data['operators'] as $operatorId) {
            $operator = Operator::find($operatorId);

            $tripsCount = Trip::where('operator_id', $operatorId)
                ->whereBetween('start_date', [$data['week_start'], $data['week_end']])
                ->where('status', 'completed')
                ->count();

            $paymentScale = PaymentScale::forOperator($operator)->first();
            $basePayment = $tripsCount * $paymentScale->rate_per_trip;

            WeeklyPayroll::updateOrCreate(
                [
                    'operator_id' => $operatorId,
                    'week_start' => $data['week_start'],
                ],
                [
                    'week_end' => $data['week_end'],
                    'trips_count' => $tripsCount,
                    'base_payment' => $basePayment,
                    'total_payment' => $basePayment, // Sin ajustes
                ]
            );
        }

        Notification::make()
            ->success()
            ->title('Nóminas generadas')
            ->body(count($data['operators']) . ' nóminas creadas')
            ->send();
    }
}
```

---

## Problemas Identificados

### Categoría: Base de Datos

| # | Problema | Severidad | Impacto |
|---|----------|-----------|---------|
| DB-1 | Tabla `operators` duplica funcionalidad de `users` | 🔴 Alta | Inconsistencias de datos |
| DB-2 | Faltan tablas de auditoría | 🟡 Media | No hay trazabilidad |
| DB-3 | Campos JSON sin validación | 🟡 Media | Datos corruptos |
| DB-4 | Falta normalización en tipos de gastos | 🟢 Baja | Dificultad para reportes |
| DB-5 | No hay soft deletes en catálogos | 🟡 Media | Pérdida de histórico |
| DB-6 | Faltan índices compuestos | 🟡 Media | Queries lentas |
| DB-7 | No hay tabla de inventory_movements | 🔴 Alta | No hay trazabilidad de stock |
| DB-8 | trip_costs y travel_expenses se solapan | 🟡 Media | Confusión conceptual |

### Categoría: UX/UI

| # | Problema | Severidad | Impacto |
|---|----------|-----------|---------|
| UX-1 | Formularios muy largos sin wizards | 🔴 Alta | Usuarios se pierden |
| UX-2 | Repeaters confusos para usuarios no técnicos | 🔴 Alta | Errores de captura |
| UX-3 | Cálculos automáticos sin feedback visual | 🟡 Media | Confusión |
| UX-4 | Campos condicionales sin explicación | 🟡 Media | Frustración |
| UX-5 | No hay tooltips o ayuda contextual | 🔴 Alta | Curva de aprendizaje alta |
| UX-6 | Validaciones tardías (al guardar) | 🟡 Media | Pérdida de tiempo |
| UX-7 | No hay indicadores de progreso | 🟢 Baja | Sensación de lentitud |
| UX-8 | Gestión de archivos confusa (temp vs permanente) | 🟡 Media | Archivos perdidos |

### Categoría: Validaciones

| # | Problema | Severidad | Impacto |
|---|----------|-----------|---------|
| VAL-1 | Stock no se valida en tiempo real | 🟡 Media | Uso de stock inexistente |
| VAL-2 | Solapamiento de viajes no se valida | 🔴 Alta | Doble asignación |
| VAL-3 | No hay límites de crédito para clientes | 🟡 Media | Riesgo financiero |
| VAL-4 | Fechas futuras permitidas donde no deben | 🟢 Baja | Datos incorrectos |
| VAL-5 | Montos negativos permitidos | 🟡 Media | Datos corruptos |

### Categoría: Seguridad

| # | Problema | Severidad | Impacto |
|---|----------|-----------|---------|
| SEC-1 | No hay 2FA para super_admin | 🔴 Alta | Acceso no autorizado |
| SEC-2 | Archivos sin validación de virus | 🟡 Media | Malware |
| SEC-3 | No hay rate limiting en uploads | 🟡 Media | Abuso de recursos |
| SEC-4 | Logs de auditoría inexistentes | 🔴 Alta | No hay trazabilidad |
| SEC-5 | Passwords sin requisitos de complejidad | 🟡 Media | Cuentas comprometidas |

### Categoría: Performance

| # | Problema | Severidad | Impacto |
|---|----------|-----------|---------|
| PERF-1 | N+1 queries en tablas con relaciones | 🟡 Media | Lentitud |
| PERF-2 | No hay cache de queries repetidas | 🟢 Baja | Carga innecesaria en DB |
| PERF-3 | Carga eager de relaciones no usadas | 🟢 Baja | Memoria desperdiciada |
| PERF-4 | Archivos grandes sin compresión | 🟡 Media | Storage caro |

### Categoría: Funcionalidad

| # | Problema | Severidad | Impacto |
|---|----------|-----------|---------|
| FUNC-1 | No hay notificaciones push o email | 🟡 Media | Comunicación ineficiente |
| FUNC-2 | No hay exportación a Excel/PDF | 🔴 Alta | Reportes manuales |
| FUNC-3 | No hay dashboard por rol | 🟡 Media | Información irrelevante |
| FUNC-4 | No hay calendario de mantenimientos | 🔴 Alta | Mantenimientos olvidados |
| FUNC-5 | No hay rastreo GPS en tiempo real | 🟡 Media | Visibilidad limitada |
| FUNC-6 | No hay chat interno | 🟢 Baja | Comunicación externa |

---

## Recomendaciones de Mejora

### Prioridad 1: CRÍTICAS (Implementar en 1-2 semanas)

#### 1. Consolidar Operators y Users
**Problema:** DB-1, duplicación de datos
**Acción:**
```bash
# 1. Crear migración
php artisan make:migration consolidate_operators_into_users

# 2. Migrar datos
# 3. Actualizar modelos y relaciones
# 4. Actualizar tests
# 5. Actualizar seeders
```

#### 2. Implementar Wizards en Formularios Complejos
**Problemas:** UX-1, UX-2
**Recursos a convertir:**
- MaintenanceRecordResource (Prioridad #1)
- TravelExpenseResource (Prioridad #2)
- VehicleResource (Prioridad #3)

**Implementación:** Ver código detallado en sección de MaintenanceRecordResource

#### 3. Agregar Tooltips y Ayuda Contextual
**Problema:** UX-5
**Acción:**
```php
// Crear componente reutilizable
// app/Filament/Components/HelpText.php
class HelpText extends Component
{
    public string $content;
    public string $title = 'Ayuda';

    public static function make(string $content): static
    {
        return app(static::class, ['content' => $content]);
    }

    public function render(): View
    {
        return view('filament.components.help-text');
    }
}

// Uso en formularios
Forms\Components\TextInput::make('unit_number')
    ->label('Número Económico')
    ->suffixAction(
        Forms\Components\Actions\Action::make('help')
            ->icon('heroicon-o-question-mark-circle')
            ->tooltip('El número económico es el identificador interno de tu empresa para el vehículo. Ej: T-001, Tracto-45')
    )
```

#### 4. Implementar Tabla de Auditoría
**Problema:** SEC-4, DB-2
**Acción:**
```bash
# 1. Instalar paquete
composer require spatie/laravel-activitylog

# 2. Publicar configuración
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate

# 3. Agregar trait a modelos críticos
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Trip extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['origin', 'destination', 'status', 'operator_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

#### 5. Implementar Tabla de Inventory Movements
**Problema:** DB-7
**Acción:**
```bash
php artisan make:migration create_inventory_movements_table
```
Ver SQL detallado en sección de Base de Datos.

#### 6. Validación de Solapamiento de Viajes
**Problema:** VAL-2
**Acción:**
```php
// app/Services/VehicleAssignmentService.php
public function validateTripOverlap(int $vehicleId, Carbon $startDate, Carbon $endDate, ?int $excludeTripId = null): array
{
    $overlappingTrips = Trip::where('truck_id', $vehicleId)
        ->where('status', '!=', 'cancelled')
        ->where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q) use ($startDate, $endDate) {
                      $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                  });
        })
        ->when($excludeTripId, fn ($q) => $q->where('id', '!=', $excludeTripId))
        ->exists();

    return [
        'can_assign' => !$overlappingTrips,
        'errors' => $overlappingTrips ? ['El vehículo ya tiene un viaje en estas fechas'] : [],
    ];
}

// Usar en TripResource::form()
Forms\Components\DatePicker::make('start_date')
    ->live()
    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
        $service = app(VehicleAssignmentService::class);
        $validation = $service->validateTripOverlap(
            $get('truck_id'),
            $state,
            $get('end_date') ?? $state
        );

        if (!$validation['can_assign']) {
            Notification::make()
                ->danger()
                ->title('Conflicto de fechas')
                ->body($validation['errors'][0])
                ->send();
        }
    })
```

### Prioridad 2: IMPORTANTES (Implementar en 3-4 semanas)

#### 7. Sistema de Exportación de Reportes
**Problema:** FUNC-2
**Acción:**
```bash
composer require pelmered/filament-excel

# En cada Resource
use pelmered\FilamentExcel\Actions\Tables\ExportBulkAction;

public static function table(Table $table): Table
{
    return $table
        ->bulkActions([
            ExportBulkAction::make()
                ->label('Exportar a Excel')
                ->exports([
                    ExcelExport::make()
                        ->fromTable()
                        ->withFilename('viajes_' . date('Y-m-d'))
                        ->withColumns([
                            Column::make('display_name'),
                            Column::make('status'),
                            Column::make('total_cost'),
                        ]),
                ]),
        ]);
}
```

#### 8. Dashboard Personalizado por Rol
**Problema:** FUNC-3
**Acción:**
```php
// app/Filament/Pages/OperatorDashboard.php
class OperatorDashboard extends BaseDashboard
{
    protected static string $view = 'filament.pages.operator-dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            OperatorTripsWidget::class,
            OperatorExpensesWidget::class,
            OperatorPayrollWidget::class,
        ];
    }

    public function mount(): void
    {
        $this->currentTrip = Trip::where('operator_id', auth()->id())
            ->where('status', 'in_progress')
            ->first();

        $this->pendingExpenses = TravelExpense::where('operator_id', auth()->id())
            ->where('status', 'pending')
            ->count();
    }
}

// resources/views/filament/pages/operator-dashboard.blade.php
<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-filament::card>
                <a href="{{ route('filament.operator.resources.travel-expenses.create') }}"
                   class="block text-center p-4">
                    <x-heroicon-o-plus-circle class="w-12 h-12 mx-auto text-primary-500"/>
                    <h3 class="mt-2 font-bold">Registrar Gasto</h3>
                </a>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center p-4">
                    <x-heroicon-o-truck class="w-12 h-12 mx-auto text-success-500"/>
                    <h3 class="mt-2 font-bold">Viaje Actual</h3>
                    <p class="text-sm">{{ $currentTrip?->display_name ?? 'Sin viaje activo' }}</p>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-center p-4">
                    <x-heroicon-o-currency-dollar class="w-12 h-12 mx-auto text-warning-500"/>
                    <h3 class="mt-2 font-bold">Gastos Pendientes</h3>
                    <p class="text-2xl font-bold">{{ $pendingExpenses }}</p>
                </div>
            </x-filament::card>
        </div>

        {{-- Widgets --}}
        <div class="grid grid-cols-1 gap-6">
            @foreach ($this->getHeaderWidgets() as $widget)
                @livewire($widget)
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
```

#### 9. Calendario de Mantenimientos
**Problema:** FUNC-4
**Acción:**
```bash
composer require saade/filament-fullcalendar

# app/Filament/Pages/MaintenanceCalendar.php
class MaintenanceCalendar extends FullCalendarPage
{
    public function getViewData(): array
    {
        return [
            'events' => MaintenanceRecord::query()
                ->whereDate('scheduled_date', '>=', now()->subMonth())
                ->whereDate('scheduled_date', '<=', now()->addMonths(3))
                ->with(['vehicle'])
                ->get()
                ->map(fn ($record) => [
                    'title' => $record->vehicle->display_name . ' - ' . $record->maintenance_type,
                    'start' => $record->scheduled_date->toDateString(),
                    'backgroundColor' => match($record->status) {
                        'scheduled' => '#3b82f6',
                        'in_progress' => '#f59e0b',
                        'completed' => '#10b981',
                        'cancelled' => '#ef4444',
                    },
                    'url' => route('filament.admin.resources.maintenance-records.edit', $record),
                ])
                ->toArray(),
        ];
    }
}
```

#### 10. Notificaciones por Email y Push
**Problema:** FUNC-1
**Acción:**
```php
// app/Notifications/LowStockNotification.php
class LowStockNotification extends Notification
{
    public function __construct(public SparePart $sparePart) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Alerta: Stock Bajo')
            ->line("El producto {$this->sparePart->name} tiene stock bajo.")
            ->line("Stock actual: {$this->sparePart->stock_quantity} unidades")
            ->line("Stock mínimo: {$this->sparePart->min_stock} unidades")
            ->action('Ver Producto', url("/admin/spare-parts/{$this->sparePart->id}/edit"));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'spare_part_id' => $this->sparePart->id,
            'spare_part_name' => $this->sparePart->name,
            'current_stock' => $this->sparePart->stock_quantity,
            'min_stock' => $this->sparePart->min_stock,
        ];
    }
}

// Enviar en SparePartObserver
public function updated(SparePart $sparePart): void
{
    if ($sparePart->isDirty('stock_quantity') && $sparePart->stock_quantity <= $sparePart->min_stock) {
        $users = User::workshopUsers()->get();
        Notification::send($users, new LowStockNotification($sparePart));
    }
}
```

### Prioridad 3: DESEABLES (Implementar en 5-8 semanas)

#### 11. Integración de GPS en Tiempo Real
**Problema:** FUNC-5
**Nota:** Ya tienen integración con Samsara, solo falta UI

```php
// app/Filament/Pages/LiveFleetMap.php
class LiveFleetMap extends Page
{
    protected static string $view = 'filament.pages.live-fleet-map';

    protected function getViewData(): array
    {
        return [
            'vehicles' => Vehicle::whereNotNull('last_lat')
                ->whereNotNull('last_lng')
                ->where('last_location_at', '>=', now()->subHours(2))
                ->with(['currentTrip'])
                ->get()
                ->map(fn ($vehicle) => [
                    'id' => $vehicle->id,
                    'name' => $vehicle->display_name,
                    'lat' => $vehicle->last_lat,
                    'lng' => $vehicle->last_lng,
                    'status' => $vehicle->status,
                    'speed' => $vehicle->last_speed_mph,
                    'trip' => $vehicle->currentTrip?->display_name,
                ]),
        ];
    }
}

// resources/views/filament/pages/live-fleet-map.blade.php
<div x-data="fleetMap(@js($vehicles))"
     x-init="initMap()"
     wire:poll.30s="$refresh">
    <div id="map" class="h-[600px] rounded-lg"></div>
</div>

<script>
function fleetMap(vehicles) {
    return {
        map: null,
        markers: [],

        initMap() {
            this.map = L.map('map').setView([19.4326, -99.1332], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(this.map);

            vehicles.forEach(vehicle => {
                const icon = L.divIcon({
                    html: `<div class="vehicle-marker ${vehicle.status}">🚚</div>`,
                    className: 'custom-div-icon',
                });

                const marker = L.marker([vehicle.lat, vehicle.lng], { icon })
                    .bindPopup(`
                        <b>${vehicle.name}</b><br>
                        Estado: ${vehicle.status}<br>
                        Velocidad: ${vehicle.speed} mph<br>
                        ${vehicle.trip ? `Viaje: ${vehicle.trip}` : ''}
                    `)
                    .addTo(this.map);

                this.markers.push(marker);
            });
        }
    }
}
</script>
```

#### 12. OCR para Extracción de Datos de Tickets
**Problema:** UX - entrada manual tediosa
**Ver código en sección de ExpenseResource**

#### 13. Chat Interno
**Problema:** FUNC-6
```bash
composer require lloricode/filament-chat-support
```

#### 14. Reportes Avanzados con Gráficas
```bash
composer require filament/spatie-laravel-analytics-plugin

# Crear widgets personalizados
php artisan make:filament-widget CostTrendChart --chart
```

---

## Plan de Implementación

### Sprint 1 (Semana 1-2): Fundamentos Críticos

**Objetivos:**
- ✅ Consolidar operators y users
- ✅ Implementar tabla de auditoría
- ✅ Agregar tabla de inventory_movements
- ✅ Implementar validación de solapamiento de viajes

**Tareas Detalladas:**

**Día 1-2: Análisis y Preparación**
- [ ] Backup completo de base de datos
- [ ] Crear rama de desarrollo: `git checkout -b feature/critical-improvements`
- [ ] Documentar estado actual
- [ ] Crear tests de regresión

**Día 3-5: Consolidación de Operators**
- [ ] Crear migración `consolidate_operators_into_users`
- [ ] Migrar datos de operators a users
- [ ] Actualizar foreign keys
- [ ] Actualizar modelos (Trip, WeeklyPayroll, etc)
- [ ] Actualizar Resources y seeders
- [ ] Ejecutar tests

**Día 6-7: Sistema de Auditoría**
- [ ] Instalar spatie/laravel-activitylog
- [ ] Configurar logging en modelos críticos
- [ ] Crear Resource para ver logs
- [ ] Agregar widget de "Actividad Reciente"

**Día 8-9: Inventory Movements**
- [ ] Crear tabla inventory_movements
- [ ] Crear modelo InventoryMovement
- [ ] Crear observer para registrar movimientos automáticos
- [ ] Actualizar ProductUsage para registrar movimientos
- [ ] Crear Resource para ver histórico

**Día 10: Validación de Solapamientos**
- [ ] Implementar método en VehicleAssignmentService
- [ ] Agregar validación en TripResource
- [ ] Agregar tests unitarios
- [ ] Documentar lógica

### Sprint 2 (Semana 3-4): Mejoras de UX

**Objetivos:**
- ✅ Implementar wizards en formularios complejos
- ✅ Agregar tooltips y ayuda contextual
- ✅ Mejorar feedback visual

**Tareas Detalladas:**

**Día 1-4: Wizard de MaintenanceRecord**
- [ ] Crear CreateMaintenanceRecord con wizard
- [ ] Diseñar 4 pasos del wizard
- [ ] Implementar validación por paso
- [ ] Agregar calculadora de costo total
- [ ] Mejorar UI de productos (repeater)
- [ ] Tests de integración

**Día 5-7: Wizard de TravelExpense**
- [ ] Crear wizard de 3 pasos
- [ ] Implementar selector visual de tipo de gasto
- [ ] Mejorar UX de combustible (opciones A/B)
- [ ] Agregar sugerencias inteligentes
- [ ] Tests de integración

**Día 8-9: Sistema de Ayuda**
- [ ] Crear componente HelpText
- [ ] Agregar tooltips a todos los campos críticos
- [ ] Crear página de "Guía Rápida" por resource
- [ ] Grabar videos cortos de uso

**Día 10: Feedback Visual**
- [ ] Agregar indicadores de "calculando..."
- [ ] Agregar animaciones a campos auto-completados
- [ ] Mejorar mensajes de notificación
- [ ] Agregar progress bars en uploads

### Sprint 3 (Semana 5-6): Reportes y Dashboards

**Objetivos:**
- ✅ Sistema de exportación
- ✅ Dashboards por rol
- ✅ Calendario de mantenimientos

**Tareas Detalladas:**

**Día 1-2: Exportación**
- [ ] Instalar filament-excel
- [ ] Agregar exports a todos los Resources
- [ ] Crear templates personalizados
- [ ] Agregar export a PDF con DomPDF

**Día 3-5: Dashboards**
- [ ] Crear OperatorDashboard
- [ ] Crear AccountingDashboard
- [ ] Crear WorkshopDashboard
- [ ] Crear widgets personalizados por rol
- [ ] Agregar quick actions

**Día 6-8: Calendario**
- [ ] Instalar filament-fullcalendar
- [ ] Crear MaintenanceCalendar
- [ ] Integrar con sistema de recordatorios
- [ ] Agregar drag & drop para reprogramar

**Día 9-10: Alertas y Notificaciones**
- [ ] Configurar email notifications
- [ ] Crear notificaciones de stock bajo
- [ ] Crear notificaciones de mantenimiento próximo
- [ ] Crear notificaciones de aprobaciones pendientes

### Sprint 4 (Semana 7-8): Features Avanzadas

**Objetivos:**
- ✅ Mapa de flota en tiempo real
- ✅ Mejoras de inventario
- ✅ Sistema de presupuestos

**Tareas Detalladas:**

**Día 1-3: Mapa de Flota**
- [ ] Instalar Leaflet.js
- [ ] Crear LiveFleetMap page
- [ ] Integrar con datos de Samsara
- [ ] Agregar filtros y layers
- [ ] Agregar geocercas (opcional)

**Día 4-6: Inventario**
- [ ] Dashboard de inventario
- [ ] Alertas automáticas de stock bajo
- [ ] Quick request desde tabla de SpareParts
- [ ] Categorías de productos
- [ ] Múltiples proveedores por producto

**Día 7-8: Presupuestos**
- [ ] Agregar campo budget a cost_centers
- [ ] Crear widget de presupuesto vs real
- [ ] Alertas cuando se excede 90% del presupuesto
- [ ] Reportes mensuales de presupuesto

**Día 9-10: Testing y Refinamiento**
- [ ] Tests de integración end-to-end
- [ ] Optimización de queries
- [ ] Limpieza de código
- [ ] Documentación final

### Post-Implementación (Semana 9+)

**Monitoreo y Ajustes:**
- [ ] Recopilar feedback de usuarios
- [ ] Ajustar basado en uso real
- [ ] Capacitación a usuarios
- [ ] Documentación de usuario final

**Mejoras Continuas:**
- [ ] Análisis de performance con Laravel Telescope
- [ ] Optimización de queries lentas
- [ ] Agregar más tests automatizados
- [ ] Refactorizar código duplicado

---

## Métricas de Éxito

### KPIs a Medir

**1. Tiempo de Captura**
- **Antes:** ~10 minutos para registrar un mantenimiento
- **Meta:** <5 minutos con wizard
- **Cómo medir:** Time tracking en FormSubmit events

**2. Errores de Captura**
- **Antes:** ~30% de registros con datos incompletos o incorrectos
- **Meta:** <10%
- **Cómo medir:** Validaciones fallidas / Total de intentos

**3. Adopción del Sistema**
- **Antes:** Solo 60% de operadores usan el sistema regularmente
- **Meta:** >90%
- **Cómo medir:** Usuarios activos semanalmente

**4. Satisfacción de Usuario**
- **Meta:** >4/5 en encuesta de satisfacción
- **Cómo medir:** Encuesta trimestral NPS

**5. Performance**
- **Antes:** Tiempo de carga promedio ~3s
- **Meta:** <1s
- **Cómo medir:** Laravel Telescope

---

## Conclusiones

### Fortalezas del Sistema Actual

1. ✅ Arquitectura sólida con separación de concerns
2. ✅ Sistema de permisos robusto
3. ✅ Integración exitosa con Samsara
4. ✅ Base de datos bien estructurada
5. ✅ Uso correcto de Filament 3

### Áreas que Requieren Atención Urgente

1. 🔴 **Experiencia de Usuario**: Formularios complejos sin guías
2. 🔴 **Consolidación de Datos**: Eliminar duplicación operators/users
3. 🔴 **Auditoría**: No hay trazabilidad de cambios
4. 🔴 **Inventario**: Falta histórico de movimientos
5. 🔴 **Validaciones**: Solapamiento de viajes sin validar

### ROI Estimado de las Mejoras

**Inversión:**
- 8 semanas de desarrollo
- ~320 horas de trabajo

**Retorno:**
- 50% reducción en tiempo de captura = ~20 horas/semana ahorradas
- 70% reducción en errores de datos = menos reproceso
- 30% mejora en adopción = más datos para decisiones
- Mejor visibilidad de flota = optimización de rutas (~10% ahorro en combustible)

**Payback:** ~3 meses

---

## Recursos Adicionales

### Documentación Recomendada

1. **Filament PHP**: https://filamentphp.com/docs
2. **Laravel Best Practices**: https://github.com/alexeymezenin/laravel-best-practices
3. **Database Design**: https://www.databasestar.com/
4. **UX Guidelines for Forms**: https://www.nngroup.com/articles/web-form-design/

### Herramientas Útiles

1. **Laravel Telescope**: Para debugging y performance
2. **Laravel Debugbar**: Para queries N+1
3. **PHPStan**: Para análisis estático de código
4. **Laravel Pint**: Para code style
5. **Postman/Insomnia**: Para testing de APIs

### Paquetes Filament Recomendados

```bash
# Wizards mejorados
composer require jeffgreco13/filament-breezy

# Calendario
composer require saade/filament-fullcalendar

# Exports avanzados
composer require pelmered/filament-excel

# Logs de auditoría
composer require spatie/laravel-activitylog

# Notificaciones avanzadas
composer require filament/notifications

# Mapas
composer require cheesegrits/filament-google-maps

# Analytics
composer require filament/spatie-laravel-analytics-plugin
```

---

**Documento generado el:** 2025-10-23
**Versión:** 1.0
**Autor:** Análisis automatizado del sistema ERP

---

## Apéndices

### Apéndice A: Ejemplo de Configuración de Políticas

```php
// config/permission.php
return [
    'role_permissions' => [
        'super_admin' => '*',
        'administrador' => [
            'vehicles' => ['viewAny', 'view', 'create', 'update', 'delete'],
            'trips' => ['viewAny', 'view', 'create', 'update'],
            'expenses' => ['viewAny', 'view', 'create', 'update'],
            // ...
        ],
        'supervisor' => [
            'trips' => ['viewAny', 'view', 'create', 'update'],
            'maintenance' => ['viewAny', 'view', 'create', 'update', 'delete'],
            // ...
        ],
        'contador' => [
            'expenses' => ['viewAny', 'view', 'create', 'update', 'delete'],
            'payrolls' => ['viewAny', 'view', 'create', 'update'],
            // ...
        ],
        'operador' => [
            'trips' => ['view'], // solo propios
            'expenses' => ['viewAny', 'view', 'create'], // solo propios
            // ...
        ],
    ],
];
```

### Apéndice B: Queries Útiles para Reportes

```sql
-- Reporte de costos por vehículo
SELECT
    v.name as vehicle,
    COUNT(DISTINCT t.id) as total_trips,
    SUM(tc.amount) as total_costs,
    AVG(tc.amount) as avg_cost_per_trip,
    SUM(CASE WHEN tc.cost_type = 'diesel' THEN tc.amount ELSE 0 END) as fuel_costs,
    SUM(CASE WHEN tc.cost_type = 'toll' THEN tc.amount ELSE 0 END) as toll_costs
FROM vehicles v
LEFT JOIN trips t ON t.truck_id = v.id
LEFT JOIN trip_costs tc ON tc.trip_id = t.id
WHERE t.start_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY v.id, v.name
ORDER BY total_costs DESC;

-- Top 10 refacciones más usadas
SELECT
    sp.name,
    sp.part_number,
    SUM(pu.quantity_used) as total_used,
    COUNT(DISTINCT pu.maintenance_record_id) as times_used,
    SUM(pu.quantity_used * sp.unit_cost) as total_cost
FROM spare_parts sp
JOIN product_usages pu ON pu.spare_part_id = sp.id
WHERE pu.date_used >= DATE_SUB(NOW(), INTERVAL 90 DAY)
GROUP BY sp.id, sp.name, sp.part_number
ORDER BY total_used DESC
LIMIT 10;

-- Operadores con más gastos de viaje pendientes
SELECT
    o.name,
    COUNT(te.id) as pending_expenses,
    SUM(te.amount) as total_pending
FROM operators o
JOIN travel_expenses te ON te.operator_id = o.id
WHERE te.status = 'pending'
GROUP BY o.id, o.name
ORDER BY total_pending DESC;

-- Vehículos próximos a mantenimiento
SELECT
    v.name,
    v.unit_number,
    v.last_odometer_km,
    v.next_maintenance_km,
    (v.next_maintenance_km - v.last_odometer_km) as km_remaining,
    v.next_maintenance_date,
    DATEDIFF(v.next_maintenance_date, NOW()) as days_remaining
FROM vehicles v
WHERE v.next_maintenance_km IS NOT NULL
  AND (v.next_maintenance_km - v.last_odometer_km) < 500
   OR DATEDIFF(v.next_maintenance_date, NOW()) < 7
ORDER BY km_remaining ASC, days_remaining ASC;
```

### Apéndice C: Checklist de Testing

```markdown
## Checklist de Testing por Sprint

### Sprint 1: Fundamentos
- [ ] Migración de operators a users exitosa
- [ ] Todos los foreign keys actualizados
- [ ] Auditoría funciona en modelos críticos
- [ ] Inventory movements se registran correctamente
- [ ] Validación de solapamiento funciona

### Sprint 2: UX
- [ ] Wizard de mantenimiento fluye correctamente
- [ ] Cálculos automáticos funcionan en todos los pasos
- [ ] Validación de stock en tiempo real
- [ ] Wizard de travel expense funciona
- [ ] Tooltips visibles y útiles

### Sprint 3: Reportes
- [ ] Exportación a Excel funciona
- [ ] Exportación a PDF con formato correcto
- [ ] Dashboards cargan rápido (<2s)
- [ ] Calendario muestra eventos correctamente
- [ ] Notificaciones por email funcionan

### Sprint 4: Advanced
- [ ] Mapa de flota carga en <3s
- [ ] Marcadores se actualizan cada 30s
- [ ] Alertas de stock bajo se disparan
- [ ] Presupuestos se calculan correctamente
```

---

**FIN DEL DOCUMENTO**
