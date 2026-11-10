<?php
// =============================================================
// Vista: recall/index.php (TAREA-33 / HU-11 / RF-15 y RF-16)
// Lista de Pacientes Inactivos (Recall): la tabla lista a los
// que llevan +180 días sin cita y trae el botón de WhatsApp.
// Variables que recibe: $inactivos.
// =============================================================

// Alerta de resultado (reservada por si el módulo avisa algo).
$tipoAlerta    = $_SESSION['tipo_alerta'] ?? '';
$mensajeAlerta = $_SESSION['mensaje_alerta'] ?? '';
unset($_SESSION['tipo_alerta'], $_SESSION['mensaje_alerta']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Care | Recall de Pacientes</title>
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
    <h1 class="h4 fw-bold mb-3">Recall: Pacientes por Reactivar</h1>

    <!-- ============ ALERTA DE RESULTADO ============ -->
    <?php if ($mensajeAlerta !== ''): ?>
        <div class="alert alert-<?= $tipoAlerta === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensajeAlerta) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ============ TABLA DE PACIENTES INACTIVOS ============ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-whatsapp me-1 text-success"></i>
            Pacientes con +180 días sin cita o con saldo por pagar
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Paciente</th>
                            <th>Teléfono</th>
                            <th>Última Cita</th>
                            <th>Días sin Visitar</th>
                            <th class="text-end">Saldo por Pagar</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inactivos as $paciente): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos']) ?>
                                    <br><small class="text-secondary">C.C. <?= htmlspecialchars($paciente['numero_documento']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($paciente['telefono']) ?></td>
                                <td>
                                    <?= $paciente['ultima_cita'] ? date('d/m/Y', strtotime($paciente['ultima_cita'])) : '—' ?>
                                </td>
                                <td>
                                    <?php if ($paciente['dias_inactivo'] === null): ?>
                                        <span class="badge bg-secondary">Nunca ha venido</span>
                                    <?php elseif ((int)$paciente['dias_inactivo'] < 0): ?>
                                        <span class="badge bg-info text-dark">Cita programada</span>
                                    <?php else: ?>
                                        <span class="badge <?= $paciente['dias_inactivo'] > 180 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                                            <?= $paciente['dias_inactivo'] ?> días
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <!-- Rojo si aún debe dinero de su presupuesto -->
                                <td class="text-end <?= (float)$paciente['saldo_total'] > 0 ? 'fw-bold text-danger' : 'text-secondary' ?>">
                                    $<?= number_format((float)$paciente['saldo_total'], 2) ?>
                                </td>
                                <td class="text-end">
                                    <!-- Botón verde directo a WhatsApp (RF-16) -->
                                    <a href="<?= $paciente['enlace_whatsapp'] ?>" target="_blank"
                                       class="btn btn-sm btn-success" title="Enviar mensaje por WhatsApp">
                                        <i class="bi bi-whatsapp me-1"></i> WhatsApp
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($inactivos === []): ?>
                            <tr><td colspan="6" class="text-center text-secondary py-4">
                                <i class="bi bi-emoji-smile me-1"></i>
                                ¡Excelente! No hay pacientes inactivos: todos mantienen su control al día.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-secondary d-block mt-3">
                <i class="bi bi-info-circle me-1"></i>
                RF-15: se listan pacientes con más de 180 días sin cita efectiva o con tratamientos activos con saldo.
                El botón abre WhatsApp con el mensaje ya redactado para enviarlo con un clic.
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
