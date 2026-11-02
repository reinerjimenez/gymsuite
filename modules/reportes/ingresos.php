<?php
// modules/reportes/ingresos.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);

$gimnasio_id = get_gimnasio_id();
$anio = (int)($_GET['anio'] ?? date('Y'));

// 1. Total por mes (Meses del año seleccionado)
$stmtM = $pdo->prepare("
    SELECT MONTH(fecha) as mes, SUM(monto) as total
    FROM pagos
    WHERE gimnasio_id = ? AND YEAR(fecha) = ?
    GROUP BY MONTH(fecha)
");
$stmtM->execute([$gimnasio_id, $anio]);
$ingresos_mes = [];
$ingresos_membresias_mes = [];
while ($row = $stmtM->fetch()) {
    $ingresos_membresias_mes[(int)$row['mes']] = (float)$row['total'];
}
$stmtProductos = $pdo->prepare('SELECT MONTH(fecha_hora) AS mes, SUM(total) AS total FROM ventas WHERE gimnasio_id=? AND fecha_hora>=? AND fecha_hora<? GROUP BY MONTH(fecha_hora)');
$stmtProductos->execute([$gimnasio_id, $anio . '-01-01', ($anio + 1) . '-01-01']);
$ingresos_productos_mes = [];
while ($row = $stmtProductos->fetch()) $ingresos_productos_mes[(int)$row['mes']] = (float)$row['total'];
for ($mes = 1; $mes <= 12; $mes++) $ingresos_mes[$mes] = ($ingresos_membresias_mes[$mes] ?? 0) + ($ingresos_productos_mes[$mes] ?? 0);

$meses_nombres = [1=>'Enero', 2=>'Febrero', 3=>'Marzo', 4=>'Abril', 5=>'Mayo', 6=>'Junio', 7=>'Julio', 8=>'Agosto', 9=>'Septiembre', 10=>'Octubre', 11=>'Noviembre', 12=>'Diciembre'];
$total_anual = array_sum($ingresos_mes);

// 2. Ingresos por método de pago (del año)
$stmtMet = $pdo->prepare("
    SELECT metodo, SUM(monto) as total, COUNT(*) as cantidad
    FROM pagos
    WHERE gimnasio_id = ? AND YEAR(fecha) = ?
    GROUP BY metodo
");
$stmtMet->execute([$gimnasio_id, $anio]);
$metodos = $stmtMet->fetchAll();
$stmtMetProductos = $pdo->prepare('SELECT metodo, SUM(total) AS total, COUNT(*) AS cantidad FROM ventas WHERE gimnasio_id=? AND fecha_hora>=? AND fecha_hora<? GROUP BY metodo');
$stmtMetProductos->execute([$gimnasio_id, $anio . '-01-01', ($anio + 1) . '-01-01']);
$metodos_agrupados = [];
foreach (array_merge($metodos, $stmtMetProductos->fetchAll()) as $m) {
    $clave = $m['metodo'];
    if (!isset($metodos_agrupados[$clave])) $metodos_agrupados[$clave] = ['metodo'=>$clave,'total'=>0,'cantidad'=>0];
    $metodos_agrupados[$clave]['total'] += (float)$m['total'];
    $metodos_agrupados[$clave]['cantidad'] += (int)$m['cantidad'];
}
$metodos = array_values($metodos_agrupados);

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
            <h1 class="text-h2">Reporte de Ingresos</h1>
            <p class="text-muted">Resumen financiero detallado del año seleccionado.</p>
        </div>
        <div style="display:flex;gap:0.75rem;align-items:center;">
            <form method="GET" style="display:flex;align-items:center;gap:0.5rem;">
                <label for="anio" style="font-size:0.875rem;color:var(--color-text-muted);">Año:</label>
                <select name="anio" id="anio" class="form-control" style="width:100px;min-height:36px;padding:0.25rem 0.75rem;" onchange="this.form.submit()">
                    <?php for($i = date('Y') + 1; $i >= 2023; $i--): ?>
                    <option value="<?= $i ?>" <?= $i === $anio ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </form>
            <button onclick="window.print()" class="btn btn-secondary" style="display:flex;align-items:center;gap:0.5rem;min-height:36px;padding:0.25rem 0.875rem;">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir
            </button>
        </div>
    </div>

    <!-- Módulo Estadístico KPIs -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));gap:1rem;margin-bottom:1.5rem;">
        <div class="card" style="padding:1.5rem;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Total del Año <?= $anio ?></p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:var(--color-primary);"><?= formatear_moneda($total_anual) ?></p>
        </div>
        <div class="card" style="padding:1.5rem;">
            <p style="font-size:0.875rem;color:var(--color-text-muted);margin-bottom:0.25rem;">Promedio Mensual</p>
            <p style="font-size:2rem;font-weight:800;font-family:var(--font-heading);color:white;">
                <?= formatear_moneda($total_anual / (date('Y') == $anio ? (int)date('n') : 12)) ?>
            </p>
        </div>
        <div class="card" style="padding:1.5rem;"><p class="text-muted">Ingresos por membresías</p><p class="text-h3"><?= formatear_moneda(array_sum($ingresos_membresias_mes)) ?></p></div>
        <div class="card" style="padding:1.5rem;"><p class="text-muted">Ingresos por productos</p><p class="text-h3"><?= formatear_moneda(array_sum($ingresos_productos_mes)) ?></p></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
        <!-- Distribución Mensual -->
        <div class="card">
            <h3 class="text-h3" style="margin-bottom:1rem;">Ingresos por Mes</h3>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Mes</th><th style="text-align:right;">Membresías</th><th style="text-align:right;">Productos</th><th style="text-align:right;">Total (S/.)</th></tr></thead>
                    <tbody>
                        <?php foreach($meses_nombres as $num => $nombre): 
                            $monto = $ingresos_mes[$num] ?? 0;
                        ?>
                        <tr>
                            <td style="font-weight:<?= $monto > 0 ? '600' : '400' ?>;color:<?= $monto > 0 ? 'white' : 'var(--color-text-muted)' ?>;"><?= $nombre ?></td>
                            <td style="text-align:right;"><?= number_format($ingresos_membresias_mes[$num] ?? 0, 2) ?></td>
                            <td style="text-align:right;"><?= number_format($ingresos_productos_mes[$num] ?? 0, 2) ?></td>
                            <td style="text-align:right;font-weight:<?= $monto > 0 ? '700' : '400' ?>;color:<?= $monto > 0 ? 'var(--color-primary)' : 'var(--color-text-muted)' ?>;">
                                <?= number_format($monto, 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="border-top:2px solid #333;">
                            <td style="font-weight:800;">TOTAL GLOBAL</td>
                            <td style="text-align:right;"><?= number_format(array_sum($ingresos_membresias_mes), 2) ?></td>
                            <td style="text-align:right;"><?= number_format(array_sum($ingresos_productos_mes), 2) ?></td>
                            <td style="text-align:right;font-weight:800;color:var(--color-primary);"><?= number_format($total_anual, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Por método de pago -->
        <div class="card">
            <h3 class="text-h3" style="margin-bottom:1rem;">Comparativa por Métodos</h3>
            <?php if (empty($metodos)): ?>
            <p class="text-muted" style="text-align:center;padding:2rem;">Sin datos registrados aún.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Método</th><th style="text-align:center;">Transacciones</th><th style="text-align:right;">Total (S/.)</th></tr></thead>
                    <tbody>
                        <?php foreach($metodos as $m): ?>
                        <tr>
                            <td style="text-transform:capitalize;font-weight:600;"><?= h($m['metodo']) ?></td>
                            <td style="text-align:center;color:var(--color-text-muted);"><?= $m['cantidad'] ?></td>
                            <td style="text-align:right;font-weight:700;"><?= number_format($m['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Barras visuales simples -->
            <div style="margin-top:2rem;display:flex;flex-direction:column;gap:1rem;">
                <?php foreach($metodos as $m): $pct = ($m['total'] / $total_anual) * 100; ?>
                <div>
                    <div style="display:flex;justify-content:space-between;font-size:0.75rem;margin-bottom:0.25rem;">
                        <span style="text-transform:uppercase;font-weight:700;color:var(--color-text-muted);"><?= h($m['metodo']) ?></span>
                        <span><?= round($pct, 1) ?>%</span>
                    </div>
                    <div style="width:100%;height:6px;background:rgba(255,255,255,0.1);border-radius:99px;overflow:hidden;">
                        <div style="width:<?= $pct ?>%;height:100%;background:var(--color-primary);"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
