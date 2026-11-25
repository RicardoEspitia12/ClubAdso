<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/Reporte.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$reporteObj = new Reporte();

try {
    switch ($metodo) {
        case 'GET':
            $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : 'general';

            if ($tipo === 'resumen') {
                $resumen = $reporteObj->obtenerResumen();
                echo json_encode([
                    'success' => true,
                    'succes'  => true,
                    'rol'     => isset($_SESSION['rol']) ? $_SESSION['rol'] : 'admin',
                    'resumen' => $resumen
                ]);
            } else {
                $filtros = [
                    'fechaInicio' => isset($_GET['fechaInicio']) ? trim($_GET['fechaInicio']) : '',
                    'fechaFin'    => isset($_GET['fechaFin']) ? trim($_GET['fechaFin']) : '',
                    'documento'   => isset($_GET['documento']) ? trim($_GET['documento']) : ''
                ];

                $reportes = $reporteObj->consultarReportes($filtros);
                echo json_encode([
                    'success'  => true,
                    'succes'   => true,
                    'rol'      => isset($_SESSION['rol']) ? $_SESSION['rol'] : 'admin',
                    'reportes' => $reportes
                ]);
            }
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