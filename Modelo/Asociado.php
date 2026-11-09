<?php
require_once __DIR__ . '/../Config/conexion.php';

class Asociado
{
    private $documento;
    private $nombre;
    private $apellidos;
    private $fechaNacimiento;
    private $genero;
    private $direccionResidencia;
    private $ciudad;
    private $telefono;
    private $email;
    private $passwordHash;
    private $password;
    private $rol;

    private $conexion;

    public function __construct($datos)
    {
        $this->documento = $datos['documento'];
        $this->nombre = $datos['nombre'];
        $this->apellidos = $datos['apellidos'];
        $this->fechaNacimiento = $datos['fechaNacimiento'];
        $this->genero = $datos['genero'];
        $this->direccionResidencia = $datos['direccionResidencia'];
        $this->ciudad = $datos['ciudad'];
        $this->telefono = $datos['telefono'];
        $this->email = $datos['email'];
        $this->passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
        
        // Si el formulario envía un rol, lo toma; de lo contrario, asigna 'asociado' por defecto
        $this->rol = isset($datos['rol']) && !empty($datos['rol']) ? $datos['rol'] : 'asociado';

        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
    }

    public function guardar() 
    {
        // Hashea la contraseña de manera segura 
        $passwordHash = password_hash($this->password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO asociados (documento, nombre, apellidos, fechaNacimiento, genero, direccionResidencia, ciudad, telefono, email, :password_hash, rol) 
                VALUES (:documento, :nombre, :apellidos, :fechaNacimiento, :genero, :direccionResidencia, :ciudad, :telefono, :email, :password_hash, :rol)"; 
        
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':documento' => $this->documento,
            ':nombre' => $this->nombre,
            ':apellidos' => $this->apellidos,
            ':fechaNacimiento' => $this->fechaNacimiento,
            ':genero' => $this->genero,
            ':direccionResidencia' => $this->direccionResidencia,
            ':ciudad' => $this->ciudad,
            ':telefono' => $this->telefono,
            ':email' => $this->email,
            ':password_hash' => $this->passwordHash,
            ':rol' => $this->rol
        ]);
    }
}
?>