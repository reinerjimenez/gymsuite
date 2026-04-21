<?php
// modules/reportes/asistencia.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);

$gimnasio_id = get_gimnasio_id();

// Filtros de fecha (Por defecto: últimos 15 días)
$fecha_fin   = $_GET['fecha_fin'] ?? date('Y-m-d');
$fecha_ini   = $_GET['fecha_ini'] ?? date('Y-m-d', strtotime('-14 days'));

// 1. Estadísticas agregadas por fecha
$stmtGraf = $pdo->prepare("
    SELECT DATE(fecha_hora) as dia, COUNT(*) as cantidad 
    FROM accesos 
    WHERE gimnasio_id = ? AND DATE(fecha_hora) BETWEEN ? AND ? 
    GROUP BY DATE(fecha_hora) ORDER BY dia ASC
");
$stmtGraf->execute([$gimnasio_id, $fecha_ini, $fecha_fin]);
$datos_dias = $stmtGraf->fetchAll(PDO::FETCH_KEY_PAIR);

// Asegurar que todos los días del rango existan en el array para gráfico/tabla lineal
$fechas_completas = [];
$current = strtotime($fecha_ini);
$last = strtotime($fecha_fin);
$total_accesos = 0;
while($current <= $last) {
    $f = date('Y-m-d', $current);
    $cant = $datos_dias[$f] ?? 0;
    $fechas_completas[$f] = $cant;
    $total_accesos += $cant;
    $current = strtotime('+1 day', $current);
}

// 2. Porcentaje de uso por método
$stmtMet = $pdo->prepare("
    SELECT metodo_usado, COUNT(*) as cantidad 
    FROM accesos 
    WHERE gimnasio_id = ? AND DATE(fecha_hora) BETWEEN ? AND ? 
    GROUP BY metodo_usado
");
$stmtMet->execute([$gimnasio_id, $fecha_ini, $fecha_fin]);
$metodos_stats = $stmtMet->fetchAll();

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<style>
@media print {
    .sidebar, .header, .btn, form { display: none !important; }
    .app-wrapper { padding: 0 !important; margin: 0 !important; }
    body { background: white !important; color: black !important; }
    .card { border: 1px solid #ccc !important; background: white !important; color: black !important; box-shadow: none !important; }
    .text-muted { color: #555 !important; }
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
            <h1 class="text-h2">Reporte de Asistencia</h1>
            <p class="text-muted">Afluencia de socios en el rango seleccionado.</p>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
            <form method="GET" style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <input type="date" name="fecha_ini" class="form-control" value="<?= h($fecha_ini) ?>" style="min-height:36px;padding:0.25rem 0.75rem;">
                <span style="color:var(--color-text-muted);">hasta</span>
                <input type="date" name="fecha_fin" class="form-control" value="<?= h($fecha_fin) ?>" style="min-height:36px;padding:0.25rem 0.75rem;">
                <button type="submit" class="btn btn-primary" style="min-height:36px;padding:0.25rem 0.875rem;">Filtrar</button>
            </form>
            <button onclick="window.print()" class="btn btn-secondary" style="min-height:36px;padding:0.25rem 0.875rem;">Imprimir</button>
        </div>
    </div>

    <!-- KPIs Totales -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="padding:1.5rem;text-align:center;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Total de Asistencias</p>
            <p style="font-size:2.5rem;font-weight:800;font-family:var(--font-heading);color:var(--color-primary);"><?= number_format($total_accesos) ?></p>
        </div>
        <div class="card" style="padding:1.5rem;text-align:center;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Promedio Diario</p>
            <p style="font-size:2.5rem;font-weight:800;font-family:var(--font-heading);color:white;">
                <?= count($fechas_completas) > 0 ? round($total_accesos / count($fechas_completas), 1) : 0 ?>
            </p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start;">
        <!-- Desglose por Días -->
        <div class="card">
            <h3 class="text-h3" style="margin-bottom:1rem;">Afluencia por Día</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Fecha</th><th style="text-align:right;">Accesos</th><th></th></tr></thead>
                    <tbody>
                        <?php 
                        $max_accesos = empty($fechas_completas) ? 1 : max($fechas_completas);
                        if ($max_accesos == 0) $max_accesos = 1;
                        foreach(array_reverse($fechas_completas, true) as $fecha => $cant): 
                            $pct = ($cant / $max_accesos) * 100;
                        ?>
                        <tr>
                            <td style="font-weight:600;width:120px;"><?= date('d/m/Y', strtotime($fecha)) ?></td>
                            <td style="text-align:right;font-weight:700;color:<?= $cant > 0 ? 'white' : 'var(--color-text-muted)' ?>;width:80px;"><?= $cant ?></td>
                            <td style="width:100%;">
                                <div style="height:8px;background:rgba(255,255,255,0.05);border-radius:99px;overflow:hidden;">
                                    <div style="height:100%;background:var(--color-primary);width:<?= $pct ?>%;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Breakdown por Método -->
        <div class="card">
            <h3 class="text-h3" style="margin-bottom:1rem;">Método de Entrada</h3>
            <?php if (empty($metodos_stats)): ?>
            <p class="text-muted" style="text-align:center;padding:2rem;">Sin registros en estas fechas.</p>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:1.25rem;">
                <?php 
                $metodos_labels = ['dni'=>'Búsqueda DNI', 'clave'=>'Teclado PIN', 'qr'=>'Escaneo QR', 'manual'=>'Admin (Manual)'];
                foreach($metodos_stats as $m): 
                    $pct = ($m['cantidad'] / max(1,$total_accesos)) * 100;
                ?>
                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                        <span style="font-weight:600;font-size:0.875rem;"><?= $metodos_labels[$m['metodo_usado']] ?? $m['metodo_usado'] ?></span>
                        <div style="text-align:right;">
                            <span style="font-weight:800;"><?= $m['cantidad'] ?></span>
                            <span style="color:var(--color-text-muted);font-size:0.75rem;margin-left:0.25rem;">(<?= round($pct) ?>%)</span>
                        </div>
                    </div>
                    <div style="width:100%;height:6px;background:rgba(255,255,255,0.05);border-radius:99px;overflow:hidden;">
                        <div style="width:<?= $pct ?>%;height:100%;background:<?= $m['metodo_usado']==='qr'?'#22c55e':($m['metodo_usado']==='clave'?'#eab308':'var(--color-primary)') ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
