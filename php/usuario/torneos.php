<?php

require_once '../auth_user.php'; 
require_once '../includes/header.php'; 


$sql_activos = "SELECT t.id, t.nombre, t.descripcion, d.nombre_mostrado as deporte, t.fecha_inicio, e.nombre_mostrado as estado, e.codigo as estado_codigo
                FROM torneos t
                JOIN deportes d ON t.deporte_id = d.id
                JOIN estados_torneo e ON t.estado_id = e.id
                WHERE e.codigo IN ('active', 'registration')
                ORDER BY t.fecha_inicio DESC";
$activos_res = $conn->query($sql_activos);
$torneos_activos = $activos_res->fetch_all(MYSQLI_ASSOC);


$sql_finalizados = "SELECT t.id, t.nombre, t.descripcion, d.nombre_mostrado as deporte, t.fecha_fin, e.nombre_mostrado as estado, e.codigo as estado_codigo
                    FROM torneos t
                    JOIN deportes d ON t.deporte_id = d.id
                    JOIN estados_torneo e ON t.estado_id = e.id
                    WHERE e.codigo IN ('closed', 'cancelled', 'finalizado')
                    ORDER BY t.fecha_fin DESC
                    LIMIT 10"; 
$finalizados_res = $conn->query($sql_finalizados);
$torneos_finalizados = $finalizados_res->fetch_all(MYSQLI_ASSOC);

echo "<script>document.title = 'Torneos - Portal Deportivo CFLC';</script>";
?>

<div class="container page-container">
    <div class="page-header-user">
        <h1><i class="fas fa-trophy"></i> Torneos</h1>
        <p>Explora las competiciones activas y revisa el historial de torneos finalizados.</p>
    </div>

    <div class="tab-navigation">
        <button class="tab-link active" data-tab="activos"><i class="fas fa-play-circle"></i> Torneos Activos</button>
        <button class="tab-link" data-tab="finalizados"><i class="fas fa-check-circle"></i> Torneos Finalizados</button>
    </div>

    <div class="tab-content">
        <div class="tab-pane active" id="tab-activos">
            <h2 class="section-title-sub">Competiciones en Curso</h2>
            <?php if (!empty($torneos_activos)): ?>
                <div class="torneo-grid">
                    <?php foreach($torneos_activos as $torneo): ?>
                        <div class="torneo-card card">
                            <div class="card-content">
                                <span class="torneo-estado-badge <?php echo htmlspecialchars($torneo['estado_codigo']); ?>">
                                    <?php echo htmlspecialchars($torneo['estado']); ?>
                                </span>
                                <h3 class="card-title"><?php echo htmlspecialchars($torneo['nombre']); ?></h3>
                                <p class="torneo-info-preview">
                                    <i class="fas fa-futbol"></i> <?php echo htmlspecialchars($torneo['deporte']); ?>
                                </p>
                                <p class="torneo-info-preview">
                                    <i class="fas fa-calendar-alt"></i> Inició: <?php echo date('d/m/Y', strtotime($torneo['fecha_inicio'])); ?>
                                </p>
                                <a href="torneo_detalle.php?id=<?php echo $torneo['id']; ?>" class="btn btn-primary btn-sm" style="width: 100%; margin-top: 1rem;">Ver Torneo <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="info-box text-center">
                    <p class="info-text" style="margin: 0;"><i class="fas fa-info-circle"></i> No hay torneos activos en este momento.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="tab-pane" id="tab-finalizados">
             <h2 class="section-title-sub">Historial de Competiciones</h2>
            <?php if (!empty($torneos_finalizados)): ?>
                <div class="torneo-grid">
                    <?php foreach($torneos_finalizados as $torneo): ?>
                        <div class="torneo-card card">
                            <div class="card-content">
                                <span class="torneo-estado-badge <?php echo htmlspecialchars($torneo['estado_codigo']); ?>">
                                    <?php echo htmlspecialchars($torneo['estado']); ?>
                                </span>
                                <h3 class="card-title"><?php echo htmlspecialchars($torneo['nombre']); ?></h3>
                                <p class="torneo-info-preview">
                                    <i class="fas fa-futbol"></i> <?php echo htmlspecialchars($torneo['deporte']); ?>
                                </p>
                                <p class="torneo-info-preview">
                                    <i class="fas fa-calendar-check"></i> Finalizó: <?php echo date('d/m/Y', strtotime($torneo['fecha_fin'])); ?>
                                </p>
                                <a href="torneo_detalle.php?id=<?php echo $torneo['id']; ?>" class="btn btn-secondary btn-sm" style="width: 100%; margin-top: 1rem;">Ver Resultados <i class="fas fa-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                 <div class="info-box text-center">
                    <p class="info-text" style="margin: 0;"><i class="fas fa-info-circle"></i> Aún no hay torneos finalizados en el historial.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            const tabId = this.dataset.tab;

            tabLinks.forEach(l => l.classList.remove('active'));
            tabPanes.forEach(p => p.classList.remove('active'));

            this.classList.add('active');
            document.getElementById('tab-' + tabId).classList.add('active');
        });
    });
});
</script>

<?php
$activos_res->close();
$finalizados_res->close();
$conn->close();
require_once '../includes/footer.php'; 
?>