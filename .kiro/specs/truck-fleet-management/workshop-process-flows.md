# Diagramas de Flujo - Procesos de Mantenimiento del Taller

## Resumen de Procesos

Este documento define los diagramas de flujo para todos los procesos críticos del módulo de taller, desde la solicitud de mantenimiento hasta la finalización y facturación. Los procesos están diseñados para integrarse con el sistema actual y proporcionar trazabilidad completa.

## 1. Flujo Principal de Órdenes de Trabajo

### Diagrama de Flujo - Creación y Procesamiento de Órdenes

```mermaid
flowchart TD
    A[Solicitud de Mantenimiento] --> B{Tipo de Solicitud}
    B -->|Correctivo| C[Crear Orden Correctiva]
    B -->|Preventivo| D[Crear Orden Preventiva]
    B -->|Emergencia| E[Crear Orden de Emergencia]
    
    C --> F[Evaluar Prioridad]
    D --> F
    E --> G[Prioridad Alta Automática]
    
    F --> H{Prioridad Asignada}
    G --> I[Asignar Mecánico Inmediatamente]
    H -->|Alta| I
    H -->|Media| J[Programar en Cola]
    H -->|Baja| K[Programar Según Disponibilidad]
    
    I --> L[Verificar Disponibilidad de Refacciones]
    J --> L
    K --> L
    
    L --> M{Refacciones Disponibles?}
    M -->|Sí| N[Iniciar Trabajo]
    M -->|No| O[Generar Orden de Compra]
    
    O --> P[Esperar Refacciones]
    P --> Q{Refacciones Recibidas?}
    Q -->|Sí| N
    Q -->|No| R[Actualizar ETA]
    R --> P
    
    N --> S[Registrar Inicio de Trabajo]
    S --> T[Ejecutar Tareas de Mantenimiento]
    T --> U{Trabajo Completado?}
    U -->|No| V[Registrar Progreso]
    V --> T
    U -->|Sí| W[Registrar Finalización]
    
    W --> X[Actualizar Inventario]
    X --> Y[Calcular Costos Totales]
    Y --> Z[Generar Reporte de Trabajo]
    Z --> AA[Aprobar Orden]
    AA --> BB[Actualizar Estado del Vehículo]
    BB --> CC[Orden Completada]
```

### Estados de Órdenes de Trabajo

```mermaid
stateDiagram-v2
    [*] --> Pendiente
    Pendiente --> Asignada : Asignar Mecánico
    Asignada --> EnProceso : Iniciar Trabajo
    EnProceso --> EsperandoRefacciones : Falta Material
    EsperandoRefacciones --> EnProceso : Refacciones Disponibles
    EnProceso --> Completada : Finalizar Trabajo
    Completada --> Aprobada : Supervisión Aprueba
    Aprobada --> [*]
    
    Pendiente --> Cancelada : Cancelar
    Asignada --> Cancelada : Cancelar
    EnProceso --> Cancelada : Cancelar
    EsperandoRefacciones --> Cancelada : Cancelar
    Cancelada --> [*]
```

## 2. Flujo de Mantenimiento Preventivo

### Diagrama de Programación Automática

```mermaid
flowchart TD
    A[Sistema de Programación] --> B[Verificar Vehículos Activos]
    B --> C[Obtener Datos de Samsara]
    C --> D[Revisar Odómetro Actual]
    D --> E[Revisar Horas de Motor]
    E --> F[Calcular Próximo Mantenimiento]
    
    F --> G{Mantenimiento Vencido?}
    G -->|Sí| H[Crear Orden Automática]
    G -->|No| I[Programar Fecha Futura]
    
    H --> J[Asignar Prioridad Media]
    J --> K[Notificar Supervisor]
    K --> L[Agregar a Cola de Trabajo]
    
    I --> M[Actualizar Calendario]
    M --> N[Configurar Recordatorio]
    
    L --> O[Seguir Flujo Normal de Órdenes]
    N --> P[Fin del Proceso]
    O --> P
```

### Tipos de Mantenimiento Preventivo

