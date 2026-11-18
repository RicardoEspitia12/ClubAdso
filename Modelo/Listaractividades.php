<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'Actividad.php'; // Apunta a tu archivo Actividad.php en la misma carpeta Modelo/

try {
    $actividadObj = new Actividad();
    $lista = $actividadObj->obtenerActividades();

    echo json_encode([
        'success' => true,
        'datos' => $lista
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}
?>