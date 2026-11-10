-- Creamos la base de datos si no existe y la seleccionamos.
CREATE DATABASE IF NOT EXISTS dental_care
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE dental_care;

CREATE TABLE IF NOT EXISTS usuario (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    nombre        VARCHAR(80)  NOT NULL,
    correo        VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    -- Rol define los permisos de acceso según el perfil.
    rol  ENUM('recepcionista', 'odontologo', 'admin') NOT NULL,
    -- Estado permite desactivar un usuario sin borrarlo.
    estado    ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo'
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_spanish_ci;


INSERT IGNORE INTO usuario (nombre, correo, password_hash, rol, estado) VALUES
('Sebastian Vega',  'admin@dentalcare.com',      '$2y$10$WM1qSi67xMqtrnVWmiuc4e0pMLgqqGgvIQZJQS2RZWmEKs/6YQUJe', 'admin',         'activo'),
('Laura Restrepo',  'recepcion@dentalcare.com',  '$2y$10$WM1qSi67xMqtrnVWmiuc4e0pMLgqqGgvIQZJQS2RZWmEKs/6YQUJe', 'recepcionista', 'activo'),
('Carlos González', 'odontologo@dentalcare.com', '$2y$10$WM1qSi67xMqtrnVWmiuc4e0pMLgqqGgvIQZJQS2RZWmEKs/6YQUJe', 'odontologo',    'activo');


CREATE TABLE IF NOT EXISTS paciente (
    id                 INT AUTO_INCREMENT PRIMARY KEY,
    numero_documento   VARCHAR(20)  NOT NULL,
    nombres            VARCHAR(60)  NOT NULL,
    apellidos          VARCHAR(60)  NOT NULL,
    telefono           VARCHAR(15)  NOT NULL,
    correo             VARCHAR(100) NULL,
    -- Fecha de nacimiento para calcular la edad en la vista.
    fecha_nacimiento   DATE  NOT NULL,
    -- Antecedentes médicos: alergias, enfermedades, etc.
    antecedentes_medicos TEXT NOT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    -- Asegura que no puedan registrarse dos pacientes con el mismo número de documento de identidad
    UNIQUE KEY uq_documento (numero_documento)

    -- engine == innodb es un motor muy potente que tien msql para que pueda leer los datos de forma segura y pueda soportar las conecciones foraneas

) ENGINE = InnoDB

-- colocamos en cada tabla por buenas placticas el default chatset para que cuando reciva los datos de la tabla 
-- pueda entender las ñ las i las tildes y collate es para que entienda el idioma español y _ci es para 
-- que cuando tenemos nombres como Maria y maria que es lo msismo los trate como duplicados 

  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_spanish_ci;

INSERT IGNORE INTO paciente (numero_documento, nombres, apellidos, telefono, correo, fecha_nacimiento, antecedentes_medicos) VALUES
('1020304051', 'Andrés Felipe', 'Castro Muñoz',  '3102223344', 'andres.castro@test.com', '1995-04-12', 'Alérgico a la penicilina.'),
('1098765432', 'María Camila',  'Torres Ortega', '3115558877', 'maria.torres@test.com',  '2001-09-25', 'Ninguno. Hipertensión controlada.');


CREATE TABLE IF NOT EXISTS cita (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id     INT NOT NULL,
    -- Odontólogo: apunta a la tabla usuario (rol odontologo).
    odontologo_id   INT NOT NULL,
    fecha_cita      DATE NOT NULL,
    hora_inicio     TIME NOT NULL,
    hora_fin        TIME NOT NULL,
    motivo          VARCHAR(200) NOT NULL,
    
    estado   ENUM('Pendiente','Confirmada','Atendida','Cancelada','Inasistencia')
    -- cunado vamos a registrar una nueva cita por defecto conocamos pendiente
      NOT NULL DEFAULT 'Pendiente',
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paciente_id)   REFERENCES paciente(id),
    FOREIGN KEY (odontologo_id) REFERENCES usuario(id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_spanish_ci;


INSERT INTO cita (paciente_id, odontologo_id, fecha_cita, hora_inicio, hora_fin, motivo, estado)
SELECT (SELECT id FROM paciente  WHERE numero_documento = '1020304051' LIMIT 1),
       (SELECT id FROM usuario   WHERE rol = 'odontologo' LIMIT 1),
       CURDATE(), '08:00:00', '08:30:00', 'Valoración inicial y limpieza.', 'Pendiente'
WHERE NOT EXISTS (SELECT 1 FROM cita WHERE fecha_cita = CURDATE() AND hora_inicio = '08:00:00');

INSERT INTO cita (paciente_id, odontologo_id, fecha_cita, hora_inicio, hora_fin, motivo, estado)
SELECT (SELECT id FROM paciente  WHERE numero_documento = '1098765432' LIMIT 1),
       (SELECT id FROM usuario   WHERE rol = 'odontologo' LIMIT 1),
       -- curdate() lo usamos para que cuando seleccionamos una fecha no la tengamos que volver a editar manualmente 
       CURDATE(), '10:00:00', '10:45:00', 'Control de ortodoncia.', 'Confirmada'
WHERE NOT EXISTS (SELECT 1 FROM cita WHERE fecha_cita = CURDATE() AND hora_inicio = '10:00:00');

-- =============================================================
-- NUEVA TABLA: ODONTOGRAMA
-- Almacena los hallazgos clínicos (marcas en el SVG) de cada paciente
-- =============================================================
CREATE TABLE IF NOT EXISTS odontograma (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id     INT NOT NULL,
    diente          VARCHAR(10) NOT NULL,
    cara            VARCHAR(10) NOT NULL,
    diagnostico     VARCHAR(50) NOT NULL,
    observacion     TEXT,
    fecha_registro  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Llave foránea para vincular el hallazgo con el paciente correspondiente
    FOREIGN KEY (paciente_id) REFERENCES paciente(id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_spanish_ci;

-- =============================================================
-- Datos de prueba: 2 hallazgos del primer paciente (CP-09).
-- Con WHERE NOT EXISTS no se duplican si re-ejecutas el script.
-- =============================================================
INSERT INTO odontograma (paciente_id, diente, cara, diagnostico, observacion)
SELECT (SELECT id FROM paciente WHERE numero_documento = '1020304051' LIMIT 1),
       '16', 'Oclusal', 'Caries', 'Caries profunda en la pieza 16.'
WHERE NOT EXISTS (SELECT 1 FROM odontograma
                  WHERE paciente_id = (SELECT id FROM paciente WHERE numero_documento = '1020304051' LIMIT 1)
                    AND diente = '16' AND cara = 'Oclusal');

INSERT INTO odontograma (paciente_id, diente, cara, diagnostico, observacion)
SELECT (SELECT id FROM paciente WHERE numero_documento = '1020304051' LIMIT 1),
       '21', 'Vestibular', 'Resina', 'Resina estética en el incisivo superior.'
WHERE NOT EXISTS (SELECT 1 FROM odontograma
                  WHERE paciente_id = (SELECT id FROM paciente WHERE numero_documento = '1020304051' LIMIT 1)
                    AND diente = '21' AND cara = 'Vestibular');

-- =============================================================
-- NUEVA TABLA: TRATAMIENTO (TAREA-25 / HU-09)
-- Guarda los presupuestos: cada plan de tratamiento con su costo
-- total y el saldo pendiente por cobrar al paciente.
-- =============================================================
CREATE TABLE IF NOT EXISTS tratamiento (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    paciente_id     INT NOT NULL,
    -- Odontólogo que elaboró el presupuesto (apunta a la tabla usuario).
    odontologo_id   INT NOT NULL,
    descripcion     VARCHAR(200) NOT NULL,
    -- DECIMAL(10,2) guarda dinero ($999.999,99) sin errores de redondeo.
    costo_total     DECIMAL(10, 2) NOT NULL,
    -- RN-02: el saldo pendiente arranca igual al costo total.
    saldo_pendiente DECIMAL(10, 2) NOT NULL,
    -- Estado inicial 'Activo'; al pagar todo pasa a 'Pagado' (HU-10).
    estado          ENUM('Activo', 'Pagado') NOT NULL DEFAULT 'Activo',
    fecha_creacion  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (paciente_id)   REFERENCES paciente(id),
    FOREIGN KEY (odontologo_id) REFERENCES usuario(id)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_spanish_ci;

-- =============================================================
-- Datos de prueba (CP-11): un presupuesto ya guardado con el
-- saldo inicial exactamente igual al costo total.
-- Con WHERE NOT EXISTS no se duplica si re-ejecutas el script.
-- =============================================================
INSERT INTO tratamiento (paciente_id, odontologo_id, descripcion, costo_total, saldo_pendiente, estado)
SELECT (SELECT id FROM paciente  WHERE numero_documento = '1020304051' LIMIT 1),
       (SELECT id FROM usuario   WHERE rol = 'odontologo' LIMIT 1),
       'Endodoncia pieza 36 + corona metal-porcelana.',
       950000.00, 950000.00, 'Activo'
WHERE NOT EXISTS (SELECT 1 FROM tratamiento
                  WHERE descripcion = 'Endodoncia pieza 36 + corona metal-porcelana.');

-- =============================================================
-- NUEVA TABLA: PAGO_ABONO (TAREA-28 / HU-10)
-- Almacena cada abono (pago parcial) que hace el paciente sobre
-- su presupuesto. Varios abonos van drenando el saldo pendiente.
-- =============================================================
CREATE TABLE IF NOT EXISTS pago_abono (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tratamiento_id  INT NOT NULL,
    -- DECIMAL(10,2): dinero exacto, sin errores de redondeo.
    monto           DECIMAL(10, 2) NOT NULL,
    -- Cómo pagó: caja registrando desde recepción.
    metodo_pago     ENUM('Efectivo', 'Transferencia', 'Tarjeta') NOT NULL,
    observacion     VARCHAR(200),
    fecha_pago      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Si se borra el tratamiento, se borra su historial de pagos.
    FOREIGN KEY (tratamiento_id) REFERENCES tratamiento(id) ON DELETE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_spanish_ci;

-- =============================================================
-- Datos de prueba (CP-12): un abono de $500.000 sobre el
-- presupuesto de $950.000 deja el saldo en $450.000 (diseño UI).
-- Con WHERE NOT EXISTS no se duplica si re-ejecutas el script.
-- =============================================================
INSERT INTO pago_abono (tratamiento_id, monto, metodo_pago, observacion)
SELECT (SELECT id FROM tratamiento
        WHERE descripcion = 'Endodoncia pieza 36 + corona metal-porcelana.' LIMIT 1),
       500000.00, 'Efectivo', 'Abono inicial de la cuota.'
WHERE NOT EXISTS (SELECT 1 FROM pago_abono
                  WHERE tratamiento_id = (SELECT id FROM tratamiento
                     WHERE descripcion = 'Endodoncia pieza 36 + corona metal-porcelana.' LIMIT 1)
                     AND monto = 500000.00);

-- Actualizamos el saldo en la tabla tratamiento para reflejar
-- el abono anterior y dejar el saldo en $450.000 de prueba (RN-02).
UPDATE tratamiento
   SET saldo_pendiente = saldo_pendiente - 500000.00
 WHERE descripcion = 'Endodoncia pieza 36 + corona metal-porcelana.'
   AND saldo_pendiente = 950000.00;
