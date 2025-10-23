# Requirements Document

## Introduction

Este documento define los requerimientos para mejorar la deuda técnica identificada en el sistema ERP de gestión de flota, enfocándose en corregir flujos de trabajo inconsistentes entre paneles, automatizar campos que deberían ser automáticos, y mejorar la organización del código mediante clusters.

## Glossary

- **Sistema_ERP**: El sistema de planificación de recursos empresariales para gestión de flota
- **Panel_Operador**: Interfaz específica para usuarios con rol de operador
- **Panel_Taller**: Interfaz específica para usuarios con rol de taller/mecánico
- **Panel_Admin**: Interfaz administrativa para supervisores y administradores
- **Panel_Contabilidad**: Interfaz específica para usuarios con rol de contador
- **Registro_Mantenimiento**: Entidad que representa un registro de mantenimiento de vehículo
- **Gasto_Viaje**: Entidad que representa gastos incurridos durante viajes
- **Usuario_Autenticado**: Usuario que ha iniciado sesión en el sistema
- **Cluster_Recursos**: Agrupación lógica de recursos relacionados en Filament

## Requirements

### Requirement 1

**User Story:** Como mecánico del taller, quiero que el sistema automáticamente asigne mi ID de usuario cuando creo un registro de mantenimiento, para que no tenga que introducir manualmente esta información.

#### Acceptance Criteria

1. WHEN un Usuario_Autenticado con rol de taller crea un Registro_Mantenimiento, THE Sistema_ERP SHALL automáticamente asignar el ID del usuario autenticado al campo mechanic_id
2. WHEN un Usuario_Autenticado con rol de taller accede al formulario de creación de Registro_Mantenimiento, THE Sistema_ERP SHALL ocultar el campo mechanic_id del formulario
3. WHEN un Usuario_Autenticado con rol de administrador crea un Registro_Mantenimiento, THE Sistema_ERP SHALL mostrar un selector de mecánicos disponibles para el campo mechanic_id
4. WHEN un Usuario_Autenticado visualiza un Registro_Mantenimiento, THE Sistema_ERP SHALL mostrar el nombre del mecánico en lugar del ID numérico

### Requirement 2

**User Story:** Como operador, quiero que solo pueda crear gastos de viaje sin poder cambiar el estado de aprobación, para que el flujo de aprobación sea controlado por personal autorizado.

#### Acceptance Criteria

1. WHEN un Usuario_Autenticado con rol de operador accede al formulario de Gasto_Viaje, THE Sistema_ERP SHALL ocultar el campo de estado
2. WHEN un Usuario_Autenticado con rol de operador crea un Gasto_Viaje, THE Sistema_ERP SHALL automáticamente asignar el estado "pending"
3. WHEN un Usuario_Autenticado con rol de contador o administrador accede al formulario de Gasto_Viaje, THE Sistema_ERP SHALL mostrar el campo de estado con opciones de modificación
4. WHEN un Usuario_Autenticado con rol de operador intenta editar un Gasto_Viaje aprobado, THE Sistema_ERP SHALL denegar la acción

### Requirement 3

**User Story:** Como administrador del sistema, quiero que los recursos estén organizados en clusters lógicos, para que la navegación sea más intuitiva y el código más mantenible.

#### Acceptance Criteria

1. WHEN el sistema se inicializa, THE Sistema_ERP SHALL agrupar recursos relacionados en Cluster_Recursos específicos
2. WHEN un Usuario_Autenticado navega por el Panel_Admin, THE Sistema_ERP SHALL mostrar recursos agrupados por funcionalidad (Flota, Operaciones, Finanzas, Mantenimiento)
3. WHEN se añaden nuevos recursos al sistema, THE Sistema_ERP SHALL permitir su asignación a clusters existentes o nuevos
4. WHEN un Usuario_Autenticado accede a diferentes paneles, THE Sistema_ERP SHALL mostrar solo los clusters relevantes para su rol

### Requirement 4

