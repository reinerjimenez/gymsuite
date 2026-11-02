<?php
// modules/punto_venta/confirmar.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';
verificar_sesion();
verificar_rol(['admin', 'recepcionista']);
header('Content-Type: application/json; charset=utf-8');
$gimnasio_id = (int)get_gimnasio_id();

function responder_venta(bool $success, $data, string $message, int $codigo = 200): void {
    http_response_code($codigo);
    echo json_encode(['success'=>$success,'data'=>$data,'message'=>$message], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder_venta(false, null, 'Método no permitido.', 405);
$entrada = json_decode(file_get_contents('php://input'), true);
if (!is_array($entrada) || !isset($_SESSION['venta_csrf']) || !is_string($entrada['csrf'] ?? null) || !hash_equals($_SESSION['venta_csrf'], $entrada['csrf'])) responder_venta(false, null, 'Sesión vencida. Recarga la página.', 403);
$metodo = $entrada['metodo'] ?? '';
if (!in_array($metodo, ['efectivo','yape','plin','transferencia'], true)) responder_venta(false, null, 'Método de pago no válido.', 422);
$socio_id = $entrada['socio_id'] ?? null;
if ($socio_id !== null && (!filter_var($socio_id, FILTER_VALIDATE_INT) || (int)$socio_id <= 0)) responder_venta(false, null, 'Socio no válido.', 422);
$items = $entrada['items'] ?? null;
if (!is_array($items) || !$items || count($items) > 100) responder_venta(false, null, 'Agrega productos a la venta.', 422);
$cantidades = [];
foreach ($items as $item) {
    $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || $id <= 0 || !$cantidad || $cantidad <= 0 || $cantidad > 10000 || isset($cantidades[$id])) responder_venta(false, null, 'Cantidad o producto no válido.', 422);
    $cantidades[$id] = $cantidad;
}
ksort($cantidades, SORT_NUMERIC);

try {
    $pdo->beginTransaction();
    if ($socio_id !== null) {
        $stmt = $pdo->prepare('SELECT id FROM socios WHERE id=? AND gimnasio_id=?');
        $stmt->execute([(int)$socio_id,$gimnasio_id]);
        if (!$stmt->fetchColumn()) throw new RuntimeException('El socio no pertenece a este gimnasio.');
    }
    $lineas = [];
    $total_centimos = 0;
    $buscar = $pdo->prepare('SELECT id,nombre,precio_venta,stock_actual,activo FROM productos WHERE id=? AND gimnasio_id=? FOR UPDATE');
    foreach ($cantidades as $id => $cantidad) {
        $buscar->execute([$id,$gimnasio_id]);
        $producto = $buscar->fetch();
        if (!$producto || !$producto['activo']) throw new RuntimeException('Un producto ya no está disponible. Actualiza el catálogo.');
        if ((int)$producto['stock_actual'] < $cantidad) throw new RuntimeException('Stock insuficiente para ' . $producto['nombre'] . '.');
        $precio_centimos = (int)round((float)$producto['precio_venta'] * 100);
        $subtotal_centimos = $precio_centimos * $cantidad;
        $total_centimos += $subtotal_centimos;
        $lineas[] = [$producto,$cantidad,$subtotal_centimos];
    }
    if ($total_centimos > 999999999999) throw new RuntimeException('El total supera el máximo permitido.');
    $total = number_format($total_centimos / 100, 2, '.', '');
    $pdo->prepare('INSERT INTO ventas (gimnasio_id,socio_id,total,metodo,registrado_por,fecha_hora) VALUES (?,?,?,?,?,NOW())')
        ->execute([$gimnasio_id,$socio_id === null ? null : (int)$socio_id,$total,$metodo,$_SESSION['usuario_id']]);
    $venta_id = (int)$pdo->lastInsertId();
    $detalle = $pdo->prepare('INSERT INTO detalle_venta (gimnasio_id,venta_id,producto_id,nombre_producto,cantidad,precio_unitario,subtotal) VALUES (?,?,?,?,?,?,?)');
    $descontar = $pdo->prepare('UPDATE productos SET stock_actual=stock_actual-? WHERE id=? AND gimnasio_id=?');
    $movimiento = $pdo->prepare("INSERT INTO movimientos_stock (gimnasio_id,producto_id,venta_id,tipo,cantidad,stock_anterior,stock_nuevo,registrado_por,fecha_hora,nota) VALUES (?,?,?,'venta',?,?,?,?,NOW(),NULL)");
    foreach ($lineas as [$producto,$cantidad,$subtotal_centimos]) {
        $id = (int)$producto['id'];
        $stock_anterior = (int)$producto['stock_actual'];
        $detalle->execute([$gimnasio_id,$venta_id,$id,$producto['nombre'],$cantidad,$producto['precio_venta'],number_format($subtotal_centimos / 100, 2, '.', '')]);
        $descontar->execute([$cantidad,$id,$gimnasio_id]);
        $movimiento->execute([$gimnasio_id,$id,$venta_id,-$cantidad,$stock_anterior,$stock_anterior-$cantidad,$_SESSION['usuario_id']]);
    }
    $pdo->commit();
    responder_venta(true, ['venta_id'=>$venta_id,'total'=>$total], 'Venta registrada.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    responder_venta(false, null, $e instanceof PDOException ? 'No se pudo registrar la venta.' : $e->getMessage(), 422);
}
