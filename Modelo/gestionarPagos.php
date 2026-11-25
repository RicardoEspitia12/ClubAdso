<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Pago.php';

// Validar sesión
if (!isset($_SESSION['documento']) || empty($_SESSION['documento'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'succes' => false, 'mensaje' => 'Sesión expirada o no iniciada']);
    exit;
}

$documento = $_SESSION['documento'];
$pagoObj = new Pago();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        // GET: Listar los pagos del asociado
        case 'GET':
            $pagos = $pagoObj->obtenerMisPagos($documento);
            echo json_encode([
                'success' => true,
                'succes'  => true,
                'pagos'   => $pagos
            ]);
            break;

        // POST: Registrar un nuevo abono
        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (empty($datos)) {
                $datos = $_POST;
            }

            $resultado = $pagoObj->registrarAbono($datos, $documento);

            echo json_encode([
                'success' => $resultado,
                'succes'  => $resultado,
                'mensaje' => $resultado ? '¡Abono registrado con éxito!' : 'No se pudo registrar el abono'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'succes' => false, 'mensaje' => 'Método no soportado']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'succes' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'succes' => false, 'mensaje' => $e->getMessage()]);
}
?>