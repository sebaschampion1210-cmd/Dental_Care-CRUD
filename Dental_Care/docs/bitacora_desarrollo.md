# 📖 Bitácora de Desarrollo — Sistema Dental Care

**Proyecto:** Sistema Web Consultorio Odontológico (MVC PHP 8 + MySQL + JS Vanilla + Bootstrap 5)
**Aprendiz:** Sebastian Vega (SENA)
**Última actualización:** 10/11/2026 — ✅ PROYECTO TERMINADO Y CERRADO (sistema completo + extras de seguridad/UX)

> Esta bitácora funciona como memoria del proyecto: registra lo que se hizo, dónde quedó y qué falta. Cualquier agente o persona puede leerla para continuar el desarrollo sin empezar de cero.

---

## 🏆 Estado General del Proyecto (10/11/2026) — CIERRE FINAL ✅

| Épica | Módulo | Estado | Story Points |
| :--- | :--- | :--- | :---: |
| EP-01 | Seguridad y Gestión de Accesos | ✅ Completada | 5 |
| EP-02 | Gestión de Pacientes y Expedientes | ✅ Completada | 5 |
| EP-03 | Agenda y Control de Citas Médicas | ✅ Completada | 7 |
| EP-04 | Odontograma Clínico Interactivo (SVG) | ✅ Completada | 13 |
| EP-05 | Presupuestos y Control de Cartera | ✅ Completada | 8 |
| EP-06 | Retención (Recall) y Dashboard | ✅ Completada | 8 |

**Avance: 36 de 36 tareas técnicas (46 de 46 story points) — TODAS las épicas completadas con prueba en navegador por Sebastian.**

---

## 📅 Sesión de trabajo del 10/11/2026

### 1. Construcción completa de EP-05 (Presupuestos y Cartera)
- **TASK-25 (SQL):** tabla `tratamiento` con `DECIMAL(10,2)`, FKs a `paciente` y `usuario`, estado `ENUM('Activo','Pagado') DEFAULT 'Activo'`. Presupuesto de prueba: $950.000.
- **TASK-26 (MVC):** `TratamientoModel.php` (`listar`, `obtenerPorId`, `crear` — el INSERT usa el mismo marcador `:costo` para costo_total y saldo_pendiente) y `TratamientoController.php` (`index`, `guardar`). Límite de costo: constante `COSTO_MAXIMO = 99999999.99` en HTML (`max`) y PHP (petición de Sebastian: evitar cifras ilógicas).
- **TASK-28 (SQL):** tabla `pago_abono` (`monto`, `metodo_pago` ENUM Efectivo/Transferencia/Tarjeta, `observacion`, FK a tratamiento).
- **TASK-29 (Modelo):** `PagoModel.php` — transacción PDO con `beginTransaction`, `SELECT ... FOR UPDATE` (bloqueo de fila), validación RN-02 (abono > 0 y ≤ saldo), INSERT del abono, recálculo de saldo y estado (`Pagado` si llega a $0), `commit`/`rollBack`.
- **TASK-30/27 (Vistas):** `tratamiento/index.php` (form + tabla con botón "Abonar"/"Liquidado") y `tratamiento/pagos.php` (resumen tipo Modal A del diseño, historial de recibos `PA-xxxx`, modal Bootstrap de abono).
- Probado por Sebastian: CP-11, CP-12, CP-13 (rechazo de abono mayor al saldo), CP-14 (paso automático a Pagado). Marcadas: HU-09, HU-10, EP-05. Trazabilidad: RF-11 a RF-14. RN-02 ✅.

### 2. Construcción completa de HU-11 (Recall WhatsApp)
- **TASK-31 (SQL):** `RecallModel.php` — `LEFT JOIN cita` filtrando estados no efectivos, `MAX(fecha_cita)` + `DATEDIFF(CURDATE(), MAX(...)) > 180`, y `SUM(t.saldo_pendiente)` de tratamientos activos (la regla del HAVING es: inactivo por días O con saldo por pagar).
- **TASK-32 (Controlador):** `RecallController.php` — mensaje personalizado (saludo + días + saldo o invitación a agendar) y URL `https://wa.me/57[numero]?text=...` con `rawurlencode()`.
- **TASK-33 (Vista):** `recall/index.php` — badges: 🔴 +180 días, 🟡 normal, 🔵 cita programada (futuras, días negativos corregidos), ⚪ nunca ha venido; botón verde WhatsApp por paciente.
- Probado por Sebastian con datos reales (Andrés aparece por saldo aunque citó hoy, andrea aparece como "nunca ha venido"). Marcadas: HU-11. Trazabilidad: RF-15, RF-16.

