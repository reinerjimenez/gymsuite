<?php
// ajax/buscar_socio.php — Búsqueda de socio por DNI en tiempo real
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$dni = trim($_GET['dni'] ?? '');

if (strlen($dni) < 2) {
    echo json_encode(['encontrado' => false]);
    exit;
}

// Buscar socio por DNI dentro del gimnasio
$stmt = $pdo->prepare("
    SELECT s.id, s.nombre, s.apellido, s.dni, s.foto, s.activo,
           m.fecha_fin, m.estado as mem_estado, p.nombre as plan_nombre
    FROM socios s
    LEFT JOIN membresias m ON m.id = (
        SELECT id FROM membresias
        WHERE socio_id = s.id AND gimnasio_id = s.gimnasio_id
        ORDER BY fecha_fin DESC LIMIT 1
    )
    LEFT JOIN planes p ON p.id = m.plan_id
    WHERE s.dni = ? AND s.gimnasio_id = ?
    LIMIT 1
");
$stmt->execute([$dni, $gimnasio_id]);
$socio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$socio) {
    echo json_encode(['encontrado' => false, 'mensaje' => 'Socio no registrado']);
    exit;
}

if (!$socio['activo']) {
    echo json_encode([
        'encontrado'  => true,
        'bloqueado'   => true,
        'nombre'      => $socio['nombre'] . ' ' . $socio['apellido'],
        'mensaje'     => 'Socio inactivo — Contactar al administrador'
    ]);
    exit;
}

// Calcular estado de membresía
$estado_mem = 'sin_membresia';
$dias_rest   = null;

if ($socio['fecha_fin']) {
    $dias_rest  = dias_restantes($socio['fecha_fin']);
    $estado_mem = calcular_estado_membresia($socio['fecha_fin']);
}

echo json_encode([
    'encontrado'   => true,
    'bloqueado'    => false,
    'id'           => (int)$socio['id'],
    'nombre'       => $socio['nombre'] . ' ' . $socio['apellido'],
    'dni'          => $socio['dni'],
    'foto'         => $socio['foto'],
    'plan'         => $socio['plan_nombre'] ?? null,
    'fecha_fin'    => $socio['fecha_fin'] ? formatear_fecha($socio['fecha_fin']) : null,
    'dias_restantes' => $dias_rest,
    'estado'       => $estado_mem, // 'activa' | 'por_vencer' | 'vencida' | 'sin_membresia'
]);
