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
        // CAMBIO OBLIGATORIO: se agregó p.estado al SELECT
        $sql = "SELECT p.idPrestamo, p.valorPrestamo, p.tasaInteres, p.numeroCuotas, p.fechaPrestamo, p.estado,
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
        $this->validarDatos($datos); // AGREGADO

        // Buscar el idAsociado real a partir del documento ingresado
        $sqlBuscar = "SELECT idAsociado FROM asociados WHERE documento = :documento";
        $consultaBuscar = $this->conexion->prepare($sqlBuscar);
        $consultaBuscar->execute([':documento' => $datos['documento']]);
        $asociado = $consultaBuscar->fetch();

        if (!$asociado) {
            throw new Exception("El documento del asociado no existe en la base de datos.");
        }

        $idAsociadoReal = $asociado['idAsociado'];

        // AGREGADO: máximo 3 préstamos activos por asociado
        if ($this->contarActivos($idAsociadoReal) >= 3) {
            throw new Exception("El asociado ya tiene 3 préstamos activos o pendientes.");
        }
        // FIN AGREGADO

        // CAMBIO OBLIGATORIO: se agregó estado = 'Aprobado' (lo registra el admin)
        $sql = "INSERT INTO prestamos (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado, estado)
                VALUES (:valor, :tasa, :cuotas, :fechaPrestamo, :idAsociado, 'Aprobado')";
        
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
        $this->validarDatos($datos); // AGREGADO

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

    // ================= AGREGADO (todo lo de abajo es nuevo) =================

    // Validaciones de negocio (se usan al guardar, editar y solicitar)
    private function validarDatos($datos)
    {
        $valor  = (float)$datos['valor'];
        $tasa   = (float)$datos['tasa'];
        $cuotas = (int)$datos['cuotas'];

        if ($valor < 100000 || $valor > 80000000) {
            throw new Exception("El valor del préstamo debe estar entre $100.000 y $80.000.000.");
        }
        if ($tasa < 0.5 || $tasa > 3) {
            throw new Exception("La tasa de interés debe estar entre 0,5% y 3% mensual.");
        }
        if ((float)$datos['cuotas'] != $cuotas || $cuotas < 1 || $cuotas > 60) {
            throw new Exception("El número de cuotas debe ser un entero entre 1 y 60.");
        }
        if ($datos['fechaPrestamo'] > date('Y-m-d')) {
            throw new Exception("La fecha del préstamo no puede ser futura.");
        }
    }

    // Cuenta los préstamos "activos" de un asociado: pendientes, o aprobados con saldo por pagar
    private function contarActivos($idAsociado)
    {
        $sql = "SELECT COUNT(*)
                FROM prestamos p
                WHERE p.idAsociado = :idAsociado
                  AND (
                        p.estado = 'Pendiente'
                        OR (p.estado = 'Aprobado'
                            AND p.valorPrestamo > (SELECT COALESCE(SUM(pc.valorCuota), 0)
                                                   FROM pagos_cuotas pc
                                                   WHERE pc.idPrestamo = p.idPrestamo))
                      )";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':idAsociado' => $idAsociado]);
        return (int)$consulta->fetchColumn();
    }

    // Aprobar o rechazar una solicitud (solo si está Pendiente)
    public function cambiarEstado($idPrestamo, $estado)
    {
        if (!in_array($estado, ['Aprobado', 'Rechazado'], true)) {
            throw new Exception("Estado no válido.");
        }

        $sql = "UPDATE prestamos SET estado = :estado
                WHERE idPrestamo = :id AND estado = 'Pendiente'";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':estado' => $estado, ':id' => $idPrestamo]);

        if ($consulta->rowCount() === 0) {
            throw new Exception("Solo se pueden aprobar o rechazar solicitudes en estado Pendiente.");
        }
        return true;
    }

    // Listar las solicitudes y préstamos del asociado conectado
    public function obtenerMisSolicitudes($documento)
    {
        $sql = "SELECT p.idPrestamo, p.valorPrestamo, p.tasaInteres, p.numeroCuotas, p.fechaPrestamo, p.estado
                FROM prestamos p
                INNER JOIN asociados a ON p.idAsociado = a.idAsociado
                WHERE a.documento = :documento
                ORDER BY p.idPrestamo DESC";
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':documento' => $documento]);
        return $consulta->fetchAll();
    }

    // Solicitud hecha por el asociado (queda Pendiente hasta que el admin decida)
    public function solicitar($datos, $documento)
    {
        // El documento y la fecha los pone el servidor, no el usuario
        $datos['documento'] = $documento;
        $datos['fechaPrestamo'] = date('Y-m-d');
        $datos['tasa'] = 2; // AGREGADO: tasa provisional, el admin la ajusta antes de aprobar

        $this->validarDatos($datos);

        $sqlBuscar = "SELECT idAsociado FROM asociados WHERE documento = :documento";
        $consultaBuscar = $this->conexion->prepare($sqlBuscar);
        $consultaBuscar->execute([':documento' => $documento]);
        $asociado = $consultaBuscar->fetch();

        if (!$asociado) {
            throw new Exception("No se encontró tu cuenta de asociado.");
        }

        if ($this->contarActivos($asociado['idAsociado']) >= 3) {
            throw new Exception("Ya tienes 3 préstamos activos o pendientes. Termina de pagar alguno antes de solicitar otro.");
        }

        $sql = "INSERT INTO prestamos (valorPrestamo, tasaInteres, numeroCuotas, fechaPrestamo, idAsociado, estado)
                VALUES (:valor, :tasa, :cuotas, :fechaPrestamo, :idAsociado, 'Pendiente')";

        $consulta = $this->conexion->prepare($sql);
        return $consulta->execute([
            ':valor'         => $datos['valor'],
            ':tasa'          => $datos['tasa'],
            ':cuotas'        => $datos['cuotas'],
            ':fechaPrestamo' => $datos['fechaPrestamo'],
            ':idAsociado'    => $asociado['idAsociado']
        ]);
    }
}
?>