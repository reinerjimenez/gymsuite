<?php
// pase.php - Tarjeta de acceso pública mediante Token (para enviar por WhatsApp)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$tk = $_GET['tk'] ?? '';
if (!$tk) { die("Pase inválido o enlace dañado."); }

$stmt = $pdo->prepare("
    SELECT s.nombre, s.apellido, s.activo, g.nombre as gym_nombre 
    FROM socios s 
    JOIN gimnasios g ON s.gimnasio_id = g.id 
    WHERE s.token_qr = ? LIMIT 1
");
$stmt->execute([$tk]);
$socio = $stmt->fetch();

if (!$socio || (int)$socio['activo'] !== 1) {
    die("Pase inactivo, cancelado o no encontrado en el sistema.");
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pase de Acceso - <?= h($socio['gym_nombre']) ?></title>
    
    <!-- Open Graph Meta Tags (Para previsualización en WhatsApp / Redes Sociales) -->
    <meta property="og:title" content="Tu Pase Digital - <?= h($socio['gym_nombre']) ?>">
    <meta property="og:description" content="Abre este enlace para ver tu Código QR y escanéalo en recepción para ingresar.">
    <meta property="og:image" content="https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=<?= rawurlencode($tk) ?>">
    <meta property="og:image:width" content="400">
    <meta property="og:image:height" content="400">
    <meta property="og:type" content="website">

    <style>
        body { margin: 0; padding: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 100vh; background-color: #09090b; color: white; font-family: system-ui, -apple-system, sans-serif; }
        .card { background: #18181b; padding: 2rem; border-radius: 1rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5); text-align: center; max-width: 90%; width: 320px; border: 1px solid #27272a; position: relative; overflow: hidden; }
        .card::before { content: ''; position: absolute; top:0; left:0; width:100%; height:4px; background: #e11d48; }
        #qrcode { background: white; padding: 1.25rem; border-radius: 0.75rem; display: inline-block; margin-bottom: 1.5rem; }
        h1 { margin: 0 0 0.25rem 0; font-size: 1.5rem; color: #f43f5e; line-height: 1.2; }
        p { margin: 0 0 1.5rem 0; color: #a1a1aa; font-size: 0.875rem; text-transform: uppercase; letter-spacing: 0.1em; }
        .name { font-size: 1.25rem; font-weight: bold; color: white; margin-bottom: 0.25rem; }
        .instruction { margin-top: 1.5rem; font-size: 0.8rem; color: #71717a; border-top: 1px solid #27272a; padding-top: 1rem; }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
    <div class="card">
        <h1><?= h($socio['gym_nombre']) ?></h1>
        <p>Pase de Ingreso</p>
        
        <div id="qrcode"></div>
        
        <div class="name"><?= h($socio['nombre'] . ' ' . $socio['apellido']) ?></div>
        <div style="font-size:0.875rem; color:#71717a; font-family: monospace;">Tk-<?= h(substr($tk, 0, 8)) ?>...</div>

        <div class="instruction">
            Presenta este código QR en recepción o en el torniquete automatizado para validar tu ingreso.
        </div>
    </div>
    
    <script>
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= h($tk) ?>",
            width: 220,
            height: 220,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    </script>
</body>
</html>
