<?php
// includes/functions.php

session_start();

/**
 * Verifica si hay una sesión activa, si no la hay redirige al index (login).
 */
function verificar_sesion() {
    if (!isset($_SESSION['usuario_id'])) {
        header("Location: /gymsuite/index.php");
        exit;
    }
}

/**
 * Verifica si el rol del usuario conectado está dentro del array de roles permitidos.
 * 
 * @param array $roles Permitidos ej: ['admin', 'superadmin']
 */
function verificar_rol($roles) {
    if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $roles)) {
        // Si no tiene acceso, redirigir al dashboard general (dashboard/index.php ruteará correcto)
        header("Location: /gymsuite/modules/dashboard/index.php");
        exit;
    }
}

/**
 * Retorna el gimnasio_id almacenado en la sesión.
 * Si es superadmin puede no tener gimnasio definido en sesión permanente 
 * (lo selecciona al gestionar cada gym), pero en general sirve para admin/recepcionista.
 */
function get_gimnasio_id() {
    return isset($_SESSION['gimnasio_id']) ? $_SESSION['gimnasio_id'] : null;
}

/**
 * Calcula los días restantes hasta que la membresía finalice.
 * Si retorna negativo es que ya venció.
 */
function dias_restantes($fecha_fin) {
    $fechaFinObj = new DateTime($fecha_fin);
    $hoy = new DateTime();
    // Resetear las horas para cálculo de días puramente de fechas
    $fechaFinObj->setTime(0,0,0);
    $hoy->setTime(0,0,0);
    
    $diferencia = $hoy->diff($fechaFinObj);
    $dias = $diferencia->days;
    
    // Si la fecha actual superó a la de fin, los días son negativos
    if ($hoy > $fechaFinObj) {
        return -$dias;
    }
    return $dias;
}

/**
 * Calcula el estado textual de la membresía para mostrar en la interfaz.
 */
function calcular_estado_membresia($fecha_fin) {
    $dias = dias_restantes($fecha_fin);
    
    if ($dias < 0) {
        return 'vencida';
    } elseif ($dias <= 7) {
        return 'por_vencer';
    } else {
        return 'activa';
    }
}

/**
 * Formatea una fecha en formato español "15 de enero de 2026"
 */
function formatear_fecha($fecha) {
    $timestamp = strtotime($fecha);
    $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    $dia = date('j', $timestamp);
    $mesIndex = date('n', $timestamp) - 1;
    $anio = date('Y', $timestamp);
    
    return $dia . ' de ' . $meses[$mesIndex] . ' de ' . $anio;
}

/**
 * Formatea un monto a Soles
 */
function formatear_moneda($monto) {
    return 'S/ ' . number_format($monto, 2, '.', ',');
}

/**
 * Genera un token QR único seguro
 */
function generar_token_qr() {
    return bin2hex(random_bytes(16));
}

/**
 * Guarda un mensaje flash en la sesión para mostrarse al redireccionar.
 * @param string $tipo 'success', 'error', 'warning', 'info'
 * @param string $texto El mensaje.
 */
function mensaje_flash($tipo, $texto) {
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'texto' => $texto
    ];
}

/**
 * Muestra el mensaje flash si existe y luego lo destruye.
 */
function mostrar_flash() {
    if (isset($_SESSION['flash'])) {
        $tipo = $_SESSION['flash']['tipo'];
        $texto = $_SESSION['flash']['texto'];
        
        $colorClases = "bg-gray-800 text-white"; // default
        if($tipo === 'success') $colorClases = "bg-green-600/20 text-green-400 border border-green-500/30";
        if($tipo === 'error') $colorClases = "bg-red-600/20 text-red-400 border border-red-500/30";
        if($tipo === 'warning') $colorClases = "bg-orange-600/20 text-orange-400 border border-orange-500/30";
        
        echo "<div class='p-4 rounded-lg mb-6 flex items-center justify-between {$colorClases}'>";
        echo "<p>{$texto}</p>";
        echo "<button onclick='this.parentElement.remove()' class='text-gray-400 hover:text-white'>&times;</button>";
        echo "</div>";
        
        unset($_SESSION['flash']);
    }
}

/**
 * Aplica htmlspecialchars para prevenir XSS.
 */
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>
