# Enriquecimiento de Vehículos desde Samsara

## Configuración

1. Ejecuta la migración para agregar los nuevos campos:
```bash
php artisan migrate
```

2. Asegúrate de tener el token de API de Samsara en tu `.env`:
```env
SAMSARA_API_TOKEN=tu_token_aqui
```

## Uso del Comando

Para enriquecer un vehículo con datos completos de Samsara:

```bash
php artisan vehicle:enrich {vehicle_id} --samsara-id={samsara_vehicle_id}
```

### Ejemplos:

```bash
# Si el vehículo ya tiene external_id configurado
php artisan vehicle:enrich 1

# Si necesitas especificar el ID de Samsara manualmente
php artisan vehicle:enrich 1 --samsara-id=112
```

## Campos Enriquecidos

El comando obtiene y actualiza los siguientes campos desde la API de Samsara:

### Información Básica
- `name` - Nombre del vehículo
- `vin` - VIN
- `make` - Marca
- `model` - Modelo
- `year` - Año
- `license_plate` - Placa
- `vehicle_type` - Tipo (truck, trailer, etc)

### Información Técnica
- `serial_number` - Número de serie
- `esn` - Electronic Serial Number
- `camera_serial` - Serial de cámara
- `gateway_model` - Modelo del gateway
- `gateway_serial` - Serial del gateway

### Configuración
- `regulation_mode` - Modo de regulación
- `gross_vehicle_weight` - Peso bruto vehicular (lbs)
- `sensor_configuration` - Configuración de sensores (JSON)

### Datos Adicionales
- `notes` - Notas sobre el vehículo
- `external_ids` - IDs externos (maintenance, payroll, etc) (JSON)
- `tags` - Tags de Samsara (JSON)
- `attributes` - Atributos personalizados (JSON)
- `static_assigned_driver_id` - ID del conductor asignado
- `static_assigned_driver_name` - Nombre del conductor asignado

### Metadata
- `raw_snapshot` - Snapshot completo de la respuesta de Samsara (JSON)
- `synced_at` - Timestamp de última sincronización

## Endpoint de Samsara

El comando utiliza el endpoint:
```
GET https://api.samsara.com/fleet/vehicles/{id}
```

Documentación: https://developers.samsara.com/reference/getvehicle
