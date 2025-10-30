<?php
    
    require_once '../auth_user.php'; 
    require_once '../includes/header.php'; 
    

    
    
    if (!isset($conn) || !$conn) {
        
        
        die("Error crítico: No se pudo establecer la conexión a la base de datos a través del header.");
    }


    
    $sql_torneos = "SELECT t.id, t.nombre, d.nombre_mostrado as deporte, t.fecha_inicio, e.codigo as estado_codigo
                    FROM torneos t
                    JOIN deportes d ON t.deporte_id = d.id
                    JOIN estados_torneo e ON t.estado_id = e.id
                    ORDER BY FIELD(e.codigo, 'active', 'registration', 'draft', 'paused', 'closed', 'cancelled'), t.fecha_inicio DESC
                    LIMIT 4";
    $torneos_res = $conn->query($sql_torneos);
    
    if (!$torneos_res) {
        die("Error en consulta de torneos: " . $conn->error);
    }
    $torneos = $torneos_res->fetch_all(MYSQLI_ASSOC);

    
    $sql_destacados = "SELECT jd.*, m.nombre_jugador, m.posicion, m.url_foto,
                    COALESCE(t.nombre, temp.nombre, d.nombre_mostrado) AS contexto
                    FROM jugadores_destacados jd
                    JOIN miembros_plantel m ON jd.miembro_plantel_id = m.id
                    JOIN deportes d ON jd.deporte_id = d.id
                    LEFT JOIN torneos t ON jd.torneo_id = t.id
                    LEFT JOIN temporadas temp ON jd.temporada_id = temp.id
                    WHERE jd.esta_activo = 1
                    ORDER BY jd.orden ASC, jd.fecha_destacado DESC
                    LIMIT 3";
    $destacados_res = $conn->query($sql_destacados);
    if (!$destacados_res) {
        die("Error en consulta de destacados: " . $conn->error);
    }
    $destacados = $destacados_res->fetch_all(MYSQLI_ASSOC);

    
    $sql_noticias = "SELECT n.id, n.titulo, n.subtitulo, n.imagen_portada, n.fecha_publicacion, n.destacada
                    FROM noticias n
                    WHERE n.publicada = 1
                    ORDER BY n.destacada DESC, n.fecha_publicacion DESC
                    LIMIT 4"; 
    $noticias_res = $conn->query($sql_noticias);
    if (!$noticias_res) {
        die("Error en consulta de noticias: " . $conn->error);
    }
    $noticias = $noticias_res->fetch_all(MYSQLI_ASSOC);
    $noticia_hero = null;
    $noticias_recientes = [];
    if (!empty($noticias)) {
        if ($noticias[0]['destacada']) {
            $noticia_hero = array_shift($noticias); 
            $noticias_recientes = $noticias; 
        } else {
            $noticias_recientes = $noticias; 
        }
    }


    
    $sql_galeria = "SELECT url_foto, titulo FROM galeria_temporadas WHERE esta_activa = 1 ORDER BY RAND() LIMIT 6";
    $galeria_res = $conn->query($sql_galeria);
    if (!$galeria_res) {
        die("Error en consulta de galería: " . $conn->error);
    }
    $galeria_fotos = $galeria_res->fetch_all(MYSQLI_ASSOC);

    
    $mision = "Fomentar la participación activa de todos los estudiantes en eventos deportivos inclusivos, desarrollando habilidades físicas, mentales y sociales a través del deporte, mientras fortalecemos el espíritu de comunidad y compañerismo en el Colegio Fernando Llort Choussy.";
    $vision = "Ser un referente en la formación integral de estudiantes-atletas, promoviendo valores deportivos de excelencia, trabajo en equipo y disciplina que trasciendan más allá de las canchas, preparando líderes para el futuro.";

    
    echo "<script>document.title = 'Inicio - Portal Deportivo CFLC';</script>";
?>

