# Documento de Diseño - Sistema de Administración de Tractocamiones

## Resumen General

El Sistema de Administración de Tractocamiones será una aplicación web desarrollada en Laravel que proporcionará una interfaz completa para gestionar flotas de transporte. El sistema seguirá una arquitectura MVC con patrones de repositorio para el acceso a datos y servicios para la lógica de negocio.

## Arquitectura

### Arquitectura General
- **Frontend**: Laravel Filament v3 (admin panel framework)
- **Backend**: Laravel 12 con PHP 8.3+
- **Base de Datos**: PostgreSQL para datos principales
- **Cache**: Redis para sesiones y cache de datos frecuentes
- **API Externa**: Integración con Samsara API
- **Autenticación**: Filament Authentication (built-in)
- **UI Framework**: Filament v3 con Tailwind CSS y Alpine.js
- **Sail**: Entorno de ejecución de docker

### Patrones de Diseño
- **Repository Pattern**: Para abstracción de acceso a datos
- **Service Pattern**: Para lógica de negocio compleja
- **Observer Pattern**: Para eventos del sistema (sincronización, notificaciones)
- **Strategy Pattern**: Para diferentes métodos de cálculo de pagos
- **Filament Resources**: Para gestión CRUD automatizada
- **Filament Pages**: Para dashboards y reportes personalizados

## Estructura de Paneles Filament

### Panel Principal de Administración
**Propósito**: Panel central para administradores y supervisores con acceso completo al sistema

**Recursos Filament:**
- **FleetResource**: Gestión de tractocamiones y trailers
- **OperatorResource**: Administración de operadores
- **TripResource**: Gestión completa de viajes
- **ExpenseResource**: Registro y categorización de gastos
- **ProviderResource**: Gestión de proveedores
- **PayrollResource**: Cálculos y reportes de nómina

**Páginas Personalizadas:**
- **DashboardPage**: Vista general con métricas clave
- **FinancialReportsPage**: Reportes financieros avanzados
- **FleetStatusPage**: Estado en tiempo real de la flota
- **SamsaraIntegrationPage**: Monitoreo de sincronización

### Panel de Operadores (Futuro)
**Propósito**: Panel limitado para operadores con acceso a sus propios datos

**Recursos Limitados:**
- Vista de sus viajes asignados
- Registro de costos de viaje
- Consulta de pagos semanales

### Panel de Contabilidad
**Propósito**: Panel especializado para personal contable

**Recursos Específicos:**
- **ExpenseResource**: Gestión completa de gastos
- **ProviderResource**: Administración de proveedores
- **CostCenterResource**: Control de centros de costo
- **PayrollResource**: Revisión y aprobación de nóminas
- **FinancialReportsPage**: Reportes detallados

## Componentes e Interfaces

### Módulos Principales

#### 1. Módulo de Gestión Financiera
**Responsabilidades:**
- Registro y categorización de gastos por tipo (renta de patio, combustible, seguros, mantenimiento, otros)
- Gestión completa de proveedores con información de contacto
- Reportes financieros por período, categoría y centro de costo
- Control de centros de costo con presupuestos
- Validación de campos obligatorios en gastos
- Prevención de eliminación de proveedores con gastos asociados

**Componentes Filament:**
- `ExpenseResource`: Resource completo con forms, tables y validaciones
- `ExpenseCategoryResource`: Gestión de categorías personalizadas
- `ProviderResource`: Resource con validaciones de eliminación
- `CostCenterResource`: Administración de centros de costo
- `FinancialReportPage`: Página personalizada para reportes

**Servicios de Soporte:**
- `FinancialReportService`: Generación de reportes por múltiples criterios
- `ExpenseValidationService`: Validaciones de negocio complejas

#### 2. Módulo de Gestión de Flota
**Responsabilidades:**
- Inventario completo de tractocamiones (número económico, placas, modelo, año, estatus)
- Inventario completo de trailers (número económico, placas, tipo, capacidad, estatus)
- Asignación dinámica de operadores a tractocamiones
- Control de estatus de vehículos (disponible, en viaje, en mantenimiento, fuera de servicio)
- Validación de disponibilidad antes de asignaciones
- Actualización automática de estatus según operaciones

**Componentes Filament:**
- `TruckResource`: Resource completo para tractocamiones
- `TrailerResource`: Resource completo para trailers  
- `OperatorResource`: Resource para operadores con validaciones
- `FleetStatusPage`: Página personalizada para estado de flota

**Servicios de Soporte:**
- `VehicleStatusService`: Control automático de estados
- `VehicleAssignmentService`: Lógica de asignaciones con validaciones

