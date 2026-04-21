<?php
// modules/recepcionistas/crear.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();
verificar_rol(['admin']); // Solo el admin puede crear

$gimnasio_id = get_gimnasio_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validar usuario existente (en todo el sistema, por ser login unificado)
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->execute([$usuario]);
    if ($stmt->fetch()) {
        mensaje_flash('error', 'El nombre de usuario ya está registrado en el sistema. Elige otro.');
    } elseif (strlen($password) < 6) {
        mensaje_flash('error', 'La contraseña debe tener al menos 6 caracteres.');
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmtIns = $pdo->prepare("
            INSERT INTO usuarios (gimnasio_id, nombre, apellido, usuario, password, rol, estado) 
            VALUES (?, ?, ?, ?, ?, 'recepcionista', 1)
        ");
        if ($stmtIns->execute([$gimnasio_id, $nombre, $apellido, $usuario, $hash])) {
            mensaje_flash('success', 'Recepcionista creado exitosamente.');
            header("Location: index.php");
            exit;
        } else {
            mensaje_flash('error', 'Error al crear el recepcionista.');
        }
    }
}

include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div style="max-width: 600px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="index.php" style="display:inline-flex;align-items:center;gap:0.35rem;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:0.5rem;text-decoration:none;" onmouseover="this.style.color='white'" onmouseout="this.style.color=''">
            <svg style="width:0.9rem;height:0.9rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Volver a la lista
        </a>
        <h1 class="text-h2">Nuevo Recepcionista</h1>
    </div>

    <?php mostrar_flash(); ?>

    <div class="card">
        <form method="POST" action="">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;">
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label class="form-label" for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" class="form-control" required>
                </div>
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label class="form-label" for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" class="form-control" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:1rem;">
                <label class="form-label" for="usuario">Usuario de Ingreso</label>
                <input type="text" id="usuario" name="usuario" class="form-control" placeholder="Ej. juan_recepcion" pattern="[a-zA-Z0-9_]+" title="Solo letras, números y guión bajo (_)" required autocomplete="off">
                <small style="color:var(--color-text-muted);font-size:0.75rem;margin-top:0.25rem;display:block;">Será necesario para iniciar sesión. No uses espacios.</small>
            </div>

            <div class="form-group" style="margin-bottom:2rem;">
                <label class="form-label" for="password">Contraseña</label>
                <input type="text" id="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required autocomplete="new-password">
            </div>

            <div style="display:flex;gap:1rem;justify-content:flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary" onclick="this.innerHTML='Guardando...';">Crear Recepcionista</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