<section class="hero-section" style="background-image: url('<?php echo !empty($galeria_fotos) ? htmlspecialchars($galeria_fotos[array_rand($galeria_fotos)]['url_foto']) : 'img/default-hero.jpg'; ?>');">
    <div class="hero-overlay"></div>
    <div class="hero-content container">
        <h1>Colegio Fernando Llort Choussy</h1>
        <p>Tu portal para el deporte estudiantil</p>
        <?php if ($noticia_hero): ?>
            <div class="hero-noticia">
                <h2><?php echo htmlspecialchars($noticia_hero['titulo']); ?></h2>
                <p><?php echo htmlspecialchars($noticia_hero['subtitulo']); ?></p>
                <a href="noticia_detalle.php?id=<?php echo $noticia_hero['id']; ?>" class="btn btn-primary">Leer Más</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<div class="container page-container">

    <section class="mision-vision-section">
         <div class="mision-vision-grid">
            <div class="info-box vision">
                <h3><i class="fas fa-eye"></i> Nuestra Visión</h3>
                <p><?php echo $vision; ?></p>
            </div>
            <div class="info-box mision">
                 <h3><i class="fas fa-bullseye"></i> Nuestra Misión</h3>
                <p><?php echo $mision; ?></p>
            </div>
        </div>
    </section>

     <hr class="section-divider">

    <?php if (!empty($galeria_fotos)): ?>
    <section class="galeria-preview-section">
        <h2 class="section-title">Galería Reciente</h2>
        <div class="galeria-grid-preview">
            <?php foreach($galeria_fotos as $foto): ?>
                <div class="galeria-item-preview">
                    <img src="<?php echo htmlspecialchars($foto['url_foto']); ?>" alt="<?php echo htmlspecialchars($foto['titulo']); ?>" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center">
             <a href="galeria.php" class="btn btn-secondary"><i class="fas fa-images"></i> Ver Galería Completa</a>
        </div>
    </section>
    <hr class="section-divider">
    <?php endif; ?>

    <section class="torneos-section">
        <h2 class="section-title">Torneos</h2>
        <?php if (!empty($torneos)): ?>
            <div class="torneos-grid-preview">
                <?php foreach($torneos as $torneo): ?>
                    <div class="torneo-card-preview card">
                         <div class="card-content">
                             <span class="torneo-estado-badge <?php echo htmlspecialchars($torneo['estado_codigo']); ?>">
                                <?php echo htmlspecialchars(ucfirst($torneo['estado_codigo'])); ?>
                             </span>
                            <h3 class="card-title"><?php echo htmlspecialchars($torneo['nombre']); ?></h3>
                            <p class="torneo-info-preview">
                                <i class="fas fa-futbol"></i> <?php echo htmlspecialchars($torneo['deporte']); ?> |
                                <i class="fas fa-calendar-alt"></i> Inicio: <?php echo date('d/m/Y', strtotime($torneo['fecha_inicio'])); ?>
                            </p>
                            <a href="torneo_detalle.php?id=<?php echo $torneo['id']; ?>" class="card-link">Ver Detalles <i class="fas fa-arrow-right"></i></a>
                         </div>
                    </div>
                <?php endforeach; ?>
            </div>
             <div class="text-center">
                 <a href="torneos.php" class="btn btn-secondary"><i class="fas fa-trophy"></i> Ver Todos los Torneos</a>
            </div>
        <?php else: ?>
            <p class="text-center info-text">🏆 No hay torneos activos o recientes para mostrar en este momento.</p>
        <?php endif; ?>
    </section>

     <hr class="section-divider">

    <?php if (!empty($noticias_recientes)): ?>
    <section class="noticias-recientes-section">
        <h2 class="section-title">Últimas Noticias</h2>
        <div class="noticias-grid-preview">
            <?php foreach($noticias_recientes as $noticia): ?>
                 <article class="noticia-card-preview card">
                     <div class="card-image">
                         <img src="<?php echo htmlspecialchars($noticia['imagen_portada'] ?? '../../img/noticias/default.png'); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                     </div>
                     <div class="card-content">
                         <h3 class="card-title"><a href="noticia_detalle.php?id=<?php echo $noticia['id']; ?>"><?php echo htmlspecialchars($noticia['titulo']); ?></a></h3>
                         <p class="noticia-fecha-preview"><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></p>
                         <p class="card-text"><?php echo htmlspecialchars(substr($noticia['subtitulo'], 0, 80)) . '...'; ?></p>
                         <a href="noticia_detalle.php?id=<?php echo $noticia['id']; ?>" class="card-link">Leer Más <i class="fas fa-arrow-right"></i></a>
                     </div>
                 </article>
            <?php endforeach; ?>
        </div>
         <div class="text-center">
            <a href="noticias.php" class="btn btn-secondary"><i class="fas fa-newspaper"></i> Ver Todas las Noticias</a>
        </div>
    </section>
    <hr class="section-divider">
    <?php endif; ?>

    <section class="quick-links-section">
         <div class="quick-links-grid">
            <a href="torneos.php" class="quick-link-card">
                <i class="fas fa-trophy"></i>
                <h3>Torneos</h3>
                <p>Explora los torneos activos y finalizados.</p>
            </a>
            <a href="calendario.php" class="quick-link-card">
                 <i class="fas fa-calendar-alt"></i>
                <h3>Calendario</h3>
                <p>Consulta las fechas de los próximos eventos.</p>
            </a>
        </div>
    </section>

</div>
<?php

if (isset($torneos_res) && $torneos_res) $torneos_res->close();
if (isset($destacados_res) && $destacados_res) $destacados_res->close();
if (isset($noticias_res) && $noticias_res) $noticias_res->close();
if (isset($galeria_res) && $galeria_res) $galeria_res->close();




require_once '../includes/footer.php'; 
?>