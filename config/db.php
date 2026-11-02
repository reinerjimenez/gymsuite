<?php
// config/db.php

// Toda la aplicacion y la sesion de MySQL deben usar el mismo reloj.
// Colombia usa UTC-05:00 durante todo el ano (sin horario de verano).
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', 'America/Bogota');
}
date_default_timezone_set(APP_TIMEZONE);

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
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true
        )
    );

    // Se ejecuta en cada solicitud, incluso si PDO reutiliza una conexion
    // persistente, para que NOW(), CURDATE() y PHP coincidan.
    $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>
