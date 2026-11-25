<?php
require_once __DIR__ . '/../Config/conexion.php';

class Perfil
{
    private $conexion;

    public function __construct()
    {
        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
    }

    // 1. Obtener la información personal del asociado logueado
    public function obtenerDatosAsociado($documento)
    {
        $sql = "SELECT idAsociado, documento, nombre, apellidos, fechaNacimiento, 
                       genero, direccionResidencia, ciudad, telefono, email, rol
                FROM asociados 
                WHERE documento = :documento";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':documento' => $documento]);
        return $consulta->fetch();
    }

    // 2. Obtener únicamente los préstamos que pertenecen a este asociado
    public function obtenerMisPrestamos($idAsociado)
    {
        $sql = "SELECT p.idPrestamo,
                       p.valorPrestamo,
                       p.tasaInteres,
                       p.numeroCuotas,
                       p.fechaPrestamo,
                       COALESCE(SUM(pc.valorCuota), 0) AS totalPagado,
                       (p.valorPrestamo - COALESCE(SUM(pc.valorCuota), 0)) AS saldoPendiente
                FROM prestamos p
                LEFT JOIN pagos_cuotas pc ON p.idPrestamo = pc.idPrestamo
                WHERE p.idAsociado = :idAsociado
                GROUP BY p.idPrestamo
                ORDER BY p.fechaPrestamo DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idAsociado' => $idAsociado]);
        return $consulta->fetchAll();
    }

    // 3. Permitir al asociado actualizar sus datos de contacto
    public function actualizarContacto($documento, $telefono, $email, $direccion, $ciudad)
    {
        $sql = "UPDATE asociados 
                SET telefono = :telefono,
                    email = :email,
                    direccionResidencia = :direccion,
                    ciudad = :ciudad
                WHERE documento = :documento";

        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':telefono'  => $telefono,
            ':email'     => $email,
            ':direccion' => $direccion,
            ':ciudad'    => $ciudad,
            ':documento' => $documento
        ]);
    }
}
?>