```mermaid
graph LR
    A[Mantenimiento Preventivo] --> B[Por Tiempo]
    A --> C[Por Kilómetros]
    A --> D[Por Horas de Motor]
    A --> E[Por Eventos]
    
    B --> B1[Diario]
    B --> B2[Semanal]
    B --> B3[Mensual]
    B --> B4[Trimestral]
    B --> B5[Anual]
    
    C --> C1[Cada 5,000 km]
    C --> C2[Cada 10,000 km]
    C --> C3[Cada 20,000 km]
    C --> C4[Cada 50,000 km]
    
    D --> D1[Cada 250 horas]
    D --> D2[Cada 500 horas]
    D --> D3[Cada 1,000 horas]
    
    E --> E1[Cambio de Temporada]
    E --> E2[Inspección DOT]
    E --> E3[Renovación de Licencias]
```

## 3. Flujo de Gestión de Inventario

### Diagrama de Control de Stock

```mermaid
flowchart TD
    A[Movimiento de Inventario] --> B{Tipo de Movimiento}
    B -->|Entrada| C[Recepción de Refacciones]
    B -->|Salida| D[Uso en Mantenimiento]
    B -->|Ajuste| E[Ajuste de Inventario]
    B -->|Transferencia| F[Transferencia Entre Ubicaciones]
    
    C --> G[Verificar Orden de Compra]
    G --> H[Actualizar Stock]
    H --> I[Generar Entrada de Inventario]
    
    D --> J[Verificar Disponibilidad]
    J --> K{Stock Suficiente?}
    K -->|Sí| L[Reservar Refacciones]
    K -->|No| M[Generar Alerta de Stock]
    
    L --> N[Consumir en Orden de Trabajo]
    N --> O[Actualizar Stock]
    O --> P[Registrar Movimiento]
    
    M --> Q[Crear Solicitud de Compra]
    Q --> R[Evaluar Proveedores]
    R --> S[Generar Orden de Compra]
    
    E --> T[Contar Físico]
    T --> U[Comparar con Sistema]
    U --> V[Registrar Diferencias]
    V --> W[Actualizar Stock Real]
    
    F --> X[Verificar Ubicación Destino]
    X --> Y[Registrar Salida de Origen]
    Y --> Z[Registrar Entrada en Destino]
    
    I --> AA[Verificar Niveles Mínimos]
    P --> AA
    W --> AA
    Z --> AA
    
    AA --> BB{Stock Bajo Mínimo?}
    BB -->|Sí| CC[Generar Alerta Automática]
    BB -->|No| DD[Fin del Proceso]
    CC --> DD
```

### Flujo de Órdenes de Compra

```mermaid
flowchart TD
    A[Necesidad de Refacciones] --> B{Origen de Solicitud}
    B -->|Orden de Trabajo| C[Solicitud Directa]
    B -->|Stock Bajo| D[Alerta Automática]
    B -->|Mantenimiento Programado| E[Solicitud Preventiva]
    
    C --> F[Verificar Presupuesto]
    D --> F
    E --> F
    
    F --> G{Presupuesto Disponible?}
    G -->|Sí| H[Evaluar Proveedores]
    G -->|No| I[Solicitar Aprobación]
    
    I --> J{Aprobación Obtenida?}
    J -->|Sí| H
    J -->|No| K[Rechazar Solicitud]
    
    H --> L[Comparar Precios]
    L --> M[Seleccionar Proveedor]
    M --> N[Crear Orden de Compra]
    N --> O[Enviar a Proveedor]
    
    O --> P[Seguimiento de Entrega]
    P --> Q{Refacciones Recibidas?}
    Q -->|No| R[Contactar Proveedor]
    R --> P
    Q -->|Sí| S[Verificar Calidad]
    
    S --> T{Calidad Aceptable?}
    T -->|No| U[Generar Reclamo]
    T -->|Sí| V[Recibir en Inventario]
    
    U --> W[Proceso de Devolución]
    V --> X[Actualizar Stock]
    X --> Y[Cerrar Orden de Compra]
    
    K --> Z[Fin del Proceso]
    W --> Z
    Y --> Z
```

## 4. Flujo de Control de Tiempo y Costos

### Diagrama de Seguimiento de Tiempo

