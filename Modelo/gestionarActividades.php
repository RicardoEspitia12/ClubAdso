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

function validarActividad($datos){
    $nombre=$datos['nombreActividad'] ?? null;
    $fecha = $datos['fechaEvento'] ?? null;
    $municipio = $datos['municipio'] ?? null;
    $totalRecaudado = $datos['totalRecaudado'] ?? 0;
    if(!is_string($nombre)|| trim($nombre)===""|| strlen($nombre)> 100 || !is_string($municipio) || trim($municipio) === "" || strlen($municipio) > 50) {
       http_response_code(400);
       throw new Exception(
        "Ingresa un nombre y municipio validos"
       ); 
    }
    return[
        "nombreActividad"=>trim($nombre),
        "fechaEvento"=>$fecha,
        "municipio"=>trim($municipio),
        "totalRecaudado"=>$totalRecaudado
    ];
}

function validarId($id){
    $idActividad=filter_var($id,FILTER_VALIDATE_INT,["OPTIONS" => ["min_range" => 1]]);
    if($idActividad === false) {
        http_response_code(400);
        throw new Exception("El identificador de la actividad no es valido");
    }
    return $idActividad;
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

        case "PUT":
            $datos=leerDatos();
            $datos["idActividad"]=validarId($datos["idActividad"]??null);
            $datos = array_merge($datos,validarActividad($datos));
            
            if (!$actividad->editar($datos)){
                http_response_code(404);
                throw new Exception("No se encontro la actividad");
            }
            $resultado= [
                "succes" => true,
                "mensaje" => "Actividad actualizada correctamente"
            ];
            
            http_response_code(200);
            break;

        case "DELETE":
            $datos=leerDatos();
            $idActividad=validarId($datos["idActividad"]??null);
            if (!$actividad->eliminar($idActividad)){
                http_response_code(404);
                throw new Exception("No se encontro la actividad");
            }
            $resultado= [
                "succes" => true,
                "mensaje" => "Actividad eliminada correctamente"
            ];
            http_response_code(200);
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