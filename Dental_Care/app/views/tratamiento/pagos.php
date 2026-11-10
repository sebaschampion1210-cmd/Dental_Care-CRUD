<?php
// =============================================================
// Vista: tratamiento/pagos.php (TAREA-30 / HU-10 / RF-12 a RF-14)
// Pantalla de caja: resumen del presupuesto (Modal A del diseño),
// historial de abonos y modal Bootstrap para registrar cobros.
// Variables que recibe: $tratamiento, $abonos.
// =============================================================

// Alerta de resultado (éxito del abono o rechazo de RN-02).
$tipoAlerta    = $_SESSION['tipo_alerta'] ?? '';
$mensajeAlerta = $_SESSION['mensaje_alerta'] ?? '';
unset($_SESSION['tipo_alerta'], $_SESSION['mensaje_alerta']);

// Si el saldo es $0, ya NO debe admitirse ningún abono más.
$puedeAbonar = ((float)$tratamiento['saldo_pendiente'] > 0.0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Care | Pagos del Tratamiento</title>
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
    <h1 class="h4 fw-bold mb-3">Pagos y Recibos del Tratamiento</h1>

    <!-- ============ ALERTA DE RESULTADO (RN-02 rechazo va aquí) ============ -->
    <?php if ($mensajeAlerta !== ''): ?>
        <div class="alert alert-<?= $tipoAlerta === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <i class="bi <?= $tipoAlerta === 'exito' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($mensajeAlerta) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ============ TARJETA RESUMEN: MODAL A DEL DISEÑO UI ============ -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row align-items-center g-3">
                <div class="col-md-9">
                    <h2 class="h6 text-secondary fw-semibold mb-1">Tratamiento #<?= $tratamiento['id'] ?></h2>
                    <p class="fw-bold mb-1"><?= htmlspecialchars($tratamiento['descripcion']) ?></p>
                    <small class="text-secondary">
                        <i class="bi bi-person me-1"></i>
                        <?= htmlspecialchars($tratamiento['nombres'] . ' ' . $tratamiento['apellidos']) ?>
                        (C.C. <?= htmlspecialchars($tratamiento['numero_documento']) ?>)
                        &nbsp;|&nbsp;
                        <i class="bi bi-person-badge me-1"></i>
                        Dr. <?= htmlspecialchars($tratamiento['odontologo']) ?>
                    </small>
                </div>
                <div class="col-md-3 text-md-end">
                    <small class="text-secondary d-block">Costo Total</small>
                    <span class="fw-semibold">$<?= number_format((float)$tratamiento['costo_total'], 2) ?></span>
                    <small class="text-secondary d-block mt-2">Saldo Pendiente Actual</small>
                    <span class="fs-4 fw-bold text-danger">$<?= number_format((float)$tratamiento['saldo_pendiente'], 2) ?></span>
                    <div class="mt-1">
                        <span class="badge bg-<?= $tratamiento['estado'] === 'Pagado' ? 'success' : 'warning text-dark' ?>">
                            <?= $tratamiento['estado'] ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ BOTÓN QUE ABRE EL MODAL DE ABONO ============ -->
    <?php if ($puedeAbonar): ?>
        <button type="button" class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#modalAbono">
            <i class="bi bi-cash-stack me-1"></i> Registrar Abono
        </button>
    <?php else: ?>
        <div class="alert alert-success mb-3">
            <i class="bi bi-check-circle-fill me-2"></i> ¡Tratamiento liquidado! El saldo llegó a $0 y el estado es Pagado.
        </div>
    <?php endif; ?>

    <!-- ============ TABLA HISTORIAL DE ABONOS (RECIBOS) ============ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-receipt me-1 text-primary"></i>
            Historial de Abonos Registrados
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th># Recibo</th>
                            <th>Fecha</th>
                            <th class="text-end">Monto Abonado</th>
                            <th>Método de Pago</th>
                            <th>Observación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($abonos as $abono): ?>
                            <tr>
                                <td class="fw-semibold">PA-<?= str_pad($abono['id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= date('d/m/Y h:i A', strtotime($abono['fecha_pago'])) ?></td>
                                <!-- Monto en verde: dinero que ENTRÓ a la caja -->
                                <td class="text-end text-success fw-bold">$<?= number_format((float)$abono['monto'], 2) ?></td>
                                <td><span class="badge bg-info text-dark"><?= $abono['metodo_pago'] ?></span></td>
                                <td><small><?= htmlspecialchars($abono['observacion'] ?? '') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($abonos === []): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">
                                <i class="bi bi-wallet2 me-1"></i> Este tratamiento aún no tiene abonos registrados.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-secondary d-block mt-3">
                <i class="bi bi-shield-lock me-1"></i>
                RN-02: el sistema rechaza abonos negativos o superiores al saldo pendiente (se valida en transacción PDO).
                RF-14: al llegar el saldo a $0, el estado cambia automáticamente a 'Pagado'.
            </small>
        </div>
    </div>
</div>

<!-- ============ MODAL BOOTSTRAP: REGISTRAR ABONO (MODAL A) ============ -->
<div class="modal fade" id="modalAbono" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="index.php?c=tratamiento&a=registrarAbono">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Registrar Abono a Tratamiento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Resumen informativo superior del diseño (Modal A) -->
                    <div class="alert alert-light border py-2">
                        <small>
                            <strong>Saldo Pendiente Actual:</strong>
                            <span class="fw-bold text-danger">$<?= number_format((float)$tratamiento['saldo_pendiente'], 2) ?></span>
                        </small>
                    </div>
                    <input type="hidden" name="tratamiento_id" value="<?= $tratamiento['id'] ?>">
                    <div class="mb-2">
                        <label class="form-label">Monto a Abonar ($) *</label>
                        <input type="number" name="monto" class="form-control" step="0.01"
                               min="1" max="<?= (float)$tratamiento['saldo_pendiente'] ?>" required>
                        <div class="form-text text-danger">
                            <i class="bi bi-exclamation-circle me-1"></i>
                            El abono NO puede superar el saldo pendiente de $<?= number_format((float)$tratamiento['saldo_pendiente'], 2) ?>.
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Método de Pago *</label>
                        <select name="metodo_pago" class="form-select" required>
                            <option value="">— Seleccione el método —</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Tarjeta">Tarjeta</option>
                        </select>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Observación / Recibo</label>
                        <input type="text" name="observacion" class="form-control"
                               placeholder="Ej: Abono cuota mensual noviembre" maxlength="200">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-lg me-1"></i> Confirmar Pago y Generar Recibo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
