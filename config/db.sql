DROP DATABASE IF EXISTS directorioTelMTC;
CREATE DATABASE IF NOT EXISTS directorioTelMTC 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE directorioTelMTC;

/* Tabla - usuarios */
CREATE TABLE usuario (
  idUsuario INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del usuario',
  nombre VARCHAR(50) NOT NULL COMMENT 'Nombre del usuario',
  email VARCHAR(100) NOT NULL UNIQUE COMMENT 'Correo electronico del usuario',
  contrasenia VARCHAR(255) NOT NULL COMMENT 'Hash de la contraseña',
  tipoUsuario ENUM('admin','editor','consultor') NOT NULL DEFAULT 'consultor' COMMENT 'Rol del usuario',
  fechaRegistro TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de registro',

  PRIMARY KEY (idUsuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - recuperarContrasenia */
CREATE TABLE recuperarPassword (
  idRecuperarP INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del reset',
  token_hash VARCHAR(255) NOT NULL COMMENT 'Hash del token',
  expira_at DATETIME NOT NULL COMMENT 'Caducidad del enlace',
  used TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=vigente,1=usado',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  usuarioId INT NOT NULL COMMENT 'FK a idUsuario',
  PRIMARY KEY (idRecuperarP),
  CONSTRAINT fk_recuperar_password_usuario
  FOREIGN KEY (usuarioId) REFERENCES usuario(idUsuario)
  ON DELETE CASCADE
  ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - areas */
CREATE TABLE area (
  idArea INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del area',
  nombre VARCHAR(50) NOT NULL COMMENT 'Nombre del area',
  email VARCHAR(100) NULL COMMENT 'Correo electronico de el area',
  descripcion VARCHAR(255) NULL COMMENT 'Descripcion del area',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  PRIMARY KEY (idArea)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - puestos */
CREATE TABLE puesto (
  idPuesto INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del puesto',
  nombre VARCHAR(50) NOT NULL COMMENT 'Nombre del puesto',
  descripcion VARCHAR(100) NOT NULL COMMENT 'Descripcion breve del puesto',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  areaId INT NOT NULL COMMENT 'FK a idArea',
  PRIMARY KEY (idPuesto),
  CONSTRAINT fk_puesto_area
  FOREIGN KEY (areaId) REFERENCES area(idArea) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - extension  */
CREATE TABLE extension (
  idExtension INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico de la extension',
  numero VARCHAR(3) NOT NULL COMMENT 'Numero de la extension',
  descripcion VARCHAR(100) NOT NULL COMMENT 'Descripcion breve de la extension',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  PRIMARY KEY (idExtension)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - propietario */
CREATE TABLE propietario (
  idPropietario INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del propietario',
  nombre VARCHAR(50) NOT NULL COMMENT 'Nombre(s) del propietario',
  email VARCHAR(150) NULL COMMENT 'Correo(s) electronico(s) del propietario',
  apellidoP VARCHAR(50) NOT NULL COMMENT 'Apellido paterno del propietario',
  apellidoM VARCHAR(50) NOT NULL COMMENT 'Apellido materno del propietario',
  fechaAlta TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de alta',

  puestoId INT NOT NULL COMMENT 'FK a idPuesto',
  extensionId INT NULL COMMENT 'FK a idExtension',
  PRIMARY KEY (idPropietario),
  CONSTRAINT fk_prop_puesto    FOREIGN KEY (puestoId)   REFERENCES puesto(idPuesto)       ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_prop_extension FOREIGN KEY (extensionId)REFERENCES extension(idExtension) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - correoArea varios correos por área */
CREATE TABLE correoArea (
  idCorreoArea INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del correo de area',
  areaId INT NOT NULL COMMENT 'FK a idArea',
  correo VARCHAR(100) NOT NULL COMMENT 'Correo adicional del area',
  creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  PRIMARY KEY (idCorreoArea),
  CONSTRAINT fk_correo_area
  FOREIGN KEY (areaId) REFERENCES area(idArea) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - correoPropietario varios correos por persona */
CREATE TABLE correoPropietario (
  idCorreoPropietario INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del correo del propietario',
  propietarioId INT NOT NULL COMMENT 'FK a idPropietario',
  correo VARCHAR(100) NOT NULL COMMENT 'Correo adicional del propietario',
  creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  PRIMARY KEY (idCorreoPropietario),
  CONSTRAINT fk_correo_propietario
  FOREIGN KEY (propietarioId) REFERENCES propietario(idPropietario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - horario múltiples por persona */
CREATE TABLE horario (
  idHorario INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del horario',
  dia_semana TINYINT NULL COMMENT '1=Lun ... 7=Dom; NULL=general',
  horaEntrada TIME NOT NULL COMMENT 'Hora de entrada',
  horaSalida  TIME NOT NULL COMMENT 'Hora de salida',
  creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  propietarioId INT NOT NULL COMMENT 'FK a idPropietario',
  PRIMARY KEY (idHorario),
  CONSTRAINT fk_horario_propietario
  FOREIGN KEY (propietarioId) REFERENCES propietario(idPropietario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - notas */
CREATE TABLE nota (
  idNota INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico de la nota',
  texto TEXT NOT NULL COMMENT 'Contenido de la nota',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  propietarioId INT NOT NULL COMMENT 'FK a idPropietario',
  usuarioId INT NOT NULL COMMENT 'FK a idUsuario (autor)',
  PRIMARY KEY (idNota),
  CONSTRAINT fk_nota_propietario
  FOREIGN KEY (propietarioId) REFERENCES propietario(idPropietario) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_nota_usuario
  FOREIGN KEY (usuarioId) REFERENCES usuario(idUsuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - solicitudDeCambios */
CREATE TABLE solicitudCambio (
  idSolicitudCambio INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico de la solicitud de cambio',
  campo VARCHAR(64) NOT NULL COMMENT 'Campo a modificar',
  valor_anterior TEXT NULL COMMENT 'Valor anterior',
  valor_nuevo TEXT NOT NULL COMMENT 'Valor nuevo solicitado',
  comentario TEXT NOT NULL COMMENT 'Comentario del solicitante',
  estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente' COMMENT 'Estado de la solicitud',
  motivo_revision TEXT NULL COMMENT 'Motivo de aprobacion/rechazo',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  propietarioId INT NOT NULL COMMENT 'FK a idPropietario',
  usuarioSolicitanteId INT NOT NULL COMMENT 'FK a idUsuario',
  PRIMARY KEY (idSolicitudCambio),
  CONSTRAINT fk_sc_propietario
  FOREIGN KEY (propietarioId) REFERENCES propietario(idPropietario) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_sc_solicitante
  FOREIGN KEY (usuarioSolicitanteId) REFERENCES usuario(idUsuario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


/* Tabla - favoritos */
CREATE TABLE favoritoPropietario (
  idFavoritoPropietario INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del favorito',
  creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  usuarioId INT NOT NULL COMMENT 'FK a idUsuario',
  propietarioId INT NOT NULL COMMENT 'FK a idPropietario',
  PRIMARY KEY (idFavoritoPropietario),
  CONSTRAINT fk_fav_usuario
  FOREIGN KEY (usuarioId) REFERENCES usuario(idUsuario) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_fav_propietario
  FOREIGN KEY (propietarioId) REFERENCES propietario(idPropietario) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - notificaciones (evento) */
CREATE TABLE notificacion (
  idNotificacion INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico de la notificacion',
  tipo ENUM('nuevo_contacto','eliminacion_contacto','modificacion_contacto') NOT NULL COMMENT 'Tipo de acción',
  titulo VARCHAR(150) NOT NULL COMMENT 'Titulo',
  mensaje TEXT NOT NULL COMMENT 'Mensaje',
  creadoPor INT NULL COMMENT 'FK a idUsuario (emisor)',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  PRIMARY KEY (idNotificacion),
  CONSTRAINT fk_notif_creador
  FOREIGN KEY (creadoPor) REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - bitacora */
CREATE TABLE bitacorasistema (
  idBitacora INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico de bitacora',
  accion ENUM('inicio_sesion_ok','inicio_sesion_fallido','cierre_sesion','registro',
  'creacion','actualizacion','eliminacion','respaldo','restauracion') NOT NULL COMMENT 'Accion',
  modulo VARCHAR(50) NULL COMMENT 'Modulo aplicable',
  tabla_bd VARCHAR(50) NULL COMMENT 'Tabla afectada',
  pk_valor VARCHAR(50) NULL COMMENT 'PK afectada',
  descripcion TEXT NULL COMMENT 'Descripcion del evento',
  usuarioNombre VARCHAR(50) NULL COMMENT 'Snapshot nombre del actor',
  usuarioEmail  VARCHAR(50) NULL COMMENT 'Snapshot email del actor',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  usuarioId INT NULL COMMENT 'FK a idUsuario (actor)',
  PRIMARY KEY (idBitacora),
  CONSTRAINT fk_bitacora_usuario
  FOREIGN KEY (usuarioId) REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - consultaDeExtension */
CREATE TABLE consultaExtension (
  idConsultaExtension INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico de la consulta',
  creada_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  extensionId INT NOT NULL COMMENT 'FK a idExtension',
  usuarioId INT NULL COMMENT 'FK a idUsuario (quien consulto)',
  PRIMARY KEY (idConsultaExtension),
  CONSTRAINT fk_ce_extension
  FOREIGN KEY (extensionId) REFERENCES extension(idExtension) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ce_usuario
  FOREIGN KEY (usuarioId) REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* Tabla - respaldoDeBD */
CREATE TABLE respaldoBD (
  idRespaldoDB INT NOT NULL AUTO_INCREMENT COMMENT 'ID unico del respaldo',
  archivo VARCHAR(255) NOT NULL COMMENT 'Nombre/ubicacion del archivo SQL',
  tamano_bytes BIGINT NULL COMMENT 'Tamano del archivo',
  realizadoPor_nombre VARCHAR(50) NULL COMMENT 'Snapshot nombre',
  realizadoPor_email  VARCHAR(50) NULL COMMENT 'Snapshot email',
  creado_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de creacion',

  realizadoPor INT NULL COMMENT 'FK a idUsuario',
  PRIMARY KEY (idRespaldoDB),
  CONSTRAINT fk_respaldo_usuario
  FOREIGN KEY (realizadoPor) REFERENCES usuario(idUsuario) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* TRIGGERS */
DELIMITER //
/* Bitácora antes de insertar, copiar nombre/email del usuario a los snapshots */
CREATE TRIGGER trg_bitacora_bi
BEFORE INSERT ON bitacorasistema
FOR EACH ROW
BEGIN
  DECLARE v_nombre VARCHAR(50);
  DECLARE v_email  VARCHAR(50);
  IF NEW.usuarioId IS NOT NULL THEN
    SELECT u.nombre, u.email
      INTO v_nombre, v_email
    FROM usuario u
    WHERE u.idUsuario = NEW.usuarioId
    LIMIT 1;
    SET NEW.usuarioNombre = v_nombre;
    SET NEW.usuarioEmail  = v_email;
  END IF;
END//

/* Respaldos: antes de insertar, copiar nombre/email del autor a los snapshots */
CREATE TRIGGER trg_respaldo_bi
BEFORE INSERT ON respaldoBD
FOR EACH ROW
BEGIN
  DECLARE v_nombre VARCHAR(50);
  DECLARE v_email  VARCHAR(50);
  IF NEW.realizadoPor IS NOT NULL THEN
    SELECT u.nombre, u.email
      INTO v_nombre, v_email
    FROM usuario u
    WHERE u.idUsuario = NEW.realizadoPor
    LIMIT 1;
    SET NEW.realizadoPor_nombre = v_nombre;
    SET NEW.realizadoPor_email  = v_email;
  END IF;
END//
DELIMITER ;

INSERT INTO usuario (nombre, email, contrasenia, tipoUsuario) VALUES
('Admin de prueba','admin@mtcenter.com.mx','$2y$10$qJwFCVMQsENgEYluFsaspupzumucBoQWDlvMflCsFpQrxj.oWfMGK','admin'),
('Editor de prueba','editor@mtcenter.com.mx','$2y$10$etr4Ttsn8glruHUNxtW8ZecB10fsi15cVuGngk2i2gUYNdKw197.W','editor'),
('Consultor de prueba', 'consultor@mtcenter.com.mx', '$2y$10$etr4Ttsn8glruHUNxtW8ZecB10fsi15cVuGngk2i2gUYNdKw197.W', 'consultor');

/* INSERCIÓN DE DATOS DE EJEMPLO */
-- 2. Areas
INSERT INTO `area` (`idArea`, `nombre`, `email`, `descripcion`) VALUES
(1, 'Dirección General de TI', 'dti@mtcenter.com.mx', 'Área de Tecnologías de la Información'),
(2, 'Recursos Humanos', 'rrhh@mtcenter.com.mx', 'Gestión de personal'),
(3, 'Administración y Finanzas', 'admin@mtcenter.com.mx', 'Gestión de recursos financieros'),
(4, 'Operaciones y Mantenimiento', 'operaciones@mtcenter.com.mx', 'Operaciones de campo y soporte'),
(5, 'Asesoría Jurídica', 'legal@mtcenter.com.mx', 'Asuntos legales y regulatorios'),
(6, 'Comunicaciones', 'comms@mtcenter.com.mx', 'Prensa y comunicación interna');

-- 3. Extensiones
INSERT INTO `extension` (`idExtension`, `numero`, `descripcion`) VALUES
(1, '100', 'Recepción Principal'),
(2, '102', 'Soporte Técnico N1'),
(3, '103', 'Oficina Director General TI'),
(4, '104', 'Sala de Juntas A'),
(5, '105', 'Gerencia de Finanzas'),
(6, '106', 'Soporte Técnico N2'),
(7, '107', 'Oficina Gerente RRHH');

-- 4. Puestos
INSERT INTO `puesto` (`idPuesto`, `nombre`, `descripcion`, `areaId`) VALUES
(1, 'Director de TI', 'Encargado del área de TI', 1),
(2, 'Analista de Soporte Técnico', 'Soporte técnico a usuarios', 1),
(3, 'Gerente de RH', 'Encargado de Recursos Humanos', 2),
(4, 'Contador General', 'Contabilidad y finanzas', 3),
(5, 'Abogado Senior', 'Asesoría legal', 5),
(6, 'Asistente Administrativo', 'Apoyo a Gerencia de Finanzas', 3),
(7, 'Especialista en Redes', 'Administración de red', 1);

-- 5. Propietarios
INSERT INTO `propietario` (`idPropietario`, `nombre`, `email`, `apellidoP`, `apellidoM`, `puestoId`, `extensionId`) VALUES
(1, 'Juan Carlos', 'juan.perez@mtcenter.com.mx', 'Pérez', 'López', 1, 3),
(2, 'Maria Elena', 'maria.garcia@mtcenter.com.mx', 'García', 'Martinez', 2, 2),
(3, 'Carlos Alberto', 'carlos.rodriguez@mtcenter.com.mx', 'Rodríguez', 'Sánchez', 3, 7),
(4, 'Ana Sofia', 'ana.hernandez@mtcenter.com.mx', 'Hernández', 'Gómez', 4, 5),
(5, 'Luis Fernando', 'luis.martinez@mtcenter.com.mx', 'Martínez', 'Díaz', 5, NULL),
(6, 'Sofia Isabel', 'sofia.chavez@mtcenter.com.mx', 'Chávez', 'Ramírez', 2, 6),
(7, 'Miguel Angel', 'miguel.torres@mtcenter.com.mx', 'Torres', 'Vargas', 7, 2);

-- 7. Notas
INSERT INTO `nota` (`idNota`, `texto`, `propietarioId`, `usuarioId`) VALUES
(1, 'Contactar para reunión de presupuesto el viernes.', 4, 1), -- Nota sobre Ana (Contador) por Admin
(2, 'Experto en configuración de VPN Fortinet.', 7, 2), -- Nota sobre Miguel (Redes) por Editor
(3, 'Usuario reportó lentitud en su equipo (Ticket #12345), se revisará mañana.', 2, 1), -- Nota sobre Maria (Soporte) por Admin
(4, 'Preguntar sobre las nuevas políticas de vacaciones.', 3, 3), -- Nota sobre Carlos (RH) por Consultor
(5, 'Revisar contrato de proveedor de enlaces (Proveedor X).', 5, 1), -- Nota sobre Luis (Legal) por Admin
(6, 'Asignada al ticket #54321, pendiente de monitor.', 6, 2); -- Nota sobre Sofia (Soporte) por Editor


