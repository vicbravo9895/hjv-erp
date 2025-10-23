# Plan de Implementación - Sistema de Administración de Tractocamiones

- [x] 1. Configuración inicial del proyecto Laravel 12 con Filament v3
  - Instalar Laravel 12 con PostgreSQL como base de datos
  - Configurar Filament v3 con autenticación
  - Configurar Redis para cache y sesiones
  - Configurar variables de entorno para Samsara API
  - _Requerimientos: Todos los módulos del sistema_

- [x] 2. Crear estructura de base de datos y modelos principales
  - [x] 2.1 Crear migración y modelo Vehicle con campos de sincronización Samsara
    - Incluir campos: external_id, vin, serial_number, name, unit_number, plate, make, model, year, status
    - Incluir campos de ubicación: last_lat, last_lng, formatted_location, last_location_at
    - Incluir campos de telemetría: last_odometer_km, last_fuel_percent, last_engine_state, last_speed_mph
    - Incluir campos de conductor: current_driver_external_id, current_driver_name
    - Incluir campos de sincronización: synced_at, raw_snapshot
    - _Requerimientos: 2.1, 2.2, 7.1, 7.2, 7.3_

  - [x] 2.2 Crear migración y modelo Trailer con campos de sincronización Samsara
    - Incluir campos: external_id, name, asset_number, plate, type, status
    - Incluir campos de ubicación: last_lat, last_lng, formatted_location, last_location_at
    - Incluir campos de telemetría: last_speed_mph, last_heading_degrees
    - Incluir campos de sincronización: synced_at, raw_snapshot
    - _Requerimientos: 2.1, 2.2, 7.1_

  - [x] 2.3 Crear migración y modelo Operator
    - Incluir campos: name, license_number, phone, email, hire_date, status
    - _Requerimientos: 2.5, 3.1_

  - [x] 2.4 Crear migración y modelo Trip con relaciones
    - Incluir campos: origin, destination, start_date, end_date, truck_id, trailer_id, operator_id, status, completed_at
    - Definir relaciones belongsTo con Vehicle, Trailer, Operator
    - _Requerimientos: 3.1, 3.2, 3.3, 3.4_

- [x] 3. Implementar gestión financiera básica
  - [x] 3.1 Crear modelos para gestión de gastos
    - Crear modelo ExpenseCategory con campos: name, description
    - Crear modelo Provider con campos: name, contact_name, phone, email, address, service_type
    - Crear modelo CostCenter con campos: name, description, budget
    - Crear modelo Expense con campos: date, amount, description, category_id, provider_id, cost_center_id, receipt_url
    - _Requerimientos: 1.1, 1.2, 1.3, 6.1, 6.2_

  - [x] 3.2 Crear Filament Resources para gestión financiera
    - Implementar ExpenseResource con formularios y validaciones completas
    - Implementar ExpenseCategoryResource para categorías personalizadas
    - Implementar ProviderResource con validación de eliminación
    - Implementar CostCenterResource
    - _Requerimientos: 1.1, 1.5, 6.1, 6.4, 6.5_

- [x] 4. Implementar gestión de flota
  - [x] 4.1 Crear Filament Resources para vehículos
    - Implementar VehicleResource (TruckResource) con formularios completos
    - Implementar TrailerResource con formularios completos
    - Incluir validaciones de campos obligatorios
    - _Requerimientos: 2.1, 2.2, 2.5_

  - [x] 4.2 Crear Filament Resource para operadores
    - Implementar OperatorResource con gestión completa
    - Incluir validaciones de licencia y contacto
    - _Requerimientos: 2.5_

  - [x] 4.3 Implementar servicios de validación de disponibilidad
    - Crear VehicleStatusService para control automático de estados
    - Crear VehicleAssignmentService con validaciones de disponibilidad
    - Implementar lógica para prevenir asignaciones múltiples
    - _Requerimientos: 2.3, 2.4, 3.2, 3.3_

- [x] 5. Implementar gestión de viajes y operaciones
  - [x] 5.1 Crear modelo y Resource para costos de viaje
    - Crear modelo TripCost con campos: trip_id, cost_type, amount, description, receipt_url, location, quantity, unit_price
    - Implementar TripCostResource con tipos específicos (diésel, peajes, maniobras)
    - _Requerimientos: 4.1, 4.2, 4.3, 4.4_

  - [x] 5.2 Implementar TripResource con validaciones completas
    - Crear formularios para registro de viajes con validación de disponibilidad
    - Implementar actualización automática de estatus de vehículos
    - Incluir gestión de finalización de viajes
    - _Requerimientos: 3.1, 3.2, 3.3, 3.4_

  - [x] 5.3 Crear servicios de cálculo y reportes
    - Implementar ProfitabilityService para cálculos de rentabilidad
    - Crear TripReportService para reportes por operador, vehículo y período
    - _Requerimientos: 4.4, 3.5, 4.5_

