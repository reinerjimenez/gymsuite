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

// Verificar si el socio ya ingresó hoy
$stmtCheck = $pdo->prepare("
    SELECT id, DATE_FORMAT(fecha_hora, '%h:%i %p') as hora 
    FROM accesos
    WHERE socio_id = ? AND gimnasio_id = ? AND DATE(fecha_hora) = CURDATE()
    LIMIT 1
");
$stmtCheck->execute([$socio_id, $gimnasio_id]);
$ya_ingreso = $stmtCheck->fetch();

if ($ya_ingreso) {
    echo json_encode([
        'exito' => false, 
        'mensaje' => 'Este socio ya registró su entrada hoy a las ' . $ya_ingreso['hora']
    ]);
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
