<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Prestamo.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$prestamoObj = new Prestamo();

try {
    switch ($metodo) {
        // GET: Listar préstamos
        case 'GET':
            $lista = $prestamoObj->obtenerPrestamos();
            echo json_encode([
                'success' => true,
                'prestamos' => $lista
            ]);
            break;

        // POST: Registrar préstamo
        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);

            if ($datos['valor'] > 80000000) {
                throw new Exception("El valor no puede superar los 80 millones de pesos.");
            }

            $resultado = $prestamoObj->guardar($datos);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Préstamo registrado con éxito' : 'No se pudo registrar el préstamo'
            ]);
            break;

        // PUT: Actualizar préstamo
        case 'PUT':
            $datos = json_decode(file_get_contents('php://input'), true);

            if ($datos['valor'] > 80000000) {
                throw new Exception("El valor no puede superar los 80 millones de pesos.");
            }

            $resultado = $prestamoObj->editar($datos);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Préstamo actualizado con éxito' : 'No se pudo actualizar el préstamo'
            ]);
            break;

        // DELETE: Eliminar préstamo
        case 'DELETE':
            $datos = json_decode(file_get_contents('php://input'), true);
            $resultado = $prestamoObj->eliminar($datos['idPrestamo']);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Préstamo eliminado con éxito' : 'No se pudo eliminar el préstamo'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'mensaje' => 'Método no soportado']);
            break;
    }
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['success' => false, 'mensaje' => 'El documento del asociado no existe en la base de datos.']);
    } else {
        echo json_encode(['success' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>