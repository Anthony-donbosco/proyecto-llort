# Instrucciones de Implementación - Sistema Deportivo Mejorado

## Resumen de Mejoras

Se han implementado las siguientes mejoras al sistema deportivo del colegio:

1. **Estadísticas de Jugadores**: Goles, asistencias y porterías a cero
2. **Jugadores Destacados**: Sistema para destacar jugadores por deporte/torneo
3. **Galería por Temporadas**: Sistema de fotos organizadas por temporadas
4. **Temporadas**: Gestión de temporadas deportivas
5. **Diseño Mejorado**: Interfaz más moderna y funcional

## Pasos de Implementación

### 1. Actualizar la Base de Datos

Ejecuta el siguiente archivo SQL en tu base de datos `sistema_deportivo`:

```sql
-- Ubicación: db/mejoras_bd.sql
```

Para ejecutarlo:
1. Abre phpMyAdmin (http://localhost/phpmyadmin)
2. Selecciona la base de datos `sistema_deportivo`
3. Ve a la pestaña "SQL"
4. Copia y pega el contenido del archivo `db/mejoras_bd.sql`
5. Haz clic en "Continuar" para ejecutar

### 2. Crear Carpetas para Imágenes

Crea las siguientes carpetas si no existen:

```
img/
├── jugadores/    (ya existe)
└── galeria/      (nueva - para fotos de temporadas)
```

### 3. Verificar Archivos Creados/Modificados

#### Archivos de Base de Datos:
- `db/mejoras_bd.sql` - Script SQL con las mejoras

#### Módulo de Jugadores (Modificado):
- `php/admin/crear_jugador.php` - Ahora incluye goles, asistencias y porterías a cero
- `php/admin/jugador_process.php` - Procesa las estadísticas
- `php/admin/ver_plantel.php` - Muestra las estadísticas en la tabla

#### Módulo de Destacados (Nuevo):
- `php/admin/gestionar_destacados.php` - Lista de jugadores destacados
- `php/admin/crear_destacado.php` - Formulario para crear/editar destacados
- `php/admin/destacado_process.php` - Procesamiento de destacados

#### Módulo de Temporadas (Nuevo):
- `php/admin/gestionar_temporadas.php` - Lista de temporadas
- `php/admin/crear_temporada.php` - Formulario para crear/editar temporadas
- `php/admin/temporada_process.php` - Procesamiento de temporadas

#### Módulo de Galería (Nuevo):
- `php/admin/gestionar_galeria.php` - Vista de galería de fotos
- `php/admin/subir_foto_galeria.php` - Formulario para subir fotos
- `php/admin/galeria_process.php` - Procesamiento de fotos
- `php/admin/configurar_galeria.php` - Configuración de qué temporada mostrar

#### Dashboard (Modificado):
- `php/admin/dashboard.php` - Ahora incluye enlaces a todos los módulos

#### Estilos (Modificado):
- `css/admin_style.css` - Estilos mejorados para todos los módulos

## Uso de los Nuevos Módulos

### Gestión de Jugadores con Estadísticas

1. Accede a "Gestionar Jugadores" desde el dashboard
2. Al crear o editar un jugador, ahora verás campos para:
   - **Goles**: Número de goles anotados
   - **Asistencias**: Número de asistencias realizadas
   - **Porterías a Cero**: Solo para porteros
3. Las estadísticas se mostrarán en la tabla de jugadores

### Jugadores Destacados

1. Accede a "Gestionar Destacados" desde el dashboard
2. Haz clic en "Agregar Destacado"
3. Completa el formulario:
   - **Deporte**: Selecciona el deporte
   - **Tipo**: General, Por Torneo o Selección
   - **Jugador**: Selecciona el jugador del listado
   - **Torneo/Temporada**: Opcional, para contextualizar
   - **Descripción**: Describe el logro
   - **Orden**: Menor número aparece primero
   - **Activo**: Marca para mostrar en el sitio web

### Gestión de Temporadas

1. Accede a "Gestionar Temporadas" desde el dashboard
2. Haz clic en "Agregar Temporada"
3. Completa:
   - **Nombre**: Ej: "Temporada 2024-2025"
   - **Año**: 2024
   - **Fechas**: Inicio y fin
   - **Temporada Actual**: Solo puede haber una activa

### Galería de Fotos por Temporadas

1. **Subir Fotos**:
   - Accede a "Administrar Galería" > "Subir Foto"
   - Selecciona la temporada
   - Opcional: Selecciona un deporte específico
   - Agrega título y descripción
   - Sube la foto
   - Marca "Es foto grupal de selección" si aplica
   - Define el orden de visualización

2. **Configurar Galería Pública**:
   - Accede a "Configuración" en la galería
   - Opciones:
     - **Mostrar todas las temporadas**: Muestra fotos de todas
     - **Temporada activa**: Muestra solo de una temporada específica

3. **Gestionar Fotos**:
   - Filtra por temporada o deporte
   - Edita o elimina fotos existentes

## Estructura de la Base de Datos

### Nuevas Tablas:

1. **jugadores_destacados**:
   - Relaciona jugadores con destacados por deporte/torneo
   - Campos: deporte, torneo, jugador, temporada, tipo, descripción, orden

2. **galeria_temporadas**:
   - Almacena fotos organizadas por temporada
   - Campos: temporada, deporte, título, descripción, foto, orden, activa

3. **configuracion_galeria**:
   - Configuración para controlar qué temporada mostrar
   - Claves: temporada_galeria_activa, mostrar_todas_temporadas

### Campos Añadidos a `miembros_plantel`:
- `goles` (INT) - Goles anotados
- `asistencias` (INT) - Asistencias realizadas
- `porterias_cero` (INT) - Porterías a cero (porteros)

## Próximos Pasos Recomendados

1. **Implementar vistas públicas** para que los usuarios puedan ver:
   - Jugadores destacados en la página principal
   - Galería de fotos por temporada
   - Estadísticas de jugadores

2. **Agregar funcionalidad de triggers/vistas en PHP**:
   - Calcular automáticamente estadísticas
   - Actualizar clasificaciones
   - Generar reportes

3. **Optimizaciones**:
   - Caché de imágenes
   - Compresión de fotos al subir
   - Paginación en listados largos

## Soporte y Mantenimiento

- Todos los módulos incluyen validación de datos
- Los formularios tienen protección CSRF mediante POST
- Las imágenes se validan por tipo (JPG, PNG, WEBP)
- Las consultas usan prepared statements para prevenir SQL injection

## Notas Importantes

- **Permisos de carpetas**: Asegúrate de que las carpetas `img/jugadores` e `img/galeria` tengan permisos de escritura (755 o 777)
- **Tamaño de archivos**: Verifica en `php.ini` que `upload_max_filesize` y `post_max_size` sean adecuados
- **MySQL**: La base de datos actual no acepta vistas y triggers, por eso se implementaron las funcionalidades en PHP

## Capturas de Pantalla

### Dashboard Mejorado
- Muestra todos los módulos organizados por categorías
- Íconos visuales para mejor navegación

### Gestión de Jugadores
- Tabla con estadísticas (goles, asistencias, porterías a cero)
- Formulario mejorado con secciones organizadas

### Jugadores Destacados
- Sistema flexible para destacar por deporte/torneo
- Orden personalizable de visualización

### Galería de Fotos
- Vista en grid con thumbnails
- Filtros por temporada y deporte
- Configuración centralizada

---

**Generado para el Sistema Deportivo del Colegio**
**Fecha**: Octubre 2025
