<?php

// =============================================================
// Controlador: OdontogramaController.php (TASK-22 / HU-07)
// Muestra el odontograma SVG y guarda los hallazgos que llegan
// por fetch() en JSON.
// =============================================================

require_once __DIR__ . '/../models/OdontogramaModel.php';
require_once __DIR__ . '/../config/Seguridad.php';

class OdontogramaController
{
    // Modelo del odontograma.
    private $modelo;

    public function __construct()
    {
        // Garantiza la sesión para mover alertas entre páginas.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->modelo = new OdontogramaModel();

        // PERMISOS: el odontograma es TRABAJO CLÍNICO: solo el
        // odontólogo y el admin (de consulta) dibujan en el SVG.
        Seguridad::exigirRol(['odontologo', 'admin']);
    }

    // Pantalla del odontograma de UN paciente (?paciente_id=).
    public function ver()
    {
        // 1. El paciente llega por la URL.
        $pacienteId = (int)($_GET['paciente_id'] ?? 0);
        $paciente   = $pacienteId > 0 ? $this->modelo->obtenerPaciente($pacienteId) : false;

        // 2. Si el paciente no existe, devolvemos al directorio con aviso.
        if ($paciente === false) {
            $_SESSION['tipo_alerta']    = 'peligro';
            $_SESSION['mensaje_alerta'] = 'No se encontró el paciente indicado para el odontograma.';
            header('Location: index.php?c=paciente&a=index');
            exit;
        }

        // 3. Datos para la vista: hallazgos de HOY pintan el SVG y
        //    las fechas guardadas alimentan el selector histórico (HU-08).
        $hallazgos = $this->modelo->listarDelDia($pacienteId, date('Y-m-d'));
        $fechasHistoricas = $this->modelo->listarFechas($pacienteId);

        require_once __DIR__ . '/../views/odontograma/ver.php';
    }

    // Devuelve en JSON los hallazgos de UNA fecha (HU-08 / TASK-23).
    // Lo consume odontograma.js para repintar el SVG histórico.
    public function hallazgos()
    {
        header('Content-Type: application/json; charset=utf-8');

        // 1. Paciente y fecha llegan por la URL.
        $pacienteId = (int)($_GET['paciente_id'] ?? 0);
        $fecha      = trim($_GET['fecha'] ?? '');

        // 2. Validamos el formato de la fecha (AAAA-MM-DD).
        if ($pacienteId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Fecha o paciente no válido.']);
            exit;
        }

        // 3. Consultamos y respondemos con el arreglo de hallazgos.
        $hallazgos = $this->modelo->listarDelDia($pacienteId, $fecha);
        echo json_encode(['exito' => true, 'hallazgos' => $hallazgos]);
        exit;
    }

    // Recibe el JSON del botón Guardar y lo persiste (RF-09).
    public function guardar()
    {
        // La respuesta de esta acción SIEMPRE será JSON.
        header('Content-Type: application/json; charset=utf-8');

        // 1. Leemos y validamos el cuerpo JSON enviado por fetch().
        $cuerpo    = json_decode(file_get_contents('php://input'), true) ?? [];
        $hallazgos = $cuerpo['hallazgos'] ?? [];

        if (!$this->hallazgosValidos($hallazgos)) {
            echo json_encode(['exito' => false, 'mensaje' => 'Datos del odontograma incompletos o inválidos.']);
            exit;
        }

        // 2. El id del paciente viaja en la URL (mismo de la vista).
        $pacienteId = (int)($_GET['paciente_id'] ?? 0);
        $guardado   = $this->modelo->guardarHallazgosDelDia($pacienteId, $hallazgos);

        // 3. Respondemos con el contrato acordado en odontograma.js.
        echo json_encode($guardado
            ? ['exito' => true,  'mensaje' => 'Odontograma guardado con éxito (' . count($hallazgos) . ' hallazgos).']
            : ['exito' => false, 'mensaje' => 'No fue posible guardar el odontograma. Intente de nuevo.']);
        exit;
    }

    // Revisa que CADA hallazgo traiga pieza, cara y diagnóstico reales
    // (protege el ENUM de la base de datos contra datos raros).
    private function hallazgosValidos($hallazgos)
    {
        $carasValidas       = ['Oclusal', 'Vestibular', 'Lingual', 'Mesial', 'Distal'];
        $diagnosticosValidos = ['Caries', 'Resina', 'Endodoncia', 'Ausente'];

        foreach ($hallazgos as $hallazgo) {
            $dienteOk = isset($hallazgo['diente'])
                        && (int)$hallazgo['diente'] >= 11
                        && (int)$hallazgo['diente'] <= 48;
            $caraOk   = in_array($hallazgo['cara'] ?? '', $carasValidas, true);
            $diagOk   = in_array($hallazgo['diagnostico'] ?? '', $diagnosticosValidos, true);

            if (!$dienteOk || !$caraOk || !$diagOk) {
                return false; // Basta un solo hallazgo malo para rechazar todo.
            }
        }

        return true;
    }
}
