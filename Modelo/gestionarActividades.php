<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

function leerDatos()
{
    $datos = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }
    return $datos;
}

try {
    if (!isset($_SESSION["documento"], $_SESSION["rol"])) {
        http_response_code(401);
        throw new Exception("Inicia sesion para consultar las actividades");
    }

    $metodo = $_SERVER["REQUEST_METHOD"];

    if (!in_array($metodo, ["GET", "POST", "PUT", "DELETE"], true)) {
        http_response_code(405);
        throw new Exception("Metodo HTTP no permitido");
    }

    if ($metodo !== "GET" && $_SESSION["rol"] !== "admin") {
        http_response_code(403);
        throw new Exception("No tienes permiso para realizar esta accion");
    }

    require_once __DIR__ . "/Actividad.php";
    $actividad = new Actividad();

    $resultado = [];

    switch ($metodo) {
        case "GET":
            $resultado = [
                "succes" => true,
                "rol" => $_SESSION["rol"],
                "actividades" => $actividad->obteneractividades()
            ];
            http_response_code(200);
            break;

        case "POST":
            $datos = validarActividad(leerDatos());
            $actividad->guardar($datos);
            $resultado = [
                "succes" => true,
                "message" => "Actividad creada exitosamente"
            ];
            http_response_code(201);
            break;

        default:
            http_response_code(405);
            throw new Exception("Metodo HTTP no soportado");
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    if (http_response_code() < 400) {
        http_response_code(500);
    }
    echo json_encode([
        "succes" => false,
        "message" => $e->getMessage()
    ]);
}