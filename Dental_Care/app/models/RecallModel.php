<?php
// =============================================================
// Clase RecallModel (TAREA-31 / HU-11 / RF-15)
// Detecta los pacientes INACTIVOS (recall): los que llevan más
// de 180 días sin cita efectiva o que tienen tratamientos con
// saldo por pagar. Se usa MAX(fecha) y DATEDIFF de MySQL.
// =============================================================

require_once __DIR__ . '/../config/Conexion.php';

class RecallModel
{
    // Regla del caso de prueba CP-15: 6 meses = 180 días.
    private const DIAS_INACTIVIDAD = 180;

    // Guarda el objeto PDO que usará toda la clase.
    private $conexion;

    // Al crear el modelo, obtenemos la conexión lista para usar.
    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    // Lista pacientes con más de 180 días SIN visita (o que nunca
    // han venido) o con saldo pendiente en su presupuesto activo.
    // MAX(fecha_cita): la última cita que SÍ asistió el paciente.
    // DATEDIFF: días que faltan entre HOY y esa última cita.
    public function listarInactivos()
    {
        $sql = 'SELECT p.id, p.numero_documento, p.nombres, p.apellidos, p.telefono,
                       MAX(c.fecha_cita) AS ultima_cita,
                       DATEDIFF(CURDATE(), MAX(c.fecha_cita)) AS dias_inactivo,
                       COALESCE(SUM(t.saldo_pendiente), 0) AS saldo_total
                FROM paciente p
                LEFT JOIN cita c
                       ON c.paciente_id = p.id
                      AND c.estado NOT IN ("Cancelada", "Inasistencia")
                LEFT JOIN tratamiento t
                       ON t.paciente_id = p.id
                      AND t.estado = "Activo"
                GROUP BY p.id, p.numero_documento, p.nombres, p.apellidos, p.telefono
                HAVING (MAX(c.fecha_cita) IS NULL
                            OR DATEDIFF(CURDATE(), MAX(c.fecha_cita)) > ' . self::DIAS_INACTIVIDAD . ')
                    OR COALESCE(SUM(t.saldo_pendiente), 0) > 0
                ORDER BY dias_inactivo DESC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    // Construye la URL de WhatsApp con mensaje personalizado y
    // codificado (RF-16). Pública para que también la use el
    // Dashboard (HU-12) sin repetir el mensaje en 2 lados.
    public function crearEnlaceWhatsApp($paciente)
    {
        // 1. Mensaje personalizado: saludo + días de espera + saldo.
        // Con max() evitamos días negativos si su cita es futura.
        $diasVisita = max(0, (int)($paciente['dias_inactivo'] ?? 0));
        $texto = 'Hola ' . $paciente['nombres'] . ' ' . $paciente['apellidos']
            . ', le saludamos de Dental Care. Notamos que han pasado '
            . $diasVisita . ' días desde su última visita';

        // Si tiene saldo por pagar, le recordamos su cita de caja.
        if ((float)$paciente['saldo_total'] > 0) {
            $texto .= ' Además, tiene un saldo pendiente de $' . number_format((float)$paciente['saldo_total'], 2) . '.';
        } else {
            // Si no debe nada, el motivo del mensaje es solo la visita.
            $texto .= ' Queremos agendar su próxima cita de control.';
        }

        // 2. Armamos la URL: wa.me/57[numero]?text=mensaje codificado.
        $telefono = preg_replace('/\D/', '', $paciente['telefono']);

        return 'https://wa.me/57' . $telefono . '?text=' . rawurlencode($texto);
    }
}
