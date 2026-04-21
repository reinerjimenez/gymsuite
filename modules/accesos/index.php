<?php
// modules/accesos/index.php — Orquestador: detecta el método y redirige
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin', 'recepcionista']);

$gimnasio_id = get_gimnasio_id();

// Leer método configurado para este gimnasio
$stmt = $pdo->prepare("SELECT metodo_acceso FROM configuracion_gym WHERE gimnasio_id = ? LIMIT 1");
$stmt->execute([$gimnasio_id]);
$config  = $stmt->fetch();
$metodo  = $config['metodo_acceso'] ?? 'dni';

switch ($metodo) {
    case 'clave': header("Location: por_clave.php"); break;
    case 'qr':    header("Location: por_qr.php");    break;
    default:      header("Location: por_dni.php");   break;
}
exit;
