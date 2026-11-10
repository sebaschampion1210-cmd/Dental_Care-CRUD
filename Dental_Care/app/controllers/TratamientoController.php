<?php
// =============================================================
// Clase TratamientoController (TAREA-26 / HU-09 / RF-11)
// Recibe las peticiones ($_POST, $_GET), valida los datos,
// invoca al TratamientoModel y carga la vista de presupuestos.
// =============================================================

require_once __DIR__ . '/../models/TratamientoModel.php';
require_once __DIR__ . '/../models/PacienteModel.php';
require_once __DIR__ . '/../models/PagoModel.php';
require_once __DIR__ . '/../config/Seguridad.php';

class TratamientoController
{
    // RN-02 (base cartera): tope de dinero coherente con la BD,
    // DECIMAL(10,2) no admite más de $99.999.999,99.
    private const COSTO_MAXIMO = 99999999.99;

    // Referencias al modelo de presupuestos, pacientes y pagos.
    private $modelo;
    private $pacienteModelo;
    private $pagoModelo;

    public function __construct()
    {
        // Nos aseguramos de tener la sesión activa (las alertas
        // viajan por la sesión entre una página y otra).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->modelo         = new TratamientoModel();
        $this->pacienteModelo = new PacienteModel();
        $this->pagoModelo     = new PagoModel();

        // PERMISOS: la caja (presupuestos y abonos) es SOLO para
        // recepción y administración. El odontólogo ve su info en el
        // panel, pero no toca dinero (petición explícita de Sebastian).
        Seguridad::exigirRol(['recepcionista', 'admin']);
    }

    // Pantalla principal del módulo: listado de presupuestos.
    public function index()
    {
        // Datos para los <select> y para la tabla de la vista.
        $odontologos = $this->modelo->listarOdontologos();
        $pacientes   = $this->pacienteModelo->listar();
        $tratamientos = $this->modelo->listar();

        require_once __DIR__ . '/../views/tratamiento/index.php';
    }

    // Guarda el presupuesto que llega por el formulario (POST).
    public function guardar()
    {
        // 1. Tomamos los datos enviados por el formulario.
        $datos = [
            'paciente_id'   => (int)($_POST['paciente_id'] ?? 0),
            'odontologo_id' => (int)($_POST['odontologo_id'] ?? 0),
            'descripcion'   => trim($_POST['descripcion'] ?? ''),
            'costo_total'   => (float)($_POST['costo_total'] ?? 0),
        ];

        // 2. Validación RF-11: nada vacío y costo mayor que cero.
        if (!$this->datosValidos($datos)) {
            header('Location: index.php?c=tratamiento&a=index');
            exit;
        }

        // 3. Guardamos: la BD coloca saldo = costo y estado 'Activo'.
        $guardado = $this->modelo->crear($datos);
        $this->alertar(
            $guardado ? 'exito' : 'peligro',
            $guardado
                ? 'Presupuesto creado con éxito. Saldo inicial: $' . number_format($datos['costo_total'], 2) . '.'
                : 'No fue posible crear el presupuesto. Intente de nuevo.'
        );

        header('Location: index.php?c=tratamiento&a=index');
        exit;
    }

    // Pantalla de pagos de UN presupuesto: resumen + historial de
    // abonos + modal para registrar un abono nuevo (HU-10).
    public function pagos()
    {
        // 1. Leemos el presupuesto a revisar desde la URL (?id=X).
        $id = (int)($_GET['id'] ?? 0);
        $tratamiento = $this->modelo->obtenerPorId($id);

        // 2. Si no existe, volvemos al listado con la alerta.
        if ($tratamiento === false) {
            $this->alertar('peligro', 'El presupuesto solicitado no existe en el sistema.');
            header('Location: index.php?c=tratamiento&a=index');
            exit;
        }

        // 3. Cargamos el historial de abonos para la tabla del recibo.
        $abonos = $this->pagoModelo->listarAbonos($id);

        require_once __DIR__ . '/../views/tratamiento/pagos.php';
    }

    // Registra el abono que llega del modal (POST) aplicando RN-02.
    public function registrarAbono()
    {
        // 1. Tomamos los datos enviados por el formulario del modal.
        $id           = (int)($_POST['tratamiento_id'] ?? 0);
        $monto        = (float)($_POST['monto'] ?? 0);
        $metodoPago   = trim($_POST['metodo_pago'] ?? '');
        $observacion  = trim($_POST['observacion'] ?? '');

        // 2. Métodos de pago válidos según el ENUM de la BD.
        $metodosValidos = ['Efectivo', 'Transferencia', 'Tarjeta'];
        if ($id <= 0 || $metodoPago === '' || !in_array($metodoPago, $metodosValidos, true)) {
            $this->alertar('peligro', 'Datos incompletos para registrar el abono.');
            header('Location: index.php?c=tratamiento&a=index');
            exit;
        }

        // 3. Delegamos al modelo: la transacción valida RN-02 (no
        //    superar la deuda) y actualiza saldo y estado a la vez.
        $resultado = $this->pagoModelo->registrarAbono($id, $monto, $metodoPago, $observacion);

        // 4. Traducimos el resultado a un mensaje amable para el usuario.
        if ($resultado === 'ok') {
            $this->alertar('exito', 'Abono de $' . number_format($monto, 2) . ' registrado con éxito.');
        } elseif ($resultado === 'supera_saldo') {
            $this->alertar('peligro', 'RN-02: el abono no puede superar el saldo pendiente ni ser negativo. No se registró el cobro.');
        } else {
            $this->alertar('peligro', 'No fue posible registrar el abono. Intente de nuevo.');
        }

        // 5. Volvemos al recibo del mismo presupuesto recién actualizado.
        header('Location: index.php?c=tratamiento&a=pagos&id=' . $id);
        exit;
    }

    // Valida obligatorios y reglas de negocio del costo (criterio 1 HU-09).
    private function datosValidos($datos)
    {
        if ($datos['paciente_id'] <= 0 || $datos['odontologo_id'] <= 0
            || $datos['descripcion'] === '' || $datos['costo_total'] <= 0) {
            $this->alertar('peligro', 'Datos incompletos: el costo total debe ser mayor a $0.');
            return false;
        }

        // Límite para evitar cifras ilógicas que la BD tampoco soporta.
        if ($datos['costo_total'] > self::COSTO_MAXIMO) {
            $this->alertar('peligro', 'El costo total no puede superar $'
                . number_format(self::COSTO_MAXIMO, 2) . '. Verifica el valor digitado.');
            return false;
        }

        return true;
    }

    // Guarda una alerta en la sesión para mostrarla en la vista destino.
    private function alertar($tipo, $mensaje)
    {
        $_SESSION['tipo_alerta']    = $tipo;
        $_SESSION['mensaje_alerta'] = $mensaje;
    }
}
