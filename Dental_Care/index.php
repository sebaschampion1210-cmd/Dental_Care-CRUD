<?php

// Activamos la sesión PHP (necesaria para validar el login).
session_start();

// ------------------------------------------------------------------
// 1. Leemos el controlador y la acción solicitados por la URL.
// ------------------------------------------------------------------
$controlador = $_GET['c'] ?? 'auth';

// CORRECCIÓN: Si el controlador es 'auth', por defecto va a 'login'. 
// Para cualquier otro controlador, la acción por defecto será 'index'.
$accion = $_GET['a'] ?? ($controlador === 'auth' ? 'login' : 'index');

// ------------------------------------------------------------------
// 2. PROTECCIÓN DE RUTAS:
//    Si NO hay sesión activa y la petición NO es del login,
//    redirigimos inmediatamente al formulario de acceso.
// ------------------------------------------------------------------
if ($controlador !== 'auth' && !isset($_SESSION['usuario_id'])) {
    header('Location: index.php?c=auth&a=login');
    exit;
}

// ------------------------------------------------------------------
// 3. Si SÍ hay sesión activa y abren el login a mano, lo enviamos
//    al panel (no tiene sentido re-loguearse). El cierre de sesión
//    siempre está permitido.
// ------------------------------------------------------------------
if ($controlador === 'auth' && isset($_SESSION['usuario_id']) && $accion !== 'cerrarSesion') {
    header('Location: index.php?c=dashboard&a=index');
    exit;
}

// ------------------------------------------------------------------
// 4. Traducimos el nombre de la URL a la clase del controlador.
//    Ej: c=auth  ->  AuthController.php
// ------------------------------------------------------------------
$claseControlador = ucfirst(strtolower($controlador)) . 'Controller';
$archivo          = __DIR__ . '/app/controllers/' . $claseControlador . '.php';

// Si el módulo no existe todavía, mostramos un aviso claro.
if (!is_file($archivo)) {
    die('Módulo "' . htmlspecialchars($controlador) . '" aún no está disponible en el sistema.');
}
require_once $archivo;

// ------------------------------------------------------------------
// 5. Traducimos la acción de la URL al método de la clase.
// ------------------------------------------------------------------
// CORRECCIÓN: Aplicamos el diccionario de métodos SOLO si estamos en AuthController
if ($controlador === 'auth') {
    $metodosAuth = [
        'login'         => 'mostrarLogin',
        'iniciarSesion' => 'iniciarSesion',
        'cerrarSesion'  => 'cerrarSesion',
    ];
    
    // Si la acción existe en el arreglo, usa el nombre mapeado; si no, usa la acción tal cual
    $metodo = $metodosAuth[$accion] ?? $accion;
} else {
    // Para los demás controladores (Citas, Dashboard, etc.), el método será exactamente la acción
    $metodo = $accion;
}

// Verificamos si el método final realmente existe en el controlador antes de ejecutarlo
if (!method_exists($claseControlador, $metodo)) {
    die('Error: El método "' . htmlspecialchars($metodo) . '" no existe en el controlador ' . $claseControlador . '.');
}

// ------------------------------------------------------------------
// 6. Ejecutamos: creamos el controlador y llamamos su método.
// ------------------------------------------------------------------
$instancia = new $claseControlador();
$instancia->$metodo();