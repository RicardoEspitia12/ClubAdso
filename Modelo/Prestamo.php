<?php
require_once __DIR__ . '/../Config/conexion.php';

class Prestamo
{
    private $conexion;

    public function __construct()
    {
        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
    }

    // 1. Obtener la lista de préstamos de la base de datos
    public function obtenerPrestamos()
    {
        $sql = "SELECT p.idPrestamo, p.valorPrestamo, p.tasaInteres, p.numeroCuotas, p.fechaPrestamo,
                       a.documento AS idAsociado,
                       CONCAT(a.nombre, ' ', a.apellidos) AS nombreAsociado
                FROM prestamos p
                INNER JOIN asociados a ON p.idAsociado = a.idAsociado
                ORDER BY p.idPrestamo DESC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        return $consulta->fetchAll();
    }

    // 2. Guardar nuevo préstamo (idPrestamo es auto_increment en MySQL)
    public function guardar($datos)
    {
        // Buscar el idAsociado real a partir del documento ingresado
        $sqlBuscar = "SELECT idAsociado FROM asociados WHERE documento = :documento";
        $consultaBuscar = $this->conexion->prepare($sqlBuscar);
        $consultaBuscar->execute([':documento' => $datos['documento']]);
        $asociado = $consultaBuscar->fetch();

        if (!$asociado) {
            throw new Exception("El documento del asociado no existe en la base de datos.");
        }

        $idAsociadoReal = $asociado['idAsociado'];

        $sql = "INSERT INTO prestamos (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado)
                VALUES (:valor, :tasa, :cuotas, :fechaPrestamo, :idAsociado)";
        
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':valor'         => $datos['valor'],
            ':tasa'          => $datos['tasa'],
            ':cuotas'        => $datos['cuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado'    => $idAsociadoReal
        ]);
    }

    // 3. Editar préstamo existente
    public function editar($datos)
    {
        // Buscar el idAsociado real a partir del documento ingresado
        $sqlBuscar = "SELECT idAsociado FROM asociados WHERE documento = :documento";
        $consultaBuscar = $this->conexion->prepare($sqlBuscar);
        $consultaBuscar->execute([':documento' => $datos['documento']]);
        $asociado = $consultaBuscar->fetch();

        if (!$asociado) {
            throw new Exception("El documento del asociado no existe en la base de datos.");
        }

        $idAsociadoReal = $asociado['idAsociado'];

        $sql = "UPDATE prestamos 
                SET valorPrestamo = :valor,
                    tasaInteres   = :tasa,
                    numeroCuotas  = :cuotas,
                    fechaPrestamo = :fechaPrestamo,
                    idAsociado    = :idAsociado
                WHERE idPrestamo  = :codigo";

        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':valor'         => $datos['valor'],
            ':tasa'          => $datos['tasa'],
            ':cuotas'        => $datos['cuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado'    => $idAsociadoReal,
            ':codigo'        => $datos['codigo']
        ]);
    }

    // 4. Eliminar préstamo
    public function eliminar($idPrestamo)
    {
        $sql = "DELETE FROM prestamos WHERE idPrestamo = :codigo";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':codigo' => $idPrestamo]);
        return $consulta->rowCount() > 0;
    }
}
?>