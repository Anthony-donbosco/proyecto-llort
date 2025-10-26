# Instrucciones - Sistema de Torneos con Jornadas Automáticas

## Cambios Implementados

### 1. Jugadores y Equipos
Se han agregado:
- 10 nuevos jugadores a la Selecta del LLORT
- 11 equipos adicionales (total: 12 equipos)
- Jugadores de ejemplo para cada equipo

### 2. Sistema de Jornadas Automáticas
El sistema ahora calcula y genera jornadas automáticamente basado en:
- **Número de equipos**: Si hay 12 equipos → 11 jornadas (todos contra todos)
- **Ida y Vuelta**: Si se marca esta opción → 22 jornadas (11 de ida + 11 de vuelta)
- **Algoritmo Round-Robin**: Genera el calendario automáticamente sin repeticiones

### 3. Gestión de Torneos Mejorada
- Checkbox para "Ida y Vuelta"
- Generación automática de jornadas
- Vista de progreso de jornadas
- Opción de finalizar torneo cuando todas las jornadas estén completas

## Pasos para Implementar

### Paso 1: Actualizar la Base de Datos

Ejecuta los siguientes scripts SQL en orden:

```sql
-- 1. Primero ejecuta el script de mejoras (si no lo has hecho)
-- Archivo: db/mejoras_bd.sql

-- 2. Luego ejecuta el script de jugadores y equipos
-- Archivo: db/agregar_jugadores_equipos.sql
```

### Paso 2: Verificar Archivos Creados

Los siguientes archivos han sido creados/modificados:

**Nuevos archivos:**
- `php/admin/gestionar_jornadas.php` - Vista de jornadas de un torneo
- `php/admin/jornadas_process.php` - Generador automático de jornadas

**Archivos modificados:**
- `php/admin/crear_torneo.php` - Agregado checkbox "Ida y Vuelta"
- `php/admin/torneo_process.php` - Procesa campo ida_y_vuelta

### Paso 3: Uso del Sistema

#### A. Crear un Torneo

1. Accede al panel admin
2. Ve a "Gestionar Torneos" → "Crear Nuevo Torneo"
3. Completa el formulario:
   - **Nombre**: Ej: "Liga Intercolegial 2024-2025"
   - **Deporte**: Fútbol
   - **Tipo**: Liga
   - **Fase Inicial**: Liga
   - **Ida y Vuelta**: Marca si quieres doble ronda
   - **Max Participantes**: 12 o más
4. Guarda el torneo

#### B. Inscribir Equipos

1. En "Gestionar Torneos", click en "Ver Jornadas" del torneo creado
2. Si no hay equipos, inscribe los 12 equipos creados manualmente o usa el botón de inscripción

**NOTA**: Por ahora deberás inscribir equipos directamente en la BD:

```sql
-- Inscribir los 12 equipos al torneo (asume que torneo_id = 1)
INSERT INTO torneo_participantes (torneo_id, participante_id) VALUES
(1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6),
(1, 7), (1, 8), (1, 9), (1, 10), (1, 11), (1, 12);
```

#### C. Generar Jornadas Automáticamente

1. Una vez inscritos los equipos, ve a "Gestionar Jornadas"
2. Click en "Generar Jornadas"
3. El sistema creará automáticamente:
   - 11 jornadas si NO marcaste "Ida y Vuelta"
   - 22 jornadas si SÍ marcaste "Ida y Vuelta"
   - Todos los partidos distribuidos correctamente
   - Fechas espaciadas por semana

#### D. Gestionar Partidos

1. En cada jornada, click en "Ver Partidos"
2. Podrás ver todos los enfrentamientos de esa jornada
3. Registra resultados
4. Actualiza estadísticas

#### E. Finalizar Torneo

Cuando todas las jornadas estén completas (todos los partidos finalizados):
1. Aparecerá el botón "Opciones de Finalización"
2. Podrás elegir:
   - **Terminar Torneo**: Declarar campeón y cerrar
   - **Continuar con Playoffs**: Pasar a cuartos de final con los 8 mejores

## Fórmulas del Sistema

### Cálculo de Jornadas

```
n = número de equipos

Jornadas de ida = n - 1
Partidos por jornada = n / 2

Si es ida y vuelta:
Jornadas totales = (n - 1) × 2
```

**Ejemplo con 12 equipos:**
- Jornadas de ida: 11
- Partidos por jornada: 6
- Si es ida y vuelta: 22 jornadas totales
- Total de partidos: 66 (o 132 con vuelta)

### Algoritmo Round-Robin

El sistema usa el algoritmo "Round-Robin" para asegurar que:
- Cada equipo juegue contra todos los demás exactamente una vez (o dos veces si es ida y vuelta)
- No haya repeticiones
- Los partidos estén distribuidos equitativamente
- Se respete la condición de local/visitante

## Archivos Pendientes de Crear

Para completar el sistema, necesitas crear estos archivos adicionales:

### 1. `php/admin/inscribir_equipos.php`
Interfaz para inscribir equipos al torneo

### 2. `php/admin/gestionar_partidos.php`
Vista y edición de partidos de una jornada

### 3. `php/admin/finalizar_torneo.php`
Pantalla con opciones cuando termine la fase de liga:
- Terminar torneo y declarar campeón
- Continuar con cuartos de final (los 8 mejores)

### 4. `php/admin/generar_playoffs.php`
Generador de cuartos de final basado en posiciones de tabla

## Próximas Mejoras Sugeridas

1. **Tabla de Posiciones en Vivo**
   - Calcular automáticamente con cada resultado
   - Mostrar: PJ, PG, PE, PP, GF, GC, DG, Pts

2. **Calendario Completo**
   - Vista de calendario mensual con todos los partidos
   - Filtros por equipo, fecha, estado

3. **Estadísticas Avanzadas**
   - Goleadores del torneo
   - Vallas menos batidas
   - Tarjetas amarillas/rojas
   - Fair Play

4. **Notificaciones**
   - Avisos de próximos partidos
   - Resultados actualizados
   - Cambios de horario

5. **Bracket Interactivo**
   - Vista visual del bracket de playoffs
   - Actualización en tiempo real

## Estructura de Archivos

```
proyecto-llort/
├── db/
│   ├── mejoras_bd.sql (ya ejecutado)
│   └── agregar_jugadores_equipos.sql (nuevo)
├── php/
│   └── admin/
│       ├── gestionar_jornadas.php (nuevo)
│       ├── jornadas_process.php (nuevo)
│       ├── crear_torneo.php (modificado)
│       └── torneo_process.php (modificado)
└── INSTRUCCIONES_NUEVO_SISTEMA.md (este archivo)
```

## Solución de Problemas

### Error: "No se generan jornadas"
- Verifica que haya al menos 2 equipos inscritos
- Revisa que no existan jornadas previas para ese torneo

### Error: "Partidos duplicados"
- Elimina las jornadas existentes antes de regenerar
- Verifica la inscripción de equipos

### Error: "Fechas incorrectas"
- Verifica que la fecha de inicio del torneo sea correcta
- El sistema suma 7 días por cada jornada

---

**Desarrollado para el Sistema Deportivo del Colegio**
**Fecha**: Octubre 2025
