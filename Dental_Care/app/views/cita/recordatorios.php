<?php
// =============================================================
// Vista: cita/recordatorios.php (extensión HU-11 solicitada por
// Sebastian: avisar al paciente UN DÍA ANTES de su cita médica).
// Muestra la agenda del día siguiente con botones de WhatsApp.
// Variables que recibe: $citas, $fechaCitas.
// =============================================================

// Alerta de resultado (éxito o error, por si el módulo avisa algo).
$tipoAlerta    = $_SESSION['tipo_alerta'] ?? '';
$mensajeAlerta = $_SESSION['mensaje_alerta'] ?? '';
unset($_SESSION['tipo_alerta'], $_SESSION['mensaje_alerta']);

// Colores Bootstrap de cada estado del turno (igual que la agenda).
$coloresEstado = [
    'Pendiente'    => 'warning text-dark',
    'Confirmada'   => 'primary',
    'Atendida'     => 'success',
    'Cancelada'    => 'secondary',
    'Inasistencia' => 'danger',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Care | Recordatorios de Citas</title>
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
    <h1 class="h4 fw-bold mb-3">Recordatorios de Citas (Día Siguiente)</h1>

    <!-- ============ ALERTA DE RESULTADO ============ -->
    <?php if ($mensajeAlerta !== ''): ?>
        <div class="alert alert-<?= $tipoAlerta === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensajeAlerta) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ============ TABLA DE CITAS DE MAÑANA ============ -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold">
            <i class="bi bi-bell me-1 text-warning"></i>
            Citas del <?= date('d/m/Y', strtotime($fechaCitas)) ?> — aviso 1 día antes
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Hora</th>
                            <th>Paciente</th>
                            <th>Teléfono</th>
                            <th>Odontólogo</th>
                            <th>Motivo</th>
                            <th>Estado</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($citas as $cita): ?>
                            <tr>
                                <td class="fw-semibold">
                                    <?= substr($cita['hora_inicio'], 0, 5) ?> – <?= substr($cita['hora_fin'], 0, 5) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos']) ?>
                                    <br><small class="text-secondary">C.C. <?= htmlspecialchars($cita['numero_documento']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($cita['telefono']) ?></td>
                                <td><?= htmlspecialchars($cita['odontologo']) ?></td>
                                <td><small><?= htmlspecialchars($cita['motivo']) ?></small></td>
                                <td>
                                    <span class="badge bg-<?= $coloresEstado[$cita['estado']] ?>">
                                        <?= $cita['estado'] ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($cita['enlace_whatsapp'] !== ''): ?>
                                        <!-- Botón verde: recordatorio listo para enviar -->
                                        <a href="<?= $cita['enlace_whatsapp'] ?>" target="_blank"
                                           class="btn btn-sm btn-success" title="Enviar recordatorio por WhatsApp">
                                            <i class="bi bi-whatsapp me-1"></i> Recordar
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secondary" title="Turno cerrado: no necesita aviso">
                                            <i class="bi bi-lock-fill"></i> Sin aviso
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if ($citas === []): ?>
                            <tr><td colspan="7" class="text-center text-secondary py-4">
                                <i class="bi bi-calendar-check me-1"></i>
                                No hay citas reservadas para mañana: nada pendiente por recordar.
                            </td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <small class="text-secondary d-block mt-3">
                <i class="bi bi-info-circle me-1"></i>
                Idea clave: este aviso se envía UN DÍA ANTES (ej. la cita del 10/11/2026 se recuerda el 9/11/2026).
                Las citas Canceladas, Inasistencia o Atendida no reciben recordatorio.
            </small>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
