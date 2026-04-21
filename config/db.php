<?php
// config/db.php

// ── URL Base del sistema ──────────────────────────────────────────────────────
// Calcula automáticamente la URL base según el entorno
if (!defined('BASE_URL')) {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // Obtener la ruta base desde la ubicación de este archivo
    $docRoot  = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $thisDir  = str_replace('\\', '/', dirname(__DIR__));
    $basePath = trim(str_replace($docRoot, '', $thisDir), '/');
    define('BASE_URL', $scheme . '://' . $host . '/' . ($basePath ? $basePath . '/' : ''));
}

// ── Constantes de conexión ────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gymsuite'); // Asegúrate de crear esta base de datos en phpMyAdmin

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
