<?php
// =============================================================
// Clase DashboardController (TAREA-35 / HU-12 / RF-17)
// Pantalla de inicio: envía a la vista los 4 indicadores
// financieros y de citas, la agenda del día y la lista de
// pacientes por llamar (recall).
// =============================================================

require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../models/CitaModel.php';
require_once __DIR__ . '/../models/RecallModel.php';
require_once __DIR__ . '/../config/Seguridad.php';

class DashboardController
{
    // Modelos que aporta cada bloque del panel gerencial.
    private $modelo;
    private $citaModelo;
    private $recallModelo;

    public function __construct()
    {
        // Nos aseguramos de tener la sesión activa (las alertas
        // viajan por la sesión entre una página y otra).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->modelo      = new DashboardModel();
        $this->citaModelo  = new CitaModel();
        $this->recallModelo = new RecallModel();
    }

    // Pantalla principal del panel (el login redirige aquí).
    public function index()
    {
        // PERMISOS: la información FINANCIERA solo se calcula y
        // muestra para recepción y administración. El odontólogo ve
        // su panel clínico (citas e inasistencias) sin cifras de caja.
        $esRolCaja = in_array($_SESSION['rol'] ?? '', ['recepcionista', 'admin'], true);

        // 1. Los 4 KPI del diseño (Pantalla 3 de Figma).
        $citasHoy     = $this->modelo->citasDeHoy();
        $inasistencia = $this->modelo->tasaInasistencia();

        // 2. KPI de dinero: SOLO si el rol lo autoriza (queda en
        //    null para el doctor y la vista ni lo pinta).
        $ingresosMes = $esRolCaja ? $this->modelo->ingresosDelMes() : null;
        $cartera     = $esRolCaja ? $this->modelo->carteraPendiente() : null;

        // 2. Columna izquierda: agenda del día ya programada.
        $citasAgenda = $this->citaModelo->listarDelDia(date('Y-m-d'));

        // 3. Columna derecha: pacientes por llamar con enlace listo.
        $porLlamar = $this->recallModelo->listarInactivos();
        foreach ($porLlamar as $i => $paciente) {
            $porLlamar[$i]['enlace_whatsapp'] = $this->recallModelo->crearEnlaceWhatsApp($paciente);
        }

        require_once __DIR__ . '/../views/dashboard/index.php';
    }
}
