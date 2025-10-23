# Seeders del Sistema de Gestión de Transporte

Este directorio contiene los seeders para poblar la base de datos con datos de demostración realistas para el sistema de gestión de transporte.

## 📋 Seeders Disponibles

### Seeders Principales
- **`DatabaseSeeder.php`** - Seeder principal que ejecuta todos los demás
- **`DemoDataSeeder.php`** - Seeder específico para datos de demostración

### Seeders por Módulo

#### 🚛 Flota y Equipos
- **`VehicleSeeder.php`** - Crea 15 vehículos con datos realistas
- **`TrailerSeeder.php`** - Crea 20 remolques de diferentes tipos
- **`OperatorSeeder.php`** - Crea 25 operadores con licencias y contactos

#### 🗺️ Operaciones
- **`TripSeeder.php`** - Genera ~150 viajes con rutas mexicanas comunes
- **`TripCostSeeder.php`** - Crea costos asociados (combustible, peajes, maniobras)

#### 💰 Finanzas
- **`ExpenseSeeder.php`** - Genera 300 gastos operativos categorizados
- **`WeeklyPayrollSeeder.php`** - Crea nóminas semanales para operadores

#### 🔧 Mantenimiento
- **`MaintenanceSeeder.php`** - Registros de mantenimiento y refacciones

#### 🔄 Integración
- **`SamsaraSyncSeeder.php`** - Logs de sincronización con sistema Samsara

## 🚀 Uso

### Ejecutar todos los seeders
```bash
php artisan db:seed
```

### Ejecutar solo datos de demostración
```bash
php artisan db:seed --class=DemoDataSeeder
```

### Ejecutar seeder específico
```bash
php artisan db:seed --class=VehicleSeeder
```

### Refrescar base de datos y poblar
```bash
php artisan migrate:fresh --seed
```

## 📊 Datos Generados

### Vehículos (15 unidades)
- Marcas: Freightliner, Peterbilt, Kenworth, Volvo, Mack, International
- Estados: disponible, en viaje, mantenimiento, fuera de servicio
- Datos de telemetría: ubicación, combustible, odómetro, estado del motor

### Remolques (20 unidades)
- Tipos: caja seca, refrigerado, plataforma, tanque
- Datos de ubicación y estado

### Operadores (25 personas)
- Nombres mexicanos realistas
- Licencias de conducir válidas
- Contactos y fechas de contratación
- 80% activos, 20% inactivos

### Viajes (~150 registros)
- Rutas comunes en México
- Estados: planeado, en progreso, completado, cancelado
- Últimos 3 meses de datos

### Costos de Viaje (~600 registros)
- Combustible con precios realistas
- Peajes de casetas mexicanas
- Maniobras en terminales y puertos
- Otros gastos operativos

### Gastos Operativos (300 registros)
- Categorías: combustible, mantenimiento, seguros, peajes, oficina, personal, tecnología, legal
- Proveedores mexicanos
- Centros de costo organizacionales

### Mantenimiento (~200 registros)
- Tipos: preventivo, correctivo, emergencia, inspección
- Refacciones comunes con inventario
- Costos realistas por tipo de servicio

### Nóminas (~300 registros)
- 12 semanas de historial
- Pagos base + bonos + deducciones
- Escalas de pago por experiencia

## 🎯 Casos de Uso

Los datos generados permiten probar:

1. **Gestión de Flota**
   - Seguimiento de vehículos y remolques
   - Estados y ubicaciones en tiempo real
   - Asignación de equipos a viajes

2. **Operaciones**
   - Planificación de rutas
   - Seguimiento de viajes
   - Gestión de operadores

3. **Finanzas**
   - Control de costos por viaje
   - Gastos operativos categorizados
   - Análisis de rentabilidad

4. **Mantenimiento**
   - Historial de servicios
   - Control de refacciones
   - Costos de mantenimiento

5. **Recursos Humanos**
   - Nóminas semanales
   - Bonos y deducciones
   - Escalas de pago

6. **Reportes y Analytics**
   - Dashboards con datos reales
   - KPIs operativos
   - Análisis de tendencias

## ⚠️ Consideraciones

- Los datos son ficticios pero realistas
- Las ubicaciones y rutas son de México
- Los precios están en pesos mexicanos
- Los datos cubren los últimos 3-12 meses según el módulo
- Se incluyen tanto registros exitosos como con errores para pruebas completas

## 🔄 Regenerar Datos

Para limpiar y regenerar todos los datos:

```bash
php artisan migrate:fresh
php artisan db:seed --class=DemoDataSeeder
```

Esto eliminará todos los datos existentes y creará un conjunto fresco de datos de demostración.