```mermaid
flowchart TD
    A[Mecánico Asignado] --> B[Iniciar Sesión de Trabajo]
    B --> C[Registrar Hora de Inicio]
    C --> D[Seleccionar Orden de Trabajo]
    D --> E[Iniciar Tarea Específica]
    
    E --> F[Trabajar en Tarea]
    F --> G{Tomar Descanso?}
    G -->|Sí| H[Pausar Tiempo]
    G -->|No| I{Tarea Completada?}
    
    H --> J[Registrar Descanso]
    J --> K[Reanudar Trabajo]
    K --> F
    
    I -->|No| L{Cambiar de Tarea?}
    I -->|Sí| M[Finalizar Tarea]
    
    L -->|Sí| N[Pausar Tarea Actual]
    L -->|No| F
    N --> O[Iniciar Nueva Tarea]
    O --> E
    
    M --> P[Registrar Tiempo de Tarea]
    P --> Q{Más Tareas Pendientes?}
    Q -->|Sí| E
    Q -->|No| R[Finalizar Sesión]
    
    R --> S[Registrar Hora de Fin]
    S --> T[Calcular Tiempo Total]
    T --> U[Calcular Costo de Mano de Obra]
    U --> V[Actualizar Orden de Trabajo]
    V --> W[Generar Reporte de Tiempo]
```

### Cálculo de Costos Totales

```mermaid
flowchart TD
    A[Orden de Trabajo Completada] --> B[Recopilar Costos]
    B --> C[Costo de Mano de Obra]
    B --> D[Costo de Refacciones]
    B --> E[Costos Indirectos]
    
    C --> F[Tiempo Total × Tarifa por Hora]
    D --> G[Suma de Refacciones Utilizadas]
    E --> H[Overhead del Taller]
    
    F --> I[Subtotal Mano de Obra]
    G --> J[Subtotal Refacciones]
    H --> K[Subtotal Overhead]
    
    I --> L[Calcular Total]
    J --> L
    K --> L
    
    L --> M[Aplicar Margen de Ganancia]
    M --> N[Calcular Impuestos]
    N --> O[Costo Total Final]
    
    O --> P[Actualizar Registro Financiero]
    P --> Q[Generar Factura]
    Q --> R[Notificar Administración]
```

## 5. Flujo de Programación y Calendario

### Diagrama de Asignación de Recursos

```mermaid
flowchart TD
    A[Nueva Orden de Trabajo] --> B[Evaluar Requerimientos]
    B --> C[Determinar Especialidad Requerida]
    C --> D[Verificar Mecánicos Disponibles]
    
    D --> E{Mecánicos Especializados Disponibles?}
    E -->|Sí| F[Verificar Carga de Trabajo]
    E -->|No| G[Buscar Mecánicos Alternativos]
    
    G --> H{Mecánicos Alternativos Disponibles?}
    H -->|Sí| I[Evaluar Capacitación Requerida]
    H -->|No| J[Programar para Fecha Futura]
    
    F --> K[Calcular Tiempo Estimado]
    I --> L[Asignar con Supervisión]
    J --> M[Agregar a Lista de Espera]
    
    K --> N[Verificar Disponibilidad de Bahía]
    L --> N
    
    N --> O{Bahía Disponible?}
    O -->|Sí| P[Asignar Recursos]
    O -->|No| Q[Programar Según Disponibilidad]
    
    P --> R[Crear Evento en Calendario]
    Q --> S[Buscar Próxima Disponibilidad]
    S --> T[Programar Fecha Alternativa]
    
    R --> U[Notificar Mecánico]
    T --> U
    M --> V[Fin del Proceso]
    U --> V
```

### Optimización de Calendario

```mermaid
flowchart TD
    A[Inicio del Día] --> B[Revisar Órdenes Programadas]
    B --> C[Verificar Disponibilidad de Mecánicos]
    C --> D[Verificar Disponibilidad de Refacciones]
    
    D --> E{Todos los Recursos Disponibles?}
    E -->|Sí| F[Confirmar Programación]
    E -->|No| G[Identificar Conflictos]
    
    G --> H{Tipo de Conflicto}
    H -->|Mecánico Ausente| I[Reasignar a Otro Mecánico]
    H -->|Refacciones Faltantes| J[Reprogramar Orden]
    H -->|Bahía Ocupada| K[Buscar Bahía Alternativa]
    
    I --> L[Verificar Especialidad]
    J --> M[Estimar Nueva Fecha]
    K --> N[Verificar Compatibilidad]
    
    L --> O{Especialidad Compatible?}
    M --> P[Notificar Cliente]
    N --> Q{Bahía Compatible?}
    
    O -->|Sí| R[Confirmar Reasignación]
    O -->|No| S[Buscar Especialista]
    Q -->|Sí| T[Confirmar Nueva Bahía]
    Q -->|No| U[Reprogramar Completamente]
    
    F --> V[Iniciar Trabajos del Día]
    R --> V
    S --> W[Programar Capacitación]
    T --> V
    P --> X[Actualizar Calendario]
    U --> X
    W --> X
    
    V --> Y[Monitorear Progreso]
    X --> Y
    Y --> Z[Fin del Proceso Diario]
```

