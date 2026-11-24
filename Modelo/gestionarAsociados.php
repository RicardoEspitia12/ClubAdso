<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Asociado.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$asociadoObj = new Asociado();

try {
    switch ($metodo) {
        // LISTAR ASOCIADOS
        case 'GET':
            $lista = $asociadoObj->obtenerAsociados();
            echo json_encode([
                'success' => true,
                'asociados' => $lista
            ]);
            break;

        // GUARDAR NUEVO ASOCIADO
        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (empty($datos['password'])) {
                // Si el admin no escribe contraseña, se le asigna por defecto el documento
                $datos['password'] = (string)$datos['documento'];
            }
            $resultado = $asociadoObj->guardar($datos);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Asociado registrado con éxito' : 'No se pudo registrar el asociado'
            ]);
            break;

        // EDITAR ASOCIADO Y ROL
        case 'PUT':
            $datos = json_decode(file_get_contents('php://input'), true);
            $resultado = $asociadoObj->editar($datos);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Asociado actualizado con éxito' : 'No se pudo actualizar el asociado'
            ]);
            break;

        // ELIMINAR ASOCIADO
        case 'DELETE':
            $datos = json_decode(file_get_contents('php://input'), true);
            $resultado = $asociadoObj->eliminar($datos['documento']);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Asociado eliminado con éxito' : 'No se pudo eliminar el asociado'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'mensaje' => 'Método no soportado']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>