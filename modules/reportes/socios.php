<?php
// modules/reportes/socios.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);

$gimnasio_id = get_gimnasio_id();

// 1. Estadísticas de Estado Global (basado en la membresía actual)
// Estado 'activa' significa socio activo
$stmtGlobal = $pdo->prepare("
    SELECT 
        (SELECT COUNT(*) FROM socios WHERE gimnasio_id = ?) as total_inscritos,
        (SELECT COUNT(DISTINCT socio_id) FROM membresias WHERE gimnasio_id = ? AND estado = 'activa') as activos_ahora
");
$stmtGlobal->execute([$gimnasio_id, $gimnasio_id]);
$stats = $stmtGlobal->fetch();

$total_inscritos = $stats['total_inscritos'];
$activos = $stats['activos_ahora'];
$inactivos = $total_inscritos - $activos;

// 2. Alertas: Socios "Por Vencer" (En los próximos 7 días)
$fecha_hoy = date('Y-m-d');
$fecha_7dias = date('Y-m-d', strtotime('+7 days'));
$stmtAlerts = $pdo->prepare("
    SELECT s.id, s.nombre, s.apellido, s.telefono, m.fecha_fin, p.nombre as plan_nombre
    FROM membresias m
    JOIN socios s ON s.id = m.socio_id
    JOIN planes p ON p.id = m.plan_id
    WHERE m.gimnasio_id = ? AND m.estado = 'activa' AND m.fecha_fin BETWEEN ? AND ?
    ORDER BY m.fecha_fin ASC
");
$stmtAlerts->execute([$gimnasio_id, $fecha_hoy, $fecha_7dias]);
$por_vencer = $stmtAlerts->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
@media print {
    .sidebar, .header, .btn { display: none !important; }
    .app-wrapper { padding: 0 !important; margin: 0 !important; }
    body { background: white !important; color: black !important; }
    .card { border: 1px solid #ccc !important; background: white !important; color: black !important; box-shadow: none !important; }
    .text-muted { color: #555 !important; }
    .table th { background: #eee !important; color: black !important; border-bottom: 2px solid #aaa !important; }
    .table td { border-bottom: 1px solid #ddd !important; color: black !important; }
    * { print-color-adjust: exact !important; -webkit-print-color-adjust: exact !important; }
}
</style>

<div>
    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:1.5rem;flex-wrap:wrap;gap:1rem;">
        <div>
            <a href="index.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;">
                <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                Volver a Reportes
            </a>
            <h1 class="text-h2">Censo de Socios</h1>
            <p class="text-muted">Estado actual de toda tu base de clientes.</p>
        </div>
        <div>
            <button onclick="window.print()" class="btn btn-secondary" style="min-height:36px;padding:0.25rem 0.875rem;">Imprimir</button>
        </div>
    </div>

    <!-- Stats Principales -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="border-left:4px solid #3b82f6;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Total Histórico</p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:white;"><?= number_format($total_inscritos) ?></p>
        </div>
        <div class="card" style="border-left:4px solid #22c55e;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Socios Activos (Al día)</p>
            <div style="display:flex;align-items:baseline;gap:0.5rem;">
                <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:#22c55e;"><?= number_format($activos) ?></p>
                <span style="font-size:0.875rem;color:var(--color-text-muted);">(<?= $total_inscritos>0 ? round(($activos/$total_inscritos)*100) : 0 ?>%)</span>
            </div>
        </div>
        <div class="card" style="border-left:4px solid #ef4444;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Socios Inactivos / Vencidos</p>
            <div style="display:flex;align-items:baseline;gap:0.5rem;">
                <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:#ef4444;"><?= number_format($inactivos) ?></p>
                <span style="font-size:0.875rem;color:var(--color-text-muted);">(<?= $total_inscritos>0 ? round(($inactivos/$total_inscritos)*100) : 0 ?>%)</span>
            </div>
        </div>
    </div>

    <!-- Alertas: Por Vencer -->
    <div class="card" style="margin-bottom:1.5rem; <?= !empty($por_vencer) ? 'border-color:rgba(234,179,8,0.3);background:rgba(249,115,22,0.02);' : '' ?>">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1.25rem;">
            <div style="width:2.5rem;height:2.5rem;border-radius:0.5rem;background:rgba(234,179,8,0.1);color:#eab308;display:flex;align-items:center;justify-content:center;">
                <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div>
                <h3 class="text-h3" style="margin:0;color:#eab308;">Atención Requerida: Por Vencer</h3>
                <p style="font-size:0.8r75em;color:var(--color-text-muted);margin:0;">Socios cuya membresía caducará en los próximos 7 días.</p>
            </div>
        </div>

        <?php if (empty($por_vencer)): ?>
        <p style="text-align:center;color:var(--color-text-muted);padding:1rem;">Nadie tiene vencimiento cercano en este momento.</p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Socio</th><th>Plan</th><th>Fecha de Fin</th><th>Días Restantes</th><th>Ventas/Seguimiento</th></tr></thead>
                <tbody>
                    <?php foreach($por_vencer as $pv): 
                        $dias = dias_restantes($pv['fecha_fin']);
                    ?>
                    <tr>
                        <td style="font-weight:600;"><a href="<?= BASE_URL ?>modules/socios/perfil.php?id=<?= $pv['id'] ?>" style="color:white;text-decoration:none;"><?= h($pv['nombre'] . ' ' . $pv['apellido']) ?></a></td>
                        <td><?= h($pv['plan_nombre']) ?></td>
                        <td><?= date('d/m/Y', strtotime($pv['fecha_fin'])) ?></td>
                        <td><span style="font-weight:700;color:<?= $dias<=3 ? '#ef4444' : '#eab308' ?>;"><?= $dias ?> días</span></td>
                        <td>
                            <?php if($pv['telefono']): ?>
                                <?php
                                    $tel = preg_replace('/\D/', '', $pv['telefono']);
                                    if(strlen($tel)==9) $tel = '51'.$tel;
                                ?>
                                <a href="https://wa.me/<?= $tel ?>?text=<?= rawurlencode("Hola {$pv['nombre']}, te saludamos de recepción. Tu plan {$pv['plan_nombre']} está por terminar en {$dias} días. Puedes renovar desde hoy para no perder ningún día de acceso. ¡Te esperamos!") ?>" target="_blank" style="color:#22c55e;text-decoration:none;display:flex;align-items:center;gap:0.35rem;font-size:0.875rem;font-weight:600;">
                                    <svg style="width:1rem;height:1rem;" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                    Notificar Renovación
                                </a>
                            <?php else: ?>
                                <span style="font-size:0.8rem;color:var(--color-text-muted);">Sin teléfono registrado</span>
                            <?php endif; ?>
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
