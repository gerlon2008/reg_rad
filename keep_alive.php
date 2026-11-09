<?php
/**
 * REGISTRO RADIOLÓGICO - CNE / NA-SA
 * Keep-Alive Heartbeat Endpoint
 * Previene la caída de sesión PHP por inactividad (session.gc_maxlifetime = 1440s / 24m)
 * Compatible con PHP 5.5.7 y Apache 2.4
 */

// Iniciar o reanudar sesión existente
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Renovar timestamp de última actividad
$_SESSION['ultimo_acceso_regrad'] = time();

// Headers para respuesta JSON y no-cache
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$response = array(
    'status' => 'ok',
    'session_id' => session_id(),
    'servidor' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'sn-c-c55.cne.central.nasa',
    'timestamp' => time(),
    'fecha_hora' => date('d/m/Y H:i:s'),
    'mensaje' => 'Sesión activa renovada correctamente'
);

echo json_encode($response);
exit;
