<?php
require_once __DIR__ . '/../Config/conexion.php';

class Pago
{
    private $conexion;

    public function __construct()
    {
        $conexionDB = new Conexion();
        $this->conexion = $conexionDB->conectar();
    }

    // 1. Obtener el historial de abonos del asociado conectado
    public function obtenerMisPagos($documento)
    {
        $sql = "SELECT pc.idPago,
                       pc.valorCuota,
                       pc.fechaPago,
                       pc.idPrestamo,
                       p.valorPrestamo
                FROM pagos_cuotas pc
                INNER JOIN prestamos p ON pc.idPrestamo = p.idPrestamo
                INNER JOIN asociados a ON p.idAsociado = a.idAsociado
                WHERE a.documento = :documento
                ORDER BY pc.fechaPago DESC, pc.idPago DESC";

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':documento' => $documento]);
        return $consulta->fetchAll();
    }

    // 2. Registrar un nuevo abono con validaciones de seguridad
    public function registrarAbono($datos, $documento)
    {
        $hoy = date('Y-m-d');
        $idPrestamo = (int)$datos['codigoPrestamo'];
        $valorPago  = (float)$datos['valorPago'];
        $fechaPago  = $datos['fechaPago'];

        // Validación de fecha
        if ($fechaPago > $hoy) {
            throw new Exception("La fecha de pago no puede ser futura.");
        }

        // Validación de monto
        if ($valorPago <= 0) {
            throw new Exception("El valor a pagar debe ser mayor a cero.");
        }

        // Verificar que el préstamo exista y pertenezca al asociado logueado
        $sqlVerificar = "SELECT p.idPrestamo, p.valorPrestamo, 
                                COALESCE(SUM(pc.valorCuota), 0) AS totalAbonado
                         FROM prestamos p
                         INNER JOIN asociados a ON p.idAsociado = a.idAsociado
                         LEFT JOIN pagos_cuotas pc ON p.idPrestamo = pc.idPrestamo
                         WHERE p.idPrestamo = :idPrestamo AND a.documento = :documento
                         GROUP BY p.idPrestamo";

        $stmtVerificar = $this->conexion->prepare($sqlVerificar);
        $stmtVerificar->execute([
            ':idPrestamo' => $idPrestamo,
            ':documento'  => $documento
        ]);
        $prestamo = $stmtVerificar->fetch();

        if (!$prestamo) {
            throw new Exception("El préstamo #{$idPrestamo} no existe o no pertenece a tu cuenta.");
        }

        // Validar saldo pendiente
        $saldoPendiente = (float)$prestamo['valorPrestamo'] - (float)$prestamo['totalAbonado'];
        if ($saldoPendiente <= 0) {
            throw new Exception("Este préstamo ya se encuentra completamente pagado (Paz y Salvo).");
        }

        if ($valorPago > $saldoPendiente) {
            throw new Exception("El abono no puede superar el saldo pendiente ($" . number_format($saldoPendiente, 0, ',', '.') . ").");
        }

        // Guardar en la tabla pagos_cuotas
        $sqlInsert = "INSERT INTO pagos_cuotas (valorCuota, fechaPago, idPrestamo)
                      VALUES (:valorCuota, :fechaPago, :idPrestamo)";

        $stmtInsert = $this->conexion->prepare($sqlInsert);
        return $stmtInsert->execute([
            ':valorCuota'  => $valorPago,
            ':fechaPago'   => $fechaPago,
            ':idPrestamo'  => $idPrestamo
        ]);
    }
}
?>