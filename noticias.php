<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticias - Portal Deportivo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/noticias.css">
</head>
<body>
    <?php
    require_once 'php/db_connect.php';

    // Paginación
    $noticias_por_pagina = 9;
    $pagina_actual = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
    $offset = ($pagina_actual - 1) * $noticias_por_pagina;

    // Filtros
    $deporte_filter = isset($_GET['deporte']) ? (int)$_GET['deporte'] : 0;

    $sql_where = " WHERE n.publicada = 1";
    $params = [];
    $types = '';

    if ($deporte_filter > 0) {
        $sql_where .= " AND n.deporte_id = ?";
        $params[] = $deporte_filter;
        $types .= "i";
    }

    // Contar total de noticias
    $sql_count = "SELECT COUNT(*) as total FROM noticias n $sql_where";
    $stmt_count = $conn->prepare($sql_count);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_noticias = $stmt_count->get_result()->fetch_assoc()['total'];
    $total_paginas = ceil($total_noticias / $noticias_por_pagina);
    $stmt_count->close();

    // Obtener noticias destacadas (máximo 3)
    $sql_destacadas = "SELECT n.*, d.nombre_mostrado AS deporte
                       FROM noticias n
                       LEFT JOIN deportes d ON n.deporte_id = d.id
                       WHERE n.publicada = 1 AND n.destacada = 1
                       ORDER BY n.fecha_publicacion DESC
                       LIMIT 3";
    $destacadas = $conn->query($sql_destacadas);

    // Obtener noticias normales
    $sql_noticias = "SELECT n.*, d.nombre_mostrado AS deporte
                     FROM noticias n
                     LEFT JOIN deportes d ON n.deporte_id = d.id
                     $sql_where
                     ORDER BY n.fecha_publicacion DESC, n.orden ASC
                     LIMIT ? OFFSET ?";

    $stmt_noticias = $conn->prepare($sql_noticias);
    if (!empty($params)) {
        $params[] = $noticias_por_pagina;
        $params[] = $offset;
        $types .= "ii";
        $stmt_noticias->bind_param($types, ...$params);
    } else {
        $stmt_noticias->bind_param("ii", $noticias_por_pagina, $offset);
    }
    $stmt_noticias->execute();
    $noticias = $stmt_noticias->get_result();

    // Obtener deportes para filtro
    $deportes = $conn->query("SELECT id, nombre_mostrado FROM deportes ORDER BY nombre_mostrado");
    ?>

    <header class="header-noticias">
        <div class="container">
            <h1><i class="fas fa-newspaper"></i> Noticias Deportivas</h1>
            <nav class="breadcrumb">
                <a href="index.php">Inicio</a> / <span>Noticias</span>
            </nav>
        </div>
    </header>

    <main class="main-noticias">
        <div class="container">
            <!-- Slider de Noticias Destacadas -->
            <?php if ($destacadas->num_rows > 0): ?>
            <section class="noticias-destacadas">
                <h2>Noticias Destacadas</h2>
                <div class="slider-destacadas">
                    <?php while($noticia = $destacadas->fetch_assoc()): ?>
                        <div class="noticia-destacada">
                            <div class="noticia-destacada-imagen">
                                <img src="<?php echo htmlspecialchars($noticia['imagen_portada'] ?? 'img/default-noticia.png'); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                                <div class="noticia-overlay">
                                    <?php if ($noticia['deporte']): ?>
                                        <span class="noticia-categoria"><?php echo htmlspecialchars($noticia['deporte']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="noticia-destacada-contenido">
                                <h3><a href="noticia_detalle.php?id=<?php echo $noticia['id']; ?>"><?php echo htmlspecialchars($noticia['titulo']); ?></a></h3>
                                <p><?php echo htmlspecialchars($noticia['subtitulo']); ?></p>
                                <div class="noticia-meta">
                                    <span><i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></span>
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($noticia['autor']); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Filtros -->
            <section class="filtros-noticias">
                <form method="GET" action="noticias.php" class="filtro-form">
                    <select name="deporte" onchange="this.form.submit()">
                        <option value="0">Todos los deportes</option>
                        <?php while($deporte = $deportes->fetch_assoc()): ?>
                            <option value="<?php echo $deporte['id']; ?>" <?php echo $deporte_filter == $deporte['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($deporte['nombre_mostrado']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <?php if ($deporte_filter > 0): ?>
                        <a href="noticias.php" class="btn-limpiar">Limpiar filtros</a>
                    <?php endif; ?>
                </form>
            </section>

            <!-- Grid de Noticias -->
            <section class="noticias-grid">
                <?php if ($noticias->num_rows > 0): ?>
                    <?php while($noticia = $noticias->fetch_assoc()): ?>
                        <article class="noticia-card">
                            <div class="noticia-imagen">
                                <img src="<?php echo htmlspecialchars($noticia['imagen_portada'] ?? 'img/default-noticia.png'); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                                <?php if ($noticia['deporte']): ?>
                                    <span class="noticia-deporte-badge"><?php echo htmlspecialchars($noticia['deporte']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="noticia-contenido">
                                <h3><a href="noticia_detalle.php?id=<?php echo $noticia['id']; ?>"><?php echo htmlspecialchars($noticia['titulo']); ?></a></h3>
                                <p><?php echo htmlspecialchars(substr($noticia['subtitulo'], 0, 120)) . '...'; ?></p>
                                <div class="noticia-footer">
                                    <span class="noticia-fecha"><?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></span>
                                    <a href="noticia_detalle.php?id=<?php echo $noticia['id']; ?>" class="noticia-leer-mas">
                                        Leer más <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-noticias">No se encontraron noticias.</p>
                <?php endif; ?>
            </section>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav class="paginacion">
                <?php if ($pagina_actual > 1): ?>
                    <a href="?pagina=<?php echo $pagina_actual - 1; ?><?php echo $deporte_filter > 0 ? "&deporte=$deporte_filter" : ''; ?>" class="pag-btn">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                <?php endif; ?>

                <div class="pag-numeros">
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <a href="?pagina=<?php echo $i; ?><?php echo $deporte_filter > 0 ? "&deporte=$deporte_filter" : ''; ?>"
                           class="pag-numero <?php echo $i == $pagina_actual ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <?php if ($pagina_actual < $total_paginas): ?>
                    <a href="?pagina=<?php echo $pagina_actual + 1; ?><?php echo $deporte_filter > 0 ? "&deporte=$deporte_filter" : ''; ?>" class="pag-btn">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </nav>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer-noticias">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Portal Deportivo. Todos los derechos reservados.</p>
        </div>
    </footer>

    <?php
    $stmt_noticias->close();
    $conn->close();
    ?>
</body>
</html>
