<?php
// modules/pagos/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();

// Query con joins para tener nombre del cliente y de quien generó el registro
$query = "
    SELECT p.*, s.nombre as socio_nombre, s.apellido as socio_apellido, u.usuario as registrado_por_login 
    FROM pagos p
    JOIN socios s ON p.socio_id = s.id
    LEFT JOIN usuarios u ON p.registrado_por = u.id
    WHERE p.gimnasio_id = ?
    ORDER BY p.id DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute([$gimnasio_id]);
$pagos = $stmt->fetchAll();

// Sumatoria total mensual (simplificada a todo el historial por el momento)
$total_ingresos = array_reduce($pagos, fn($c, $i) => $c + $i['monto'], 0);

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-h2 mb-1">Registro de Ingresos</h1>
            <p class="text-muted">Historial de pagos de todos los socios.</p>
        </div>
    </div>

    <!-- Stats -->
    <div class="dashboard-grid cols-3 mb-6">
        <div class="stat-card">
            <span class="stat-title">Total Recaudado</span>
            <span class="stat-value text-green-400"><?= formatear_moneda($total_ingresos) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Transacciones</span>
            <span class="stat-value"><?= count($pagos) ?></span>
        </div>
    </div>

    <!-- Tabla -->
    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Socio</th>
                        <th>Monto</th>
                        <th>Método</th>
                        <th>Cajero / Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($pagos as $pago): ?>
                    <tr>
                        <td class="text-sm font-semibold"><?= formatear_fecha($pago['fecha']) ?></td>
                        <td>
                            <a href="../socios/perfil.php?id=<?= $pago['socio_id'] ?>" class="text-white hover:text-primary transition font-semibold">
                                <?= h($pago['socio_nombre'] . ' ' . $pago['socio_apellido']) ?>
                            </a>
                        </td>
                        <td class="font-bold text-green-400"><?= formatear_moneda($pago['monto']) ?></td>
                        <td>
                            <span class="badge" style="background: rgba(255,255,255,0.1); color: #fff;">
                                <?= h(strtoupper($pago['metodo'])) ?>
                            </span>
                        </td>
                        <td class="text-xs text-gray-400">
                            <?= h($pago['registrado_por_login'] ?: 'Sistema') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(empty($pagos)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-gray-500 py-6">No hay pagos registrados aún.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
