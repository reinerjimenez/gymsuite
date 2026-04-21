<?php
// modules/superadmin/index.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['superadmin']);

// Acciones sobre gimnasios (Suspender / Activar)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_estado') {
    $gym_id = (int)$_POST['id'];
    $stmt = $pdo->prepare("SELECT estado FROM gimnasios WHERE id = ?");
    $stmt->execute([$gym_id]);
    $g = $stmt->fetch();
    
    if ($g) {
        $nuevo_estado = $g['estado'] === 'activo' ? 'suspendido' : 'activo';
        $pdo->prepare("UPDATE gimnasios SET estado = ? WHERE id = ?")->execute([$nuevo_estado, $gym_id]);
        mensaje_flash('success', "Gimnasio $nuevo_estado correctamente.");
    }
    header("Location: index.php");
    exit;
}

// Acción de Eliminación (Hard Delete en cascada manual)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'eliminar_gym') {
    $gym_id = (int)$_POST['id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Eliminar ingresos y accesos
        $pdo->prepare("DELETE FROM accesos WHERE gimnasio_id = ?")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM pagos WHERE gimnasio_id = ?")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM membresias WHERE gimnasio_id = ?")->execute([$gym_id]);
        
        // 2. Eliminar ejercicios asociados a rutinas del gym, y luego las rutinas
        $pdo->prepare("DELETE FROM ejercicios WHERE rutina_id IN (SELECT id FROM rutinas WHERE gimnasio_id = ?)")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM socio_rutina WHERE socio_id IN (SELECT id FROM socios WHERE gimnasio_id = ?)")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM rutinas WHERE gimnasio_id = ?")->execute([$gym_id]);
        
        // 3. Eliminar socios, planes, usuarios (admins/recepcionistas) y config
        $pdo->prepare("DELETE FROM socios WHERE gimnasio_id = ?")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM planes WHERE gimnasio_id = ?")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM usuarios WHERE gimnasio_id = ?")->execute([$gym_id]);
        $pdo->prepare("DELETE FROM configuracion_gym WHERE gimnasio_id = ?")->execute([$gym_id]);
        
        // 4. Finalmente eliminar el gimnasio
        $pdo->prepare("DELETE FROM gimnasios WHERE id = ?")->execute([$gym_id]);
        
        $pdo->commit();
        mensaje_flash('success', 'Gimnasio y todos sus datos han sido eliminados por completo.');
    } catch (Exception $e) {
        $pdo->rollBack();
        mensaje_flash('error', 'No se pudo eliminar el gimnasio: ' . $e->getMessage());
    }
    
    header("Location: index.php");
    exit;
}

