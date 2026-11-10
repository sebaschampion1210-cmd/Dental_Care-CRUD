<?php
// =============================================================
// Vista: dashboard/index.php (TAREA-36 / HU-12 / RF-17)
// Panel Gerencial (Pantalla 3 del diseño Figma): 4 tarjetas KPI,
// agenda del día (65% izquierda) y pacientes por llamar (35%).
// Variables que recibe: $citasHoy, $ingresosMes, $cartera,
// $inasistencia, $citasAgenda, $porLlamar.
// =============================================================

// Alerta de resultado (reservada para avisos del panel).
$tipoAlerta    = $_SESSION['tipo_alerta'] ?? '';
$mensajeAlerta = $_SESSION['mensaje_alerta'] ?? '';
unset($_SESSION['tipo_alerta'], $_SESSION['mensaje_alerta']);

// La tarifa roja se activa si las inasistencias superan el 15% (< diseño).
$alertaInasistencia = $inasistencia['tasa'] > 15;

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
    <title>Dental Care | Panel Gerencial</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #F8FAFC; font-family: 'Roboto', sans-serif; }
        h1, h2, .btn, .kpi-numero { font-family: 'Inter', sans-serif; }
        .kpi-numero { font-size: 1.8rem; font-weight: 700; }
    </style>
</head>
<body>

<!-- ============ BARRA SUPERIOR: usuario activo + cerrar sesión ============ -->
<?php require __DIR__ . '/../partials/barra_lateral.php'; ?>

<div class="container py-4">
    <h1 class="h4 fw-bold mb-3">Panel Gerencial — Resumen del Consultorio</h1>

    <!-- ============ ALERTA DE RESULTADO ============ -->
    <?php if ($mensajeAlerta !== ''): ?>
        <div class="alert alert-<?= $tipoAlerta === 'exito' ? 'success' : 'danger' ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($mensajeAlerta) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

<!-- ============ FILA SUPERIOR: TARJETAS KPI (filtradas por rol) ============ -->
<!-- $esRolCaja: recepcionista/admin ven las 4 tarjetas; el odontólogo
     solo ve las CLÍNICAS (citas e inasistencias) a medio ancho. -->
<?php
$esRolCaja = in_array($_SESSION['rol'] ?? '', ['recepcionista', 'admin'], true);
$medioAncho = $esRolCaja ? 'col-12 col-md-6 col-xl-3' : 'col-12 col-md-6 col-xl-6';
?>
<div class="row g-3 mb-4">
    <!-- KPI 1: Citas de hoy -->
    <div class="<?= $medioAncho ?>">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-secondary fw-semibold">
                                <i class="bi bi-calendar-day me-1"></i>Citas de Hoy
                            </span>
                            <div class="kpi-numero"><?= $citasHoy['total'] ?> citas</div>
                            <small class="text-secondary"><?= $citasHoy['pendientes'] ?> pendientes por atender</small>
                        </div>
                        <i class="bi bi-journal-check fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($esRolCaja): ?>
        <!-- KPI 2: Ingresos del mes (SOLO caja: recepcionista/admin) -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-secondary fw-semibold">
                                <i class="bi bi-cash-stack me-1"></i>Ingresos del Mes
                            </span>
                            <div class="kpi-numero text-success">$<?= number_format($ingresosMes, 2) ?></div>
                            <small class="text-secondary">sum de abonos registrados</small>
                        </div>
                        <i class="bi bi-graph-up-arrow fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI 3: Cartera por cobrar -->
        <div class="col-12 col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-secondary fw-semibold">
                                <i class="bi bi-wallet2 me-1"></i>Cartera por Cobrar
                            </span>
                            <div class="kpi-numero text-warning-emphasis">$<?= number_format($cartera, 2) ?></div>
                            <small class="text-secondary">saldos de tratamientos Activos</small>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php endif; ?>

        <!-- KPI 4: Tasa de inasistencia (también para el doctor) -->
        <div class="<?= $medioAncho ?>">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-secondary fw-semibold">
                                <i class="bi bi-person-x me-1"></i>Tasa de Inasistencia
                            </span>
                            <div class="kpi-numero <?= $alertaInasistencia ? 'text-danger' : '' ?>">
                                <?= number_format($inasistencia['tasa'], 1) ?>%
                            </div>
                            <small class="text-secondary"><?= $inasistencia['faltas'] ?> faltas este mes</small>
                        </div>
                        <i class="bi bi-exclamation-octagon fs-1 text-danger opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">

        <!-- ============ COLUMNA IZQUIERDA (65%): AGENDA DEL DÍA ============ -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-calendar-event me-1 text-primary"></i>
                        Agenda de Hoy (<?= date('d/m/Y') ?>)
                    </span>
                    <a href="index.php?c=cita&a=index" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-up-right-square me-1"></i> Ir a la Agenda
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Horario</th>
                                    <th>Paciente</th>
                                    <th>Odontólogo</th>
                                    <th>Estado</th>
                                    <th class="text-end">Expediente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($citasAgenda as $cita): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= substr($cita['hora_inicio'], 0, 5) ?> – <?= substr($cita['hora_fin'], 0, 5) ?></td>
                                        <td>
                                            <?= htmlspecialchars($cita['nombres'] . ' ' . $cita['apellidos']) ?>
                                            <br><small class="text-secondary"><?= htmlspecialchars($cita['motivo']) ?></small>
                                        </td>
                                        <td><?= htmlspecialchars($cita['odontologo']) ?></td>
                                        <td><span class="badge bg-<?= $coloresEstado[$cita['estado']] ?>"><?= $cita['estado'] ?></span></td>
                                        <td class="text-end">
                                            <a href="index.php?c=odontograma&a=ver&paciente_id=<?= $cita['paciente_id']?>"
                                               class="btn btn-sm btn-outline-secondary" title="Abrir expediente clínico">
                                                <i class="bi bi-folder2-open"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if ($citasAgenda === []): ?>
                                    <tr><td colspan="5" class="text-center text-secondary py-4">
                                        <i class="bi bi-calendar-x me-1"></i> No hay turnos reservados para hoy.
                                    </td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ COLUMNA DERECHA (35%): PACIENTES POR LLAMAR ============ -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-whatsapp me-1 text-success"></i>
                    Pacientes por Llamar (Recall)
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <?php foreach ($porLlamar as $paciente): ?>
                            <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-semibold"><?= htmlspecialchars($paciente['nombres'] . ' ' . $paciente['apellidos']) ?></span>
                                    <br><small class="text-secondary">
                                        <?php if ($paciente['dias_inactivo'] === null): ?>
                                            Nunca ha venido
                                        <?php else: ?>
                                            <?= $paciente['dias_inactivo'] ?> días sin visita
                                        <?php endif; ?>
                                        · Tel: <?= htmlspecialchars($paciente['telefono']) ?>
                                    </small>
                                </div>
                                <!-- Botón verde directo: WhatsApp en un clic -->
                                <a href="<?= $paciente['enlace_whatsapp'] ?>" target="_blank"
                                   class="btn btn-sm btn-success" title="Enviar mensaje por WhatsApp">
                                    <i class="bi bi-whatsapp"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($porLlamar === []): ?>
                            <div class="text-center text-secondary py-4">
                                <i class="bi bi-emoji-smile me-1"></i> ¡Todos los pacientes al día!
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="index.php?c=recall&a=index" class="btn btn-sm btn-outline-success w-100 mt-2">
                        <i class="bi bi-arrow-right me-1"></i> Ver lista completa
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
