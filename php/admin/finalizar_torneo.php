<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];

// Obtener información del torneo
$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte, e.nombre_mostrado AS estado
                                FROM torneos t
                                JOIN deportes d ON t.deporte_id = d.id
                                JOIN estados_torneo e ON t.estado_id = e.id
                                WHERE t.id = ?");
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo = $stmt_torneo->get_result()->fetch_assoc();

if (!$torneo) {
    header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
    exit;
}

// Verificar que todas las jornadas estén completas
$stmt_check = $conn->prepare("SELECT COUNT(*) as total_partidos,
                               SUM(CASE WHEN estado_id = 5 THEN 1 ELSE 0 END) as partidos_finalizados
                               FROM partidos p
                               WHERE p.torneo_id = ?");
$stmt_check->bind_param("i", $torneo_id);
$stmt_check->execute();
$check_result = $stmt_check->get_result()->fetch_assoc();

if ($check_result['total_partidos'] != $check_result['partidos_finalizados']) {
    header("Location: gestionar_jornadas.php?torneo_id=$torneo_id&error=Aún hay partidos sin finalizar.");
    exit;
}

// Obtener todos los equipos inscritos
$stmt_equipos = $conn->prepare("SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                                 FROM torneo_participantes tp
                                 JOIN participantes p ON tp.participante_id = p.id
                                 WHERE tp.torneo_id = ?
                                 ORDER BY p.nombre_mostrado");
$stmt_equipos->bind_param("i", $torneo_id);
$stmt_equipos->execute();
$equipos_result = $stmt_equipos->get_result();

// Inicializar tabla de posiciones
$tabla = [];
while ($equipo = $equipos_result->fetch_assoc()) {
    $tabla[$equipo['id']] = [
        'id' => $equipo['id'],
        'nombre' => $equipo['nombre_mostrado'],
        'nombre_corto' => $equipo['nombre_corto'],
        'logo' => $equipo['url_logo'],
        'pj' => 0, 'pg' => 0, 'pe' => 0, 'pp' => 0,
        'gf' => 0, 'gc' => 0, 'dg' => 0, 'pts' => 0
    ];
}

// Obtener todos los partidos finalizados
$sql_partidos = "SELECT p.participante_local_id, p.participante_visitante_id,
                 p.marcador_local, p.marcador_visitante
                 FROM partidos p
                 WHERE p.torneo_id = ? AND p.estado_id = 5";

$stmt_partidos = $conn->prepare($sql_partidos);
$stmt_partidos->bind_param("i", $torneo_id);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();

// Calcular estadísticas
while ($partido = $partidos->fetch_assoc()) {
    $local_id = $partido['participante_local_id'];
    $visitante_id = $partido['participante_visitante_id'];
    $goles_local = $partido['marcador_local'];
    $goles_visitante = $partido['marcador_visitante'];

    if (!isset($tabla[$local_id]) || !isset($tabla[$visitante_id])) {
        continue;
    }

    $tabla[$local_id]['pj']++;
    $tabla[$visitante_id]['pj']++;
    $tabla[$local_id]['gf'] += $goles_local;
    $tabla[$local_id]['gc'] += $goles_visitante;
    $tabla[$visitante_id]['gf'] += $goles_visitante;
    $tabla[$visitante_id]['gc'] += $goles_local;

    if ($goles_local > $goles_visitante) {
        $tabla[$local_id]['pg']++;
        $tabla[$local_id]['pts'] += 3;
        $tabla[$visitante_id]['pp']++;
    } elseif ($goles_local < $goles_visitante) {
        $tabla[$visitante_id]['pg']++;
        $tabla[$visitante_id]['pts'] += 3;
        $tabla[$local_id]['pp']++;
    } else {
        $tabla[$local_id]['pe']++;
        $tabla[$local_id]['pts'] += 1;
        $tabla[$visitante_id]['pe']++;
        $tabla[$visitante_id]['pts'] += 1;
    }
}

// Calcular diferencia de goles
foreach ($tabla as &$equipo) {
    $equipo['dg'] = $equipo['gf'] - $equipo['gc'];
}

// Ordenar tabla
usort($tabla, function($a, $b) {
    if ($b['pts'] != $a['pts']) return $b['pts'] - $a['pts'];
    if ($b['dg'] != $a['dg']) return $b['dg'] - $a['dg'];
    return $b['gf'] - $a['gf'];
});

$campeon = count($tabla) > 0 ? $tabla[0] : null;
$equipos_playoffs = array_slice($tabla, 0, 8);

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Finalizar Torneo - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <div>
            <a href="gestionar_jornadas.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Jornadas
            </a>
        </div>
    </div>

    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>

    <div class="finalizacion-container">
        <!-- Resumen del torneo -->
        <div class="resumen-card">
            <h2><i class="fas fa-trophy"></i> Fase de Liga Completada</h2>
            <p>Todos los partidos de la fase de liga han finalizado. Ahora puedes elegir cómo continuar:</p>
        </div>

        <!-- Campeón -->
        <?php if ($campeon): ?>
            <div class="campeon-card">
                <div class="campeon-header">
                    <i class="fas fa-crown"></i>
                    <h3>Líder de la Tabla</h3>
                </div>
                <div class="campeon-body">
                    <div class="campeon-logo">
                        <?php if ($campeon['logo']): ?>
                            <img src="<?php echo htmlspecialchars($campeon['logo']); ?>" alt="Logo">
                        <?php else: ?>
                            <i class="fas fa-shield-alt"></i>
                        <?php endif; ?>
                    </div>
                    <div class="campeon-info">
                        <h2><?php echo htmlspecialchars($campeon['nombre']); ?></h2>
                        <div class="campeon-stats">
                            <span><strong><?php echo $campeon['pts']; ?></strong> puntos</span>
                            <span><strong><?php echo $campeon['pg']; ?></strong> victorias</span>
                            <span><strong><?php echo $campeon['dg'] >= 0 ? '+' : ''; ?><?php echo $campeon['dg']; ?></strong> diferencia</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Opciones de finalización -->
        <div class="opciones-grid">
            <!-- Opción 1: Terminar torneo -->
            <div class="opcion-card">
                <div class="opcion-icon terminar">
                    <i class="fas fa-flag-checkered"></i>
                </div>
                <h3>Terminar Torneo</h3>
                <p>Declarar al líder como campeón y finalizar el torneo. Esta acción marcará el torneo como "Finalizado".</p>
                <div class="opcion-info">
                    <strong>Campeón:</strong> <?php echo htmlspecialchars($campeon['nombre'] ?? 'N/A'); ?>
                </div>
                <form action="torneo_finalizar_process.php" method="POST" onsubmit="return confirm('¿Estás seguro de terminar el torneo? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="torneo_id" value="<?php echo $torneo_id; ?>">
                    <input type="hidden" name="action" value="terminar">
                    <input type="hidden" name="campeon_id" value="<?php echo $campeon['id'] ?? ''; ?>">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Terminar Torneo
                    </button>
                </form>
            </div>

            <!-- Opción 2: Continuar con playoffs -->
            <div class="opcion-card <?php echo count($tabla) < 8 ? 'opcion-disabled' : ''; ?>">
                <div class="opcion-icon playoffs">
                    <i class="fas fa-sitemap"></i>
                </div>
                <h3>Continuar con Playoffs</h3>
                <p>Generar cuartos de final con los 8 mejores equipos de la tabla. Se creará un bracket de eliminación directa.</p>
                <div class="opcion-info">
                    <?php if (count($tabla) >= 8): ?>
                        <strong>Clasificados:</strong>
                        <ul class="clasificados-list">
                            <?php
                            for ($i = 0; $i < 8 && $i < count($equipos_playoffs); $i++):
                                echo "<li>" . ($i + 1) . ". " . htmlspecialchars($equipos_playoffs[$i]['nombre_corto']) . " (" . $equipos_playoffs[$i]['pts'] . " pts)</li>";
                            endfor;
                            ?>
                        </ul>
                    <?php else: ?>
                        <span class="text-warning">Se necesitan al menos 8 equipos para playoffs</span>
                    <?php endif; ?>
                </div>
                <?php if (count($tabla) >= 8): ?>
                    <a href="generar_playoffs.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-success btn-block">
                        <i class="fas fa-plus"></i> Generar Playoffs
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-secondary btn-block" disabled>
                        <i class="fas fa-ban"></i> No Disponible
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla de posiciones resumida -->
        <div class="tabla-final-card">
            <h3><i class="fas fa-list-ol"></i> Tabla de Posiciones Final</h3>
            <div class="tabla-scroll">
                <table class="tabla-simple">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Equipo</th>
                            <th>PJ</th>
                            <th>PG</th>
                            <th>PE</th>
                            <th>PP</th>
                            <th>GF</th>
                            <th>GC</th>
                            <th>DG</th>
                            <th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pos = 1;
                        foreach ($tabla as $equipo):
                            $clase = '';
                            if ($pos == 1) $clase = 'campeon-row';
                            elseif ($pos <= 8) $clase = 'playoff-row';
                        ?>
                            <tr class="<?php echo $clase; ?>">
                                <td><?php echo $pos; ?></td>
                                <td class="equipo-nombre-simple">
                                    <?php echo htmlspecialchars($equipo['nombre_corto']); ?>
                                </td>
                                <td><?php echo $equipo['pj']; ?></td>
                                <td><?php echo $equipo['pg']; ?></td>
                                <td><?php echo $equipo['pe']; ?></td>
                                <td><?php echo $equipo['pp']; ?></td>
                                <td><?php echo $equipo['gf']; ?></td>
                                <td><?php echo $equipo['gc']; ?></td>
                                <td class="<?php echo $equipo['dg'] >= 0 ? 'dg-pos' : 'dg-neg'; ?>">
                                    <?php echo $equipo['dg'] >= 0 ? '+' : ''; ?><?php echo $equipo['dg']; ?>
                                </td>
                                <td><strong><?php echo $equipo['pts']; ?></strong></td>
                            </tr>
                        <?php
                            $pos++;
                        endforeach;
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="tabla-actions">
                <a href="tabla_posiciones.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                    <i class="fas fa-table"></i> Ver Tabla Completa
                </a>
            </div>
        </div>
    </div>
</main>

<style>
.finalizacion-container {
    display: grid;
    gap: 2rem;
}

.resumen-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    text-align: center;
}

.resumen-card h2 {
    margin: 0 0 1rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.resumen-card p {
    margin: 0;
    color: #666;
    font-size: 1rem;
}

.campeon-card {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(255, 215, 0, 0.3);
}

.campeon-header {
    background: rgba(0,0,0,0.1);
    padding: 1rem;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.campeon-header i {
    font-size: 1.5rem;
}

.campeon-header h3 {
    margin: 0;
    font-size: 1.25rem;
}

.campeon-body {
    padding: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    justify-content: center;
}

.campeon-logo {
    width: 100px;
    height: 100px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: white;
    border-radius: 50%;
    padding: 1rem;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.campeon-logo img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.campeon-logo i {
    font-size: 3rem;
    color: #999;
}

.campeon-info h2 {
    margin: 0 0 1rem 0;
    font-size: 2rem;
}

.campeon-stats {
    display: flex;
    gap: 1.5rem;
    font-size: 1rem;
}

.campeon-stats span strong {
    font-size: 1.25rem;
}

.opciones-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.opcion-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.opcion-card.opcion-disabled {
    opacity: 0.6;
}

.opcion-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: white;
    margin: 0 auto;
}

.opcion-icon.terminar {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
}

.opcion-icon.playoffs {
    background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
}

.opcion-card h3 {
    margin: 0;
    text-align: center;
    color: #333;
}

.opcion-card p {
    margin: 0;
    color: #666;
    font-size: 0.95rem;
    text-align: center;
}

.opcion-info {
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 6px;
    font-size: 0.9rem;
}

.clasificados-list {
    list-style: none;
    padding: 0.5rem 0 0 0;
    margin: 0;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.25rem;
    font-size: 0.85rem;
}

.text-warning {
    color: #ff9800;
    font-weight: 600;
}

.btn-block {
    width: 100%;
}

.tabla-final-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
}

.tabla-final-card h3 {
    margin: 0 0 1rem 0;
    color: #1a237e;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tabla-scroll {
    overflow-x: auto;
    margin-bottom: 1rem;
}

.tabla-simple {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.9rem;
}

.tabla-simple thead {
    background: #f8f9fa;
}

.tabla-simple th {
    padding: 0.75rem 0.5rem;
    text-align: center;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.tabla-simple td {
    padding: 0.5rem;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
}

.equipo-nombre-simple {
    text-align: left !important;
    font-weight: 600;
}

.campeon-row {
    background: rgba(255, 215, 0, 0.1);
    font-weight: 600;
}

.playoff-row {
    background: rgba(76, 175, 80, 0.05);
}

.dg-pos {
    color: #28a745;
    font-weight: 600;
}

.dg-neg {
    color: #dc3545;
    font-weight: 600;
}

.tabla-actions {
    text-align: center;
}

@media (max-width: 968px) {
    .opciones-grid {
        grid-template-columns: 1fr;
    }

    .campeon-body {
        flex-direction: column;
    }

    .clasificados-list {
        grid-template-columns: 1fr;
    }
}
</style>

<?php
$stmt_torneo->close();
$stmt_check->close();
$stmt_equipos->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