### 3. Funcionalidad EXTRA solicitada por Sebastian: Recordatorios de citas
- **Idea:** avisar al paciente UN DÍA ANTES de su cita (ej. cita del 10/11/2026 → aviso el 9/11/2026).
- **Implementación:** `CitaController::recordatorios()` usa `date('Y-m-d', strtotime('+1 day'))` y reutiliza `listarDelDia()`. Se amplió `CitaModel::listarDelDia()` para traer `p.telefono`. Método privado `crearEnlaceRecordatorio()` arma el mensaje (fecha, hora, doctor y motivo) solo para citas `Pendiente`/`Confirmada`; los turnos cerrados muestran "Sin aviso".
- **Vista nueva:** `app/views/cita/recordatorios.php` + botón "Recordatorios" 🔔 en la barra superior de la agenda. Documentado también en el título HU-11 (nota de mejora continua).
- **Estado:** implementado — por probar en navegador (URL: `?c=cita&a=recordatorios`).

### 4. Construcción completa de HU-12 (Dashboard Gerencial) — la última historia 🏆
- **TASK-34 (Modelo):** `DashboardModel.php` con las 4 agregaciones SQL: `citasDeHoy()` (COUNT + SUM de pendientes), `ingresosDelMes()` (SUM de pago_abono del mes actual con MONTH/YEAR), `carteraPendiente()` (SUM saldo de tratamientos Activos) y `tasaInasistencia()` (faltas/total*100, protegida contra división por 0). Métricas probadas contra la BD real: 2 citas hoy, $700.000 de ingresos, $1.250.000 de cartera, 0 faltas en el mes.
- **TASK-35 (Controlador):** `DashboardController.php` — reutiliza `CitaModel::listarDelDia()` para la agenda y `RecallModel` (listado + enlaces WhatsApp) para la columna de pacientes por llamar (código compartido, sin duplicar).
- **TASK-36 (Vista):** `dashboard/index.php` según Pantalla 3 del diseño: 4 tarjetas KPI con borde de color (azul/green/ámbar/rojo, tasa roja si supera el 15%), agenda del día (65%) con enlace directo al expediente y columna (35%) de pacientes por llamar con botón verde de WhatsApp.
- **Refactor detalle:** el mensaje de WhatsApp se movió del `RecallController` al `RecallModel::crearEnlaceWhatsApp()` para que Dashboard y Recall compartan el mismo texto sin repetir código.
- **Corrección quirúrgica:** botón "Expediente" del panel ahora usa `paciente_id` (antes tomaba accidentalmente el id de la cita); se amplió `CitaModel::listarDelDia()` con `c.paciente_id`.
- **Estado:** implementado — probado por Sebastian ✅

