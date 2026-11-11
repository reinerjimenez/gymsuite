<?php
// modules/socios/eliminar.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']);
$gimnasio_id = (int)get_gimnasio_id();

if ($_SERVER['REQUEST_METHOD'] !== 'POST'
    || !is_string($_POST['csrf'] ?? null)
    || !isset($_SESSION['eliminar_socio_csrf'])
    || !hash_equals($_SESSION['eliminar_socio_csrf'], $_POST['csrf'])) {
    mensaje_flash('error', 'Solicitud no válida. Recarga el perfil e inténtalo de nuevo.');
    header('Location: index.php');
    exit;
}

$socio_id = (int)($_POST['socio_id'] ?? 0);
$stmt = $pdo->prepare('SELECT foto FROM socios WHERE id = ? AND gimnasio_id = ? AND activo = 0 AND eliminado_en IS NULL');
$stmt->execute([$socio_id, $gimnasio_id]);
$socio = $stmt->fetch();
if (!$socio) {
    mensaje_flash('error', 'El socio no existe, está activo o ya fue eliminado.');
    header('Location: index.php');
    exit;
}

// Conservar el ID para los historiales, pero retirar los datos personales.
$stmt = $pdo->prepare("UPDATE socios SET nombre = 'Socio eliminado', apellido = '', dni = NULL, telefono = NULL, email = NULL, foto = NULL, nota_interna = NULL, clave_acceso = NULL, token_qr = NULL, eliminado_en = NOW() WHERE id = ? AND gimnasio_id = ? AND activo = 0 AND eliminado_en IS NULL");
$stmt->execute([$socio_id, $gimnasio_id]);
if ($stmt->rowCount() !== 1) {
    mensaje_flash('error', 'No se pudo eliminar el socio. Recarga el perfil.');
    header('Location: index.php');
    exit;
}

// Borrar únicamente un archivo dentro de uploads/fotos; nunca aceptar rutas externas.
$foto = $socio['foto'];
if ($foto !== null && $foto !== '') {
    $stmt = $pdo->prepare('SELECT id FROM socios WHERE foto = ? AND id <> ? LIMIT 1');
    $stmt->execute([$foto, $socio_id]);
    $foto_compartida = (bool)$stmt->fetchColumn();
    $stmt = $pdo->prepare('SELECT id FROM gimnasios WHERE logo = ? LIMIT 1');
    $stmt->execute([$foto]);
    $foto_compartida = $foto_compartida || (bool)$stmt->fetchColumn();
    if ($foto_compartida) {
        mensaje_flash('warning', 'Socio anonimizado. La foto se conserva porque también está vinculada a otro registro.');
        header('Location: index.php');
        exit;
    }
    $carpeta = realpath(__DIR__ . '/../../uploads/fotos');
    if (!$carpeta || basename($foto) !== $foto || $foto === '.' || $foto === '..') {
        mensaje_flash('warning', 'Socio anonimizado, pero la ruta de su foto requiere revisión manual.');
        header('Location: index.php');
        exit;
    }
    $ruta = realpath($carpeta . DIRECTORY_SEPARATOR . $foto);
    if ($ruta && dirname($ruta) !== $carpeta) {
        mensaje_flash('warning', 'Socio anonimizado, pero la ruta de su foto requiere revisión manual.');
        header('Location: index.php');
        exit;
    }
    if ($ruta && is_file($ruta)) {
        if (!@unlink($ruta)) {
            mensaje_flash('warning', 'Socio anonimizado, pero no se pudo borrar su foto del servidor.');
            header('Location: index.php');
            exit;
        }
    }
}

mensaje_flash('success', 'Socio eliminado definitivamente. Su historial de pagos se conserva como Socio eliminado.');
header('Location: index.php');
exit;
