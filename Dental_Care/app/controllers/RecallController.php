<?php
// =============================================================
// Clase RecallController (TAREA-32 / HU-11 / RF-16)
// Arma el listado de pacientes inactivos y genera para cada uno
// un enlace directo a WA.ME (WhatsApp) con mensaje personalizado.
// =============================================================

require_once __DIR__ . '/../models/RecallModel.php';
require_once __DIR__ . '/../config/Seguridad.php';

class RecallController
{
    // Referencia al modelo que detecta los pacientes inactivos.
    private $modelo;

    public function __construct()
    {
        // Nos aseguramos de tener la sesión activa (las alertas
        // viajan por la sesión entre una página y otra).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->modelo = new RecallModel();

        // PERMISOS: la retención (llamadas con WhatsApp) la maneja
        // recepción y administración; el odontólogo no hace marketing.
        Seguridad::exigirRol(['recepcionista', 'admin']);
    }

    // Pantalla del módulo: tabla de pacientes por llamar (recall).
    public function index()
    {
        // 1. Consultamos la lista de inactivos del modelo.
        $inactivos = $this->modelo->listarInactivos();

        // 2. Para cada paciente armamos el enlace de WhatsApp listo
        //    (la lógica del mensaje vive en el modelo y la comparte
        //    con el Dashboard).
        foreach ($inactivos as $i => $paciente) {
            $inactivos[$i]['enlace_whatsapp'] = $this->modelo->crearEnlaceWhatsApp($paciente);
        }

        require_once __DIR__ . '/../views/recall/index.php';
    }
}