#### 3. Módulo de Operaciones
**Responsabilidades:**
- Registro completo de viajes (fecha, origen, destino, vehículos, operador)
- Validación de disponibilidad de vehículos antes de crear viajes
- Actualización automática de estatus de vehículos durante viajes
- Registro detallado de costos por viaje (diésel, peajes, maniobras)
- Cálculo automático de costo total por viaje
- Generación de reportes de rentabilidad y trazabilidad
- Control de finalización de viajes

**Componentes Filament:**
- `TripResource`: Resource completo para gestión de viajes
- `TripCostResource`: Resource para costos detallados de viajes
- `TripReportPage`: Página personalizada para reportes de operaciones

**Servicios de Soporte:**
- `TripValidationService`: Validaciones de disponibilidad y reglas de negocio
- `ProfitabilityService`: Cálculos de rentabilidad por viaje y operador
- `TripReportService`: Reportes por operador, vehículo y período

#### 4. Módulo de Nómina
**Responsabilidades:**
- Conteo automático de viajes completados por operador por semana
- Aplicación de tabla de pagos configurable (6 viajes = $1200, 7 viajes = $1400, etc.)
- Cálculo automático de pagos semanales basado en viajes
- Permitir ajustes manuales cuando sea necesario
- Generación de reportes de pagos por operador y período
- Validación de períodos semanales

**Componentes Filament:**
- `PayrollResource`: Resource para gestión de nómina semanal
- `PaymentScaleResource`: Resource para tablas de pago configurables
- `PayrollReportPage`: Página personalizada para reportes de nómina

**Servicios de Soporte:**
- `PaymentCalculationService`: Cálculos automáticos basados en viajes completados
- `PayrollReportService`: Reportes detallados por operador y período
- `WeeklyTripCountService`: Conteo automático de viajes por semana

#### 5. Módulo de Integración Samsara
**Responsabilidades:**
- Conexión segura con Samsara API
- Sincronización de ubicación de vehículos en tiempo real
- Sincronización de datos de odómetro
- Actualización de información de conductor asignado
- Manejo robusto de errores sin afectar operaciones locales
- Programación automática de sincronizaciones cada hora durante horario operativo
- Logging detallado de sincronizaciones

**Componentes Filament:**
- `SamsaraIntegrationPage`: Página personalizada para monitoreo de sincronización
- `SamsaraSyncLogResource`: Resource para logs de sincronización

**Servicios y Comandos:**
- `SamsaraClient`: Cliente principal para comunicación con API
- `SyncVehicles`: Comando Artisan para sincronización de vehículos
- `SyncTrailers`: Comando Artisan para sincronización de trailers
- `SamsaraSyncLogService`: Logging y monitoreo de sincronizaciones

**Programación en Console Kernel:**
```php
Schedule::command('samsara:sync-vehicles')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('samsara:sync-trailers')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
```

## Modelos de Datos

### Entidades Principales

```php
// Vehículos (Ajustado para sincronización Samsara)
Vehicle: id, external_id, vin, serial_number, name, unit_number, plate, make, model, year, status, 
         last_odometer_km, last_fuel_percent, last_engine_state, last_speed_mph, last_heading_degrees,
         last_lat, last_lng, formatted_location, last_address_name, last_location_at,
         current_driver_external_id, current_driver_name, synced_at, raw_snapshot, created_at, updated_at

// Trailers/Plataformas (Ajustado para sincronización Samsara)  
Trailer: id, external_id, name, asset_number, plate, type, status, last_lat, last_lng, 
         last_speed_mph, last_heading_degrees, formatted_location, last_location_at, 
         synced_at, raw_snapshot, created_at, updated_at

// Operadores
Operator: id, name, license_number, phone, email, hire_date, status, created_at, updated_at

// Viajes y Operaciones
Trip: id, origin, destination, start_date, end_date, truck_id, trailer_id, operator_id, status, completed_at, created_at, updated_at
TripCost: id, trip_id, cost_type, amount, description, receipt_url, location, quantity, unit_price, created_at, updated_at

// Gestión Financiera
Expense: id, date, amount, description, category_id, provider_id, cost_center_id, receipt_url, created_at, updated_at
ExpenseCategory: id, name, description, created_at, updated_at
Provider: id, name, contact_name, phone, email, address, service_type, created_at, updated_at
CostCenter: id, name, description, budget, created_at, updated_at

// Nómina
WeeklyPayroll: id, operator_id, week_start, week_end, trips_count, base_payment, adjustments, total_payment, created_at, updated_at
PaymentScale: id, trips_count, payment_amount, created_at, updated_at

// Integración Samsara (Simplificada - datos integrados en Vehicle/Trailer)
SamsaraSyncLog: id, sync_type, status, error_message, synced_records, duration_seconds, created_at

// Preparación para Taller (Estructura Base)
MaintenanceRecord: id, vehicle_id, vehicle_type, maintenance_type, date, cost, description, mechanic_id, created_at, updated_at
SparePart: id, part_number, name, brand, stock_quantity, unit_cost, location, created_at, updated_at
MaintenanceSpare: id, maintenance_id, spare_part_id, quantity_used, cost, created_at, updated_at
```

