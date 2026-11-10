<?php
// =============================================================
// Clase PagoModel (TAREA-29 / HU-10 / RF-12, RF-13 y RF-14)
// Controla los abonos de dinero sobre los presupuestos usando
// UNA TRANSACCIÓN PDO: o se registra el abono y se actualiza el
// saldo, o NO se cambia nada (nunca quedan datos a medias).
// =============================================================

require_once __DIR__ . '/../config/Conexion.php';

class PagoModel
{
    // Guarda el objeto PDO que usará toda la clase.
    private $conexion;

    // Al crear el modelo, obtenemos la conexión lista para usar.
    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    // Guarda un abono nuevo de forma SEGURA (RN-02):
    // dentro de una transacción revisa la deuda real, rechaza si
    // el monto la supera y recalcula saldo/estado del tratamiento.
    // Devuelve 'ok' o una palabra con el motivo del rechazo.
    public function registrarAbono($tratamientoId, $monto, $metodoPago, $observacion)
    {
        try {
            // Iniciamos la transacción: todo o nada.
            $this->conexion->beginTransaction();

            // 1. Bloqueamos la fila (FOR UPDATE) para que nadie más
            //    la modifique mientras calculamos el saldo real.
            $consulta = $this->conexion->prepare(
                'SELECT saldo_pendiente FROM tratamiento
                  WHERE id = :id FOR UPDATE');
            $consulta->execute([':id' => $tratamientoId]);
            $presupuesto = $consulta->fetch();

            if ($presupuesto === false) {
                $this->conexion->rollBack();
                return 'no_encontrado';
            }

            $saldoActual = (float)$presupuesto['saldo_pendiente'];

            // 2. RN-02: el abono debe ser positivo y NO superar la deuda.
            if ($monto <= 0 || $monto > $saldoActual) {
                $this->conexion->rollBack();
                return 'supera_saldo';
            }

            // 3. Insertamos el abono en el historial de caja.
            $insert = $this->conexion->prepare(
                'INSERT INTO pago_abono
                     (tratamiento_id, monto, metodo_pago, observacion)
                 VALUES
                     (:tratamiento, :monto, :metodo, :observacion)');
            $insert->execute([
                ':tratamiento' => $tratamientoId,
                ':monto'       => $monto,
                ':metodo'      => $metodoPago,
                ':observacion' => $observacion,
            ]);

            // 4. RF-14: si el saldo queda en $0, el estado pasa a 'Pagado'.
            $nuevoSaldo  = $saldoActual - $monto;
            $nuevoEstado = ($nuevoSaldo == 0.0) ? 'Pagado' : 'Activo';

            $update = $this->conexion->prepare(
                'UPDATE tratamiento
                    SET saldo_pendiente = :saldo, estado = :estado
                  WHERE id = :id');
            $update->execute([
                ':saldo'  => $nuevoSaldo,
                ':estado' => $nuevoEstado,
                ':id'     => $tratamientoId,
            ]);

            // Todo salió bien: confirmamos los 2 cambios a la vez.
            $this->conexion->commit();
            return 'ok';
        } catch (PDOException $error) {
            // Si algo falló a mitad de camino, deshacemos TODO.
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            return 'error_bd';
        }
    }

    // Devuelve el historial de abonos de UN presupuesto (recibo).
    public function listarAbonos($tratamientoId)
    {
        $sql = 'SELECT id, monto, metodo_pago, observacion, fecha_pago
                FROM pago_abono
                WHERE tratamiento_id = :id
                ORDER BY fecha_pago DESC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id' => $tratamientoId]);

        return $consulta->fetchAll();
    }
}
