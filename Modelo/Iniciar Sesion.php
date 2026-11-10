<?php

session_start();

header("Content-Type: application/json; charset=utf-8");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new Exception('Método HTTP no permitido.');
    }

    // Leemos los datos enviados por JSON o por POST tradicional
    $datos = json_decode(file_get_contents('php://input'), true);
    if (empty($datos)) {
        $datos = $_POST;
    }

    if (empty($datos)) {
        http_response_code(400);
        throw new Exception("Datos inválidos");
    }

    // Capturamos el documento y la contraseña que envía tu formulario HTML
    $documento = $datos['documento'] ?? '';
    $password = $datos['password'] ?? '';
        
    if ($documento === '' || $password === '') {
        http_response_code(400);
        throw new Exception("Documento y contraseña inválidos.");
    }

    require_once __DIR__ . '/../Config/conexion.php';
    $conexion = (new Conexion())->conectar();

    // Buscamos al asociado en la base de datos usando el número de documento
    $consulta = $conexion->prepare("SELECT * FROM asociados WHERE documento = :documento");
    $consulta->execute([':documento' => $documento]);
    $asociado = $consulta->fetch();

    if (!$asociado || !password_verify($password, $asociado['password']) || empty($asociado['password'])) {
        http_response_code(401);
        throw new Exception("Documento o contraseña incorrectos.");
    }

    $_SESSION["rol"] = strtolower($asociado['rol']);
    $_SESSION["documento"] = $asociado['documento'];

    echo json_encode([
        "success" => true,
        "rol" => $_SESSION["rol"],
        "nombre" => $asociado["nombre"] . " " . $asociado["apellidos"],
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "mensaje" => "Error de base de datos: " . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false, 
        "mensaje" => $e->getMessage()
    ]);
}
?>