### Relaciones Clave
- Un Operador puede tener múltiples Viajes (1:N)
- Un Viaje pertenece a un Tractocamión, un Trailer y un Operador (N:1 cada uno)
- Un Viaje puede tener múltiples Costos de Viaje (1:N)
- Un Proveedor puede tener múltiples Gastos (1:N)
- Un Centro de Costo puede tener múltiples Gastos (1:N)
- Una Categoría de Gasto puede tener múltiples Gastos (1:N)
- Un Operador puede tener múltiples Registros de Nómina Semanal (1:N)
- Un Vehículo puede tener múltiples Registros de Mantenimiento (1:N)
- Un Registro de Mantenimiento puede usar múltiples Refacciones (N:N a través de MaintenanceSpare)

### Validaciones de Negocio
- Un Tractocamión no puede estar asignado a múltiples viajes activos simultáneamente
- Un Trailer no puede estar asignado a múltiples viajes activos simultáneamente
- Un Operador no puede tener múltiples viajes activos simultáneamente
- Los costos de viaje solo pueden asociarse a viajes existentes
- Los proveedores no pueden eliminarse si tienen gastos asociados

## Manejo de Errores

### Estrategias de Error
1. **Validación de Datos**: Usar Form Requests de Laravel para validación
2. **Errores de API**: Implementar retry logic con exponential backoff
3. **Errores de Base de Datos**: Transacciones y rollback automático
4. **Logging**: Usar canales específicos para diferentes tipos de errores

### Códigos de Error Personalizados
- `FLEET_001`: Error de validación de vehículo
- `TRIP_001`: Error en creación de viaje
- `TRIP_002`: Vehículo no disponible para asignación
- `SAMSARA_001`: Error de conexión con API
- `SAMSARA_002`: Error de autenticación con Samsara
- `PAYROLL_001`: Error en cálculo de nómina
- `EXPENSE_001`: Error de validación en registro de gastos
- `PROVIDER_001`: Error al intentar eliminar proveedor con gastos asociados

## Estrategia de Pruebas

### Tipos de Pruebas

#### Pruebas Unitarias
- Servicios de cálculo de pagos
- Validaciones de modelos
- Lógica de negocio en servicios
- Transformadores de datos

#### Pruebas de Integración
- Conexión con Samsara API
- Flujos completos de viajes
- Cálculos de nómina end-to-end
- Reportes financieros

#### Pruebas de Funcionalidad
- Registro de gastos completo
- Creación y seguimiento de viajes
- Sincronización con Samsara
- Generación de reportes

### Herramientas de Prueba
- **PHPUnit**: Framework principal de pruebas
- **Laravel Dusk**: Pruebas de navegador
- **Mockery**: Mocking para pruebas unitarias
- **Faker**: Generación de datos de prueba

## Consideraciones de Rendimiento

## Tipos de Costos de Viaje

### Categorías Específicas de Costos
Según los requerimientos, el sistema debe manejar los siguientes tipos de costos por viaje:

1. **Costos de Diésel**
   - Campos: cantidad (litros), precio por litro, total
   - Validación: cantidad > 0, precio > 0

2. **Costos de Peajes**
   - Campos: monto, ubicación/caseta
   - Validación: monto > 0, ubicación requerida

3. **Costos de Maniobras**
   - Campos: tipo de maniobra, monto
   - Validación: tipo requerido, monto > 0

### Cálculo de Rentabilidad
- Suma automática de todos los costos asociados al viaje
- Comparación con ingresos del viaje (cuando esté disponible)
- Reportes de rentabilidad por viaje, operador y período

## Consideraciones de Rendimiento

### Optimizaciones PostgreSQL
1. **Índices de Base de Datos**: En campos de búsqueda frecuente (economic_number, dates, status)
2. **Índices Compuestos**: Para consultas complejas de reportes
3. **Cache de Consultas**: Para reportes y datos estáticos con Redis
4. **Lazy Loading**: Para relaciones de modelos Eloquent
5. **Queue Jobs**: Para tareas pesadas como sincronización con Samsara
6. **Particionado de Tablas**: Para tablas de logs y datos históricos (futuro)

