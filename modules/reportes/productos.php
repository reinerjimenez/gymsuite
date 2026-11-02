<?php
// modules/reportes/productos.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
verificar_sesion();
verificar_rol(['admin']);
$gimnasio_id = (int)get_gimnasio_id();
$anio = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT) ?: (int)date('Y');
$stmt = $pdo->prepare('SELECT d.producto_id,d.nombre_producto,SUM(d.cantidad) AS unidades,SUM(d.subtotal) AS ingresos FROM detalle_venta d JOIN ventas v ON v.id=d.venta_id AND v.gimnasio_id=d.gimnasio_id WHERE d.gimnasio_id=? AND v.fecha_hora>=? AND v.fecha_hora<? GROUP BY d.producto_id,d.nombre_producto ORDER BY unidades DESC,ingresos DESC LIMIT 50');
$stmt->execute([$gimnasio_id,$anio . '-01-01',($anio+1) . '-01-01']);
$productos = $stmt->fetchAll();
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>
<div><div class="flex justify-between items-center mb-6 flex-wrap gap-4"><div><a href="index.php" class="text-muted">← Volver a Reportes</a><h1 class="text-h2">Productos más vendidos</h1><p class="text-muted">Unidades e ingresos por producto durante <?= (int)$anio ?>.</p></div><form method="get"><label for="anio" class="form-label">Año</label><select id="anio" name="anio" class="form-control" style="min-height:44px" onchange="this.form.submit()"><?php for($i=(int)date('Y')+1;$i>=2023;$i--): ?><option value="<?= $i ?>" <?= $i===$anio?'selected':'' ?>><?= $i ?></option><?php endfor; ?></select></form></div><div class="card"><div class="table-responsive"><table class="table"><thead><tr><th>Producto</th><th>Unidades</th><th>Ingresos</th></tr></thead><tbody><?php foreach ($productos as $p): ?><tr><td><?= h($p['nombre_producto']) ?></td><td><?= (int)$p['unidades'] ?></td><td><?= formatear_moneda($p['ingresos']) ?></td></tr><?php endforeach; ?><?php if (!$productos): ?><tr><td colspan="3">No hay ventas de productos en este año.</td></tr><?php endif; ?></tbody></table></div></div></div>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