// 1. KPIs Globales
$stmtKPI = $pdo->query("
    SELECT 
        (SELECT COUNT(*) FROM gimnasios) as total_gyms,
        (SELECT COUNT(*) FROM gimnasios WHERE estado = 'activo') as gyms_activos,
        (SELECT COUNT(*) FROM socios) as total_socios_red,
        (SELECT SUM(monto) FROM pagos) as total_ingresos_red
");
$kpis = $stmtKPI->fetch();

// 2. Lista de Gimnasios
$stmtList = $pdo->query("
    SELECT g.*, 
           (SELECT COUNT(*) FROM socios WHERE gimnasio_id = g.id) as cant_socios,
           c.metodo_acceso
    FROM gimnasios g
    LEFT JOIN configuracion_gym c ON c.gimnasio_id = g.id
    ORDER BY g.id DESC
");
$gimnasios = $stmtList->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <h1 class="text-h2" style="margin-bottom:0.25rem;">Panel Multi-Tenant (SaaS)</h1>
            <p class="text-muted">Gestión global de todos los gimnasios inscritos en la plataforma.</p>
        </div>
        <div>
            <a href="crear.php" class="btn btn-primary" style="display:flex;align-items:center;gap:0.5rem;text-decoration:none;">
                <svg style="width:1.2rem;height:1.2rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nuevo Gimnasio Cliente
            </a>
        </div>
    </div>

    <?php mostrar_flash(); ?>

    <?php if (isset($_SESSION['nuevo_gym'])): ?>
    <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);border-radius:0.75rem;padding:1.5rem;margin-bottom:1.5rem;">
        <h3 style="color:#22c55e;font-weight:700;margin-bottom:0.75rem;font-size:1.1rem;">¡Gimnasio Registrado Exitosamente!</h3>
        <p style="color:white;margin-bottom:0.75rem;">Se ha generado el administrador de <strong><?= h($_SESSION['nuevo_gym']['gym']) ?></strong>. Entrega estos datos al cliente (solo se muestran esta vez):</p>
        <div style="background:rgba(0,0,0,0.5);padding:1rem;border-radius:0.5rem;font-family:monospace;font-size:1.1rem;margin-bottom:0.5rem;">
            <div><span style="color:var(--color-text-muted);">Usuario:</span> <span style="color:white;font-weight:700;user-select:all;"><?= h($_SESSION['nuevo_gym']['usuario']) ?></span></div>
            <div style="margin-top:0.25rem;"><span style="color:var(--color-text-muted);">Contraseña:</span> <span style="color:#eab308;font-weight:700;user-select:all;"><?= h($_SESSION['nuevo_gym']['password']) ?></span></div>
        </div>
        <p style="font-size:0.8rem;color:var(--color-text-muted);">El cliente podrá cambiar esta contraseña más adelante.</p>
    </div>
    <?php unset($_SESSION['nuevo_gym']); endif; ?>

    <!-- KPIs -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Gimnasios Totales</p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:white;"><?= $kpis['total_gyms'] ?></p>
        </div>
        <div class="card">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Gimnasios Activos</p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:#22c55e;"><?= $kpis['gyms_activos'] ?></p>
        </div>
        <div class="card">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Socios en la Red</p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:var(--color-primary);"><?= number_format($kpis['total_socios_red']) ?></p>
        </div>
        <div class="card">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Volumen Procesado global</p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:white;"><?= formatear_moneda($kpis['total_ingresos_red']) ?></p>
        </div>
    </div>

    <!-- Lista de Clientes -->
    <div class="card">
        <h3 class="text-h3" style="margin-bottom:1rem;">Clientes del Sistema</h3>
        <?php if (empty($gimnasios)): ?>
        <p style="text-align:center;color:var(--color-text-muted);padding:3rem 1rem;">No hay clientes registrados en la plataforma.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Gimnasio</th>
                        <th>Fecha de Registro</th>
                        <th>Método Accesos</th>
                        <th>Socios</th>
                        <th>Estado</th>
                        <th style="text-align:right;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($gimnasios as $g): ?>
                    <tr>
                        <td>
                            <div style="font-weight:700;"><?= h($g['nombre']) ?></div>
                            <?php if($g['ruc']): ?><div style="font-size:0.75rem;color:var(--color-text-muted);">RUC: <?= h($g['ruc']) ?></div><?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y', strtotime($g['created_at'])) ?></td>
                        <td>
                            <span style="background:rgba(255,255,255,0.05);padding:0.2rem 0.5rem;border-radius:0.25rem;font-size:0.75rem;text-transform:uppercase;">
                                <?= h($g['metodo_acceso'] ?? 'dni') ?>
                            </span>
                        </td>
                        <td><span style="font-weight:700;"><?= $g['cant_socios'] ?></span></td>
                        <td>
                            <?php if ($g['estado'] === 'activo'): ?>
                                <span class="badge" style="background:rgba(34,197,94,0.1);color:#22c55e;">Activo</span>
                            <?php else: ?>
                                <span class="badge" style="background:rgba(239,68,68,0.1);color:#ef4444;">Suspendido</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:0.5rem;justify-content:flex-end;">
                                <form method="POST" onsubmit="return confirm('¿Seguro de cambiar el estado de este gimnasio?');" style="margin:0;">
                                    <input type="hidden" name="action" value="toggle_estado">
                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                    <button type="submit" class="btn" title="<?= $g['estado'] === 'activo' ? 'Suspender' : 'Reactivar' ?>" style="display:flex;align-items:center;gap:0.35rem;padding:0.4rem 0.75rem;font-size:0.8rem;min-height:32px;border-radius:0.5rem;font-weight:600;background:<?= $g['estado'] === 'activo' ? 'rgba(239,68,68,0.1)' : 'rgba(34,197,94,0.1)' ?>;color:<?= $g['estado'] === 'activo' ? '#ef4444' : '#22c55e' ?>;border:1px solid transparent;transition:all 0.2s;" onmouseover="this.style.borderColor='<?= $g['estado'] === 'activo' ? 'rgba(239,68,68,0.3)' : 'rgba(34,197,94,0.3)' ?>'" onmouseout="this.style.borderColor='transparent'">
                                        <?php if ($g['estado'] === 'activo'): ?>
                                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Suspender
                                        <?php else: ?>
                                            <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            Reactivar
                                        <?php endif; ?>
                                    </button>
                                </form>
                                <form method="POST" onsubmit="return confirm('¡ADVERTENCIA! Vas a eliminar este gimnasio y TODOS sus accesos, pagos, socios y rutinas de forma irreversible. ¿Estás absolutamente seguro de continuar?');" style="margin:0;">
                                    <input type="hidden" name="action" value="eliminar_gym">
                                    <input type="hidden" name="id" value="<?= $g['id'] ?>">
                                    <button type="submit" class="btn" title="Eliminar definitivamente" style="display:flex;align-items:center;justify-content:center;padding:0.4rem;min-height:32px;min-width:32px;border-radius:0.5rem;background:rgba(239,68,68,0.1);color:#ef4444;border:1px solid transparent;transition:all 0.2s;" onmouseover="this.style.background='rgba(239,68,68,0.2)';this.style.borderColor='rgba(239,68,68,0.4)'" onmouseout="this.style.background='rgba(239,68,68,0.1)';this.style.borderColor='transparent'">
                                        <svg style="width:1.1rem;height:1.1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
