<?php
require_once 'auth_admin.php';
require_once 'admin_header.php';

if (!isset($_GET['equipo_id'])) {
     header("Location: gestionar_equipos.php?error=ID de equipo no especificado.");
     exit;
}
$equipo_id = (int)$_GET['equipo_id'];

$stmt_deporte = $conn->prepare("SELECT d.codigo FROM participantes p JOIN deportes d ON p.deporte_id = d.id WHERE p.id = ?");
$stmt_deporte->bind_param("i", $equipo_id);
$stmt_deporte->execute();
$result_deporte = $stmt_deporte->get_result();
$deporte_info = $result_deporte->fetch_assoc();
$codigo_deporte = $deporte_info['codigo'] ?? 'football';
$stmt_deporte->close();

$is_edit = false;
$jugador = [
    'nombre_jugador' => '',
    'posicion' => '',
    'url_foto' => '',
    'edad' => '',
    'grado' => '',
    'numero_camiseta' => '',
    'plantel_id' => '',
    'goles' => 0,
    'asistencias' => 0,
    'porterias_cero' => 0
];
$foto_preview = '../../img/jugadores/default.png';
$page_title = 'Agregar Jugador';

if (isset($_GET['edit_id'])) {
    $is_edit = true;
    $jugador_id = (int)$_GET['edit_id'];
    $page_title = 'Editar Jugador';
    
    $stmt = $conn->prepare("SELECT * FROM miembros_plantel WHERE id = ?");
    $stmt->bind_param("i", $jugador_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $jugador = $result->fetch_assoc();
        if (!empty($jugador['url_foto'])) {
            $foto_preview = htmlspecialchars($jugador['url_foto']);
        }
    } else {
        header("Location: ver_plantel.php?equipo_id=$equipo_id&error=Jugador no encontrado.");
        exit;
    }
    $stmt->close();
    
} else {
    if (!isset($_GET['plantel_id'])) {
        header("Location: ver_plantel.php?equipo_id=$equipo_id&error=ID de plantel no especificado.");
        exit;
    }
    $jugador['plantel_id'] = (int)$_GET['plantel_id'];
}
?>

<main class="admin-page">
    <div class="page-header">
        <h1><?php echo $page_title; ?></h1>
        <a href="ver_plantel.php?equipo_id=<?php echo $equipo_id; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver al Plantel
        </a>
    </div>

    <div class="form-container-admin">
        <form action="jugador_process.php" method="POST" class="admin-form" enctype="multipart/form-data">
            
            <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
            <input type="hidden" name="plantel_id" value="<?php echo $jugador['plantel_id']; ?>">
            <input type="hidden" name="equipo_id" value="<?php echo $equipo_id; ?>"> <?php if ($is_edit): ?>
                <input type="hidden" name="jugador_id" value="<?php echo $jugador['id']; ?>">
                <input type="hidden" name="current_foto_path" value="<?php echo htmlspecialchars($jugador['url_foto']); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="nombre_jugador">Nombre Completo del Jugador:</label>
                <input type="text" id="nombre_jugador" name="nombre_jugador" value="<?php echo htmlspecialchars($jugador['nombre_jugador']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="posicion">Posición:</label>
                    <select id="posicion" name="posicion" required>
                        <option value="">Seleccione una posición</option>
                        <?php
                        $posiciones = [];
                        
                        $posicion_actual = $jugador['posicion'] ?? ''; 

                        switch($codigo_deporte) {
                            case 'football':
                            case 'futsal_3':
                            case 'futsal_4':
                            case 'futsal_5':
                                $posiciones = [
                                    'Portero', 'Defensa', 'Mediocampista', 'Delantero',
                                    'Suplente Portero', 'Suplente Defensa', 'Suplente Mediocampista', 'Suplente Delantero'
                                ];
                                break;

                            case 'basketball':
                            case 'basketball_3':
                            case 'basketball_4':
                            case 'basketball_5':
                                $posiciones = [
                                    'Base', 'Escolta', 'Alero', 'Ala-Pívot', 'Pívot',
                                    'Suplente Base', 'Suplente Escolta', 'Suplente Alero', 'Suplente Ala-Pívot', 'Suplente Pívot'
                                ];
                                break;

                            case 'volleyball':
                                $posiciones = [
                                    'Colocador', 'Atacante Externo', 'Central', 'Opuesto', 'Líbero',
                                    'Suplente Colocador', 'Suplente Atacante', 'Suplente Central', 'Suplente Opuesto', 'Suplente Líbero'
                                ];
                                break;

                            case 'table_tennis':
                            case 'chess':
                            default:
                                $posiciones = [
                                    'Jugador', 'Suplente'
                                ];
                                break;
                        }

                        foreach($posiciones as $pos) {
                            $selected = ($posicion_actual == $pos) ? 'selected' : '';
                            echo "<option value=\"$pos\" $selected>$pos</option>\n";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="numero_camiseta">Número de Camiseta:</label>
                    <input type="number" id="numero_camiseta" name="numero_camiseta" value="<?php echo htmlspecialchars($jugador['numero_camiseta']); ?>" min="0">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edad">Edad:</label>
                    <input type="number" id="edad" name="edad" value="<?php echo htmlspecialchars($jugador['edad']); ?>" min="10">
                </div>
                <div class="form-group">
                    <label for="grado">Grado:</label>
                    <input type="text" id="grado" name="grado" value="<?php echo htmlspecialchars($jugador['grado']); ?>" placeholder="Ej: 9°A, 2°B">
                </div>
            </div>

            <div class="form-section-title">
                <h3>Estadísticas del Jugador</h3>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="goles">Goles:</label>
                    <input type="number" id="goles" name="goles" value="<?php echo htmlspecialchars($jugador['goles']); ?>" min="0" placeholder="0">
                </div>
                <div class="form-group">
                    <label for="asistencias">Asistencias:</label>
                    <input type="number" id="asistencias" name="asistencias" value="<?php echo htmlspecialchars($jugador['asistencias']); ?>" min="0" placeholder="0">
                </div>
            </div>

            <div class="form-group">
                <label for="porterias_cero">Porterías a Cero (Solo Porteros):</label>
                <input type="number" id="porterias_cero" name="porterias_cero" value="<?php echo htmlspecialchars($jugador['porterias_cero']); ?>" min="0" placeholder="0">
                <small>Este campo es solo para porteros</small>
            </div>
            
            <div class="form-group">
                <label for="foto">Foto del Jugador:</label>
                <input type="file" id="foto" name="foto" class="form-input-file" accept="image/png, image/jpeg, image/webp">
                <div class="form-image-preview">
                    <img src="<?php echo $foto_preview; ?>" alt="Foto Preview" id="foto-preview-img">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $is_edit ? 'Actualizar Jugador' : 'Guardar Jugador'; ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
document.getElementById('foto').addEventListener('change', function(event) {
    var reader = new FileReader();
    reader.onload = function(){
        var output = document.getElementById('foto-preview-img');
        output.src = reader.result;
    };
    reader.readAsDataURL(event.target.files[0]);
});
</script>

<?php
require_once 'admin_footer.php';
?>