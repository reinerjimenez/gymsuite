<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

verificar_sesion();

// Redireccionar al dashboard correspondiente según rol (O renderizar directamente aquí)
// Para el superadmin será el único archivo que está fuera de su propia carpeta 
// o bien podemos mostrar los dashboards aquí directamente. El prompt dice:
// modules/dashboard/index.php <- redirige al dashboard según rol
// modules/socio/dashboard.php 

$rol = $_SESSION['rol'];

if ($rol === 'superadmin') {
    // Redireccionarlo a su propio index de superadmin que maneja la tabla de gyms
    header("Location: /gymsuite/modules/superadmin/index.php");
    exit;
} elseif ($rol === 'socio') {
    // Redireccionarlo a su dashboard especial para móvil
    header("Location: /gymsuite/modules/socio/dashboard.php");
    exit;
}

// Para admin o recepcionista los mostramos aquí mismo
include __DIR__ . '/../../includes/header.php';
include __DIR__ . '/../../includes/sidebar.php';
?>

<div>
    <h1 class="text-h2 mb-2">Bienvenido, <?= h($_SESSION['nombre']) ?></h1>
    <p class="text-muted mb-6">Resumen de tu panel de control.</p>
    
    <div class="dashboard-grid cols-4">
        <div class="stat-card">
            <span class="stat-title">Ingresos del Mes</span>
            <span class="stat-value">S/ --</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Socios Activos</span>
            <span class="stat-value">--</span>
        </div>
        <div class="stat-card alert">
            <span class="stat-title alert">Por Vencer</span>
            <span class="stat-value alert">--</span>
        </div>
        <div class="stat-card">
            <span class="stat-title">Entradas Hoy</span>
            <span class="stat-value">--</span>
        </div>
    </div>
    
    <div class="card mb-6">
        <p class="text-center text-muted">Las tarjetas mostrarán información real en la próxima iteración.</p>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