## 6. Flujo de Reportes y Métricas

### Generación de Reportes Automáticos

```mermaid
flowchart TD
    A[Trigger de Reporte] --> B{Tipo de Trigger}
    B -->|Programado| C[Reporte Automático]
    B -->|Solicitud Manual| D[Reporte a Demanda]
    B -->|Evento del Sistema| E[Reporte de Evento]
    
    C --> F[Verificar Parámetros Programados]
    D --> G[Recopilar Parámetros del Usuario]
    E --> H[Identificar Datos del Evento]
    
    F --> I[Extraer Datos del Período]
    G --> J[Validar Parámetros]
    H --> K[Recopilar Datos Relevantes]
    
    J --> L{Parámetros Válidos?}
    L -->|No| M[Solicitar Corrección]
    L -->|Sí| N[Extraer Datos Solicitados]
    
    I --> O[Procesar Datos]
    N --> O
    K --> O
    
    O --> P[Calcular Métricas]
    P --> Q[Generar Gráficos]
    Q --> R[Aplicar Formato]
    R --> S[Generar Documento]
    
    S --> T{Tipo de Entrega}
    T -->|Email| U[Enviar por Correo]
    T -->|Dashboard| V[Mostrar en Pantalla]
    T -->|Archivo| W[Guardar en Sistema]
    
    M --> X[Fin del Proceso]
    U --> X
    V --> X
    W --> X
```

### Métricas de Rendimiento del Taller

```mermaid
graph TD
    A[Métricas del Taller] --> B[Eficiencia Operativa]
    A --> C[Calidad del Servicio]
    A --> D[Costos y Rentabilidad]
    A --> E[Gestión de Recursos]
    
    B --> B1[Tiempo Promedio de Reparación]
    B --> B2[Órdenes Completadas por Día]
    B --> B3[Utilización de Bahías]
    B --> B4[Tiempo de Inactividad]
    
    C --> C1[Retrabajos]
    C --> C2[Satisfacción del Cliente]
    C --> C3[Cumplimiento de Programación]
    C --> C4[Calidad de Reparaciones]
    
    D --> D1[Costo por Hora de Mano de Obra]
    D --> D2[Margen de Ganancia por Orden]
    D --> D3[Rotación de Inventario]
    D --> D4[Costo Total de Mantenimiento]
    
    E --> E1[Productividad por Mecánico]
    E --> E2[Utilización de Herramientas]
    E --> E3[Disponibilidad de Refacciones]
    E --> E4[Capacitación del Personal]
```

## 7. Flujo de Integración con Sistemas Existentes

### Integración con Gestión de Flota

```mermaid
flowchart TD
    A[Evento en Taller] --> B{Tipo de Evento}
    B -->|Vehículo Ingresa| C[Actualizar Estado: En Mantenimiento]
    B -->|Vehículo Sale| D[Actualizar Estado: Disponible]
    B -->|Mantenimiento Programado| E[Reservar Vehículo]
    
    C --> F[Notificar Sistema de Flota]
    D --> G[Verificar Condición del Vehículo]
    E --> H[Actualizar Calendario de Flota]
    
    F --> I[Actualizar Disponibilidad]
    G --> J{Vehículo Apto?}
    H --> K[Bloquear Asignaciones]
    
    J -->|Sí| L[Marcar como Disponible]
    J -->|No| M[Marcar como Fuera de Servicio]
    
    I --> N[Sincronizar con Samsara]
    L --> N
    M --> N
    K --> N
    
    N --> O[Actualizar Dashboard]
    O --> P[Notificar Operaciones]
    P --> Q[Fin del Proceso]
```

### Integración con Sistema Financiero

