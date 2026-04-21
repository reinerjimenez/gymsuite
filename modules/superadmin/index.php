<?php
// modules/superadmin/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['superadmin']);

// Obtener datos básicos de los gimnasios
$stmt = $pdo->prepare("SELECT * FROM gimnasios ORDER BY created_at DESC");
$stmt->execute();
$gimnasios = $stmt->fetchAll();

// Totales rápidos para las cards
$total_gyms = count($gimnasios);
$activos = array_reduce($gimnasios, fn($carry, $g) => $carry + ($g['estado'] === 'activo' ? 1 : 0), 0);
$suspendidos = $total_gyms - $activos;

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-h2 mb-1">Panel Superadmin</h1>
            <p class="text-muted">Gestión global de los gimnasios en el sistema.</p>
        </div>
        <a href="crear.php" class="btn btn-primary">Nuevo Gimnasio</a>
    </div>
    
    <div class="dashboard-grid cols-3">
        <div class="stat-card">
            <span class="stat-title">Total Gyms</span>
            <span class="stat-value"><?= $total_gyms ?></span>
        </div>
        <div class="stat-card success">
            <span class="stat-title success">Activos</span>
            <span class="stat-value success"><?= $activos ?></span>
        </div>
        <div class="stat-card danger">
            <span class="stat-title danger">Suspendidos</span>
            <span class="stat-value danger"><?= $suspendidos ?></span>
        </div>
    </div>
    
    <div class="card">
        <h2 class="text-h3 mb-4">Gimnasios Registrados</h2>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Contacto</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($gimnasios as $gym): ?>
                    <tr>
                        <td><?= $gym['id'] ?></td>
                        <td>
                            <div class="font-semibold text-white"><?= h($gym['nombre']) ?></div>
                            <div class="text-xs text-gray-400">RUC: <?= h($gym['ruc'] ?? 'N/A') ?></div>
                        </td>
                        <td>
                            <div class="text-sm"><?= h($gym['telefono']) ?></div>
                            <div class="text-xs text-gray-400"><?= h($gym['email']) ?></div>
                        </td>
                        <td>
                            <?php if($gym['estado'] === 'activo'): ?>
                                <span class="badge badge-success">Activo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Suspendido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="flex gap-2">
                                <a href="detalle.php?id=<?= $gym['id'] ?>" class="text-gray-400 hover:text-white" title="Ver Detalles">
                                    <svg class="nav-icon" style="width: 1.25rem; height: 1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($gimnasios)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 py-4">No hay gimnasios registrados.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
