# Filtrado por Tags de Samsara

## Configuración

Los comandos de sincronización de vehículos y trailers ahora soportan filtrado por tags de Samsara para traer solo los recursos que necesitas.

### Variables de Entorno

En tu archivo `.env`, configura los tag IDs por defecto:

```env
# Comma-separated tag IDs to filter vehicles/trailers during sync
SAMSARA_DEFAULT_TAG_IDS=51909875,12345678,98765432
```

Si no especificas esta variable, se sincronizarán **todos** los vehículos/trailers de tu cuenta de Samsara.

## Uso

### Sincronización de Vehículos

```bash
# Usar tags del .env (SAMSARA_DEFAULT_TAG_IDS)
php artisan samsara:sync-vehicles

# Especificar tags manualmente (sobrescribe el .env)
php artisan samsara:sync-vehicles --tag-ids=51909875,12345678

# Sin filtro de tags (traer todos)
php artisan samsara:sync-vehicles --tag-ids=

# Con límite de registros por página
php artisan samsara:sync-vehicles --limit=50

# Forzar sincronización fuera de horario
php artisan samsara:sync-vehicles --force
```

### Sincronización de Trailers

```bash
# Usar tags del .env (SAMSARA_DEFAULT_TAG_IDS)
php artisan samsara:sync-trailers

# Especificar tags manualmente (sobrescribe el .env)
php artisan samsara:sync-trailers --tag-ids=51909875,12345678

# Sin filtro de tags (traer todos)
php artisan samsara:sync-trailers --tag-ids=

# Con límite de registros por página
php artisan samsara:sync-trailers --limit=50

# Forzar sincronización fuera de horario
php artisan samsara:sync-trailers --force
```

## Cómo Funciona

1. **Prioridad de Tags**:
   - Si especificas `--tag-ids` en el comando, se usan esos tags
   - Si no especificas `--tag-ids`, se usan los tags de `SAMSARA_DEFAULT_TAG_IDS`
   - Si no hay tags configurados, se sincronizan todos los recursos

2. **Múltiples Tags**:
   - Puedes especificar múltiples tags separados por comas
   - Los recursos que tengan **cualquiera** de los tags especificados serán sincronizados

3. **Obtener Tag IDs de Samsara**:
   - Ve a tu dashboard de Samsara
   - Navega a Settings > Tags
   - El ID del tag aparece en la URL o en los detalles del tag

## Ejemplo de Configuración

Si tienes los siguientes tags en Samsara:
- Tag "Flota Principal" - ID: 51909875
- Tag "Flota Secundaria" - ID: 12345678
- Tag "Mantenimiento" - ID: 98765432

Y solo quieres sincronizar la flota principal y secundaria:

```env
SAMSARA_DEFAULT_TAG_IDS=51909875,12345678
```

Ahora cuando ejecutes `php artisan samsara:sync-vehicles`, solo se sincronizarán los vehículos que tengan el tag "Flota Principal" o "Flota Secundaria".

## Logs

Los comandos mostrarán en consola si están usando filtro de tags:

```
Starting vehicle synchronization with tag filter: 51909875, 12345678
```

O si no hay filtro:

```
Starting vehicle synchronization (no tag filter)...
```

Los tag IDs también se guardan en los logs de sincronización (`samsara_sync_logs` table) para auditoría.
