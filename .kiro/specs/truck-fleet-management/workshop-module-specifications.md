# Especificaciones Técnicas - Módulo de Taller Completo

## Resumen Ejecutivo

Este documento define las especificaciones técnicas completas para la expansión del módulo de taller del Sistema de Administración de Tractocamiones. El módulo actual cuenta con una estructura base preparatoria que incluye modelos básicos para registros de mantenimiento, refacciones y relaciones many-to-many. Esta expansión convertirá esta base en un sistema completo de gestión de taller.

## Arquitectura del Módulo Completo

### Componentes Principales

#### 1. Sistema de Órdenes de Trabajo
**Propósito**: Gestión completa del flujo de trabajo desde la solicitud hasta la finalización del mantenimiento.

**Modelos Adicionales Requeridos**:
```php
WorkOrder: id, vehicle_id, vehicle_type, priority, status, requested_by, assigned_mechanic_id, 
          description, estimated_hours, actual_hours, estimated_cost, actual_cost, 
          scheduled_date, started_at, completed_at, approved_by, notes, created_at, updated_at

WorkOrderTask: id, work_order_id, task_description, status, assigned_mechanic_id, 
              estimated_hours, actual_hours, notes, completed_at, created_at, updated_at

WorkOrderSparePart: id, work_order_id, spare_part_id, quantity_requested, 
                   quantity_used, unit_cost, total_cost, created_at, updated_at
```

**Estados de Órdenes de Trabajo**:
- `pending`: Pendiente de asignación
- `assigned`: Asignada a mecánico
- `in_progress`: En proceso
- `waiting_parts`: Esperando refacciones
- `completed`: Completada
- `cancelled`: Cancelada
- `approved`: Aprobada por supervisor

#### 2. Sistema de Inventario Avanzado
**Propósito**: Control completo de inventario con alertas, proveedores y movimientos.

**Modelos Adicionales Requeridos**:
```php
SparePartCategory: id, name, description, created_at, updated_at

SparePartSupplier: id, name, contact_name, phone, email, address, 
                  payment_terms, delivery_time_days, created_at, updated_at

InventoryMovement: id, spare_part_id, movement_type, quantity, unit_cost, 
                  reference_type, reference_id, notes, created_at, updated_at

PurchaseOrder: id, supplier_id, order_number, status, total_amount, 
              requested_by, approved_by, ordered_at, received_at, created_at, updated_at

PurchaseOrderItem: id, purchase_order_id, spare_part_id, quantity_ordered, 
                  quantity_received, unit_cost, total_cost, created_at, updated_at

InventoryAlert: id, spare_part_id, alert_type, threshold_quantity, 
               current_quantity, status, created_at, updated_at
```

**Tipos de Movimientos de Inventario**:
- `purchase`: Compra/Entrada
- `usage`: Uso en mantenimiento
- `adjustment`: Ajuste de inventario
- `transfer`: Transferencia entre ubicaciones
- `return`: Devolución de refacción

#### 3. Sistema de Programación y Calendario
**Propósito**: Programación de mantenimientos preventivos y gestión de calendario del taller.

**Modelos Adicionales Requeridos**:
```php
MaintenanceSchedule: id, vehicle_id, vehicle_type, maintenance_type, 
                    frequency_type, frequency_value, last_performed_at, 
                    next_due_at, odometer_frequency, last_odometer, 
                    next_odometer_due, is_active, created_at, updated_at

PreventiveMaintenance: id, name, description, estimated_hours, 
                      required_parts_json, checklist_json, created_at, updated_at

MaintenanceChecklist: id, work_order_id, checklist_item, status, 
                     notes, checked_by, checked_at, created_at, updated_at

CalendarEvent: id, event_type, title, description, start_datetime, 
              end_datetime, vehicle_id, mechanic_id, work_order_id, 
              status, created_at, updated_at
```

**Tipos de Frecuencia de Mantenimiento**:
- `days`: Cada X días
- `weeks`: Cada X semanas  
- `months`: Cada X meses
- `kilometers`: Cada X kilómetros
- `hours`: Cada X horas de operación

#### 4. Sistema de Personal de Taller
**Propósito**: Gestión completa del personal técnico con especialidades y certificaciones.

**Modelos Adicionales Requeridos**:
```php
Mechanic: id, employee_number, name, phone, email, hire_date, 
         hourly_rate, specialties_json, certifications_json, 
         status, supervisor_id, created_at, updated_at

MechanicSpecialty: id, name, description, created_at, updated_at

MechanicCertification: id, mechanic_id, certification_name, 
                      issued_by, issued_date, expiry_date, 
                      certificate_number, status, created_at, updated_at

WorkShift: id, mechanic_id, shift_date, start_time, end_time, 
          break_minutes, total_hours, hourly_rate, notes, created_at, updated_at

TimeTracking: id, mechanic_id, work_order_id, start_time, end_time, 
             break_minutes, total_minutes, hourly_rate, 
             total_cost, notes, created_at, updated_at
```

