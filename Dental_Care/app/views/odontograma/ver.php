<?php
// =============================================================
// Vista: odontograma/ver.php (TASK-20 / HU-07 / RF-09)
// Odontograma SVG interactivo: 32 piezas adultas, cada una con
// 5 caras clicables (data-diente y data-cara).
// Variables que recibe: $paciente (ficha del paciente) y
// $hallazgos (lista de diagnósticos guardados en MySQL).
// =============================================================

// Valores por defecto para que la vista no falle si falta algún dato.
$paciente  = $paciente  ?? [];
$hallazgos = $hallazgos ?? [];

// Colores de la convención clínica (RF-09).
$coloresDiagnostico = [
    'Caries'     => '#e53935', // Rojo
    'Resina'     => '#1e88e5', // Azul
    'Endodoncia' => '#8e24aa', // Morado
    'Ausente'    => '#757575', // Gris
];
$colorSinMarca = '#e8eaf0'; // Color neutro de las caras sanas.

// Mapa rápido de hallazgos: $pintados[16]['Oclusal'] = 'Caries'.
$pintados = [];
foreach ($hallazgos as $hallazgo) {
    $pintados[$hallazgo['diente']][$hallazgo['cara']] = $hallazgo['diagnostico'];
}

// Las 32 piezas adultas en notación FDI, de izquierda a derecha.
$arcadaSuperior = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
$arcadaInferior = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];

