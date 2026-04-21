<?php
// ajax/registrar_acceso.php — Registra la entrada de un socio al gimnasio
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id  = get_gimnasio_id();
$socio_id     = (int)($_POST['socio_id'] ?? 0);
$metodo_usado = trim($_POST['metodo'] ?? 'manual'); // 'dni'|'clave'|'qr'|'manual'

if (!$socio_id) {
    echo json_encode(['exito' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

// Verificar que el socio pertenece al gimnasio (seguridad)
$stmt = $pdo->prepare("SELECT id, activo FROM socios WHERE id = ? AND gimnasio_id = ?");
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();

if (!$socio || !$socio['activo']) {
    echo json_encode(['exito' => false, 'mensaje' => 'Socio no encontrado o inactivo']);
    exit;
}

// Verificar que no se registre un acceso duplicado en los últimos 5 minutos
// (para evitar doble registro por errores de UI)
$stmtCheck = $pdo->prepare("
    SELECT id FROM accesos
    WHERE socio_id = ? AND gimnasio_id = ? AND fecha_hora >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    LIMIT 1
");
$stmtCheck->execute([$socio_id, $gimnasio_id]);
if ($stmtCheck->fetch()) {
    echo json_encode(['exito' => true, 'mensaje' => 'Acceso ya registrado recientemente', 'duplicado' => true]);
    exit;
}

// Registrar el acceso
$stmtIns = $pdo->prepare("
    INSERT INTO accesos (socio_id, gimnasio_id, fecha_hora, metodo_usado, registrado_por)
    VALUES (?, ?, NOW(), ?, ?)
");
$stmtIns->execute([
    $socio_id,
    $gimnasio_id,
    $metodo_usado,
    $_SESSION['usuario_id']
]);

echo json_encode(['exito' => true, 'mensaje' => 'Acceso registrado correctamente']);
