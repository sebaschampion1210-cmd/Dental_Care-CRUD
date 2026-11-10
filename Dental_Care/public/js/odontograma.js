// =============================================================
// Script: odontograma.js (TASK-21 / HU-07 / RF-09)
// Cerebro interactivo del odontograma: pinta las caras al hacer
// clic y envía los hallazgos al servidor con fetch().
// =============================================================

// Paleta de colores (debe coincidir con la vista PHP).
const COLORES = {
    'Caries':     '#e53935', // Rojo
    'Resina':     '#1e88e5', // Azul
    'Endodoncia': '#8e24aa', // Morado
    'Ausente':    '#757575', // Gris
    'Sano':       '#e8eaf0'  // Cara sin marca
};

// Estado actual del dibujo: marcas['16|Oclusal'] = 'Caries'.
const marcas = {};

// 1. Al cargar, lee el SVG y registra lo que PHP ya pintó.
function cargarMarcasIniciales() {
    // Mapa inverso: color -> nombre del diagnóstico.
    const colorADiagnostico = {};
    Object.keys(COLORES).forEach(function (diagnostico) {
        colorADiagnostico[COLORES[diagnostico]] = diagnostico;
    });

    document.querySelectorAll('#odontograma-svg .cara').forEach(function (cara) {
        const pintura = cara.getAttribute('fill');
        const diagnostico = colorADiagnostico[pintura] || 'Sano';
        marcas[cara.dataset.diente + '|' + cara.dataset.cara] = diagnostico;
    });
}

// 2. Pinta una cara con el color de su diagnóstico y lo registra.
function pintarCara(cara, diagnostico) {
    cara.setAttribute('fill', COLORES[diagnostico]);
    marcas[cara.dataset.diente + '|' + cara.dataset.cara] = diagnostico;
}

// 3. Escucha los clics: pinta, desmarca o cambia el diagnóstico.
function activarPintado() {
    document.getElementById('odontograma-svg').addEventListener('click', function (evento) {
        const cara = evento.target.closest('.cara');
        if (cara === null) return; // Clic fuera de una cara: no pasa nada.
        if (!modoEditable()) return; // Histórico: el dibujo es de solo lectura.

        // Lee el diagnóstico activo de los botones de la vista.
        const elegido = document.querySelector('input[name="diagnostico"]:checked').value;
        const clave = cara.dataset.diente + '|' + cara.dataset.cara;

        // Si la cara ya tenía ese mismo diagnóstico, la volvemos sana.
        const nuevo = marcas[clave] === elegido ? 'Sano' : elegido;
        pintarCara(cara, nuevo);
    });
}

// 4. Arma la lista de hallazgos marcados (los 'Sano' NO se envían).
function armarHallazgos() {
    const hallazgos = [];
    Object.keys(marcas).forEach(function (clave) {
        if (marcas[clave] === 'Sano') return; // Sin marca: se ignora.

        const partes = clave.split('|');
        hallazgos.push({
            diente: partes[0],
            cara: partes[1],
            diagnostico: marcas[clave]
        });
    });
    return hallazgos;
}

// 5. Muestra una alerta Bootstrap arriba del contenido principal.
function mostrarAlerta(tipo, mensaje) {
    const vieja = document.getElementById('alerta-odontograma');
    if (vieja !== null) vieja.remove(); // Borra la alerta anterior.

    const alerta = document.createElement('div');
    alerta.id = 'alerta-odontograma';
    alerta.className = 'alert alert-' + tipo + ' alert-dismissible fade show';
    alerta.innerHTML = mensaje +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    document.querySelector('.container').prepend(alerta);
}

// 6. Envía los hallazgos al servidor con fetch() (POST en JSON).
// Contrato con el controlador (TASK-22): responde
// { "exito": true|false, "mensaje": "texto" } y guarda los
// hallazgos de HOY dentro de una transacción PDO.
async function guardarOdontograma() {
    // El histórico es de solo lectura: no se guarda sobre el pasado.
    if (!modoEditable()) {
        mostrarAlerta('warning', '<i class="bi bi-clock-history me-2"></i>Estás viendo una sesión histórica: no se puede guardar sobre el pasado.');
        return;
    }

    // El id del paciente viaja en la URL de la vista.
    const parametros = new URLSearchParams(window.location.search);
    const pacienteId = parametros.get('paciente_id');

    if (pacienteId === null || pacienteId === '') {
        mostrarAlerta('danger', '<i class="bi bi-exclamation-triangle-fill me-2"></i>Falta el paciente en la URL.');
        return;
    }

    const boton = document.getElementById('btn-guardar');
    boton.disabled = true; // Evita dobles clics mientras viaja la info.

    try {
        const respuesta = await fetch('index.php?c=odontograma&a=guardar&paciente_id=' + pacienteId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ hallazgos: armarHallazgos() })
        });

        const datos = await respuesta.json();
        mostrarAlerta(datos.exito === true ? 'success' : 'danger', datos.mensaje);
    } catch (error) {
        mostrarAlerta('danger', '<i class="bi bi-wifi me-2"></i>Error de conexión con el servidor.');
    }

    boton.disabled = false;
}

// 7. Indica si la sesión en pantalla es la de HOY (editable).
function modoEditable() {
    const selector = document.getElementById('selector-fecha');
    return selector.value === selector.dataset.hoy;
}

// 8. Limpia el SVG: todas las caras vuelven al color de sano.
function limpiarOdontograma() {
    document.querySelectorAll('#odontograma-svg .cara').forEach(function (cara) {
        pintarCara(cara, 'Sano');
    });
}

// 9. Repinta el SVG con los hallazgos de la fecha elegida (HU-08).
async function verOdontogramaHistorico(fecha) {
    const parametros = new URLSearchParams(window.location.search);
    const pacienteId = parametros.get('paciente_id');

    try {
        const respuesta = await fetch(
            'index.php?c=odontograma&a=hallazgos&paciente_id=' + pacienteId + '&fecha=' + fecha
        );
        const datos = await respuesta.json();

        if (datos.exito !== true) {
            mostrarAlerta('danger', datos.mensaje);
            return;
        }

        // Limpiamos lo de la otra sesión y pintamos la fecha pedida.
        limpiarOdontograma();
        datos.hallazgos.forEach(function (hallazgo) {
            const cara = document.querySelector(
                '#odontograma-svg .cara[data-diente="' + hallazgo.diente + '"][data-cara="' + hallazgo.cara + '"]'
            );
            if (cara !== null) pintarCara(cara, hallazgo.diagnostico);
        });

        // Aviso de solo lectura solo si la fecha NO es hoy.
        const esHistorico = fecha !== document.getElementById('selector-fecha').dataset.hoy;
        document.getElementById('aviso-historico').classList.toggle('d-none', !esHistorico);
        document.getElementById('fecha-historica').textContent = fecha;
    } catch (error) {
        mostrarAlerta('danger', '<i class="bi bi-wifi me-2"></i>Error al consultar el histórico.');
    }
}

// ============ ARRANQUE: conecta todo cuando carga la página ============
document.addEventListener('DOMContentLoaded', function () {
    cargarMarcasIniciales();   // Pinta lo guardado en MySQL.
    activarPintado();          // Activa los clics en las caras.
    document.getElementById('btn-guardar')
            .addEventListener('click', guardarOdontograma);

    // HU-08: al cambiar la fecha, el SVG se limpia y repinta.
    document.getElementById('selector-fecha')
            .addEventListener('change', function () {
                verOdontogramaHistorico(this.value);
            });
});