```mermaid
flowchart TD
    A[Orden de Trabajo Completada] --> B[Calcular Costos Totales]
    B --> C[Generar Asiento Contable]
    C --> D[Clasificar por Centro de Costo]
    
    D --> E{Tipo de Mantenimiento}
    E -->|Preventivo| F[Centro de Costo: Mantenimiento Preventivo]
    E -->|Correctivo| G[Centro de Costo: Reparaciones]
    E -->|Emergencia| H[Centro de Costo: Mantenimiento de Emergencia]
    
    F --> I[Registrar Gasto]
    G --> I
    H --> I
    
    I --> J[Actualizar Presupuesto]
    J --> K[Generar Reporte Financiero]
    K --> L[Notificar Contabilidad]
    
    L --> M{Requiere Aprobación?}
    M -->|Sí| N[Enviar para Aprobación]
    M -->|No| O[Registrar Automáticamente]
    
    N --> P[Proceso de Aprobación]
    P --> Q{Aprobado?}
    Q -->|Sí| O
    Q -->|No| R[Rechazar y Revisar]
    
    O --> S[Actualizar Estados Financieros]
    R --> T[Notificar Supervisor]
    S --> U[Fin del Proceso]
    T --> U
```

## 8. Flujos de Excepción y Manejo de Errores

### Manejo de Emergencias

```mermaid
flowchart TD
    A[Emergencia Detectada] --> B{Tipo de Emergencia}
    B -->|Vehículo Averiado| C[Crear Orden de Emergencia]
    B -->|Accidente| D[Protocolo de Accidente]
    B -->|Falla Crítica| E[Protocolo de Falla Crítica]
    
    C --> F[Asignar Prioridad Máxima]
    D --> G[Notificar Seguros]
    E --> H[Evaluar Seguridad]
    
    F --> I[Interrumpir Trabajos No Críticos]
    G --> J[Documentar Incidente]
    H --> K{Seguro Continuar?}
    
    I --> L[Asignar Mejor Mecánico Disponible]
    J --> M[Crear Orden Especial]
    K -->|Sí| N[Proceder con Precaución]
    K -->|No| O[Detener Operaciones]
    
    L --> P[Iniciar Reparación Inmediata]
    M --> P
    N --> P
    O --> Q[Evacuar Área]
    
    P --> R[Monitoreo Continuo]
    Q --> S[Llamar Especialistas]
    R --> T[Reportar Progreso]
    S --> T
    T --> U[Resolución de Emergencia]
```

### Recuperación de Fallos del Sistema

```mermaid
flowchart TD
    A[Fallo del Sistema Detectado] --> B{Tipo de Fallo}
    B -->|Base de Datos| C[Activar Backup de BD]
    B -->|Aplicación| D[Reiniciar Servicios]
    B -->|Red/Conectividad| E[Modo Offline]
    
    C --> F[Verificar Integridad de Datos]
    D --> G[Verificar Estado de Servicios]
    E --> H[Activar Cache Local]
    
    F --> I{Datos Íntegros?}
    G --> J{Servicios Funcionando?}
    H --> K[Continuar Operaciones Básicas]
    
    I -->|Sí| L[Restaurar Operaciones]
    I -->|No| M[Restaurar desde Backup]
    J -->|Sí| L
    J -->|No| N[Diagnóstico Avanzado]
    
    K --> O[Sincronizar al Restaurar Conexión]
    M --> P[Verificar Pérdida de Datos]
    N --> Q[Contactar Soporte Técnico]
    
    L --> R[Notificar Usuarios]
    P --> S{Datos Perdidos?}
    Q --> T[Implementar Solución]
    
    S -->|Sí| U[Recuperar desde Logs]
    S -->|No| R
    T --> R
    U --> R
    O --> R
    
    R --> V[Generar Reporte de Incidente]
    V --> W[Fin del Proceso]
```

## Consideraciones de Implementación

### Puntos Críticos de Control
1. **Validación de Estados**: Verificar que los cambios de estado sean válidos
2. **Integridad de Datos**: Asegurar consistencia entre módulos
3. **Notificaciones**: Implementar sistema robusto de alertas
4. **Auditoría**: Registrar todos los cambios críticos
5. **Recuperación**: Planes de contingencia para fallos

### Métricas de Rendimiento de Procesos
- Tiempo promedio por tipo de proceso
- Tasa de éxito de cada flujo
- Puntos de embotellamiento identificados
- Eficiencia de recursos por proceso
- Satisfacción del usuario por flujo

### Optimizaciones Futuras
- Automatización de decisiones rutinarias
- Inteligencia artificial para programación óptima
- Integración con IoT para monitoreo en tiempo real
- Análisis predictivo para mantenimiento preventivo