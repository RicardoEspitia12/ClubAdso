<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Prestamo.php';

// Validar sesión
if (!isset($_SESSION['documento']) || empty($_SESSION['documento'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'mensaje' => 'Sesión expirada o no iniciada']);
    exit;
}

$documento = $_SESSION['documento'];
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    $prestamoObj = new Prestamo();

    switch ($metodo) {
        // GET: mis solicitudes y préstamos
        case 'GET':
            echo json_encode([
                'success' => true,
                'solicitudes' => $prestamoObj->obtenerMisSolicitudes($documento)
            ]);
            break;

        // POST: enviar una solicitud (queda Pendiente)
        case 'POST':
            $datos = json_decode(file_get_contents('php://input'), true);
            if (empty($datos)) {
                throw new Exception("Datos inválidos.");
            }
            $resultado = $prestamoObj->solicitar($datos, $documento);
            echo json_encode([
                'success' => $resultado,
                'mensaje' => $resultado ? 'Solicitud enviada. El administrador la revisará pronto.' : 'No se pudo enviar la solicitud'
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'mensaje' => 'Método no soportado']);
            break;
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}
?>