<?php
require_once __DIR__ . '/../Config/conexion.php';

class Reporte
{
    private $conexion;

    public function __construct()
    {
        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
    }

    // 1. Estadísticas globales para las tarjetas de resumen
    public function obtenerResumen()
    {
        // Total prestado y cantidad de préstamos
        $sqlPrestamos = "SELECT COUNT(*) AS totalPrestamos, 
                                COALESCE(SUM(valorPrestamo), 0) AS totalPrestado 
                         FROM prestamos";
        $stmtPrestamos = $this->conexion->prepare($sqlPrestamos);
        $stmtPrestamos->execute();
        $datosPrestamos = $stmtPrestamos->fetch();

        // Total recaudado de cuotas
        $sqlRecaudo = "SELECT COALESCE(SUM(valorCuota), 0) AS totalRecaudado FROM pagos_cuotas";
        $stmtRecaudo = $this->conexion->prepare($sqlRecaudo);
        $stmtRecaudo->execute();
        $datosRecaudo = $stmtRecaudo->fetch();

        // Total de asociados
        $sqlAsociados = "SELECT COUNT(*) AS totalAsociados FROM asociados";
        $stmtAsociados = $this->conexion->prepare($sqlAsociados);
        $stmtAsociados->execute();
        $datosAsociados = $stmtAsociados->fetch();

        $prestado  = (float)$datosPrestamos['totalPrestado'];
        $recaudado = (float)$datosRecaudo['totalRecaudado'];
        $pendiente = $prestado - $recaudado;

        return [
            'totalPrestamos' => (int)$datosPrestamos['totalPrestamos'],
            'totalPrestado'  => $prestado,
            'totalRecaudado' => $recaudado,
            'saldoPendiente' => ($pendiente < 0) ? 0 : $pendiente,
            'totalAsociados' => (int)$datosAsociados['totalAsociados']
        ];
    }

    // 2. Consulta de préstamos con filtros y validaciones de fecha en el servidor
    public function consultarReportes($filtros = [])
    {
        $hoy = date('Y-m-d');
        $fechaMinima = '2000-01-01';

        // Validaciones estrictas de fechas
        if (!empty($filtros['fechaInicio'])) {
            if ($filtros['fechaInicio'] > $hoy) {
                throw new Exception("La fecha inicial no puede ser futura.");
            }
            if ($filtros['fechaInicio'] < $fechaMinima) {
                throw new Exception("La fecha inicial no puede ser anterior al año 2000.");
            }
        }

        if (!empty($filtros['fechaFin'])) {
            if ($filtros['fechaFin'] > $hoy) {
                throw new Exception("La fecha final no puede ser futura.");
            }
            if ($filtros['fechaFin'] < $fechaMinima) {
                throw new Exception("La fecha final no puede ser anterior al año 2000.");
            }
        }

        if (!empty($filtros['fechaInicio']) && !empty($filtros['fechaFin'])) {
            if ($filtros['fechaInicio'] > $filtros['fechaFin']) {
                throw new Exception("La fecha inicial no puede ser mayor que la fecha final.");
            }
        }

        // Consulta SQL uniendo prestamos, asociados y pagos_cuotas
        $sql = "SELECT p.idPrestamo,
                       p.valorPrestamo,
                       p.tasaInteres,
                       p.numeroCuotas,
                       p.fechaPrestamo,
                       a.documento,
                       CONCAT(a.nombre, ' ', a.apellidos) AS nombreAsociado,
                       COALESCE(SUM(pc.valorCuota), 0) AS totalRecaudado,
                       (p.valorPrestamo - COALESCE(SUM(pc.valorCuota), 0)) AS saldoRestante
                FROM prestamos p
                INNER JOIN asociados a ON p.idAsociado = a.idAsociado
                LEFT JOIN pagos_cuotas pc ON p.idPrestamo = pc.idPrestamo
                WHERE 1=1";

        $parametros = [];

        if (!empty($filtros['fechaInicio'])) {
            $sql .= " AND p.fechaPrestamo >= :fechaInicio";
            $parametros[':fechaInicio'] = $filtros['fechaInicio'];
        }

        if (!empty($filtros['fechaFin'])) {
            $sql .= " AND p.fechaPrestamo <= :fechaFin";
            $parametros[':fechaFin'] = $filtros['fechaFin'];
        }

        if (!empty($filtros['documento'])) {
            $sql .= " AND a.documento LIKE :documento";
            $parametros[':documento'] = '%' . $filtros['documento'] . '%';
        }

        $sql .= " GROUP BY p.idPrestamo ORDER BY p.fechaPrestamo DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll();
    }
}
?>