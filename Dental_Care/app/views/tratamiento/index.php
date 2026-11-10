<?php
// =============================================================
// Vista: tratamiento/index.php (TAREA-27 / HU-09 / RF-11)
// Pantalla de Presupuestos y Cartera: formulario de creación
// (izquierda) y listado de presupuestos (derecha).
// Variables que recibe: $odontologos, $pacientes, $tratamientos.
// =============================================================

// Alerta de resultado (éxito de guardado o error de validación).
$tipoAlerta    = $_SESSION['tipo_alerta'] ?? '';
$mensajeAlerta = $_SESSION['mensaje_alerta'] ?? '';
unset($_SESSION['tipo_alerta'], $_SESSION['mensaje_alerta']);

// Colores Bootstrap de cada estado del presupuesto (badges).
$coloresEstado = [
    'Activo' => 'warning text-dark',
    'Pagado' => 'success',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Care | Presupuestos y Caja</title>
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
    <h1 class="h4 fw-bold mb-3">Presupuestos y Control de Cartera</h1>

    <!-- ============ ALERTA DE RESULTADO ============ -->
    <?php if ($mensajeAlerta !== ''): ?>
        <div class="alert alert-<?= $tipoAlerta === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $tipoAlerta === 'exito' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($mensajeAlerta) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-3">

        <!-- ============ COLUMNA IZQUIERDA (40%): FORMULARIO DE PRESUPUESTO ============ -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-cash-coin me-1 text-primary"></i> Nuevo Presupuesto
                </div>
                <div class="card-body">
                    <form method="POST" action="index.php?c=tratamiento&a=guardar">
                        <div class="mb-2">
                            <label class="form-label">Paciente *</label>
                            <select name="paciente_id" class="form-select" required>
                                <option value="">— Seleccione el paciente —</option>
                                <?php foreach ($pacientes as $paciente): ?>
                                    <option value="<?= $paciente['id'] ?>">
                                        <?= htmlspecialchars($paciente['numero_documento'] . ' — ' . $paciente['nombres'] . ' ' . $paciente['apellidos']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Odontólogo que elabora *</label>
                            <select name="odontologo_id" class="form-select" required>
                                <option value="">— Seleccione el odontólogo —</option>
                                <?php foreach ($odontologos as $odontologo): ?>
                                    <option value="<?= $odontologo['id'] ?>">
                                        <?= htmlspecialchars('Dr. ' . $odontologo['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">Plan de Tratamiento (Descripción) *</label>
                            <textarea name="descripcion" class="form-control" rows="2" required
                                      placeholder="Ej: Endodoncia pieza 36 + corona metal-porcelana"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Costo Total ($) *</label>
                            <input type="number" name="costo_total" class="form-control"
                                   min="1" max="99999999.99" step="0.01" required placeholder="Ej: 950000">
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                HU-09: el saldo pendiente arranca por el MISMO valor del costo total.
                            </div>
                            <div class="form-text text-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                Valor máximo permitido: $99.999.999,99.
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-save me-1"></i> Guardar Presupuesto
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============ COLUMNA DERECHA (60%): LISTADO DE PRESUPUESTOS ============ -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-journal-text me-1 text-primary"></i>
                    Presupuestos Registrados
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Paciente</th>
                                    <th>Tratamiento</th>
                                    <th>Odontólogo</th>
                                    <th class="text-end">Costo Total</th>
                                    <th class="text-end">Saldo Pendiente</th>
                                    <th>Estado</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tratamientos as $tratamiento): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($tratamiento['nombres'] . ' ' . $tratamiento['apellidos']) ?>
                                            <br><small class="text-secondary">C.C. <?= htmlspecialchars($tratamiento['numero_documento']) ?></small>
                                        </td>
                                        <td><small><?= htmlspecialchars($tratamiento['descripcion']) ?></small></td>
                                        <td><?= htmlspecialchars($tratamiento['odontologo']) ?></td>
                                        <td class="text-end">$<?= number_format((float)$tratamiento['costo_total'], 2) ?></td>
                                        <!-- El saldo va en negrita: es la cifra clave de cartera -->
                                        <td class="text-end fw-bold">$<?= number_format((float)$tratamiento['saldo_pendiente'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $coloresEstado[$tratamiento['estado']] ?? 'secondary' ?>">
                                                <?= $tratamiento['estado'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <?php if ((float)$tratamiento['saldo_pendiente'] > 0.0): ?>
                                                <!-- Botón verde de abono (diseño Pantalla 6) -->
                                                <a href="index.php?c=tratamiento&a=pagos&id=<?= $tratamiento['id'] ?>"
                                                   class="btn btn-sm btn-success" title="Registrar abono">
                                                    <i class="bi bi-cash-stack"></i> Abonar
                                                </a>
                                            <?php else: ?>
                                                <span class="text-secondary" title="Tratamiento liquidado">
                                                    <i class="bi bi-lock-fill"></i> Liquidado
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($tratamientos === []): ?>
                                    <tr><td colspan="7" class="text-center text-secondary py-4">
                                        <i class="bi bi-receipt-x me-1"></i> Aún no hay presupuestos registrados.
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <small class="text-secondary d-block mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        RF-11: al guardar, el saldo pendiente se inicializa igual al costo total y el estado es 'Activo'.
                        Los abonos (HU-10) se registrarán aquí en la siguiente tarea.
                    </small>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
