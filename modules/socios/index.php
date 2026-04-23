<?php
// modules/socios/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();

// Filtros básicos por GET
$search = trim($_GET['q'] ?? '');
$filter_estado = $_GET['estado'] ?? 'todos'; // todos, activos, inactivos

$query = "
    SELECT s.*, max_m.fecha_fin_membresia
    FROM socios s
    LEFT JOIN (
        SELECT socio_id, MAX(fecha_fin) as fecha_fin_membresia
        FROM membresias
        WHERE gimnasio_id = ?
        GROUP BY socio_id
    ) max_m ON max_m.socio_id = s.id
    WHERE s.gimnasio_id = ?
";
$params = [$gimnasio_id, $gimnasio_id];

if ($search !== '') {
    $query .= " AND (s.nombre LIKE ? OR s.apellido LIKE ? OR s.dni LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_estado === 'activos') {
    $query .= " AND s.activo = 1";
} elseif ($filter_estado === 'inactivos') {
    $query .= " AND s.activo = 0";
}

$query .= " ORDER BY s.id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$socios = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-h2 mb-1">Socios Registrados</h1>
            <p class="text-muted">Directorio completo de miembros del gimnasio.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">Nuevo Socio</a>
    </div>

    <?php mostrar_flash(); ?>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card mb-6" style="padding: 1rem;">
        <form method="GET" action="" class="flex items-center gap-4 flex-wrap">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" name="q" value="<?= h($search) ?>" placeholder="Buscar por DNI, Nombre o Apellido..." class="form-control" style="margin-bottom: 0;">
            </div>
            <div>
                <select name="estado" class="form-control" style="margin-bottom: 0;">
                    <option value="todos" <?= $filter_estado==='todos'?'selected':'' ?>>Ver Todos</option>
                    <option value="activos" <?= $filter_estado==='activos'?'selected':'' ?>>Solo Habilitados</option>
                    <option value="inactivos" <?= $filter_estado==='inactivos'?'selected':'' ?>>Inactivos (Baja)</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">Filtrar</button>
            <?php if($search || $filter_estado !== 'todos'): ?>
                <a href="index.php" class="btn btn-secondary text-red-400">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>N° DNI</th>
                        <th>Socio</th>
                        <th>Contacto</th>
                        <th>Membresía</th>
                        <th>Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($socios as $socio): ?>
                    <?php
                        $estado_socio = (int)$socio['activo'] === 1;
                        if (!$estado_socio) {
                            $badge_clase = "badge-danger";
                            $badge_texto = "INACTIVO";
                            $estado_texto = "<span class='text-red-400 text-xs'>Socio Bloqueado</span>";
                        } else {
                            if (!$socio['fecha_fin_membresia']) {
                                $badge_clase = "badge-warning";
                                $badge_texto = "SIN PLAN";
                                $estado_texto = "Sin membresías previas";
                            } else {
                                $estado_mem = calcular_estado_membresia($socio['fecha_fin_membresia']);
                                $dias = dias_restantes($socio['fecha_fin_membresia']);
                                if ($estado_mem === 'activa') {
                                    $badge_clase = "badge-success";
                                    $badge_texto = "ACTIVO";
                                    $estado_texto = "Vence en $dias días";
                                } elseif ($estado_mem === 'por_vencer') {
                                    $badge_clase = "badge-warning";
                                    $badge_texto = "POR VENCER";
                                    $estado_texto = "Vence en $dias días";
                                } else {
                                    $badge_clase = "badge-danger";
                                    $badge_texto = "VENCIDA";
                                    $estado_texto = "Venció hace " . abs($dias) . " días";
                                }
                            }
                        }
                    ?>
                    <tr>
                        <td class="text-sm font-bold text-gray-300"><?= h($socio['dni']) ?></td>
                        <td style="white-space: nowrap;">
                            <div class="font-semibold text-white"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></div>
                            <div class="text-xs text-gray-500">Inscrito: <?= formatear_fecha($socio['fecha_inscripcion']) ?></div>
                        </td>
                        <td style="white-space: nowrap;">
                            <div class="text-sm"><?= h($socio['telefono'] ?: 'N/A') ?></div>
                        </td>
                        <td style="white-space: nowrap;">
                            <div><span class="badge <?= $badge_clase ?>"><?= $badge_texto ?></span></div>
                            <div class="text-xs mt-1 text-gray-400"><?= $estado_texto ?></div>
                        </td>
                        <td>
                            <?php if($estado_socio): ?>
                                <span class="badge badge-success">PERMITIDO</span>
                            <?php else: ?>
                                <span class="badge badge-danger">BLOQUEADO</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="perfil.php?id=<?= $socio['id'] ?>" class="btn btn-success" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Ver Perfil</a>
                                <button type="button" onclick="registrarEntradaManual(<?= $socio['id'] ?>)" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Registrar Entrada</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($socios)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-gray-500 py-6">No se encontraron socios con los filtros actuales.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function registrarEntradaManual(socioId) {
    if (!confirm('¿Confirmas el ingreso manual de este socio?')) return;
    
    fetch('<?= BASE_URL ?>ajax/registrar_acceso.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'socio_id=' + socioId + '&metodo=manual'
    })
    .then(res => res.json())
    .then(data => {
        if(data.exito) {
            alert('✅ ' + data.mensaje);
        } else {
            alert('❌ ' + data.mensaje);
        }
    })
    .catch(err => {
        alert('❌ Error de conexión con el servidor.');
        console.error(err);
    });
}
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
