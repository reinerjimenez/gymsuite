<?php
// modules/planes/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo el admin gestiona los planes

$gimnasio_id = get_gimnasio_id();

// Activar o desactivar plan si viene por GET
if (isset($_GET['toggle']) && isset($_GET['id'])) {
    $plan_id = (int)$_GET['id'];
    $estado_nuevo = $_GET['toggle'] === '1' ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE planes SET activo = ? WHERE id = ? AND gimnasio_id = ?");
    $stmt->execute([$estado_nuevo, $plan_id, $gimnasio_id]);
    
    mensaje_flash('success', 'El estado del plan ha sido actualizado.');
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM planes WHERE gimnasio_id = ? ORDER BY activo DESC, precio ASC");
$stmt->execute([$gimnasio_id]);
$planes = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-h2 mb-1">Planes de Membresía</h1>
            <p class="text-muted">Gestiona las tarifas y duraciones ofrecidas a los clientes.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">Nuevo Plan</a>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nombre del Plan</th>
                        <th>Duración</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($planes as $plan): ?>
                    <tr>
                        <td>
                            <div class="font-semibold text-white"><?= h($plan['nombre']) ?></div>
                        </td>
                        <td><?= (int)$plan['duracion_dias'] ?> días</td>
                        <td class="font-bold text-green-400"><?= formatear_moneda($plan['precio']) ?></td>
                        <td>
                            <?php if($plan['activo']): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inactivo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="editar.php?id=<?= $plan['id'] ?>" class="nav-link" style="padding: 0.5rem;" title="Editar Plan">
                                    <svg class="nav-icon" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                </a>
                                <?php if($plan['activo']): ?>
                                    <a href="?id=<?= $plan['id'] ?>&toggle=0" class="nav-link danger require-confirm" data-confirm="¿Desea desactivar este plan? Ya no estará disponible para nuevas suscripciones." style="padding: 0.5rem;" title="Desactivar">
                                        <svg class="nav-icon" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </a>
                                <?php else: ?>
                                    <a href="?id=<?= $plan['id'] ?>&toggle=1" class="nav-link" style="padding: 0.5rem; color: #22C55E;" title="Activar">
                                        <svg class="nav-icon" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($planes)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 py-4">No hay planes creados. <a href="crear.php" class="text-primary">Crea el primero aquí</a>.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
