<?php
// modules/superadmin/crear.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['superadmin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $ruc = trim($_POST['ruc'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    // Crear el Gimnasio
    $pdo->beginTransaction();
    try {
        $stmtG = $pdo->prepare("INSERT INTO gimnasios (nombre, ruc, direccion, telefono, estado) VALUES (?, ?, ?, ?, 'activo')");
        $stmtG->execute([$nombre, $ruc, $direccion, $telefono]);
        $gimnasio_id = $pdo->lastInsertId();

        // Crear la Configuración inicial por defecto
        $pdo->prepare("INSERT INTO configuracion_gym (gimnasio_id, metodo_acceso, zona_horaria) VALUES (?, 'dni', 'America/Lima')")->execute([$gimnasio_id]);

        // Crear al Admin inicial del Gimnasio
        // Generar una contraseña temporal aleatoria de 8 caracteres
        $pass_temporal = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
        $hash = password_hash($pass_temporal, PASSWORD_DEFAULT);
        
        // Generar un nombre de usuario basado en el nombre del gimnasio
        $username_base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $nombre));
        $username = 'admin_' . substr($username_base, 0, 10) . rand(10, 99);

        $stmtU = $pdo->prepare("INSERT INTO usuarios (gimnasio_id, nombre, apellido, usuario, password, rol, estado) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
        $stmtU->execute([$gimnasio_id, 'Admin', $nombre, $username, $hash]);

        $pdo->commit();
        
        // Almacenar credenciales en sesión solo para mostrarlas una vez
        $_SESSION['nuevo_gym'] = [
            'gym' => $nombre,
            'usuario' => $username,
            'password' => $pass_temporal
        ];
        
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        mensaje_flash('error', 'Ocurrió un error al crear el gimnasio.');
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="index.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver a la red
        </a>
        <h1 class="text-h2">Nuevo Gimnasio en la Red</h1>
        <p class="text-muted">Da de alta un nuevo cliente (tenant). El sistema le generará automáticamente un usuario administrador y contraseña.</p>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <form method="POST" action="">
            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="nombre">Nombre Comercial del Gimnasio</label>
                <input type="text" id="nombre" name="nombre" class="form-control" required autofocus>
            </div>

            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label class="form-label" for="ruc">RUC / ID Comercial (Opcional)</label>
                    <input type="text" id="ruc" name="ruc" class="form-control" maxlength="11">
                </div>
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label class="form-label" for="telefono">Teléfono (Opcional)</label>
                    <input type="text" id="telefono" name="telefono" class="form-control">
                </div>
            </div>

            <div class="form-group" style="margin-bottom:2rem;">
                <label class="form-label" for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" class="form-control">
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='Creando Tenant...';">Crear Gimnasio y Administrador</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
