<?php
// modules/dashboard/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();

$rol = $_SESSION['rol'];

if ($rol === 'superadmin') {
    header("Location: " . BASE_URL . "modules/superadmin/index.php");
    exit;
} elseif ($rol === 'socio') {
    header("Location: " . BASE_URL . "modules/socio/dashboard.php");
    exit;
}

$gimnasio_id = get_gimnasio_id();

// 1. Ingresos del Mes
$mes_actual = date('Y-m');
$stmt1 = $pdo->prepare("SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE gimnasio_id = ? AND DATE_FORMAT(fecha, '%Y-%m') = ?");
$stmt1->execute([$gimnasio_id, $mes_actual]);
$ingresos_mes = $stmt1->fetchColumn();

// 2. Socios Activos (Tienen membresía estado='activa')
$stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT socio_id) FROM membresias WHERE gimnasio_id = ? AND estado = 'activa'");
$stmt2->execute([$gimnasio_id]);
$socios_activos = $stmt2->fetchColumn();

// 3. Membresías Por Vencer (En los próximos 7 días)
$fecha_hoy = date('Y-m-d');
$fecha_7dias = date('Y-m-d', strtotime('+7 days'));
$stmt3 = $pdo->prepare("SELECT COUNT(*) FROM membresias WHERE gimnasio_id = ? AND estado = 'activa' AND fecha_fin BETWEEN ? AND ?");
$stmt3->execute([$gimnasio_id, $fecha_hoy, $fecha_7dias]);
$por_vencer = $stmt3->fetchColumn();

// 4. Entradas Hoy
$stmt4 = $pdo->prepare("SELECT COUNT(*) FROM accesos WHERE gimnasio_id = ? AND DATE(fecha_hora) = ?");
$stmt4->execute([$gimnasio_id, $fecha_hoy]);
$entradas_hoy = $stmt4->fetchColumn();