- [x] 6. Implementar sistema de nómina
  - [x] 6.1 Crear modelos para nómina semanal
    - Crear modelo PaymentScale con campos: trips_count, payment_amount
    - Crear modelo WeeklyPayroll con campos: operator_id, week_start, week_end, trips_count, base_payment, adjustments, total_payment
    - _Requerimientos: 5.1, 5.2, 5.3_

  - [x] 6.2 Implementar servicios de cálculo de nómina
    - Crear WeeklyTripCountService para conteo automático de viajes por semana
    - Crear PaymentCalculationService para aplicar tabla de pagos
    - Implementar lógica para ajustes manuales
    - _Requerimientos: 5.1, 5.2, 5.4_

  - [x] 6.3 Crear Filament Resources para nómina
    - Implementar PayrollResource para gestión de nómina semanal
    - Implementar PaymentScaleResource para configuración de tablas de pago
    - Crear PayrollReportPage para reportes detallados
    - _Requerimientos: 5.3, 5.5_

- [x] 7. Implementar integración con Samsara API
  - [x] 7.1 Crear cliente y servicios base de Samsara
    - Implementar SamsaraClient con autenticación y manejo de errores
    - Crear SamsaraSyncLogService para logging detallado
    - Configurar retry logic con exponential backoff
    - _Requerimientos: 7.1, 7.4_

  - [x] 7.2 Implementar comandos Artisan de sincronización
    - Crear comando SyncVehicles con procesamiento de datos de vehículos
    - Crear comando SyncTrailers con procesamiento de datos de trailers
    - Implementar marcado automático de vehículos inactivos
    - _Requerimientos: 7.1, 7.2, 7.3_

  - [x] 7.3 Configurar programación automática de sincronización
    - Configurar Schedule en Console Kernel para ejecutar cada minuto
    - Implementar withoutOverlapping() y runInBackground()
    - Crear SamsaraIntegrationPage para monitoreo
    - _Requerimientos: 7.5_

- [x] 8. Crear estructura preparatoria para módulo de taller
  - [x] 8.1 Crear modelos base para mantenimiento
    - Crear modelo MaintenanceRecord con campos: vehicle_id, vehicle_type, maintenance_type, date, cost, description, mechanic_id
    - Crear modelo SparePart con campos: part_number, name, brand, stock_quantity, unit_cost, location
    - Crear modelo MaintenanceSpare para relación many-to-many
    - _Requerimientos: 8.1, 8.2, 8.3_

  - [x] 8.2 Crear interfaces básicas preparatorias
    - Implementar MaintenanceRecordResource básico
    - Crear SparePartResource básico
    - Establecer relaciones con vehículos existentes
    - _Requerimientos: 8.4_

  - [x] 8.3 Crear documentación para futura expansión
    - Documentar especificaciones técnicas para módulo completo
    - Crear diagramas de flujo para procesos de mantenimiento
    - Documentar estructura de permisos para personal de taller
    - _Requerimientos: 8.5_

- [x] 9. Configurar paneles Filament y autenticación
  - [x] 9.1 Configurar panel principal de administración
    - Configurar recursos principales (Fleet, Operators, Trips, Expenses)
    - Crear DashboardPage con métricas clave
    - Configurar navegación y menús
    - _Requerimientos: Todos los módulos_

  - [x] 9.2 Configurar panel de contabilidad
    - Configurar recursos específicos (Expenses, Providers, Payroll)
    - Crear FinancialReportsPage personalizada
    - Configurar permisos por rol
    - _Requerimientos: 1.4, 5.5, 6.4_

  - [x] 9.3 Configurar roles y permisos
    - Implementar roles: Super Admin, Administrador, Supervisor, Contador
    - Configurar acceso a paneles por rol
    - Implementar middleware de autorización
    - _Requerimientos: Seguridad del sistema_

- [x] 10. Pruebas y validación del sistema
  - [x] 10.1 Crear pruebas unitarias para servicios críticos
    - Pruebas para PaymentCalculationService
    - Pruebas para VehicleStatusService
    - Pruebas para ProfitabilityService
    - _Requerimientos: Validación de lógica de negocio_

  - [x] 10.2 Crear pruebas de integración
    - Pruebas para sincronización con Samsara API
    - Pruebas para flujos completos de viajes
    - Pruebas para cálculos de nómina end-to-end
    - _Requerimientos: Validación de integraciones_