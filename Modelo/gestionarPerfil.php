<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Perfil.php';

// Validar que la sesión esté activa
if (!isset($_SESSION['documento']) || empty($_SESSION['documento'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'succes' => false, 'mensaje' => 'Sesión expirada o no iniciada']);
    exit;
}

$documento = $_SESSION['documento'];
$perfilObj = new Perfil();
$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        // GET: Traer perfil y préstamos del asociado
        case 'GET':
            $datosAsociado = $perfilObj->obtenerDatosAsociado($documento);

            if (!$datosAsociado) {
                throw new Exception("Asociado no encontrado.");
            }

            $misPrestamos = $perfilObj->obtenerMisPrestamos($datosAsociado['idAsociado']);

            echo json_encode([
                'success'   => true,
                'succes'    => true,
                'asociado'  => $datosAsociado,
                'prestamos' => $misPrestamos
            ]);
            break;

        // PUT: Actualizar datos de contacto (teléfono, correo, dirección)
        case 'PUT':
            $datos = json_decode(file_get_contents('php://input'), true);

            $telefono  = trim($datos['telefono'] ?? '');
            $email     = trim($datos['email'] ?? '');
            $direccion = trim($datos['direccion'] ?? '');
            $ciudad    = trim($datos['ciudad'] ?? '');

            if (empty($telefono) || empty($email)) {
                throw new Exception("El teléfono y correo son obligatorios.");
            }

            $resultado = $perfilObj->actualizarContacto($documento, $telefono, $email, $direccion, $ciudad);

            echo json_encode([
                'success' => $resultado,
                'succes'  => $resultado,
                'mensaje' => $resultado ? 'Datos actualizados correctamente' : 'No se pudo actualizar'
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