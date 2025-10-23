# Documento de Requerimientos - Sistema de Administración de Tractocamiones

## Introducción

El Sistema de Administración de Tractocamiones es una plataforma integral diseñada para gestionar todos los aspectos operativos y financieros de una flota de tractocamiones. El sistema permitirá el control completo de gastos, vehículos, operadores, viajes y pagos, con integración futura al taller y conexión con la API de Samsara para sincronización de datos de vehículos.

## Glosario

- **Sistema_Flota**: El sistema de administración de tractocamiones
- **Operador**: Conductor de tractocamión
- **Tractocamión**: Vehículo motorizado para remolcar trailers
- **Trailer**: Remolque sin motor que es jalado por el tractocamión
- **Viaje**: Recorrido completo desde origen hasta destino con carga
- **Centro_Costo**: Categoría organizacional para clasificar gastos
- **Samsara_API**: Servicio externo que proporciona datos de vehículos
- **Proveedor**: Empresa o persona que suministra servicios o productos
- **Pago_Semanal**: Compensación calculada por viajes realizados en una semana

## Requerimientos

### Requerimiento 1

**Historia de Usuario:** Como administrador de flota, quiero registrar todos los gastos operativos, para mantener un control financiero preciso de la operación.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ permitir registrar gastos con fecha, monto, descripción, proveedor y centro de costo
2. EL Sistema_Flota DEBERÁ categorizar gastos por tipo (renta de patio, combustible, seguros, mantenimiento, otros)
3. EL Sistema_Flota DEBERÁ asociar cada gasto con un centro de costo específico
4. EL Sistema_Flota DEBERÁ generar reportes de gastos por período, categoría y centro de costo
5. EL Sistema_Flota DEBERÁ validar que todos los campos obligatorios estén completos antes de guardar un gasto

### Requerimiento 2

**Historia de Usuario:** Como administrador de flota, quiero mantener un inventario actualizado de tractocamiones y trailers, para conocer el estatus y asignación de cada vehículo.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ registrar tractocamiones con número económico, placas, modelo, año y estatus
2. EL Sistema_Flota DEBERÁ registrar trailers con número económico, placas, tipo, capacidad y estatus
3. EL Sistema_Flota DEBERÁ asignar operadores a tractocamiones específicos
4. EL Sistema_Flota DEBERÁ mostrar el estatus actual de cada vehículo (disponible, en viaje, en mantenimiento, fuera de servicio)
5. EL Sistema_Flota DEBERÁ permitir cambiar la asignación de operadores y estatus de vehículos

### Requerimiento 3

**Historia de Usuario:** Como administrador de flota, quiero registrar todos los viajes realizados, para tener trazabilidad completa de las operaciones.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ registrar viajes con fecha, origen, destino, tractocamión, trailer y operador asignados
2. EL Sistema_Flota DEBERÁ validar que el tractocamión y trailer estén disponibles antes de crear un viaje
3. EL Sistema_Flota DEBERÁ actualizar automáticamente el estatus de vehículos a "en viaje" al iniciar un viaje
4. EL Sistema_Flota DEBERÁ permitir marcar viajes como completados y actualizar estatus de vehículos a "disponible"
5. EL Sistema_Flota DEBERÁ generar reportes de viajes por operador, vehículo y período

### Requerimiento 4

**Historia de Usuario:** Como administrador de flota, quiero registrar todos los costos asociados a cada viaje, para calcular la rentabilidad por operación.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ permitir registrar costos de diésel por viaje con cantidad y precio
2. EL Sistema_Flota DEBERÁ registrar costos de peajes con monto y ubicación
3. EL Sistema_Flota DEBERÁ registrar costos de maniobras con tipo y monto
4. EL Sistema_Flota DEBERÁ calcular el costo total por viaje sumando todos los gastos asociados
5. EL Sistema_Flota DEBERÁ generar reportes de rentabilidad por viaje y operador

### Requerimiento 5

**Historia de Usuario:** Como administrador de flota, quiero calcular automáticamente los pagos semanales de los operadores, para agilizar el proceso de nómina.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ contar automáticamente los viajes completados por operador en cada semana
2. EL Sistema_Flota DEBERÁ aplicar la tabla de pagos configurada (6 viajes = $1200, 7 viajes = $1400, etc.)
3. EL Sistema_Flota DEBERÁ generar el cálculo de pago semanal para cada operador
4. EL Sistema_Flota DEBERÁ permitir ajustes manuales en los pagos cuando sea necesario
5. EL Sistema_Flota DEBERÁ generar reportes de pagos por operador y período

### Requerimiento 6

**Historia de Usuario:** Como administrador de flota, quiero gestionar proveedores y categorías de gasto, para mantener organizados los registros financieros.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ permitir registrar proveedores con nombre, contacto, dirección y tipo de servicio
2. EL Sistema_Flota DEBERÁ crear y modificar categorías de gasto personalizadas
3. EL Sistema_Flota DEBERÁ asignar proveedores a categorías específicas de gasto
4. EL Sistema_Flota DEBERÁ generar reportes de gastos por proveedor
5. EL Sistema_Flota DEBERÁ validar que no se eliminen proveedores con gastos asociados

### Requerimiento 7

**Historia de Usuario:** Como administrador de flota, quiero integrar los datos de Samsara, para sincronizar automáticamente la información de vehículos y operadores.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ conectarse con Samsara_API para obtener datos de ubicación de vehículos
2. EL Sistema_Flota DEBERÁ sincronizar datos de odómetro de tractocamiones desde Samsara_API
3. EL Sistema_Flota DEBERÁ actualizar información de conductor asignado desde Samsara_API
4. EL Sistema_Flota DEBERÁ manejar errores de conexión con Samsara_API sin afectar operaciones locales
5. EL Sistema_Flota DEBERÁ programar sincronizaciones automáticas cada hora durante horario operativo

### Requerimiento 8

**Historia de Usuario:** Como administrador de flota, quiero preparar la base para el módulo de taller, para futuras funcionalidades de mantenimiento.

#### Criterios de Aceptación

1. EL Sistema_Flota DEBERÁ diseñar la estructura de datos para registros de reparaciones
2. EL Sistema_Flota DEBERÁ preparar la estructura para inventario de refacciones
3. EL Sistema_Flota DEBERÁ establecer relaciones entre vehículos y registros de mantenimiento
4. EL Sistema_Flota DEBERÁ crear interfaces básicas para el futuro módulo de taller
5. EL Sistema_Flota DEBERÁ documentar las especificaciones para la integración del taller