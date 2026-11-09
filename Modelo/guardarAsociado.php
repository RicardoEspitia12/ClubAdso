<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../Modelo/Asociado.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Método HTTP no permitido.');
    }
    
    // Leemos el JSON que envía tu JavaScript de la foto
    $json = file_get_contents('php://input');
    $datos = json_decode($json, true);

    if (empty($datos)) {
        http_response_code(400);
        throw new Exception('Datos inválidos.');
    }

    $campos = ["documento", "nombre", "apellidos", "fechaNacimiento", "genero", "direccionResidencia", "ciudad", "telefono", "email", "password"];
    foreach ($campos as $campo) {
        if (!isset($datos[$campo]) || trim($datos[$campo]) === '') { 
            http_response_code(400);
            throw new Exception("Falta el campo: " . $campo);
        }
    }

    if (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        throw new Exception("Email inválido.");
    }

    $asociado = new Asociado($datos);
    $resultado = $asociado->guardar();

    if ($resultado) {
        http_response_code(201);
        // Ojo: tu JS de la foto lee "resultado.mensaje", por eso aquí mandamos "mensaje"
        echo json_encode(["success" => true, "mensaje" => "Asociado guardado exitosamente."]);
    } else {  
        http_response_code(500);
        echo json_encode(["success" => false, "mensaje" => "Error al guardar el asociado."]);
    }

} catch (PDOException $e) {
    if ($e->getCode() == 23000) { 
        http_response_code(409); 
        echo json_encode(["success" => false, "mensaje" => "El documento ya existe."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "mensaje" => "Error de base de datos: " . $e->getMessage()]);
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["success" => false, "mensaje" => $e->getMessage()]);
}
?>