### Monitoreo
- Logs de rendimiento para consultas lentas
- Métricas de uso de API de Samsara
- Monitoreo de jobs en cola
- Alertas por errores críticos

## Seguridad

### Medidas de Seguridad
1. **Autenticación**: Sistema de roles y permisos
2. **Autorización**: Middleware para control de acceso
3. **Validación**: Sanitización de inputs
4. **Encriptación**: Datos sensibles en base de datos
5. **API Security**: Rate limiting para Samsara integration

### Roles del Sistema (Filament Authentication)
- **Super Admin**: Acceso completo al sistema y configuración
- **Administrador**: Gestión completa de flota y operaciones
- **Supervisor**: Gestión de operaciones y reportes
- **Contador**: Acceso completo a módulos financieros y nómina
- **Operador**: Acceso limitado a panel específico (futuro)

### Configuración de Paneles por Rol
- **Panel Principal**: Super Admin, Administrador, Supervisor
- **Panel Contabilidad**: Super Admin, Administrador, Contador
- **Panel Operadores**: Operadores (implementación futura)

## Integración con Samsara

### Flujo de Sincronización
1. **Programación**: Comandos automáticos cada minuto con `withoutOverlapping()` y `runInBackground()`
2. **Comandos Artisan**:
   - `samsara:sync-vehicles`: Sincronización de tractocamiones
   - `samsara:sync-trailers`: Sincronización de plataformas/trailers
3. **Datos Sincronizados**:
   - **Vehículos**: Ubicación GPS, odómetro, combustible, estado del motor, conductor asignado
   - **Trailers**: Ubicación GPS, velocidad, dirección, ubicación formateada
4. **Estrategia de Datos**: 
   - Upsert por `external_id` (ID de Samsara)
   - Marcado automático como inactivos si no aparecen en el feed
   - Almacenamiento de snapshot completo en `raw_snapshot` para auditoría

### Estructura de API
```php
SamsaraClient:
- iterateVehicles($callback, $limit): Iterar sobre feed de vehículos con callback
- iterateTrailers($callback, $limit): Iterar sobre feed de trailers con callback
- authenticate(): Manejo de autenticación con API key
- handleApiErrors($response): Manejo centralizado de errores
- retryWithBackoff($operation): Retry logic con exponential backoff

SyncVehicles Command:
- processVehicle($vehicleData): Procesar datos individuales de vehículo
- mapEngineState($state): Mapear estados del motor de Samsara
- extractUnitNumber($name): Extraer número de unidad del nombre
- markInactiveVehicles($startTime): Marcar vehículos no sincronizados como inactivos

SyncTrailers Command:
- processTrailer($trailerData): Procesar datos individuales de trailer
- extractAssetNumber($name): Extraer número de plataforma del nombre
- markInactiveTrailers($startTime): Marcar trailers no sincronizados como inactivos
```

## Preparación para Módulo de Taller

### Estructura Base de Datos
```php
// Modelos preparatorios según requerimientos
MaintenanceRecord: id, vehicle_id, vehicle_type, maintenance_type, date, cost, description, mechanic_id, created_at, updated_at
SparePart: id, part_number, name, brand, stock_quantity, unit_cost, location, created_at, updated_at
MaintenanceSpare: id, maintenance_id, spare_part_id, quantity_used, cost, created_at, updated_at

// Servicios base preparatorios
MaintenanceService: Lógica básica de registros de mantenimiento
InventoryService: Gestión básica de inventario de refacciones
WorkOrderService: Estructura para futuras órdenes de trabajo
```

### Interfaces Preparatorias Básicas
- Estructura de vistas para registros de reparaciones
- Framework para inventario de refacciones
- Relaciones establecidas entre vehículos y mantenimiento
- Documentación completa para integración futura

### Documentación para Futura Expansión
- Especificaciones técnicas para módulo completo de taller
- Diagramas de flujo para procesos de mantenimiento
- Estructura de permisos y roles para personal de taller
- Integración planificada con sistema principal

## Fases de Implementación

### Fase 1: Core del Sistema (4-6 semanas)
- Autenticación y autorización
- Gestión básica de vehículos y operadores
- Registro de gastos básico

### Fase 2: Operaciones (3-4 semanas)
- Gestión completa de viajes
- Costos por viaje
- Reportes básicos

### Fase 3: Nómina y Finanzas (2-3 semanas)
- Cálculo automático de pagos
- Reportes financieros avanzados
- Gestión de proveedores

### Fase 4: Integración Samsara (2-3 semanas)
- Conexión con API
- Sincronización automática
- Dashboard en tiempo real

### Fase 5: Preparación Taller (1-2 semanas)
- Estructura base de datos
- Interfaces preparatorias
- Documentación para futura expansión