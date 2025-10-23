# Sistema de Roles y Permisos

## Descripción General

El sistema de administración de tractocamiones implementa un sistema de roles y permisos basado en roles de usuario que controla el acceso a diferentes paneles y recursos de Filament.

## Roles Disponibles

### 1. Super Administrador (`super_admin`)
- **Acceso completo**: Puede acceder a todos los paneles y recursos
- **Gestión de usuarios**: Único rol que puede crear, editar y eliminar usuarios
- **Sin restricciones**: Puede realizar todas las operaciones en el sistema

### 2. Administrador (`administrador`)
- **Panel principal**: Acceso completo al panel de administración
- **Panel contabilidad**: Acceso completo al panel de contabilidad
- **Gestión operativa**: Puede gestionar flota, viajes, operadores
- **Gestión financiera**: Puede gestionar gastos, proveedores, nómina

### 3. Supervisor (`supervisor`)
- **Panel principal**: Acceso completo al panel de administración
- **Sin acceso contable**: No puede acceder al panel de contabilidad
- **Gestión operativa**: Puede gestionar flota, viajes, operadores
- **Sin gestión financiera**: No puede gestionar gastos ni nómina

### 4. Contador (`contador`)
- **Panel contabilidad**: Acceso completo al panel de contabilidad
- **Sin panel principal**: No puede acceder al panel de administración principal
- **Gestión financiera**: Puede gestionar gastos, proveedores, nómina
- **Sin gestión operativa**: No puede gestionar flota ni viajes

### 5. Operador (`operador`)
- **Acceso limitado**: Sin acceso a paneles administrativos
- **Futuro**: Panel específico para operadores (no implementado aún)

## Estructura de Acceso por Panel

### Panel Principal (`/admin`)
**Roles con acceso**: Super Admin, Administrador, Supervisor

**Recursos disponibles**:
- Gestión de flota (Tractocamiones, Trailers)
- Gestión de operadores
- Gestión de viajes
- Reportes operativos

### Panel de Contabilidad (`/accounting`)
**Roles con acceso**: Super Admin, Administrador, Contador

**Recursos disponibles**:
- Gestión de gastos
- Gestión de proveedores
- Gestión de centros de costo
- Nómina semanal
- Reportes financieros

## Implementación Técnica

### Middleware de Autorización

1. **CheckAdminAccess**: Controla acceso al panel principal
2. **CheckAccountingAccess**: Controla acceso al panel de contabilidad
3. **CheckRole**: Middleware flexible para verificar roles específicos

### Métodos de Usuario

```php
// Verificación de roles específicos
$user->isSuperAdmin()
$user->isAdministrator()
$user->isSupervisor()
$user->isAccountant()
$user->isOperator()

// Verificación de acceso a paneles
$user->hasAdminAccess()
$user->hasAccountingAccess()

// Verificación flexible de roles
$user->hasRole('super_admin')
$user->hasAnyRole(['administrador', 'supervisor'])
```

### BaseResource

Todos los recursos extienden de `BaseResource` que implementa control de acceso basado en roles:

```php
protected static function checkResourceAccess($user, string $action, ?Model $record = null): bool
{
    // Implementación específica por recurso
}
```

## Configuración de Recursos

### Recursos de Flota
- **Acceso**: Super Admin, Administrador, Supervisor
- **Recursos**: VehicleResource, TrailerResource, OperatorResource, TripResource

### Recursos Financieros
- **Acceso**: Super Admin, Administrador, Contador
- **Recursos**: ExpenseResource, ProviderResource, WeeklyPayrollResource

### Recursos de Administración
- **Acceso**: Solo Super Admin
- **Recursos**: UserResource

## Usuarios de Prueba

El sistema incluye usuarios de prueba para cada rol:

```
Super Admin: superadmin@flota.com / password
Administrador: admin@flota.com / password
Supervisor: supervisor@flota.com / password
Contador: contador@flota.com / password
Operador: operador@flota.com / password
```

## Seguridad

- Los usuarios no pueden eliminar su propia cuenta
- Solo Super Admin puede gestionar usuarios
- Middleware protege rutas según roles
- Validación en frontend y backend
- Redirección automática a login apropiado

## Extensibilidad

El sistema está diseñado para ser extensible:

1. Nuevos roles se pueden agregar en `User::getAvailableRoles()`
2. Nuevos middleware se pueden crear para permisos específicos
3. Recursos pueden implementar lógica de acceso personalizada
4. Paneles adicionales pueden configurarse con sus propios roles