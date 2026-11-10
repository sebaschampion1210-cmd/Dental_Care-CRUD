<?php

// =============================================================
// Modelo: OdontogramaModel.php (TASK-22 / HU-07 / RF-09)
// Maneja los hallazgos clínicos del odontograma con PDO seguro.
// =============================================================

require_once __DIR__ . '/../config/Conexion.php';

class OdontogramaModel
{
    // Guarda el objeto PDO que usará toda la clase.
    private $conexion;

    // Al crear el modelo, obtenemos la conexión lista para usar.
    public function __construct()
    {
        $this->conexion = Conexion::conectar();
    }

    // Consulta la ficha básica del paciente dueño del odontograma.
    public function obtenerPaciente($id)
    {
        $sql = 'SELECT id, numero_documento, nombres, apellidos
                FROM paciente
                WHERE id = :id
                LIMIT 1';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':id' => $id]);

        return $consulta->fetch();
    }

    // Devuelve los hallazgos de UN día para pintar el SVG al cargar.
    public function listarDelDia($pacienteId, $fecha)
    {
        $sql = 'SELECT diente, cara, diagnostico, observacion
                FROM odontograma
                WHERE paciente_id = :paciente
                  AND DATE(fecha_registro) = :fecha';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':paciente' => $pacienteId, ':fecha' => $fecha]);

        return $consulta->fetchAll();
    }

    // Lista las fechas en que el paciente tiene odontograma guardado
    // (alimenta el selector histórico de la HU-08).
    public function listarFechas($pacienteId)
    {
        $sql = 'SELECT DISTINCT DATE(fecha_registro) AS fecha
                FROM odontograma
                WHERE paciente_id = :paciente
                ORDER BY fecha DESC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute([':paciente' => $pacienteId]);

        return $consulta->fetchAll();
    }

    // Guarda la sesión de HOY completa dentro de UNA transacción:
    // o se borra y guarda TODO, o no se cambia nada (RF-09).
    // Esto permite borrar una marca en pantalla y que también se
    // borre en la base de datos al guardar.
    public function guardarHallazgosDelDia($pacienteId, $hallazgos)
    {
        try {
            // 1. Abrimos la transacción: todo o nada.
            $this->conexion->beginTransaction();

            // 2. Borramos lo guardado HOY (la sesión sigue editable).
            $borrar = $this->conexion->prepare(
                'DELETE FROM odontograma
                 WHERE paciente_id = :paciente
                   AND DATE(fecha_registro) = CURDATE()'
            );
            $borrar->execute([':paciente' => $pacienteId]);

            // 3. Insertamos cada hallazgo que llegó marcado en el SVG.
            $insertar = $this->conexion->prepare(
                'INSERT INTO odontograma (paciente_id, diente, cara, diagnostico)
                 VALUES (:paciente, :diente, :cara, :diagnostico)'
            );
            foreach ($hallazgos as $hallazgo) {
                $insertar->execute([
                    ':paciente'    => $pacienteId,
                    ':diente'      => $hallazgo['diente'],
                    ':cara'        => $hallazgo['cara'],
                    ':diagnostico' => $hallazgo['diagnostico'],
                ]);
            }

            // 4. Todo salió bien: confirmamos los cambios.
            $this->conexion->commit();
            return true;

        } catch (PDOException $error) {
            // Algo falló: deshacemos TODO (borrado e inserciones).
            $this->conexion->rollBack();
            return false;
        }
    }
}
