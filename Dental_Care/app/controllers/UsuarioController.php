<?php
// =============================================================
// Clase UsuarioController (módulo extra solicitado por Sebastian)
// Gestión de personal del consultorio: SOLO el ADMIN puede
// listar, crear y activar/desactivar usuarios del sistema.
// Con este módulo el admin finalmente agrega doctores desde la web
// (sin phpMyAdmin), tal como la Pantalla 2 del diseño lo prometía.
// =============================================================

require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../config/Seguridad.php';

class UsuarioController
{
    // Referencia al modelo de usuarios (tabla usuario).
    private $modelo;

    public function __construct()
    {
        // Nos aseguramos de tener la sesión activa (las alertas
        // viajan por la sesión entre una página y otra).
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->modelo = new UsuarioModel();

        // PERMISOS: la gestión del personal es EXCLUSIVA del admin.
        Seguridad::exigirRol(['admin']);
    }

    // Pantalla principal: tabla del personal + form de creación.
    public function index()
    {
        $usuarios = $this->modelo->listar();

        require_once __DIR__ . '/../views/usuario/index.php';
    }

    // Guarda el usuario nuevo que llega del formulario (POST).
    public function guardar()
    {
        // 1. Tomamos los datos enviados por el formulario.
        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'clave'  => $_POST['clave'] ?? '',
            'rol'    => trim($_POST['rol'] ?? ''),
        ];

        // 2. Validamos obligatorios + roles de la BD + clave mínima.
        if (!$this->datosValidos($datos)) {
            header('Location: index.php?c=usuario&a=index');
            exit;
        }

        // 3. El correo es UNIQUE en la BD: verificamos antes.
        if ($this->modelo->obtenerPorCorreo($datos['correo'])) {
            $this->alertar('peligro', 'El correo ' . htmlspecialchars($datos['correo']) . ' ya está registrado en el sistema.');
            header('Location: index.php?c=usuario&a=index');
            exit;
        }

        // 4. Creamos al usuario (el modelo encripta la clave).
        $creado = $this->modelo->crear($datos);
        $this->alertar(
            $creado ? 'exito' : 'peligro',
            $creado
                ? 'Usuario ' . htmlspecialchars($datos['nombre']) . ' creado con rol (' . $datos['rol'] . ') y clave encriptada.'
                : 'No fue posible crear el usuario. Intente de nuevo.'
        );

        header('Location: index.php?c=usuario&a=index');
        exit;
    }

    // Activa o desactiva a un integrante del personal (botón rápido).
    public function cambiarEstado()
    {
        // 1. Leemos el usuario y el nuevo estado desde la URL.
        $id     = (int)($_GET['id'] ?? 0);
        $estado = trim($_GET['estado'] ?? '');

        // 2. Protección: un admin NO puede desactivarse a sí mismo.
        if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
            $this->alertar('peligro', 'No puedes desactivar tu propia cuenta mientras la usas.');
            header('Location: index.php?c=usuario&a=index');
            exit;
        }

        // 3. Solo aceptamos los 2 estados del ENUM de la BD.
        if ($id <= 0 || !in_array($estado, ['activo', 'inactivo'], true)) {
            $this->alertar('peligro', 'Solicitud de cambio de estado no válida.');
        } else {
            $actualizo = $this->modelo->cambiarEstado($id, $estado);
            $this->alertar(
                $actualizo ? 'exito' : 'peligro',
                $actualizo ? 'El usuario #(' . $id . ') quedó ' . $estado . '.' : 'No fue posible actualizar el usuario.'
            );
        }

        header('Location: index.php?c=usuario&a=index');
        exit;
    }

    // Valida obligatorios, rol válido y clave mínima de 8 caracteres.
    private function datosValidos($datos)
    {
        $rolesValidos = ['recepcionista', 'odontologo', 'admin'];

        if ($datos['nombre'] === '' || $datos['correo'] === ''
            || $datos['clave'] === '' || !in_array($datos['rol'], $rolesValidos, true)) {
            $this->alertar('peligro', 'Datos incompletos: revisa nombre, correo, clave y rol.');
            return false;
        }

        if (strlen($datos['clave']) < 8) {
            $this->alertar('peligro', 'La clave debe tener al menos 8 caracteres.');
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
