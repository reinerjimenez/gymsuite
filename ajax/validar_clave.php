<?php
// ajax/validar_clave.php — Validación de PIN de 4 dígitos con Rate Limiting
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();
$pin = trim($_POST['pin'] ?? '');

// --- RATE LIMITING ---
$ahora = time();

// Verificar si está bloqueado
if (isset($_SESSION['pin_bloqueado_hasta']) && $ahora < $_SESSION['pin_bloqueado_hasta']) {
    $segundos_restantes = $_SESSION['pin_bloqueado_hasta'] - $ahora;
    echo json_encode([
        'exito'     => false,
        'bloqueado' => true,
        'segundos'  => $segundos_restantes,
        'mensaje'   => "Demasiados intentos. Espera {$segundos_restantes} segundos."
    ]);
    exit;
}

// Validar formato del PIN
if (!preg_match('/^\d{4}$/', $pin)) {
    echo json_encode(['exito' => false, 'mensaje' => 'PIN inválido']);
    exit;
}

// Obtener todos los socios activos del gimnasio ordenados por último acceso
// (estadísticamente, los asiduos tendrán match en los primeros intentos)
$stmt = $pdo->prepare("
    SELECT s.id, s.nombre, s.apellido, s.dni, s.foto, s.clave_acceso,
           MAX(a.fecha_hora) as ultimo_acceso
    FROM socios s
    LEFT JOIN accesos a ON a.socio_id = s.id AND a.gimnasio_id = s.gimnasio_id
    WHERE s.gimnasio_id = ? AND s.activo = 1 AND s.clave_acceso IS NOT NULL
    GROUP BY s.id
    ORDER BY ISNULL(MAX(a.fecha_hora)) ASC, MAX(a.fecha_hora) DESC
");
$stmt->execute([$gimnasio_id]);
$socios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$socio_encontrado = null;
foreach ($socios as $s) {
    if (password_verify($pin, $s['clave_acceso'])) {
        $socio_encontrado = $s;
        break;
    }
}

if (!$socio_encontrado) {
    // Incrementar contador de intentos fallidos
    $_SESSION['pin_intentos'] = ($_SESSION['pin_intentos'] ?? 0) + 1;

    if ($_SESSION['pin_intentos'] >= 5) {
        $_SESSION['pin_bloqueado_hasta'] = $ahora + 120; // 2 minutos
        $_SESSION['pin_intentos'] = 0;
        echo json_encode([
            'exito'     => false,
            'bloqueado' => true,
            'segundos'  => 120,
            'mensaje'   => 'Demasiados intentos. Sistema bloqueado por 2 minutos.'
        ]);
    } else {
        $restantes = 5 - $_SESSION['pin_intentos'];
        echo json_encode([
            'exito'   => false,
            'mensaje' => "PIN incorrecto. Intentos restantes: {$restantes}"
        ]);
    }
    exit;
}

// ✅ PIN correcto — resetear contador
$_SESSION['pin_intentos'] = 0;
unset($_SESSION['pin_bloqueado_hasta']);

// Obtener estado membresía
$stmtM = $pdo->prepare("
    SELECT m.fecha_fin, m.estado, p.nombre as plan_nombre
    FROM membresias m
    JOIN planes p ON p.id = m.plan_id
    WHERE m.socio_id = ? AND m.gimnasio_id = ?
    ORDER BY m.fecha_fin DESC
    LIMIT 1
");
$stmtM->execute([$socio_encontrado['id'], $gimnasio_id]);
$mem = $stmtM->fetch(PDO::FETCH_ASSOC);

$estado_mem = 'sin_membresia';
$dias_rest   = null;

if ($mem && $mem['fecha_fin']) {
    $dias_rest  = dias_restantes($mem['fecha_fin']);
    $estado_mem = calcular_estado_membresia($mem['fecha_fin']);
}

echo json_encode([
    'exito'          => true,
    'id'             => (int)$socio_encontrado['id'],
    'nombre'         => $socio_encontrado['nombre'] . ' ' . $socio_encontrado['apellido'],
    'dni'            => $socio_encontrado['dni'],
    'foto'           => $socio_encontrado['foto'],
    'plan'           => $mem['plan_nombre'] ?? null,
    'fecha_fin'      => ($mem && $mem['fecha_fin']) ? formatear_fecha($mem['fecha_fin']) : null,
    'dias_restantes' => $dias_rest,
    'estado'         => $estado_mem,
]);
