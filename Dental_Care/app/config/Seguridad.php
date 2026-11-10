<?php
// =============================================================
// Clase Seguridad (sistema de permisos por rol, solicitud de
// Sebastian): protege módulos enteros en 1 sola línea en el
// constructor del controlador. El diseño UI (Pantalla 2)
// promete "Usuarios del Sistema: SOLO Admin" y la caja para
// recepción, y esto lo hace realidad para todos los módulos.
// =============================================================

class Seguridad
{
    // Guard: exige que el rol actual esté en la lista permitida.
    // Si no lo está, avisa y regresa al panel (dashboard).
    // Roles válidos en la BD: recepcionista, odontologo, admin.
    public static function exigirRol($rolesPermitidos)
    {
        // Aseguramos sesión para leer el rol y guardar la alerta.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $rolActual = $_SESSION['rol'] ?? '';

        if (!in_array($rolActual, $rolesPermitidos, true)) {
            $_SESSION['tipo_alerta']    = 'peligro';
            $_SESSION['mensaje_alerta'] = '🔒 Acceso denegado: el rol '
                . htmlspecialchars($rolActual === '' ? 'desconocido' : $rolActual)
                . ' no tiene permiso para este módulo.';
            header('Location: index.php?c=dashboard&a=index');
            exit;
        }
    }
}
