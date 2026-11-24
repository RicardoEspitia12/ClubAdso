<?php
require_once __DIR__ . '/../Config/conexion.php';

class Asociado
{
    private $conexion;

    public function __construct()
    {
        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
    }

    // 1. OBTENER TODOS LOS ASOCIADOS (Para la tabla)
    public function obtenerAsociados()
    {
        $sql = "SELECT documento, nombre, apellidos, fechaNacimiento, genero, direccionResidencia, ciudad, telefono, email, rol 
                FROM asociados 
                ORDER BY apellidos ASC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    // 2. GUARDAR UN NUEVO ASOCIADO
    public function guardar($datos) 
    {
        $passwordHash = password_hash($datos['password'], PASSWORD_DEFAULT);
        $rol = !empty($datos['rol']) ? $datos['rol'] : 'asociado';

        $sql = "INSERT INTO asociados (documento, nombre, apellidos, fechaNacimiento, genero, direccionResidencia, ciudad, telefono, email, password, rol) 
                VALUES (:documento, :nombre, :apellidos, :fechaNacimiento, :genero, :direccionResidencia, :ciudad, :telefono, :email, :password, :rol)"; 
        
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':documento'           => $datos['documento'],
            ':nombre'              => $datos['nombre'],
            ':apellidos'           => $datos['apellidos'],
            ':fechaNacimiento'     => $datos['fechaNacimiento'],
            ':genero'              => $datos['genero'],
            ':direccionResidencia' => $datos['direccionResidencia'],
            ':ciudad'              => $datos['ciudad'],
            ':telefono'            => $datos['telefono'],
            ':email'               => $datos['email'],
            ':password'            => $passwordHash,
            ':rol'                 => $rol
        ]);
    }

    // 3. EDITAR ASOCIADO (Permite cambiar datos y su ROL)
    public function editar($datos)
    {
        $sql = "UPDATE asociados 
                SET nombre = :nombre, 
                    apellidos = :apellidos, 
                    fechaNacimiento = :fechaNacimiento, 
                    genero = :genero, 
                    direccionResidencia = :direccionResidencia, 
                    ciudad = :ciudad, 
                    telefono = :telefono, 
                    email = :email, 
                    rol = :rol 
                WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':nombre'              => $datos['nombre'],
            ':apellidos'           => $datos['apellidos'],
            ':fechaNacimiento'     => $datos['fechaNacimiento'],
            ':genero'              => $datos['genero'],
            ':direccionResidencia' => $datos['direccionResidencia'],
            ':ciudad'              => $datos['ciudad'],
            ':telefono'            => $datos['telefono'],
            ':email'               => $datos['email'],
            ':rol'                 => $datos['rol'],
            ':documento'           => $datos['documento']
        ]);
    }

    // 4. ELIMINAR ASOCIADO POR SU DOCUMENTO
    public function eliminar($documento)
    {
        $sql = "DELETE FROM asociados WHERE documento = :documento";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':documento' => $documento]);
        return $consulta->rowCount() > 0;
    }
}
?>