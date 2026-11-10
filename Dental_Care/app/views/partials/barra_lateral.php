<?php
// =============================================================
// Parcial: barra_lateral.php (Pantalla 2 del diseño UI/UX)
// Menú lateral fijo (#0F172A, 260px) + versión móvil hamburguesa
// (offcanvas). Se usa en TODAS las vistas y filtra los módulos
// según el ROL (la misma matriz de permisos del Seguridad.php).
// =============================================================

// 1. Módulo activo para resaltar el ítem del menú azul.
$moduloActivo = $_GET['c'] ?? 'auth';
$rolSesion    = $_SESSION['rol'] ?? '';

// 2. Inventario del menú: cada ítem dice qué roles lo pueden ver.
$navegacion = [
    ['url' => 'index.php?c=dashboard&a=index', 'icono' => 'bi-speedometer2', 'texto' => 'Dashboard', 'roles' => ['admin', 'recepcionista', 'odontologo']],
    ['url' => 'index.php?c=cita&a=index', 'icono' => 'bi-calendar-week', 'texto' => 'Agenda de Citas', 'roles' => ['admin', 'recepcionista', 'odontologo']],
    ['url' => 'index.php?c=paciente&a=index', 'icono' => 'bi-people', 'texto' => 'Pacientes y Expedientes', 'roles' => ['admin', 'recepcionista', 'odontologo']],
    ['url' => 'index.php?c=tratamiento&a=index', 'icono' => 'bi-cash-coin', 'texto' => 'Presupuestos y Caja', 'roles' => ['admin', 'recepcionista']],
    ['url' => 'index.php?c=recall&a=index', 'icono' => 'bi-bell', 'texto' => 'Recall / Pacientes Inactivos', 'roles' => ['admin', 'recepcionista']],
    ['url' => 'index.php?c=usuario&a=index', 'icono' => 'bi-gear', 'texto' => 'Usuarios del Sistema', 'roles' => ['admin']],
];

// 3. Armamos el HTML del menú UNA sola vez ( exploring lista filtrada por rol ).
$htmlMenu = '';
foreach ($navegacion as $item) {
    if (!in_array($rolSesion, $item['roles'], true)) {
        continue; // El rol actual no ve este módulo.
    }
    $claseActiva = ($moduloActivo === 'dashboard' && str_contains($item['url'], 'c=dashboard')) || str_contains($item['url'], 'c=' . $moduloActivo)
        ? 'activo' : '';
    $htmlMenu .= '<a href="' . $item['url'] . '" class="enlace-menu ' . $claseActiva . '">'
        . '<i class="bi ' . $item['icono'] . '"></i> ' . $item['texto'] . '</a>';
}

// 4. Pie del menú: usuario activo + rol + cierre de sesión.
// Nombres en blanco y badge claro: visibles sobre el fondo #0F172A.
$pieUsuario = '<div class="mt-auto pt-3 border-top border-secondary">'
    . '<span class="d-block fw-bold small text-white">' . htmlspecialchars($_SESSION['nombre'] ?? '') . '</span>'
    . '<span class="badge bg-light text-dark mb-2">' . htmlspecialchars($rolSesion) . '</span>'
    . '<a href="index.php?c=auth&a=cerrarSesion" class="enlace-menu link-danger">'
    . '<i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></div>';
?>

<!-- ============ BARRA SUPERIOR SOLO MÓVIL (hamburguesa) ============ -->
<nav class="navbar navbar-light bg-white border-bottom py-2 d-lg-none">
    <a href="index.php?c=dashboard&a=index" class="navbar-brand fw-bold text-primary">
        <i class="bi bi-heart-pulse-fill me-1"></i> Dental Care
    </a>
    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="offcanvas" data-bs-target="#menuMovil">
        <i class="bi bi-list fs-5"></i>
    </button>
</nav>

<!-- ============ BARRA LATERAL FIJA (escritorio) ============ -->
<div class="barra-lateral d-none d-lg-flex flex-column">
    <a href="index.php?c=dashboard&a=index" class="enlace-menu enlace-logo mb-3">
        <i class="bi bi-heart-pulse-fill"></i> DENTAL CARE
    </a>
    <?= $htmlMenu ?>
    <?= $pieUsuario ?>
</div>

<!-- ============ MENÚ MÓVIL FLOTANTE (offcanvas Bootstrap) ============ -->
<div class="offcanvas offcanvas-start offcanvas-oscuro" id="menuMovil" tabindex="-1">
    <div class="offcanvas-header border-bottom border-secondary">
        <span class="fw-bold text-white fs-5"><i class="bi bi-heart-pulse-fill me-1"></i> DENTAL CARE</span>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column">
        <?= $htmlMenu ?>
        <?= $pieUsuario ?>
    </div>
</div>

<!-- ============ ESTILOS DEL MENÚ (paleta del diseño: #0F172A / #0284C7) ============ -->
<style>
    .barra-lateral {
        position: fixed; top: 0; left: 0; bottom: 0; width: 260px;
        background-color: #0F172A; padding: 16px 12px; z-index: 1035;
    }
    @media (min-width: 992px) {
        body { padding-left: 260px; } /* Deja el espacio del menú fijo. */
    }
    .enlace-menu {
        display: flex; align-items: center; gap: 10px;
        color: #CBD5E1; padding: 10px 12px; margin-bottom: 4px;
        border-radius: 6px; font-family: 'Inter', sans-serif;
        font-weight: 600; font-size: .95rem; text-decoration: none;
        transition: background-color .15s ease;
    }
    .enlace-menu:hover { background-color: rgba(255, 255, 255, .08); color: #fff; }
    .enlace-menu.activo { background-color: #0284C7; color: #fff; }
    .enlace-logo { color: #fff !important; font-size: 1.15rem; }
    .offcanvas-oscuro { background-color: #0F172A; color: #fff; width: 280px; }
</style>