// 5. Últimas Entradas Registradas
$stmt5 = $pdo->prepare("
    SELECT a.*, s.nombre, s.apellido, s.foto 
    FROM accesos a 
    JOIN socios s ON a.socio_id = s.id 
    WHERE a.gimnasio_id = ? 
    ORDER BY a.fecha_hora DESC LIMIT 5
");
$stmt5->execute([$gimnasio_id]);
$ultimas_entradas = $stmt5->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 class="text-h2 mb-2">Bienvenido, <?= h($_SESSION['nombre']) ?></h1>
            <p class="text-muted">Resumen de tu gimnasio al <?= date('d/m/Y') ?>.</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>modules/accesos/index.php" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;">
                <svg style="width:1.2rem;height:1.2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Ir a Recepción (Accesos)
            </a>
        </div>
    </div>
    
    <div class="dashboard-grid cols-4 mb-6">
        <div class="stat-card" style="border-left: 4px solid var(--color-primary);">
            <span class="stat-title">Ingresos del Mes</span>
            <span class="stat-value" style="color:var(--color-primary);"><?= formatear_moneda($ingresos_mes) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Socios Activos</span>
            <span class="stat-value text-white"><?= number_format($socios_activos) ?></span>
        </div>
        <div class="stat-card <?= $por_vencer > 0 ? 'alert' : '' ?>">
            <span class="stat-title <?= $por_vencer > 0 ? 'alert' : '' ?>">Por Vencer (7 días)</span>
            <span class="stat-value <?= $por_vencer > 0 ? 'alert' : 'text-white' ?>"><?= number_format($por_vencer) ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Entradas Hoy</span>
            <span class="stat-value text-white"><?= number_format($entradas_hoy) ?></span>
        </div>
    </div>
    
    <div class="dashboard-grid profile-grid">
        <!-- Últimas Entradas -->
        <div class="card">
            <h3 class="text-h3" style="margin-bottom:1.25rem;">Últimas Asistencias</h3>
            <?php if (empty($ultimas_entradas)): ?>
            <p class="text-muted" style="text-align:center;padding:2rem;">Nadie ha ingresado recientemente.</p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <?php foreach($ultimas_entradas as $acc): 
                    $metodos_label = ['dni'=>'DNI','clave'=>'PIN','qr'=>'QR','manual'=>'Manual'];
                ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:1rem;border-bottom:1px solid #262626;">
                    <div style="display:flex;align-items:center;gap:0.875rem;">
                        <?php if ($acc['foto']): ?>
                        <img src="<?= BASE_URL ?>uploads/fotos/<?= h($acc['foto']) ?>" style="width:2.5rem;height:2.5rem;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                        <div style="width:2.5rem;height:2.5rem;border-radius:50%;background:var(--color-surface-hover);display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--color-text-muted);">
                            <?= strtoupper(substr($acc['nombre'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p style="font-weight:700;font-size:0.95rem;margin-bottom:0.1rem;"><?= h($acc['nombre'] . ' ' . $acc['apellido']) ?></p>
                            <p style="font-size:0.75rem;color:var(--color-text-muted);"><?= date('H:i A', strtotime($acc['fecha_hora'])) ?> — Hace <?= round((time() - strtotime($acc['fecha_hora'])) / 60) ?> min</p>
                        </div>
                    </div>
                    <div>
                        <span style="font-size:0.7rem;font-weight:700;padding:0.2rem 0.5rem;border-radius:999px;background:rgba(255,255,255,0.05);color:#a3a3a3;text-transform:uppercase;letter-spacing:0.05em;">
                            <?= $metodos_label[$acc['metodo_usado']] ?? $acc['metodo_usado'] ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Accesos rápidos y notificaciones -->
        <div style="display:flex;flex-direction:column;gap:1.5rem;">
            <?php if ($por_vencer > 0): ?>
            <div class="card" style="background:rgba(234,179,8,0.05);border-color:rgba(234,179,8,0.2);">
                <div style="display:flex;gap:0.75rem;align-items:flex-start;">
                    <svg style="width:1.5rem;height:1.5rem;color:#eab308;flex-shrink:0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <div>
                        <h4 style="font-weight:700;color:#eab308;margin-bottom:0.2rem;">Atención en Recepción</h4>
                        <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:1rem;line-height:1.5;">Hay <strong><?= $por_vencer ?> socios</strong> cuya membresía vence en los próximos 7 días. El sistema de accesos les notificará al ingresar.</p>
                        <a href="<?= BASE_URL ?>modules/socios/index.php?filtro=por_vencer" class="btn" style="background:#eab308;color:#000;font-size:0.8rem;padding:0.4rem 0.8rem;">Ver listado</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card">
                <h3 class="text-h3" style="margin-bottom:1rem;">Accesos Rápidos</h3>
                <div style="display:grid;gap:0.75rem;">
                    <a href="<?= BASE_URL ?>modules/socios/crear.php" style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem;border-radius:0.5rem;background:rgba(255,255,255,0.02);border:1px solid #262626;color:white;text-decoration:none;transition:border 0.2s;" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='#262626'">
                        <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        <span style="font-size:0.9rem;font-weight:600;">Nuevo Socio</span>
                    </a>
                    <a href="<?= BASE_URL ?>modules/pagos/index.php" style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem;border-radius:0.5rem;background:rgba(255,255,255,0.02);border:1px solid #262626;color:white;text-decoration:none;transition:border 0.2s;" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='#262626'">
                        <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span style="font-size:0.9rem;font-weight:600;">Registrar Pago Libre</span>
                    </a>
                    <a href="<?= BASE_URL ?>modules/membresias/index.php" style="display:flex;align-items:center;gap:0.75rem;padding:0.875rem;border-radius:0.5rem;background:rgba(255,255,255,0.02);border:1px solid #262626;color:white;text-decoration:none;transition:border 0.2s;" onmouseover="this.style.borderColor='var(--color-primary)'" onmouseout="this.style.borderColor='#262626'">
                        <svg style="width:1.25rem;height:1.25rem;color:var(--color-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <span style="font-size:0.9rem;font-weight:600;">Renovar Membresía</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
