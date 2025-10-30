<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['torneo_id'])) {
    header("Location: gestionar_torneos.php?error=ID de torneo no especificado.");
    exit;
}

$torneo_id = (int)$_GET['torneo_id'];


$stmt_torneo = $conn->prepare("SELECT t.*, d.nombre_mostrado AS deporte
                                FROM torneos t
                                JOIN deportes d ON t.deporte_id = d.id
                                WHERE t.id = ?");
$stmt_torneo->bind_param("i", $torneo_id);
$stmt_torneo->execute();
$torneo = $stmt_torneo->get_result()->fetch_assoc();

if (!$torneo) {
    header("Location: gestionar_torneos.php?error=Torneo no encontrado.");
    exit;
}


$stmt_equipos = $conn->prepare("SELECT p.id, p.nombre_mostrado, p.nombre_corto, p.url_logo
                                 FROM torneo_participantes tp
                                 JOIN participantes p ON tp.participante_id = p.id
                                 WHERE tp.torneo_id = ?
                                 ORDER BY p.nombre_mostrado");
$stmt_equipos->bind_param("i", $torneo_id);
$stmt_equipos->execute();
$equipos_result = $stmt_equipos->get_result();


$tabla = [];
while ($equipo = $equipos_result->fetch_assoc()) {
    $tabla[$equipo['id']] = [
        'id' => $equipo['id'],
        'nombre' => $equipo['nombre_mostrado'],
        'nombre_corto' => $equipo['nombre_corto'],
        'logo' => $equipo['url_logo'],
        'pj' => 0,  
        'pg' => 0,  
        'pe' => 0,  
        'pp' => 0,  
        'gf' => 0,  
        'gc' => 0,  
        'dg' => 0,  
        'pts' => 0  
    ];
}


$sql_partidos = "SELECT p.participante_local_id, p.participante_visitante_id,
                 p.marcador_local, p.marcador_visitante
                 FROM partidos p
                 WHERE p.torneo_id = ? AND p.estado_id = 5
                 ORDER BY p.inicio_partido";

$stmt_partidos = $conn->prepare($sql_partidos);
$stmt_partidos->bind_param("i", $torneo_id);
$stmt_partidos->execute();
$partidos = $stmt_partidos->get_result();


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


foreach ($tabla as &$equipo) {
    $equipo['dg'] = $equipo['gf'] - $equipo['gc'];
}


usort($tabla, function($a, $b) {
    if ($b['pts'] != $a['pts']) {
        return $b['pts'] - $a['pts'];
    }
    if ($b['dg'] != $a['dg']) {
        return $b['dg'] - $a['dg'];
    }
    return $b['gf'] - $a['gf'];
});

?>

<main class="admin-page">
    <div class="page-header">
        <h1>Tabla de Posiciones - <?php echo htmlspecialchars($torneo['nombre']); ?></h1>
        <div>
            <a href="gestionar_jornadas.php?torneo_id=<?php echo $torneo_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver a Jornadas
            </a>
            <a href="gestionar_torneos.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Ver Torneos
            </a>
        </div>
    </div>

    <div class="torneo-info-card">
        <div class="info-item">
            <span class="info-label">Deporte:</span>
            <span class="info-value"><?php echo htmlspecialchars($torneo['deporte']); ?></span>
        </div>
        <div class="info-item">
            <span class="info-label">Equipos:</span>
            <span class="info-value"><?php echo count($tabla); ?></span>
        </div>
    </div>

    <?php if (count($tabla) == 0): ?>
        <div class="empty-state">
            <i class="fas fa-users-slash" style="font-size: 3rem; color: #ccc;"></i>
            <h3>No hay equipos inscritos</h3>
            <p>Inscribe equipos para ver la tabla de posiciones.</p>
        </div>
    <?php else: ?>
        <div class="tabla-container">
            <table class="tabla-posiciones">
                <thead>
                    <tr>
                        <th class="pos-col">#</th>
                        <th class="equipo-col">Equipo</th>
                        <th class="stat-col" title="Partidos Jugados">PJ</th>
                        <th class="stat-col" title="Partidos Ganados">PG</th>
                        <th class="stat-col" title="Partidos Empatados">PE</th>
                        <th class="stat-col" title="Partidos Perdidos">PP</th>
                        <th class="stat-col" title="Goles a Favor">GF</th>
                        <th class="stat-col" title="Goles en Contra">GC</th>
                        <th class="stat-col" title="Diferencia de Goles">DG</th>
                        <th class="pts-col" title="Puntos">Pts</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $posicion = 1;
                    foreach ($tabla as $equipo):
                        $clase_posicion = '';
                        if ($posicion <= 8) {
                            $clase_posicion = 'clasificado-playoffs';
                        }
                        if ($posicion == 1) {
                            $clase_posicion = 'lider';
                        }
                    ?>
                        <tr class="clickable-row <?php echo $clase_posicion; ?>" data-equipo-id="<?php echo $equipo['id']; ?>">
                            <td class="pos-col">
                                <span class="posicion-num"><?php echo $posicion; ?></span>
                            </td>
                            <td class="equipo-col">
                                <div class="equipo-info">
                                    <div class="equipo-logo-small">
                                        <?php if ($equipo['logo']): ?>
                                            <img src="<?php echo htmlspecialchars($equipo['logo']); ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="equipo-nombres">
                                        <strong><?php echo htmlspecialchars($equipo['nombre']); ?></strong>
                                        <small><?php echo htmlspecialchars($equipo['nombre_corto']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td class="stat-col"><?php echo $equipo['pj']; ?></td>
                            <td class="stat-col"><?php echo $equipo['pg']; ?></td>
                            <td class="stat-col"><?php echo $equipo['pe']; ?></td>
                            <td class="stat-col"><?php echo $equipo['pp']; ?></td>
                            <td class="stat-col"><?php echo $equipo['gf']; ?></td>
                            <td class="stat-col"><?php echo $equipo['gc']; ?></td>
                            <td class="stat-col <?php echo $equipo['dg'] >= 0 ? 'dg-positiva' : 'dg-negativa'; ?>">
                                <?php echo $equipo['dg'] >= 0 ? '+' : ''; ?><?php echo $equipo['dg']; ?>
                            </td>
                            <td class="pts-col">
                                <strong><?php echo $equipo['pts']; ?></strong>
                            </td>
                        </tr>
                    <?php
                        $posicion++;
                    endforeach;
                    ?>
                </tbody>
            </table>
        </div>

        <div class="leyenda">
            <div class="leyenda-item">
                <span class="leyenda-color lider-color"></span>
                <span>Líder / Campeón</span>
            </div>
            <div class="leyenda-item">
                <span class="leyenda-color playoffs-color"></span>
                <span>Clasificados a Playoffs (Top 8)</span>
            </div>
        </div>
    <?php endif; ?>
    
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.tabla-posiciones tbody tr.clickable-row');
    rows.forEach(row => {
        row.addEventListener('click', function() {
            const equipoId = this.dataset.equipoId;
            if (equipoId) {
                window.location.href = `ver_plantel.php?equipo_id=${equipoId}`;
            }
        });
    });
});
</script>
<style>
.torneo-info-card {
    background: white;
    padding: 1rem 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    display: flex;
    gap: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.info-item {
    display: flex;
    gap: 0.5rem;
    align-items: baseline;
}

.info-label {
    font-size: 0.9rem;
    color: #666;
    font-weight: 600;
}

.info-value {
    font-size: 1rem;
    color: #1a237e;
    font-weight: bold;
}

.tabla-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    margin-bottom: 2rem;
}

.tabla-posiciones {
    width: 100%;
    border-collapse: collapse;
}

.tabla-posiciones thead {
    background: linear-gradient(135deg, #1a237e 0%, #303f9f 100%);
    color: white;
}

.tabla-posiciones th {
    padding: 1rem 0.75rem;
    text-align: center;
    font-weight: 600;
    font-size: 0.9rem;
    white-space: nowrap;
}

.tabla-posiciones td {
    padding: 0.75rem;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
}

.tabla-posiciones tbody tr:hover {
    background: #f8f9fa;
}

.pos-col {
    width: 50px;
}

.equipo-col {
    text-align: left !important;
    min-width: 250px;
}

.stat-col {
    width: 60px;
    font-size: 0.95rem;
}

.pts-col {
    width: 70px;
    background: #f8f9fa;
}

.pts-col strong {
    font-size: 1.1rem;
    color: #1a237e;
}

.posicion-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e0e0e0;
    font-weight: bold;
    color: #666;
}

.equipo-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.equipo-logo-small {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f5f5f5;
    border-radius: 6px;
    flex-shrink: 0;
}

.equipo-logo-small img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.equipo-logo-small i {
    font-size: 1.5rem;
    color: #999;
}

.equipo-nombres {
    display: flex;
    flex-direction: column;
    gap: 0.15rem;
}

.equipo-nombres strong {
    font-size: 0.95rem;
    color: #333;
}

.equipo-nombres small {
    font-size: 0.8rem;
    color: #666;
}

.dg-positiva {
    color: #28a745;
    font-weight: 600;
}

.dg-negativa {
    color: #dc3545;
    font-weight: 600;
}


.lider .posicion-num {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1a237e;
    box-shadow: 0 2px 4px rgba(255, 215, 0, 0.4);
}

.lider {
    background: linear-gradient(90deg, rgba(255, 215, 0, 0.1) 0%, transparent 100%);
}

.clasificado-playoffs .posicion-num {
    background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
    color: white;
}

.clasificado-playoffs {
    background: linear-gradient(90deg, rgba(76, 175, 80, 0.05) 0%, transparent 100%);
}

.leyenda {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    padding: 1rem;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.leyenda-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
}

.leyenda-color {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: inline-block;
}

.lider-color {
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
}

.playoffs-color {
    background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%);
}

@media (max-width: 968px) {
    .tabla-posiciones {
        font-size: 0.85rem;
    }

    .equipo-col {
        min-width: 180px;
    }

    .equipo-nombres strong {
        font-size: 0.85rem;
    }

    .stat-col {
        width: 45px;
        padding: 0.5rem 0.25rem;
    }

    .pts-col {
        width: 60px;
    }
}

@media (max-width: 768px) {
    .tabla-container {
        overflow-x: auto;
    }

    .tabla-posiciones th,
    .tabla-posiciones td {
        padding: 0.5rem 0.25rem;
    }
}
.clickable-row:hover {
    cursor: pointer;
    background-color: #f0f0f0 !important;
    transition: background-color 0.2s ease-in-out;
}
</style>

<?php
$stmt_torneo->close();
$stmt_equipos->close();
$stmt_partidos->close();
require_once 'admin_footer.php';
?>