## Filament Resources Requeridos

### Resources Principales
```php
// Gestión de Órdenes de Trabajo
WorkOrderResource: Gestión completa de órdenes con estados y asignaciones
WorkOrderTaskResource: Tareas individuales dentro de órdenes
PreventiveMaintenanceResource: Configuración de mantenimientos preventivos

// Inventario Avanzado
SparePartResource: Expandir el resource actual con alertas y movimientos
InventoryMovementResource: Historial de movimientos de inventario
PurchaseOrderResource: Gestión de órdenes de compra
SparePartSupplierResource: Gestión de proveedores de refacciones

// Personal de Taller
MechanicResource: Gestión completa de mecánicos
MechanicCertificationResource: Certificaciones y especialidades
TimeTrackingResource: Control de tiempo trabajado

// Programación
MaintenanceScheduleResource: Programación de mantenimientos preventivos
CalendarEventResource: Eventos del calendario del taller
```

### Páginas Personalizadas Filament
```php
// Dashboard del Taller
WorkshopDashboardPage: Métricas clave, órdenes pendientes, alertas de inventario

// Calendario y Programación
WorkshopCalendarPage: Vista de calendario con órdenes programadas
MaintenanceSchedulePage: Programación de mantenimientos preventivos

// Reportes Especializados
WorkshopReportsPage: Reportes de productividad, costos, tiempos
InventoryReportsPage: Reportes de inventario, rotación, alertas
MechanicPerformancePage: Reportes de rendimiento por mecánico

// Monitoreo en Tiempo Real
WorkshopStatusPage: Estado actual del taller, órdenes activas
InventoryAlertsPage: Alertas de stock bajo, vencimientos
```

## Servicios de Negocio Requeridos

### Servicios Principales
```php
WorkOrderService:
- createWorkOrder($vehicleId, $description, $priority)
- assignMechanic($workOrderId, $mechanicId)
- updateStatus($workOrderId, $status)
- calculateEstimatedCost($workOrderId)
- generateWorkOrderReport($workOrderId)

InventoryService:
- checkStockAvailability($sparePartId, $quantity)
- reserveParts($workOrderId, $partsArray)
- consumeParts($workOrderId, $partsArray)
- generateLowStockAlert($sparePartId)
- calculateInventoryValue()

MaintenanceScheduleService:
- generatePreventiveSchedule($vehicleId)
- checkDueMaintenances()
- createScheduledWorkOrder($scheduleId)
- updateNextDueDate($scheduleId, $completedDate)

TimeTrackingService:
- startWorkSession($mechanicId, $workOrderId)
- endWorkSession($sessionId)
- calculateLaborCost($workOrderId)
- generateMechanicTimeReport($mechanicId, $period)

WorkshopReportService:
- generateProductivityReport($period)
- calculateAverageRepairTime($maintenanceType)
- generateCostAnalysis($period)
- generateInventoryTurnoverReport($period)
```

## Integraciones Requeridas

### Integración con Módulos Existentes
```php
// Integración con Gestión de Flota
VehicleMaintenanceService:
- updateVehicleStatus($vehicleId, $status) // disponible, en_mantenimiento, fuera_servicio
- getVehicleMaintenanceHistory($vehicleId)
- schedulePreventiveMaintenance($vehicleId)

// Integración con Sistema Financiero
MaintenanceCostService:
- recordMaintenanceCost($workOrderId, $amount, $categoryId)
- generateMaintenanceExpense($workOrderId)
- calculateMaintenanceBudgetUsage($period)

// Integración con Samsara API
SamsaraMaintenanceService:
- getVehicleOdometerReading($vehicleId)
- getEngineHours($vehicleId)
- getFaultCodes($vehicleId)
- updateMaintenanceScheduleFromOdometer($vehicleId)
```

### APIs Externas Adicionales
```php
// Integración con Proveedores de Refacciones (Futuro)
PartsSupplierAPI:
- checkPartAvailability($partNumber)
- getPartPricing($partNumber, $quantity)
- createPurchaseOrder($supplierOrder)
- trackOrderStatus($orderNumber)

// Integración con Sistemas de Diagnóstico (Futuro)
DiagnosticSystemAPI:
- getFaultCodes($vehicleId)
- getRecommendedMaintenance($faultCodes)
- generateDiagnosticReport($vehicleId)
```