// Posición de las 5 caras dentro del cuadro de 40x40 de cada pieza.
$carasDiente = [
    'Vestibular' => ['x' => 0,  'y' => 0,  'w' => 40, 'h' => 12], // Franja superior
    'Lingual'    => ['x' => 0,  'y' => 28, 'w' => 40, 'h' => 12], // Franja inferior
    'Mesial'     => ['x' => 0,  'y' => 12, 'w' => 12, 'h' => 16], // Franja izquierda
    'Distal'     => ['x' => 28, 'y' => 12, 'w' => 12, 'h' => 16], // Franja derecha
    'Oclusal'    => ['x' => 12, 'y' => 12, 'w' => 16, 'h' => 16], // Cuadro central
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dental Care | Odontograma</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #F8FAFC; font-family: 'Roboto', sans-serif; }
        h1, h2, .btn { font-family: 'Inter', sans-serif; }
        /* La cara del diente reacciona al mouse para invitar al clic. */
        .cara { cursor: pointer; stroke: #b0b8c4; stroke-width: 1; }
        .cara:hover { opacity: 0.7; }
        .pieza { font-size: 11px; fill: #64748b; text-anchor: middle; }
    </style>
</head>
<body>

<!-- ============ BARRA SUPERIOR: igual estilo que la agenda ============ -->
<?php require __DIR__ . '/../partials/barra_lateral.php'; ?>

<div class="container py-4">
    <h1 class="h4 fw-bold mb-1">Odontograma Interactivo</h1>
    <p class="text-secondary mb-3">
        Paciente: <strong><?= htmlspecialchars(($paciente['nombres'] ?? '') . ' ' . ($paciente['apellidos'] ?? '')) ?></strong>
        &nbsp;|&nbsp; C.C. <?= htmlspecialchars($paciente['numero_documento'] ?? '') ?>
    </p>

    <div class="row g-3">

        <!-- ============ COLUMNA IZQUIERDA: SELECCIÓN + SVG ============ -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-clipboard2-pulse me-1 text-primary"></i>
                    1. Elige un diagnóstico &nbsp; 2. Haz clic en las caras &nbsp; 3. Guarda
                </div>
                <div class="card-body">

                    <!-- Selector de sesión histórica (HU-08 / TASK-24). -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-1" for="selector-fecha">
                            <i class="bi bi-clock-history me-1"></i> Ver la sesión del día:
                        </label>
                        <select id="selector-fecha" class="form-select" data-hoy="<?= date('Y-m-d') ?>">
                            <option value="<?= date('Y-m-d') ?>">Hoy — sesión editable</option>
                            <?php foreach ($fechasHistoricas as $fechaHistorica): ?>
                                <?php if ($fechaHistorica['fecha'] !== date('Y-m-d')): ?>
                                    <option value="<?= $fechaHistorica['fecha'] ?>">
                                        Sesión del <?= date('d/m/Y', strtotime($fechaHistorica['fecha'])) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Aviso de solo lectura al ver una sesión pasada (HU-08). -->
                    <div id="aviso-historico" class="alert alert-info py-2 d-none">
                        <i class="bi bi-eye me-1"></i>
                        Estás viendo el odontograma del <strong id="fecha-historica"></strong> (solo lectura).
                    </div>

                    <!-- Selector del diagnóstico activo (lo lee el JS de TASK-21). -->
                    <div class="btn-group w-100 mb-3" role="group" id="selector-diagnostico">
                        <?php foreach ($coloresDiagnostico as $nombre => $colorHex): ?>
                            <input type="radio" class="btn-check" name="diagnostico" id="diag-<?= $nombre ?>"
                                   value="<?= $nombre ?>" <?= $nombre === 'Caries' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-secondary" for="diag-<?= $nombre ?>">
                                <span class="d-inline-block me-1 border rounded-1"
                                      style="width:14px;height:14px;background:<?= $colorHex ?>;"></span>
                                <?= $nombre ?>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <!-- ============ ODONTOGRAMA SVG (32 piezas x 5 caras) ============ -->
                    <div class="table-responsive">
                        <svg id="odontograma-svg" viewBox="0 0 744 220" width="100%" role="img"
                             aria-label="Odontograma de 32 dientes">

                            <?php
                            // Dibujamos las dos arcadas con un bucle: fila 0 = superior.
                            foreach ([$arcadaSuperior, $arcadaInferior] as $fila => $piezas):
                                $yCuadro = $fila === 0 ? 40 : 120;  // Y del cuadro del diente.
                                $yNumero = $fila === 0 ? 34 : 174;  // Y del número de la pieza.
                                foreach ($piezas as $indice => $diente):
                                    $x = 20 + $indice * 44;          // X de la pieza en la fila.
                                    foreach ($carasDiente as $cara => $geo):
                                        // ¿Ya existe un diagnóstico guardado para esta cara?
                                        $color = $coloresDiagnostico[$pintados[$diente][$cara] ?? ''] ?? $colorSinMarca; ?>
                                        <rect class="cara"
                                              data-diente="<?= $diente ?>" data-cara="<?= $cara ?>"
                                              x="<?= $x + $geo['x'] ?>" y="<?= $yCuadro + $geo['y'] ?>"
                                              width="<?= $geo['w'] ?>" height="<?= $geo['h'] ?>"
                                              fill="<?= $color ?>"></rect>
                                    <?php endforeach; ?>
                                    <text class="pieza" x="<?= $x + 20 ?>" y="<?= $yNumero ?>"><?= $diente ?></text>
                            <?php endforeach;
                            endforeach; ?>
                        </svg>
                    </div>

                    <!-- Leyenda de colores de la convención clínica. -->
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <?php foreach ($coloresDiagnostico as $nombre => $colorHex): ?>
                            <span class="small"><span class="d-inline-block border rounded-1 me-1"
                                style="width:14px;height:14px;background:<?= $colorHex ?>;"></span><?= $nombre ?></span>
                        <?php endforeach; ?>
                        <span class="small"><span class="d-inline-block border rounded-1 me-1"
                            style="width:14px;height:14px;background:<?= $colorSinMarca ?>;"></span>Sano</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============ COLUMNA DERECHA: GUARDAR + HALLAZGOS ============ -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-save me-1 text-primary"></i> Guardar Registro
                </div>
                <div class="card-body">
                    <p class="small text-secondary">
                        Se guarda todo lo marcado en el dibujo, asociado al paciente y a la fecha de hoy.
                    </p>
                    <button id="btn-guardar" class="btn btn-primary w-100">
                        <i class="bi bi-check2-circle me-1"></i> Guardar Odontograma
                    </button>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">
                    <i class="bi bi-list-check me-1 text-primary"></i> Hallazgos Registrados
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Pieza</th><th>Cara</th><th>Diagnóstico</th><th>Observación</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hallazgos as $hallazgo): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $hallazgo['diente'] ?></td>
                                    <td><?= $hallazgo['cara'] ?></td>
                                    <td><span class="badge"
                                        style="background:<?= $coloresDiagnostico[$hallazgo['diagnostico']] ?? '#6c757d' ?>;">
                                        <?= $hallazgo['diagnostico'] ?></span></td>
                                    <td><small><?= htmlspecialchars($hallazgo['observacion'] ?? '') ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if ($hallazgos === []): ?>
                                <tr><td colspan="4" class="text-center text-secondary py-3">
                                    <i class="bi bi-emoji-smile me-1"></i> Sin hallazgos registrados.
                                </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- El cerebro interactivo se conecta en la TASK-21. -->
<script src="public/js/odontograma.js"></script>
</body>
</html>
