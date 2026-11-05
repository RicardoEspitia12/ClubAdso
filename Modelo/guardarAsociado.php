<?php
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . '/../Modelo/Asociado.php';

try{
if($_SERVER[ 'REQUEST_METHOD']!=='POST'){
        http_response_code(405);

        //Saltar a catch para manejar el error
        throw new Exception('Método  HTTPno permitido.');
    }
    $json = file_get_contents('php://input');

    //decodificar el JSON a un array asociativo de PHP
    $datos = json_decode($json, true);

    if(!is_array($datos) || json_last_error() !== JSON_ERROR_NONE){
        http_response_code(400);
        throw new Exception('Datos inválidos.');
    }

    $campos=["documento","nombre","apellidos","fechaNacimiento","genero","direccionResidencia","ciudad","telefono","email"];
    foreach($campos as $campo){
        if(!isset($datos[$campo])){ 
            http_response_code(400);
            throw new Exception("Falta el campo:" . $campo);
        }
    }
    if(!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)){
        http_response_code(400);
        throw new Exception("Email inválido.");
    }

    $asociado = new Asociado($datos);

    $asociado->guardar();
    if($resultado){
        http_response_code(201);
        echo json_encode(["success" => true, "message" => "Asociado guardado exitosamente."]);
   
   }else{  
    
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error al guardar el asociado."]);
     }
}catch(PDOException $e){
    if($e->getCode() == 23000) { // Codigo de error para datos duplicados en MySQL
        http_response_code(409); // Conflicto
        echo json_encode(["success" => false, "message" => "El documento ya existe."]);
    } else {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Error de base de datos: " . $e->getMessage()]);
    }


//errores de codigo por ejemplo metodo http, datos invalidos
}catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);


}