## Base de Datos - Esquema Completo

### Índices Requeridos
```sql
-- Índices para rendimiento en consultas frecuentes
CREATE INDEX idx_work_orders_status ON work_orders(status);
CREATE INDEX idx_work_orders_vehicle ON work_orders(vehicle_id, vehicle_type);
CREATE INDEX idx_work_orders_mechanic ON work_orders(assigned_mechanic_id);
CREATE INDEX idx_work_orders_dates ON work_orders(scheduled_date, created_at);

CREATE INDEX idx_inventory_movements_part ON inventory_movements(spare_part_id);
CREATE INDEX idx_inventory_movements_type ON inventory_movements(movement_type);
CREATE INDEX idx_inventory_movements_date ON inventory_movements(created_at);

CREATE INDEX idx_maintenance_schedule_vehicle ON maintenance_schedules(vehicle_id, vehicle_type);
CREATE INDEX idx_maintenance_schedule_due ON maintenance_schedules(next_due_at, is_active);

CREATE INDEX idx_time_tracking_mechanic ON time_tracking(mechanic_id);
CREATE INDEX idx_time_tracking_work_order ON time_tracking(work_order_id);
```

### Triggers y Procedimientos Almacenados
```sql
-- Trigger para actualizar stock automáticamente
CREATE OR REPLACE FUNCTION update_spare_part_stock()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.movement_type = 'usage' THEN
        UPDATE spare_parts 
        SET stock_quantity = stock_quantity - NEW.quantity
        WHERE id = NEW.spare_part_id;
    ELSIF NEW.movement_type = 'purchase' THEN
        UPDATE spare_parts 
        SET stock_quantity = stock_quantity + NEW.quantity
        WHERE id = NEW.spare_part_id;
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger para generar alertas de stock bajo
CREATE OR REPLACE FUNCTION check_low_stock()
RETURNS TRIGGER AS $$
BEGIN
    IF NEW.stock_quantity <= (SELECT min_stock_level FROM spare_parts WHERE id = NEW.id) THEN
        INSERT INTO inventory_alerts (spare_part_id, alert_type, threshold_quantity, current_quantity, status)
        VALUES (NEW.id, 'low_stock', (SELECT min_stock_level FROM spare_parts WHERE id = NEW.id), NEW.stock_quantity, 'active');
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;
```

## Configuración de Colas y Jobs

### Jobs Asíncronos Requeridos
```php
// Jobs para procesamiento en background
ProcessMaintenanceScheduleJob: Verificar mantenimientos vencidos diariamente
GenerateInventoryAlertsJob: Verificar stock bajo cada hora
SendMaintenanceRemindersJob: Enviar recordatorios de mantenimiento
UpdateVehicleStatusJob: Actualizar estados de vehículos según órdenes de trabajo
CalculateMaintenanceCostsJob: Calcular costos de mantenimiento completados
GenerateMaintenanceReportsJob: Generar reportes programados
SyncSamsaraMaintenanceDataJob: Sincronizar datos de mantenimiento con Samsara

// Configuración en Console Kernel
Schedule::job(new ProcessMaintenanceScheduleJob)->daily();
Schedule::job(new GenerateInventoryAlertsJob)->hourly();
Schedule::job(new SendMaintenanceRemindersJob)->dailyAt('08:00');
Schedule::job(new UpdateVehicleStatusJob)->everyFiveMinutes();
```

## Métricas y KPIs del Taller

### Dashboard Metrics
```php
WorkshopMetrics:
- Órdenes de trabajo pendientes
- Órdenes en proceso
- Tiempo promedio de reparación por tipo
- Costo promedio de mantenimiento por vehículo
- Eficiencia de mecánicos (horas trabajadas vs. estimadas)
- Rotación de inventario
- Alertas de stock bajo activas
- Vehículos en mantenimiento vs. disponibles
- Cumplimiento de mantenimientos preventivos
- Costo total de mantenimiento por período

InventoryMetrics:
- Valor total del inventario
- Refacciones con stock bajo
- Refacciones sin movimiento (más de X días)
- Costo promedio por orden de compra
- Tiempo promedio de entrega de proveedores
- Rotación de inventario por categoría

MechanicMetrics:
- Horas trabajadas por mecánico
- Órdenes completadas por mecánico
- Tiempo promedio por orden por mecánico
- Eficiencia (tiempo real vs. estimado)
- Especialidades más demandadas
- Certificaciones próximas a vencer
```

## Consideraciones de Rendimiento

