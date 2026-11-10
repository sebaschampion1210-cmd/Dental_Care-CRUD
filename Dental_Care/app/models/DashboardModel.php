<?php
// =============================================================
// Clase DashboardModel (TAREA-34 / HU-12 / RF-17)
// Calcula las 4 métricas gerenciales con agregaciones SQL
// (SUM y COUNT) tomadas EN TIEMPO REAL desde MySQL:
// citas de hoy, ingresos del mes, cartera y tasa de inasistencia.
// =============================================================

require_once __DIR__ . '/../config/Conexion.php';

class DashboardModel
{
    // Guarda el objeto PDO que usará toda la clase.
    private $conexion;

    // Al crear el modelo, obtenemos la conexión lista para usar.
    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    // KPI 1: cuántas citas hay HOY y cuántas siguen Pendientes.
    // COUNT cuenta los turnos y SUM(estado=...) suma con 1/0 los
    // que cumplen la condición (truco simple de MySQL).
    public function citasDeHoy()
    {
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(estado = "Pendiente"), 0) AS pendientes
                FROM cita
                WHERE fecha_cita = :hoy';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':hoy' => date('Y-m-d')]);

        return $consulta->fetch();
    }

    // KPI 2: dinero REAL recaudado en el MES actual (pago_abono).
    public function ingresosDelMes()
    {
        // MONTH() y YEAR() comparan el mes y año de HOY con el
        // de cada pago, evitando contar abonos de meses pasados.
        $sql = 'SELECT COALESCE(SUM(monto), 0) AS ingresos
                FROM pago_abono
                WHERE YEAR(fecha_pago) = YEAR(CURDATE())
                  AND MONTH(fecha_pago) = MONTH(CURDATE())';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return (float)$consulta->fetchColumn();
    }

    // KPI 3: cartera por cobrar (saldo de tratamientos ACTIVOS).
    public function carteraPendiente()
    {
        $sql = 'SELECT COALESCE(SUM(saldo_pendiente), 0) AS cartera
                FROM tratamiento
                WHERE estado = "Activo"';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return (float)$consulta->fetchColumn();
    }

    // KPI 4: tasa de inasistencia del MES (faltas / total * 100).
    // Devuelve un arreglo con la tasa y el detalle para la tarjeta.
    public function tasaInasistencia()
    {
        $sql = 'SELECT COUNT(*) AS total,
                       COALESCE(SUM(estado = "Inasistencia"), 0) AS faltas
                FROM cita
                WHERE YEAR(fecha_cita) = YEAR(CURDATE())
                  AND MONTH(fecha_cita) = MONTH(CURDATE())';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();
        $datos = $consulta->fetch();

        // Si no hubo citas este mes, la tasa es 0% (evita dividir 0).
        $tasa = ($datos['total'] > 0)
            ? round(($datos['faltas'] / $datos['total']) * 100, 1)
            : 0;

        return ['tasa' => $tasa, 'faltas' => (int)$datos['faltas']];
    }
}
