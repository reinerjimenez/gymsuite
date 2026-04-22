<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

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
    <title>GymSuite — Panel</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/components.css">
    <style>
        /* ── Sidebar ───────────────────────────────── */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--color-surface);
            border-right: 1px solid var(--color-border);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Push main content */
        .main-content { margin-left: var(--sidebar-width); }

        .sidebar-logo-area {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 120px;
            padding: 1.5rem 1.25rem;
            border-bottom: 1px solid var(--color-border-subtle);
            flex-shrink: 0;
        }

        .sidebar-brand-text {
            font-family: var(--font-heading);
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--color-text-main);
        }

        .sidebar-brand-text span { color: var(--color-primary); }

        .sidebar-section-label {
            padding: 0.875rem 1.25rem 0.375rem;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--color-text-muted);
        }

        .sidebar-footer {
            border-top: 1px solid var(--color-border-subtle);
            padding: 0.625rem 0.625rem;
            flex-shrink: 0;
        }

        .sidebar-user-chip {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.5rem 0.625rem;
            border-radius: var(--radius-md);
            margin-bottom: 0.25rem;
        }

        .sidebar-user-info { min-width: 0; }
        .sidebar-user-name {
            font-size: var(--text-sm);
            font-weight: 600;
            color: var(--color-text-main);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .sidebar-user-role {
            font-size: 0.6875rem;
            color: var(--color-text-muted);
            text-transform: capitalize;
            font-weight: 500;
        }

        .sidebar-avatar {
            width: 2rem; height: 2rem;
            border-radius: 50%;
            background: var(--color-primary-dim);
            border: 1.5px solid rgba(249,115,22,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700;
            color: var(--color-primary);
            font-family: var(--font-heading);
            flex-shrink: 0;
        }

        .sidebar-scroll { flex: 1; overflow-y: auto; padding-bottom: 0.5rem; }

        /* ── Header ───────────────────────────────── */
        .header {
            height: var(--header-height);
            background: rgba(8,8,8,0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--color-border-subtle);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.75rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .header-title {
            font-family: var(--font-heading);
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--color-text-main);
            letter-spacing: -0.01em;
        }

        .header-right { display: flex; align-items: center; gap: 0.75rem; }

        .header-user {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            padding: 0.375rem 0.75rem;
            border-radius: var(--radius-lg);
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            transition: var(--transition-fast);
            cursor: default;
        }
        .header-user:hover { border-color: var(--color-border-strong); background: var(--color-surface-raised); }

        .header-avatar {
            width: 1.875rem; height: 1.875rem;
            border-radius: 50%;
            background: var(--color-primary-dim);
            border: 1.5px solid rgba(249,115,22,0.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 0.75rem; font-weight: 700;
            color: var(--color-primary);
            font-family: var(--font-heading);
            flex-shrink: 0;
        }

        .header-user-name { font-size: var(--text-sm); font-weight: 600; color: var(--color-text-main); }
        .header-user-role { font-size: 0.6875rem; color: var(--color-text-muted); text-transform: capitalize; }

        /* ── Mobile toggle ─────────────────────────── */
        .mobile-toggle-btn {
            display: none;
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            color: var(--color-text-secondary);
            cursor: pointer;
            padding: 0.4rem;
            align-items: center;
            justify-content: center;
            transition: var(--transition-fast);
        }
        .mobile-toggle-btn:hover { color: var(--color-text-main); border-color: var(--color-border-strong); }
        .mobile-toggle-btn svg { width: 1.25rem; height: 1.25rem; display: block; }

        /* ── Overlay ───────────────────────────────── */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            z-index: 90;
        }

        /* ── Responsive ────────────────────────────── */
        @media (max-width: 900px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: var(--shadow-xl);
            }
            .sidebar.sidebar-open { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .mobile-toggle-btn { display: flex; }
            .sidebar-overlay.show { display: block; }
        }
    </style>
</head>
<body>
<div class="app-wrapper">
    <div class="sidebar-overlay" id="sidebar_overlay"></div>
