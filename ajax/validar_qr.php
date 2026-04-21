<?php
// ajax/validar_qr.php — Validación de token QR escaneado
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$token = trim($_POST['token'] ?? '');

if (empty($token)) {
    echo json_encode(['exito' => false, 'mensaje' => 'Token vacío']);
    exit;
}

// Buscar socio por token QR
$stmt = $pdo->prepare("
    SELECT s.id, s.nombre, s.apellido, s.dni, s.foto, s.activo, s.gimnasio_id
    FROM socios s
    WHERE s.token_qr = ?
    LIMIT 1
");
$stmt->execute([$token]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
    echo json_encode(['exito' => false, 'mensaje' => 'Código QR no válido']);
    exit;
}

// Verificar que el socio pertenece a este gimnasio
if ((int)$socio['gimnasio_id'] !== (int)$gimnasio_id) {
    echo json_encode(['exito' => false, 'mensaje' => 'Este socio no pertenece a este gimnasio']);
    exit;
}

if (!$socio['activo']) {
    echo json_encode(['exito' => false, 'mensaje' => 'Socio inactivo — Contactar al administrador']);
    exit;
}

// Obtener estado membresía
$stmtM = $pdo->prepare("
    SELECT m.fecha_fin, m.estado, p.nombre as plan_nombre
    FROM membresias m
    JOIN planes p ON p.id = m.plan_id
    WHERE m.socio_id = ? AND m.gimnasio_id = ?
    ORDER BY m.fecha_fin DESC
    LIMIT 1
");
$stmtM->execute([$socio['id'], $gimnasio_id]);
$mem = $stmtM->fetch(PDO::FETCH_ASSOC);

$estado_mem = 'sin_membresia';
$dias_rest   = null;

if ($mem && $mem['fecha_fin']) {
    $dias_rest  = dias_restantes($mem['fecha_fin']);
    $estado_mem = calcular_estado_membresia($mem['fecha_fin']);
}

echo json_encode([
    'exito'          => true,
    'id'             => (int)$socio['id'],
    'nombre'         => $socio['nombre'] . ' ' . $socio['apellido'],
    'dni'            => $socio['dni'],
    'foto'           => $socio['foto'],
    'plan'           => $mem['plan_nombre'] ?? null,
    'fecha_fin'      => ($mem && $mem['fecha_fin']) ? formatear_fecha($mem['fecha_fin']) : null,
    'dias_restantes' => $dias_rest,
    'estado'         => $estado_mem,
]);
