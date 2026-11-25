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

    private function validarCampos($datos)
    {
        if (!isset($datos['valor']) || !is_numeric($datos['valor']) || $datos['valor'] <= 0 || $datos['valor'] > 80000000) {
            throw new Exception("El valor del préstamo debe ser mayor a 0 y no superar los 80 millones de pesos.");
        }

        if (!isset($datos['tasa']) || !is_numeric($datos['tasa']) || $datos['tasa'] <= 0 || $datos['tasa'] > 100) {
            throw new Exception("La tasa de interés debe estar entre 0.1% y 100%.");
        }

        if (!isset($datos['cuotas']) || filter_var($datos['cuotas'], FILTER_VALIDATE_INT) === false || (int)$datos['cuotas'] < 1 || (int)$datos['cuotas'] > 120) {
            throw new Exception("El número de cuotas debe ser un entero entre 1 y 120.");
        }
    }

    private function validarMaximoTresPrestamos($idAsociado, $excluirId = null)
    {
        if ($excluirId !== null) {
            $sql = "SELECT COUNT(*) AS total FROM prestamos WHERE idAsociado = :idAsociado AND idPrestamo != :idPrestamo";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([
                ':idAsociado' => $idAsociado,
                ':idPrestamo' => $excluirId
            ]);
        } else {
            $sql = "SELECT COUNT(*) AS total FROM prestamos WHERE idAsociado = :idAsociado";
            $stmt = $this->conexion->prepare($sql);
            $stmt->execute([':idAsociado' => $idAsociado]);
        }

        $fila = $stmt->fetch();
        if ((int)$fila['total'] >= 3) {
            throw new Exception("El asociado ya tiene 3 préstamos registrados y no puede registrar más.");
        }
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
        $this->validarCampos($datos);

        // Buscar el idAsociado real a partir del documento ingresado
        $sqlBuscar = "SELECT idAsociado FROM asociados WHERE documento = :documento";
        $consultaBuscar = $this->conexion->prepare($sqlBuscar);
        $consultaBuscar->execute([':documento' => $datos['documento']]);
        $asociado = $consultaBuscar->fetch();

        if (!$asociado) {
            throw new Exception("El documento del asociado no existe en la base de datos.");
        }

        $idAsociadoReal = $asociado['idAsociado'];
        $this->validarMaximoTresPrestamos($idAsociadoReal);

        $sql = "INSERT INTO prestamos (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado)
                VALUES (:valor, :tasa, :cuotas, :fechaPrestamo, :idAsociado)";
        
        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':valor'         => $datos['valor'],
            ':tasa'          => $datos['tasa'],
            ':cuotas'        => (int)$datos['cuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado'    => $idAsociadoReal
        ]);
    }

    // 3. Editar préstamo existente
    public function editar($datos)
    {
        $this->validarCampos($datos);

        // Buscar el idAsociado real a partir del documento ingresado
        $sqlBuscar = "SELECT idAsociado FROM asociados WHERE documento = :documento";
        $consultaBuscar = $this->conexion->prepare($sqlBuscar);
        $consultaBuscar->execute([':documento' => $datos['documento']]);
        $asociado = $consultaBuscar->fetch();

        if (!$asociado) {
            throw new Exception("El documento del asociado no existe en la base de datos.");
        }

        $idAsociadoReal = $asociado['idAsociado'];
        $this->validarMaximoTresPrestamos($idAsociadoReal, $datos['codigo']);

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
            ':cuotas'        => (int)$datos['cuotas'],
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