### Optimizaciones Específicas
```php
// Cache de consultas frecuentes
Cache::remember('workshop_pending_orders', 300, function() {
    return WorkOrder::where('status', 'pending')->count();
});

Cache::remember('low_stock_parts', 600, function() {
    return SparePart::whereRaw('stock_quantity <= min_stock_level')->get();
});

// Paginación para listados grandes
WorkOrder::with(['vehicle', 'mechanic', 'tasks'])
    ->orderBy('priority', 'desc')
    ->orderBy('created_at', 'asc')
    ->paginate(50);

// Eager loading para evitar N+1 queries
$workOrders = WorkOrder::with([
    'vehicle:id,unit_number,make,model',
    'mechanic:id,name',
    'tasks:id,work_order_id,status',
    'spareParts:id,name,part_number'
])->get();
```

### Archivado de Datos Históricos
```php
// Estrategia de archivado para datos antiguos
ArchiveMaintenanceRecordsJob: Archivar registros mayores a 2 años
ArchiveInventoryMovementsJob: Archivar movimientos mayores a 1 año
ArchiveCompletedWorkOrdersJob: Archivar órdenes completadas mayores a 6 meses

// Tablas de archivo
maintenance_records_archive
inventory_movements_archive  
work_orders_archive
```

## Seguridad y Auditoría

### Logs de Auditoría Específicos
```php
WorkshopAuditLog: id, user_id, action, model_type, model_id, 
                 old_values, new_values, ip_address, user_agent, created_at

// Eventos auditables
- Creación/modificación de órdenes de trabajo
- Cambios de estado de órdenes
- Movimientos de inventario
- Asignación/reasignación de mecánicos
- Modificaciones de precios de refacciones
- Aprobaciones de órdenes de compra
```

### Controles de Acceso Específicos
```php
// Permisos granulares para el módulo de taller
'workshop.view_orders' => 'Ver órdenes de trabajo'
'workshop.create_orders' => 'Crear órdenes de trabajo'
'workshop.assign_mechanics' => 'Asignar mecánicos'
'workshop.approve_orders' => 'Aprobar órdenes de trabajo'
'workshop.manage_inventory' => 'Gestionar inventario'
'workshop.create_purchase_orders' => 'Crear órdenes de compra'
'workshop.view_reports' => 'Ver reportes del taller'
'workshop.manage_schedules' => 'Gestionar programación'
```

## Migración y Implementación

### Fases de Implementación Sugeridas

#### Fase 1: Órdenes de Trabajo (2-3 semanas)
- Implementar modelos WorkOrder, WorkOrderTask, WorkOrderSparePart
- Crear WorkOrderResource con estados y asignaciones
- Implementar WorkOrderService básico
- Crear dashboard básico del taller

#### Fase 2: Inventario Avanzado (2-3 semanas)  
- Expandir sistema de inventario actual
- Implementar InventoryMovement y alertas
- Crear PurchaseOrder y gestión de proveedores
- Implementar InventoryService completo

#### Fase 3: Personal y Tiempo (2 semanas)
- Implementar modelos de Mechanic y certificaciones
- Crear TimeTracking y control de horas
- Implementar MechanicResource y TimeTrackingResource
- Crear reportes de rendimiento

#### Fase 4: Programación Preventiva (2-3 semanas)
- Implementar MaintenanceSchedule y calendario
- Crear PreventiveMaintenance y checklists
- Implementar MaintenanceScheduleService
- Crear WorkshopCalendarPage

#### Fase 5: Reportes y Optimización (1-2 semanas)
- Implementar reportes avanzados
- Optimizar consultas y rendimiento
- Crear métricas y KPIs
- Implementar jobs asíncronos

### Scripts de Migración de Datos
```php
// Migrar datos existentes a nueva estructura
MigrateMaintenanceRecordsCommand: Migrar registros actuales a órdenes de trabajo
MigrateSparePartsCommand: Expandir refacciones actuales con nuevos campos
CreateInitialSchedulesCommand: Crear programaciones iniciales para vehículos existentes
```

## Consideraciones Futuras

### Integraciones Avanzadas
- Integración con sistemas de diagnóstico OBD
- Conexión con proveedores para pedidos automáticos
- Integración con sistemas de facturación
- API para aplicaciones móviles de mecánicos

### Funcionalidades Avanzadas
- Reconocimiento de imágenes para diagnóstico
- Inteligencia artificial para predicción de fallas
- Realidad aumentada para guías de reparación
- IoT para monitoreo de herramientas y equipos

### Escalabilidad
- Soporte para múltiples talleres/ubicaciones
- Gestión de subcontratistas
- Integración con sistemas ERP empresariales
- Módulo de garantías y reclamaciones