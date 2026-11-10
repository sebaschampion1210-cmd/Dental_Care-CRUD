<?php
// =============================================================
// Vista: usuario/index.php (módulo extra "Usuarios del Sistema",
// Pantalla 2 del diseño — ítem SOLO Admin). El administrador
// agrega doctores, recepcionistas y otros admins desde la web.
// Variables que recibe: $usuarios.
// =============================================================

// Alerta de resultado (creación o cambio de estado).
$tipoAlerta    = $_SESSION['tipo_alerta'] ?? '';
$mensajeAlerta = $_SESSION['mensaje_alerta'] ?? '';
unset($_SESSION['tipo_alerta'], $_SESSION['mensaje_alerta']);

// Colores Bootstrap de cada rol para las etiquetas.
$coloresRol = [
    'admin'         => 'danger',
    'odontologo'    => 'primary',
    'recepcionista' => 'warning text-dark',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Care | Usuarios del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #F8FAFC; font-family: 'Roboto', sans-serif; }
        h1, h2, .btn { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body>

<!-- ============ BARRA SUPERIOR: usuario activo + cerrar sesión ============ -->
<?php require __DIR__ . '/../partials/barra_lateral.php'; ?>

<div class="container py-4">
    <h1 class="h4 fw-bold mb-3">Usuarios del Sistema (Gestión de Personal)</h1>

    <!-- ============ ALERTA DE RESULTADO ============ -->
    <?php if ($mensajeAlerta !== ''): ?>
        <div class="alert alert-<?= $tipoAlerta === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $tipoAlerta === 'exito' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($mensajeAlerta) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ============ FORMULARIO: AGREGAR PERSONAL NUEVO ============ -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-person-plus me-1 text-primary"></i> Agregar Usuario Nuevo
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?c=usuario&a=guardar">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Nombre Completo *</label>
                        <input type="text" name="nombre" class="form-control" required
                               placeholder="Ej: Carlos González">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Correo Electrónico *</label>
                        <input type="email" name="correo" class="form-control" required
                               placeholder="doctores@dentalcare.com">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Clave de Acceso *</label>
                        <input type="password" name="clave" class="form-control" required minlength="8"
                               placeholder="Mínimo 8 caracteres">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Rol *</label>
                        <select name="rol" class="form-select" required>
                            <option value="">— Seleccione —</option>
                            <option value="odontologo">Odontólogo</option>
                            <option value="recepcionista">Recepcionista</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" title="Guardar usuario">
                            <i class="bi bi-plus-lg"></i>
                        </button>
                    </div>
                </div>
                <div class="form-text">
                    <i class="bi bi-shield-lock me-1"></i>
                    La clave se guarda encriptada con password_hash (nadie la ve ni la recupera).
                </div>
            </form>
        </div>
    </div>

    <!-- ============ TABLA: PERSONAL DEL CONSULTORIO ============ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-people me-1 text-primary"></i>
            Personal Registrado
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Correo</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php $esYo = $usuario['id'] === (int)($_SESSION['usuario_id'] ?? 0); ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($usuario['nombre']) ?>
                                    <br><small class="text-secondary">#<?= $usuario['id'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($usuario['correo']) ?></td>
                                <td><span class="badge bg-<?= $coloresRol[$usuario['rol']] ?>"><?= $usuario['rol'] ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $usuario['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                        <?= $usuario['estado'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($esYo): ?>
                                        <!-- Un admin no se desactiva a sí mismo -->
                                        <span class="text-secondary" title="Sesión actual">
                                            <i class="bi bi-person-check"></i> Tú
                                        </span>
                                    <?php elseif ($usuario['estado'] === 'activo'): ?>
                                        <a href="index.php?c=usuario&a=cambiarEstado&id=<?= $usuario['id'] ?>&estado=inactivo"
                                           class="btn btn-sm btn-outline-danger" title="Desactivar acceso">
                                            <i class="bi bi-person-x"></i> Desactivar
                                        </a>
                                    <?php else: ?>
                                        <a href="index.php?c=usuario&a=cambiarEstado&id=<?= $usuario['id'] ?>&estado=activo"
                                           class="btn btn-sm btn-outline-success" title="Reactivar acceso">
                                            <i class="bi bi-person-check"></i> Activar
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($usuarios === []): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">
                                <i class="bi bi-people me-1"></i> Aún no hay personal registrado.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-secondary d-block mt-3">
                <i class="bi bi-info-circle me-1"></i>
                Permisos actuales: Caja y Recall → recepcionista/admin · Odontograma → odontólogo/admin ·
                Gestión de usuarios → SOLO admin. Los usuarios inactivos NO pueden iniciar sesión.
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