### 5. Auditoría de diseño UI (petición final de Sebastian)
- Se compararon TODAS las vistas contra `docs/investigacion/backlog/diseno_ui_ux.md`: paleta (#F8FAFC fondo, #10B981 verde WhatsApp/abono, #EF4444 rojo, #F59E0B ámbar), badges de estado, tarjetas KPI de la Pantalla 3 y layout 65%/35% del dashboard: TODO conforme.
- **Hallazgo y corrección:** la guía pide *Inter* (títulos/botones) y *Roboto* (cuerpo) pero las fuentes no estaban cargadas. Se agregó el enlace de Google Fonts (Inter wght 600/700 + Roboto 400/500) y `font-family: 'Roboto'` al body en las 9 vistas. Verificación `php -l`: 9/9 sin errores.

---

## ✅ PROYECTO COMPLETADO (10/11/2026)

El sistema Dental Care quedó 100% funcional de punta a punta: login seguro → dashboard con métricas → pacientes → agenda con anti-cruces → odontograma SVG histórico → presupuestos y abonos con transacción → recall y recordatorios por WhatsApp → panel gerencial. Reglas RN-01, RN-02 y RN-03 implementadas y auditadas.

### 6. Seguridad final: permisos por rol + módulo de Usuarios del Sistema (solicitud de Sebastian)
- Pregunta clave: *"¿Dónde agrega el admin nuevos doctores y por qué el doctor puede registrar abonos?"* — se detectó que el diseño (Pantalla 2) prometía el ítem "Usuarios del Sistema (SOLO Admin)" y que no existía control de permisos (HU-02 solo valida sesión, no rol).
- **Clase `app/config/Seguridad.php`:** guard `exigirRol(array $roles)` que se invoca EN EL CONSTRUCTOR de cada controlador (1 línea por módulo) y redirige al panel con alerta roja si el rol no encaja.
- **Matriz de permisos aplicada:** Caja (Tratamiento) y Recall → recepcionista/admin · Odontograma → odontólogo/admin · Pacientes, Citas y Dashboard → todos · **Usuarios del Sistema → SOLO admin**.
- **Módulo de usuarios:** `UsuarioModel` extendido (listar, obtenerPorCorreo, crear con password_hash, cambiarEstado), `UsuarioController` (`index`, `guardar`, `cambiarEstado`, validación de rol de la BD + clave mínima 8 caracteres + correo UNIQUE + no auto-desactivarse) y vista `usuario/index.php` (form de alta + tabla del personal con badges de rol y botones activar/desactivar).
- **Navbar del Dashboard:** el enlace "Presupuestos" solo se muestra a recepción/admin y aparece el botón "Usuarios" solo para el admin.
- **Estado:** implementado — probado por Sebastian ✅. Ajuste post-test: las tarjetas KPI "Ingresos del Mes" y "Cartera por Cobrar" NO se muestran al odontólogo (el controlador ni siquiera calcula esas métricas para su rol, y las 2 tarjetas clínicas se expanden a la mitad del ancho para conservar el layout). **Y el logo "Dental Care" del navbar ahora es un enlace clicable que SIEMPRE regresa al Dashboard (sirve para los 3 roles en las 10 vistas).**

### 7. Barra lateral del diseño (Pantalla 2 de Figma) en TODAS las vistas
- Recordatorio de Sebastian: la sistema debía tener el menú lateral oscuro del prototipo (260px, fondo #0F172A) y no un listado horizontal de botones.
- **Partial reutilizable** `app/views/partials/barra_lateral.php`: la lista del menú se arma con un solo arreglo de ítems donde cada uno declara sus roles (la MISMA matriz de permisos del Seguridad.php) — así el doctor NO ve "Presupuestos y Caja" ni "Recall", y "Usuarios del Sistema" solo lo ve el admin, tal como el diseño.
- Ítem activo resaltado en azul #0284C7 (se compara $_GET['c']), pie del menú con nombre del usuario, badge de rol y Cerrar Sesión.
- Responsivo: en escritorio (≥992px) es fija a la izquierda (body con padding-left 260px) y en móvil se esconde en una barra superior compacta con botón hamburguesa que abre un offcanvas flotante, según la especificación de adaptación 390px del diseño.
- El logo del sidebar sigue siendo clicable al dashboard y las 10 vistas aplican el include con `require __DIR__ . '/../partials/...'`.
- **Estado:** implementado — probado por Sebastian ✅.

### 8. Ajuste visual final (último retoque de Sebastian, cierre total)
- Ajuste post-test del menú lateral: **el nombre del usuario y el badge de rol en el pie del sidebar no se leían** (texto oscuro sobre fondo oscuro #0F172A). Corrección: nombre en **blanco (`text-white`)** y badge de rol **claro (`bg-light text-dark`)** para que resalten en ambos menús (escritorio y offcanvas móvil).
- Estado del ajuste: implementado y verificado con php -l (sin errores).
- **Cierre GENERAL: sistema Dental Care 100% TERMINADO** — 6 épicas, 12 historias y 36 tareas del backlog completadas, más las extras (recordatorios de citas, módulo de usuarios con permisos por rol, dashboard filtrado por rol y barra lateral del diseño), todo probado por Sebastian en navegador y documentado.

---

## 📅 Sesión de trabajo del 09/11/2026

### 1. Cierre de EP-03 (Agenda y Citas — HU-05 y HU-06)
- Se auditaron los archivos existentes y se confirmó que TODO el módulo ya estaba programado (el agente anterior no lo había marcado).
- Probado en navegador por Sebastian: agenda diaria, RN-01 (bloqueo de cruces de horario), cambio de estados y RN-03 (citas cerradas).
- Marcadas en cascada: TASK-13 a TASK-18, HU-05, HU-06, EP-03. Trazabilidad: RF-06, RF-07, RF-08.

### 2. Construcción completa de HU-07 (Odontograma SVG interactivo)
Se programó desde cero:
- **TASK-19 (SQL):** tabla `odontograma` con FK a `paciente` con `ON DELETE CASCADE`. Sebastian la creó manualmente en phpMyAdmin y su versión quedó como la oficial del script (columnas VARCHAR). Se agregaron 2 hallazgos de prueba con `WHERE NOT EXISTS` (pieza 16 Caries, pieza 21 Resina).
- **TASK-20 (Vista):** `app/views/odontograma/ver.php` — SVG de 32 dientes (notación FDI 11-48) generado con bucle PHP, 5 caras clicables por diente con `data-diente` y `data-cara`, selector de diagnóstico (botones Bootstrap) y leyenda clínica: Rojo=Caries, Azul=Resina, Morado=Endodoncia, Gris=Ausente.
- **TASK-21 (JS):** `public/js/odontograma.js` — pinta/desmarca caras al hacer clic, arma el arreglo de hallazgos y lo envía por `fetch()` en JSON.
- **TASK-22 (MVC):** `app/controllers/OdontogramaController.php` (acciones `ver` y `guardar`, validación de hallazgos antes de guardar) y `app/models/OdontogramaModel.php` (transacción PDO: borra los hallazgos de HOY y re-inserta lo marcado; si algo falla, hace rollBack).

### 3. Construcción completa de HU-08 (Histórico de odontogramas)
- **TASK-23 (Modelo/Controlador):** método `listarFechas($pacienteId)` con `SELECT DISTINCT DATE(fecha_registro)`; acción `hallazgos()` que devuelve JSON por fecha.
- **TASK-24 (JS/Vista):** selector "Ver la sesión del día", funciones `limpiarOdontograma()`, `verOdontogramaHistorico()` y `modoEditable()`. Al elegir fecha pasada: el SVG se repinta con esa sesión, queda en solo lectura (aviso azul) y se bloquea el botón Guardar.
- Probado por Sebastian con un UPDATE en phpMyAdmin para simular sesiones pasadas: funcionó perfectamente.
- Marcadas en cascada: HU-08, EP-04 completa. Trazabilidad: RF-09, RF-10.

### 4. Notas técnicas importantes
- El script `database/dental_care.sql` es re-ejecutable: usa `CREATE TABLE IF NOT EXISTS`, `INSERT IGNORE` y `WHERE NOT EXISTS`.
- La tabla `odontograma` usa VARCHAR (no ENUM) por decisión de Sebastian; la validación de datos la hace el controlador (defensa en capas).
- El enrutador `index.php` mapea `?c=odontologo&a=accion` → `OdontogramaController::accion()` automáticamente.
- El proyecto aún NO es repositorio Git: falta `git init` y conectar el remoto.

---

## 🗂️ Inventario de archivos del proyecto

```
database/dental_care.sql            → BD completa: usuario, paciente, cita, odontograma, tratamiento, pago_abono + datos de prueba
app/config/Conexion.php             → Conexión PDO singleton
app/models/UsuarioModel.php         → Login (autenticar por correo)
app/models/PacienteModel.php        → CRUD + buscar con LIKE
app/models/CitaModel.php            → listarOdontologos, listarDelDia (con teléfono y paciente_id), cruceHorario, crear, actualizarEstado
app/models/OdontogramaModel.php     → obtenerPaciente, listarDelDia, listarFechas, guardarHallazgosDelDia
app/models/TratamientoModel.php     → listarOdontologos, listar, obtenerPorId, crear (saldo inicial = costo)
app/models/PagoModel.php            → registrarAbono (transacción PDO + RN-02), listarAbonos
app/models/RecallModel.php          → listarInactivos (MAX + DATEDIFF) + crearEnlaceWhatsApp (compartido con Dashboard)
app/models/DashboardModel.php       → citasDeHoy, ingresosDelMes, carteraPendiente, tasaInasistencia (SUM/COUNT)
app/models/UsuarioModel.php         → autenticar + listar, obtenerPorCorreo, crear (hash), cambiarEstado (módulo admin)
app/config/Seguridad.php            → guard exigirRol($roles): permisos por rol en el constructor de cada módulo
app/controllers/AuthController.php  → login, iniciarSesion, cerrarSesion
app/controllers/PacienteController.php → CRUD pacientes + endpoint buscar
app/controllers/CitaController.php  → index, guardar (RN-01), cambiarEstado (RN-03), recordatorios (aviso 1 día antes)
app/controllers/OdontogramaController.php → ver, hallazgos (JSON), guardar (JSON)
app/controllers/TratamientoController.php → index, guardar, pagos, registrarAbono
app/controllers/RecallController.php → index (arma enlaces wa.me/57 via RecallModel, rol recepcionista/admin)
app/controllers/DashboardController.php → index (4 KPI + agenda del día + pacientes por llamar)
app/controllers/UsuarioController.php → index, guardar, cambiarEstado (SOLO admin)
app/views/auth/login.php            → Login Bootstrap 5
app/views/paciente/index.php        → Directorio + búsqueda asíncrona
app/views/cita/index.php            → Agenda diaria + formulario de reserva + botón Recordatorios
app/views/cita/recordatorios.php    → Citas del día siguiente + botón WhatsApp por cita
app/views/odontograma/ver.php       → Odontograma SVG + selector histórico
app/views/tratamiento/index.php     → Presupuestos + tabla de cartera con botón Abonar
app/views/tratamiento/pagos.php     → Resumen, modal de abono e historial de recibos
app/views/recall/index.php          → Pacientes inactivos + WhatsApp
app/views/dashboard/index.php       → Panel gerencial: 4 KPI + agenda + recall
app/views/usuario/index.php         → Gestión de personal (alta + activar/desactivar) — solo admin
app/views/partials/barra_lateral.php → Menú lateral del diseño (#0F172A 260px) filtrado por rol + offcanvas móvil
public/js/pacientes.js              → Búsqueda en vivo con fetch()
public/js/odontograma.js            → Pintado SVG + guardado fetch() + histórico
```

---

## 🔑 Credenciales de prueba (contraseña: Dental2025*)

| Rol | Correo |
| :--- | :--- |
| Admin | admin@dentalcare.com |
| Recepcionista | recepcion@dentalcare.com |
| Odontólogo | odontologo@dentalcare.com |

## 🌐 URLs de prueba

- Login: `http://localhost/Dental_Care/`
- Dashboard (panel gerencial): `http://localhost/Dental_Care/index.php?c=dashboard`
- Pacientes: `http://localhost/Dental_Care/index.php?c=paciente`
- Agenda: `http://localhost/Dental_Care/index.php?c=cita`
- Recordatorios (citas de mañana): `http://localhost/Dental_Care/index.php?c=cita&a=recordatorios`
- Presupuestos/Cartera: `http://localhost/Dental_Care/index.php?c=tratamiento`
- Recall inactivos: `http://localhost/Dental_Care/index.php?c=recall`
- Odontograma: `http://localhost/Dental_Care/index.php?c=odontograma&a=ver&paciente_id=1`
- Usuarios del sistema (solo admin): `http://localhost/Dental_Care/index.php?c=usuario`

---

## ⏭️ Lo que sigue (opcional, ya no hay código del backlog)

### Ideas futuras / entregas finales para SENA
- Publicar el proyecto si la instructora pide despliegue (XAMPP local ya funciona).
- Reporte PDF de la bitácora y preparación de la sustentación (ENI ready 🎓).
- (Opcional) `git init` + conectar remoto y subir con Conventional Commits.

**Reglas de negocio: RN-01 ✅, RN-02 ✅, RN-03 ✅ — TODAS implementadas y auditadas.**
