<?php
// =============================================================
// Clase TratamientoModel (TAREA-26 / HU-09 / RF-11)
// Contiene las consultas a la tabla 'tratamiento' (presupuestos)
// ejecutadas con PDO seguro: prepare() + execute().
// =============================================================

require_once __DIR__ . '/../config/Conexion.php';

class TratamientoModel
{
    // Guarda el objeto PDO que usará toda la clase.
    private $conexion;

    // Al crear el modelo, obtenemos la conexión lista para usar.
    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    // Lista el personal con rol odontólogo para el <select> del form.
    public function listarOdontologos()
    {
        $sql = 'SELECT id, nombre
                FROM usuario
                WHERE rol = "odontologo" AND estado = "activo"
                ORDER BY nombre';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    // Devuelve TODOS los presupuestos con nombres claros para la
    // tabla (une tratamiento con paciente y usuario).
    public function listar()
    {
        $sql = 'SELECT t.id, t.descripcion, t.costo_total,
                       t.saldo_pendiente, t.estado, t.fecha_creacion,
                       p.numero_documento, p.nombres, p.apellidos,
                       u.nombre AS odontologo
                FROM tratamiento t
                JOIN paciente p ON p.id = t.paciente_id
                JOIN usuario  u ON u.id = t.odontologo_id
                ORDER BY t.id DESC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute();

        return $consulta->fetchAll();
    }

    // Consulta UN presupuesto con todos sus datos legibles
    // (sirve para la pantalla de pagos de un tratamiento).
    public function obtenerPorId($id)
    {
        $sql = 'SELECT t.id, t.descripcion, t.costo_total,
                       t.saldo_pendiente, t.estado, t.fecha_creacion,
                       p.numero_documento, p.nombres, p.apellidos,
                       u.nombre AS odontologo
                FROM tratamiento t
                JOIN paciente p ON p.id = t.paciente_id
                JOIN usuario  u ON u.id = t.odontologo_id
                WHERE t.id = :id
                LIMIT 1';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id' => $id]);

        return $consulta->fetch();
    }

    // Guarda un presupuesto NUEVO (RF-11 / CP-11):
    // el saldo_pendiente arranca EXACTAMENTE igual al costo_total
    // y el estado inicial es 'Activo' (lo define la BD por defecto).
    public function crear($datos)
    {
        $sql = 'INSERT INTO tratamiento
                    (paciente_id, odontologo_id, descripcion,
                     costo_total, saldo_pendiente)
                VALUES
                    (:paciente, :odontologo, :descripcion,
                     :costo, :costo)';

        try {
            // El mismo marcador ':costo' llena costo y saldo inicial.
            $consulta = $this->conexion->prepare($sql);
            $consulta->execute([
                ':paciente'    => $datos['paciente_id'],
                ':odontologo'  => $datos['odontologo_id'],
                ':descripcion' => $datos['descripcion'],
                ':costo'       => $datos['costo_total'],
            ]);
        } catch (PDOException $error) {
            // Si MySQL rechaza el insert (foránea inválida, etc.).
            return false;
        }

        // Si todo salió bien, el presupuesto quedó guardado.
        return true;
    }
}
