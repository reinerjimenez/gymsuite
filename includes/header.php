<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Cargar datos de configuración del gym si aplica (si está logueado como admin, recepcionista o socio)
$gym_logo = null;
$gym_nombre = "GymSuite";

if (isset($_SESSION['gimnasio_id'])) {
    $stmt = $pdo->prepare("SELECT nombre, logo FROM gimnasios WHERE id = ?");
    $stmt->execute([$_SESSION['gimnasio_id']]);
    $g = $stmt->fetch();
    if ($g) {
        $gym_nombre = $g['nombre'];
        $gym_logo = $g['logo'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GymSuite</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
    <!-- Heroicons Script (CDN) -->
    <script src="https://unpkg.com/@heroicons/v2/24/outline/index.js"></script>
    <style>
        /* Estilos específicos de layout dinámico para la app protegida */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--color-surface);
            border-right: 1px solid #262626;
            display: flex;
            flex-direction: column;
            transition: transform 0.3s ease;
        }
        .header {
            height: var(--header-height);
            background-color: var(--color-surface);
            border-bottom: 1px solid #262626;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
        }
        
        .mobile-toggle-btn {
            display: none;
            background: transparent;
            border: none;
            color: var(--color-text-muted);
            cursor: pointer;
            padding: 0.5rem;
            align-items: center;
            justify-content: center;
        }
        .mobile-toggle-btn:hover { color: white; }
        .mobile-toggle-btn svg { width: 1.75rem; height: 1.75rem; }
        .desktop-only-text { display: block; }
        
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.6);
            backdrop-filter: blur(2px);
            z-index: 30;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                z-index: 40;
                height: 100vh;
                transform: translateX(-100%);
            }
            .sidebar.sidebar-open {
                transform: translateX(0);
            }
            .mobile-toggle-btn { display: flex; }
            .desktop-only-text { display: none; }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <div class="sidebar-overlay" id="sidebar_overlay"></div>

    <!-- Se incluiría el sidebar.php justo después -->
