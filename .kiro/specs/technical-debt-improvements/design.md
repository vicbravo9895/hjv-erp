# Design Document

## Overview

Este documento describe el diseño técnico para resolver la deuda técnica identificada en el sistema ERP de gestión de flota. El enfoque principal es mejorar la experiencia del usuario mediante la automatización de campos, control de permisos consistente, y organización lógica de recursos mediante clusters.

## Architecture

### Current State Analysis

El sistema actual presenta los siguientes problemas arquitectónicos:

1. **Campos manuales innecesarios**: Los usuarios deben introducir manualmente información que el sistema puede inferir automáticamente
2. **Permisos inconsistentes**: Diferentes recursos manejan permisos de manera diferente
3. **Navegación desorganizada**: Los recursos no están agrupados lógicamente
4. **Flujos de trabajo fragmentados**: Los roles no tienen flujos coherentes entre paneles

### Target Architecture

La arquitectura mejorada implementará:

1. **Auto-assignment Pattern**: Patrón para asignación automática de usuarios basada en contexto
2. **Role-based Field Visibility**: Visibilidad de campos basada en roles de usuario
3. **Resource Clustering**: Agrupación lógica de recursos por funcionalidad
4. **Consistent Permission Layer**: Capa de permisos uniforme para todos los recursos

## Components and Interfaces

### 1. Auto-Assignment Service

```php
interface AutoAssignmentServiceInterface
{
    public function shouldAutoAssign(string $field, User $user): bool;
    public function getAutoAssignedValue(string $field, User $user): mixed;
    public function hideFieldForRole(string $field, string $role): bool;
}
```

**Responsabilidades:**
- Determinar qué campos deben ser auto-asignados por rol
- Proporcionar valores automáticos basados en el usuario autenticado
- Controlar visibilidad de campos por rol

### 2. Resource Cluster Manager

```php
interface ResourceClusterInterface
{
    public function getClusterName(): string;
    public function getClusterIcon(): string;
    public function getClusterSort(): int;
    public function getClusterResources(): array;
    public function isCollapsedByDefault(): bool;
}
```

**Clusters propuestos:**
- **Fleet Management Cluster**: Vehículos, Trailers, Operadores
- **Operations Cluster**: Viajes, Costos de Viaje, Asignaciones
- **Maintenance Cluster**: Registros de Mantenimiento, Repuestos, Uso de Productos
- **Financial Cluster**: Gastos, Proveedores, Centros de Costo
- **Payroll Cluster**: Nóminas, Escalas de Pago
- **System Cluster**: Usuarios, Logs, Configuración

### 3. Enhanced Permission System

```php
interface EnhancedPermissionInterface
{
    public function canView(User $user, Model $record): bool;
    public function canCreate(User $user): bool;
    public function canEdit(User $user, Model $record): bool;
    public function canDelete(User $user, Model $record): bool;
    public function getEditableFields(User $user, Model $record): array;
}
```

### 4. Form Field Resolver

```php
interface FormFieldResolverInterface
{
    public function resolveFieldsForUser(User $user, string $operation): array;
    public function getDefaultValues(User $user): array;
    public function getHiddenFields(User $user): array;
}
```

## Data Models

### Enhanced User Model

```php
// Métodos adicionales para el modelo User
public function getAutoAssignableFields(): array;
public function canModifyStatus(string $resourceType): bool;
public function getVisibleClusters(): array;
```

### Resource Metadata

```php
// Trait para recursos con auto-assignment
trait HasAutoAssignment
{
    public static function getAutoAssignableFields(): array;
    public static function getFieldVisibilityRules(): array;
    public static function getDefaultValuesForUser(User $user): array;
}
```

## Error Handling

### Validation Strategy

1. **Pre-validation**: Validar permisos antes de mostrar formularios
2. **Auto-assignment validation**: Verificar que los valores auto-asignados sean válidos
3. **Role-based validation**: Validaciones específicas por rol
4. **Graceful degradation**: Fallback a comportamiento manual si auto-assignment falla

### Error Recovery

- **Permission denied**: Redireccionar a panel apropiado para el rol
- **Auto-assignment failure**: Mostrar campo manual con mensaje explicativo
- **Cluster loading failure**: Mostrar recursos sin agrupación como fallback

## Testing Strategy

### Unit Tests

1. **AutoAssignmentService Tests**
   - Verificar asignación correcta por rol
   - Validar ocultación de campos
   - Probar valores por defecto

2. **Permission System Tests**
   - Verificar permisos por rol y estado de recurso
   - Probar restricciones de edición
   - Validar acceso a acciones

3. **Cluster Manager Tests**
   - Verificar agrupación correcta de recursos
   - Probar visibilidad por rol
   - Validar ordenamiento y iconos

### Integration Tests

1. **Form Rendering Tests**
   - Verificar campos visibles por rol
   - Probar valores pre-poblados
   - Validar comportamiento de formularios

2. **Navigation Tests**
   - Verificar clusters visibles por panel
   - Probar navegación entre recursos
   - Validar consistencia entre paneles

3. **Workflow Tests**
   - Probar flujo completo de mantenimiento
   - Verificar flujo de gastos de viaje
   - Validar flujo de solicitudes de productos

### Performance Considerations

1. **Caching Strategy**
   - Cache de permisos por usuario y sesión
   - Cache de configuración de clusters
   - Cache de reglas de auto-assignment

2. **Database Optimization**
   - Índices para consultas de permisos
   - Optimización de consultas de relaciones
   - Lazy loading para datos no críticos

3. **Memory Management**
   - Singleton pattern para servicios de configuración
   - Cleanup de objetos temporales
   - Optimización de carga de recursos

## Implementation Phases

### Phase 1: Auto-Assignment Foundation
- Implementar AutoAssignmentService
- Crear trait HasAutoAssignment
- Aplicar a MaintenanceRecord y TravelExpense

### Phase 2: Permission Enhancement
- Implementar EnhancedPermissionInterface
- Actualizar todos los recursos con permisos consistentes
- Crear middleware de validación uniforme

### Phase 3: Resource Clustering
- Implementar ResourceClusterInterface
- Crear clusters para cada funcionalidad
- Actualizar providers de paneles

### Phase 4: Form Field Resolution
- Implementar FormFieldResolver
- Actualizar formularios para usar resolver
- Implementar validaciones específicas por rol

### Phase 5: Testing and Optimization
- Implementar suite completa de tests
- Optimizar performance
- Documentar cambios y nuevos patrones

## Migration Strategy

### Backward Compatibility
- Mantener campos existentes durante transición
- Implementar feature flags para rollback
- Migración gradual por recurso

### Data Migration
- No se requieren cambios de esquema de base de datos
- Migración de configuraciones existentes
- Actualización de datos de prueba

### Deployment Strategy
- Deploy por fases con feature flags
- Monitoreo de performance en cada fase
- Rollback plan para cada componente