**User Story:** Como operador, quiero que el sistema automáticamente me asigne a mis propios gastos de viaje, para que no tenga que seleccionar mi usuario manualmente.

#### Acceptance Criteria

1. WHEN un Usuario_Autenticado con rol de operador crea un Gasto_Viaje, THE Sistema_ERP SHALL automáticamente asignar el ID del usuario autenticado al campo operator_id
2. WHEN un Usuario_Autenticado con rol de operador accede al formulario de Gasto_Viaje, THE Sistema_ERP SHALL ocultar el campo operator_id
3. WHEN un Usuario_Autenticado con rol de administrador crea un Gasto_Viaje, THE Sistema_ERP SHALL mostrar un selector de operadores disponibles
4. WHEN un Usuario_Autenticado con rol de operador visualiza la lista de gastos, THE Sistema_ERP SHALL mostrar solo sus propios gastos

### Requirement 5

**User Story:** Como usuario del sistema, quiero que los campos de usuario se muestren como nombres legibles en lugar de IDs numéricos, para que la información sea más comprensible.

#### Acceptance Criteria

1. WHEN el sistema muestra información de usuarios en tablas, THE Sistema_ERP SHALL mostrar nombres de usuario en lugar de IDs numéricos
2. WHEN el sistema muestra relaciones de usuario en formularios, THE Sistema_ERP SHALL usar selectores con nombres de usuario
3. WHEN el sistema registra automáticamente un usuario, THE Sistema_ERP SHALL mantener la relación correcta en la base de datos
4. WHEN se exportan reportes, THE Sistema_ERP SHALL incluir nombres de usuario legibles

### Requirement 6

**User Story:** Como mecánico del taller, quiero que el sistema automáticamente registre quién realizó el uso de productos, para que no tenga que introducir manualmente esta información.

#### Acceptance Criteria

1. WHEN un Usuario_Autenticado crea un registro de uso de producto, THE Sistema_ERP SHALL automáticamente asignar el ID del usuario autenticado al campo used_by
2. WHEN un Usuario_Autenticado accede al formulario de uso de producto, THE Sistema_ERP SHALL ocultar el campo used_by
3. WHEN se visualiza un registro de uso de producto, THE Sistema_ERP SHALL mostrar el nombre del usuario que registró el uso
4. WHEN se crean solicitudes de producto, THE Sistema_ERP SHALL automáticamente asignar el usuario autenticado como solicitante

### Requirement 7

**User Story:** Como administrador del sistema, quiero que los permisos de edición y eliminación sean consistentes entre todos los recursos, para que el control de acceso sea uniforme.

#### Acceptance Criteria

1. WHEN un Usuario_Autenticado intenta editar un recurso, THE Sistema_ERP SHALL verificar permisos basados en el rol del usuario y el estado del recurso
2. WHEN un Usuario_Autenticado intenta eliminar un recurso, THE Sistema_ERP SHALL aplicar las mismas reglas de permisos que para edición
3. WHEN un recurso está en estado final (aprobado, completado), THE Sistema_ERP SHALL restringir modificaciones a usuarios no autorizados
4. WHEN se muestran acciones en tablas, THE Sistema_ERP SHALL mostrar solo las acciones permitidas para el usuario actual

### Requirement 8

**User Story:** Como usuario del sistema, quiero que la navegación entre paneles sea consistente y lógica, para que pueda encontrar fácilmente las funciones que necesito.

#### Acceptance Criteria

1. WHEN un Usuario_Autenticado accede a cualquier panel, THE Sistema_ERP SHALL mostrar una navegación consistente con grupos lógicos
2. WHEN los recursos están relacionados funcionalmente, THE Sistema_ERP SHALL agruparlos en el mismo cluster de navegación
3. WHEN un Usuario_Autenticado cambia de panel, THE Sistema_ERP SHALL mantener la coherencia en la organización de recursos
4. WHEN se añaden nuevos recursos, THE Sistema_ERP SHALL seguir la estructura de clusters establecida