<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 

if (!isset($_GET['id'])) {
    header("Location: noticias.php");
    exit;
}

$noticia_id = (int)$_GET['id'];


$stmt_visita = $conn->prepare("UPDATE noticias SET visitas = visitas + 1 WHERE id = ?");
$stmt_visita->bind_param("i", $noticia_id);
$stmt_visita->execute();
$stmt_visita->close();


$stmt = $conn->prepare("SELECT n.*, d.nombre_mostrado AS deporte, temp.nombre AS temporada
                        FROM noticias n
                        LEFT JOIN deportes d ON n.deporte_id = d.id
                        LEFT JOIN temporadas temp ON n.temporada_id = temp.id
                        WHERE n.id = ? AND n.publicada = 1");
$stmt->bind_param("i", $noticia_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header("Location: noticias.php?error=Noticia no encontrada");
    exit;
}

$noticia = $result->fetch_assoc();


$contenido = htmlspecialchars($noticia['contenido']);
$contenido = nl2br($contenido); 
$contenido = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $contenido);
$contenido = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $contenido);
$contenido = preg_replace('/^- (.+)$/m', '<li>$1</li>', $contenido);
if (strpos($contenido, '<li>') !== false) {
    $contenido = '<ul>' . $contenido . '</ul>';
}

$contenido = str_replace('../../', '../', $contenido);



$sql_relacionadas = "SELECT id, titulo, imagen_portada, fecha_publicacion
                     FROM noticias
                     WHERE publicada = 1 AND id != ?";
if ($noticia['deporte_id']) {
    $sql_relacionadas .= " AND deporte_id = ?";
}
$sql_relacionadas .= " ORDER BY fecha_publicacion DESC LIMIT 3";

$stmt_rel = $conn->prepare($sql_relacionadas);
if ($noticia['deporte_id']) {
    $stmt_rel->bind_param("ii", $noticia_id, $noticia['deporte_id']);
} else {
    $stmt_rel->bind_param("i", $noticia_id);
}
$stmt_rel->execute();
$relacionadas = $stmt_rel->get_result();

echo "<script>document.title = '" . htmlspecialchars($noticia['titulo']) . "';</script>";

echo '<link rel="stylesheet" href="../../css/noticias.css">';


setlocale(LC_TIME, 'es_ES.UTF-8', 'es_ES', 'spanish');
?>

<header class="header-noticias">
    <div class="container">
        <nav class="breadcrumb">
            <a href="index.php">Inicio</a> /
            <a href="noticias.php">Noticias</a> /
            <span><?php echo htmlspecialchars(substr($noticia['titulo'], 0, 40)) . '...'; ?></span>
        </nav>
    </div>
</header>

<main class="main-noticia-detalle">
    <div class="container">
        <article class="noticia-completa">
            <div class="noticia-portada">
                <img src="<?php echo htmlspecialchars($noticia['imagen_portada'] ?? '../../img/noticias/default.png'); ?>" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
            </div>

            <div class="noticia-body">
                <div class="noticia-metadata">
                    <?php if ($noticia['deporte']): ?>
                        <span class="noticia-deporte-tag"><?php echo htmlspecialchars($noticia['deporte']); ?></span>
                    <?php endif; ?>
                    <span class="noticia-fecha-detalle">
                        <i class="fas fa-calendar"></i>
                        <?php echo strftime('%d de %B de %Y', strtotime($noticia['fecha_publicacion'])); ?>
                    </span>
                    <span class="noticia-autor-detalle">
                        <i class="fas fa-user"></i>
                        <?php echo htmlspecialchars($noticia['autor']); ?>
                    </span>
                    <span class="noticia-visitas">
                        <i class="fas fa-eye"></i>
                        <?php echo $noticia['visitas']; ?> visitas
                    </span>
                </div>

                <h1 class="noticia-titulo"><?php echo htmlspecialchars($noticia['titulo']); ?></h1>
                <?php if ($noticia['subtitulo']): ?>
                    <p class="noticia-subtitulo"><?php echo htmlspecialchars($noticia['subtitulo']); ?></p>
                <?php endif; ?>

                <div class="noticia-contenido">
                    <?php echo $contenido; ?>
                </div>

                <?php if ($noticia['etiquetas']): ?>
                    <div class="noticia-etiquetas">
                        <i class="fas fa-tags"></i>
                        <?php
                        $etiquetas = explode(',', $noticia['etiquetas']);
                        foreach ($etiquetas as $etiqueta):
                        ?>
                            <span class="etiqueta"><?php echo htmlspecialchars(trim($etiqueta)); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>

        <?php if ($relacionadas->num_rows > 0): ?>
        <aside class="noticias-relacionadas">
            <h2>Noticias Relacionadas</h2>
            <div class="relacionadas-grid">
                <?php while($rel = $relacionadas->fetch_assoc()): ?>
                    <div class="noticia-relacionada">
                        <img src="<?php echo htmlspecialchars($rel['imagen_portada'] ?? '../../img/noticias/default.png'); ?>" alt="<?php echo htmlspecialchars($rel['titulo']); ?>">
                        <div class="relacionada-info">
                            <span class="relacionada-fecha"><?php echo date('d/m/Y', strtotime($rel['fecha_publicacion'])); ?></span>
                            <h3><a href="noticia_detalle.php?id=<?php echo $rel['id']; ?>"><?php echo htmlspecialchars($rel['titulo']); ?></a></h3>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </aside>
        <?php endif; ?>

        <div class="noticia-volver">
            <a href="noticias.php" class="btn-volver">
                <i class="fas fa-arrow-left"></i> Volver a Noticias
            </a>
        </div>
    </div>
</main>

<?php
$stmt->close();
$stmt_rel->close();
$conn->close();
require_once '../includes/footer.php'; 
?>