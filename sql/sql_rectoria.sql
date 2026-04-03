DROP DATABASE IF EXISTS rectoria_db;
CREATE DATABASE rectoria_db;
USE rectoria_db;

DROP TABLE IF EXISTS estado_ticket;
DROP TABLE IF EXISTS ticket;
DROP TABLE IF EXISTS empleado;
DROP TABLE IF EXISTS cliente;
DROP TABLE IF EXISTS sede;
DROP TABLE IF EXISTS gerente;

CREATE TABLE gerente (
    id_gerente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    hash_contrasena VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT 'default.png' 
);

CREATE TABLE sede (
    id_sede INT AUTO_INCREMENT PRIMARY KEY,
    nombre_sede VARCHAR(100) NOT NULL,
    localizacion TEXT,
    id_gerente INT,
    CONSTRAINT fk_sede_gerente FOREIGN KEY (id_gerente) REFERENCES gerente(id_gerente)
);

CREATE TABLE cliente (
    id_cliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    numero_telefono VARCHAR(20),
    id_sede INT NOT NULL,
    hash_contrasena VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT 'default.png', 
    CONSTRAINT fk_cliente_sede FOREIGN KEY (id_sede) REFERENCES sede(id_sede)
);

CREATE TABLE empleado (
    id_empleado INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(100) UNIQUE NOT NULL,
    id_sede INT NOT NULL,
    id_gerente INT,
    hash_contrasena VARCHAR(255) NOT NULL,
    foto_perfil VARCHAR(255) DEFAULT 'default.png', 
    CONSTRAINT fk_empleado_sede FOREIGN KEY (id_sede) REFERENCES sede(id_sede),
    CONSTRAINT fk_empleado_gerente FOREIGN KEY (id_gerente) REFERENCES gerente(id_gerente)
);

CREATE TABLE ticket (
    id_ticket INT AUTO_INCREMENT PRIMARY KEY,
    id_cliente INT NOT NULL,
    id_trabajador INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado VARCHAR(50) DEFAULT 'Abierto',
    CONSTRAINT fk_ticket_cliente FOREIGN KEY (id_cliente) REFERENCES cliente(id_cliente),
    CONSTRAINT fk_ticket_empleado FOREIGN KEY (id_trabajador) REFERENCES empleado(id_empleado)
);

CREATE TABLE estado_ticket (
    id_estado_ticket INT AUTO_INCREMENT PRIMARY KEY,
    id_ticket INT NOT NULL,
    fecha_estado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    nota TEXT,
    CONSTRAINT fk_historial_ticket FOREIGN KEY (id_ticket) REFERENCES ticket(id_ticket)
);

DELIMITER $$

CREATE TRIGGER trg_security_cliente_insert BEFORE INSERT ON cliente FOR EACH ROW SET NEW.hash_contrasena = SHA2(NEW.hash_contrasena, 256);$$
CREATE TRIGGER trg_security_cliente_update BEFORE UPDATE ON cliente FOR EACH ROW IF NEW.hash_contrasena <> OLD.hash_contrasena THEN SET NEW.hash_contrasena = SHA2(NEW.hash_contrasena, 256); END IF;$$

CREATE TRIGGER trg_security_gerente_insert BEFORE INSERT ON gerente FOR EACH ROW SET NEW.hash_contrasena = SHA2(NEW.hash_contrasena, 256);$$
CREATE TRIGGER trg_security_gerente_update BEFORE UPDATE ON gerente FOR EACH ROW IF NEW.hash_contrasena <> OLD.hash_contrasena THEN SET NEW.hash_contrasena = SHA2(NEW.hash_contrasena, 256); END IF;$$

CREATE TRIGGER trg_security_empleado_insert BEFORE INSERT ON empleado FOR EACH ROW SET NEW.hash_contrasena = SHA2(NEW.hash_contrasena, 256);$$
CREATE TRIGGER trg_security_empleado_update BEFORE UPDATE ON empleado FOR EACH ROW IF NEW.hash_contrasena <> OLD.hash_contrasena THEN SET NEW.hash_contrasena = SHA2(NEW.hash_contrasena, 256); END IF;$$

CREATE TRIGGER trg_inicio_ticket AFTER INSERT ON ticket FOR EACH ROW INSERT INTO estado_ticket (id_ticket, fecha_estado, nota) VALUES (NEW.id_ticket, NOW(), 'Creacion de ticket');$$

CREATE TRIGGER trg_validar_gerente_sede_insert BEFORE INSERT ON sede FOR EACH ROW IF EXISTS (SELECT 1 FROM sede WHERE id_gerente = NEW.id_gerente) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: Este gerente ya está asignado a otra sede.'; END IF;$$
CREATE TRIGGER trg_validar_gerente_sede_update BEFORE UPDATE ON sede FOR EACH ROW IF EXISTS (SELECT 1 FROM sede WHERE id_gerente = NEW.id_gerente AND id_sede <> NEW.id_sede) THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error: Este gerente ya está asignado a otra sede.'; END IF;$$

CREATE TRIGGER trg_validar_ticket_misma_sede BEFORE UPDATE ON ticket
FOR EACH ROW
BEGIN
    DECLARE v_sede_cliente INT;
    DECLARE v_sede_empleado INT;

    IF NEW.id_trabajador IS NOT NULL THEN
        SELECT id_sede INTO v_sede_cliente FROM cliente WHERE id_cliente = NEW.id_cliente;
        SELECT id_sede INTO v_sede_empleado FROM empleado WHERE id_empleado = NEW.id_trabajador;

        IF v_sede_cliente <> v_sede_empleado THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Error de Lógica: No se puede asignar un ticket a un empleado de una sede diferente a la del cliente.';
        END IF;
    END IF;
END$$

CREATE PROCEDURE sp_validar_login(IN _correo VARCHAR(100), IN _password_plana VARCHAR(255))
BEGIN
    SELECT g.id_gerente AS id_usuario, g.nombre AS nombre_usuario, 'Gerente' AS rol_usuario, s.id_sede, g.foto_perfil
    FROM gerente g
    LEFT JOIN sede s ON g.id_gerente = s.id_gerente
    WHERE g.correo = _correo AND g.hash_contrasena = SHA2(_password_plana, 256)
    
    UNION ALL
    
    SELECT id_empleado, nombre, 'Empleado', id_sede, foto_perfil
    FROM empleado
    WHERE correo = _correo AND hash_contrasena = SHA2(_password_plana, 256)
    
    UNION ALL
    
    SELECT id_cliente, nombre, 'Cliente', id_sede, foto_perfil
    FROM cliente
    WHERE correo = _correo AND hash_contrasena = SHA2(_password_plana, 256);
END$$

CREATE PROCEDURE sp_ver_historial_ticket(IN _id_ticket INT)
BEGIN
    SELECT 
        h.fecha_estado, 
        h.nota, 
        t.id_ticket,
        t.estado AS estado_actual,
        c.nombre AS nombre_cliente,
        COALESCE(e.nombre, 'Sin Asignar') AS nombre_empleado
    FROM estado_ticket h
    JOIN ticket t ON h.id_ticket = t.id_ticket
    JOIN cliente c ON t.id_cliente = c.id_cliente
    LEFT JOIN empleado e ON t.id_trabajador = e.id_empleado
    WHERE h.id_ticket = _id_ticket
    ORDER BY h.fecha_estado DESC;
END$$

CREATE PROCEDURE sp_gerente_ver_equipo(IN _id_gerente INT)
BEGIN
    SELECT 
        e.id_empleado,
        e.nombre,
        e.correo,
        s.nombre_sede,
        e.foto_perfil
    FROM empleado e
    JOIN sede s ON e.id_sede = s.id_sede
    WHERE e.id_gerente = _id_gerente;
END$$

CREATE PROCEDURE sp_ver_mis_tickets(IN _id_cliente INT)
BEGIN
    SELECT t.id_ticket, t.fecha_creacion, t.estado, COALESCE(e.nombre, 'Sin asignar') as nombre_trabajador
    FROM ticket t LEFT JOIN empleado e ON t.id_trabajador = e.id_empleado WHERE t.id_cliente = _id_cliente;
END$$

CREATE PROCEDURE sp_dashboard_empleado(IN _id_empleado INT)
BEGIN
    DECLARE v_mi_sede INT;
    SELECT id_sede INTO v_mi_sede FROM empleado WHERE id_empleado = _id_empleado;

    SELECT t.id_ticket, c.nombre AS nombre_cliente, t.estado,
        CASE WHEN t.id_trabajador = _id_empleado THEN 'MÍO' ELSE 'DISPONIBLE' END AS asignacion
    FROM ticket t JOIN cliente c ON t.id_cliente = c.id_cliente
    WHERE t.id_trabajador = _id_empleado OR (t.id_trabajador IS NULL AND t.estado = 'Abierto' AND c.id_sede = v_mi_sede);
END$$

CREATE PROCEDURE sp_gerente_ver_tickets_area(IN _id_gerente INT)
BEGIN
    SELECT t.id_ticket, c.nombre AS nombre_cliente, COALESCE(e.nombre, 'SIN ASIGNAR') AS nombre_empleado, t.estado, t.fecha_creacion
    FROM ticket t JOIN cliente c ON t.id_cliente = c.id_cliente JOIN sede s ON c.id_sede = s.id_sede LEFT JOIN empleado e ON t.id_trabajador = e.id_empleado
    WHERE s.id_gerente = _id_gerente;
END$$

CREATE PROCEDURE sp_proc_actualizar_estado(IN _id_ticket INT, IN _nuevo_estado VARCHAR(50), IN _nota TEXT)
BEGIN
    UPDATE ticket SET estado = _nuevo_estado WHERE id_ticket = _id_ticket;
    INSERT INTO estado_ticket (id_ticket, fecha_estado, nota) VALUES (_id_ticket, NOW(), _nota);
END$$

CREATE PROCEDURE sp_proc_reasignar_empleado(IN _id_ticket INT, IN _id_nuevo_trabajador INT, IN _nota_gerencia TEXT)
BEGIN
    UPDATE ticket SET id_trabajador = _id_nuevo_trabajador WHERE id_ticket = _id_ticket;
    INSERT INTO estado_ticket (id_ticket, fecha_estado, nota) VALUES (_id_ticket, NOW(), CONCAT('REASIGNACIÓN: ', _nota_gerencia));
END$$

CREATE PROCEDURE sp_proc_eliminar_ticket(IN _id_ticket INT)
BEGIN
    DELETE FROM estado_ticket WHERE id_ticket = _id_ticket;
    DELETE FROM ticket WHERE id_ticket = _id_ticket;
END$$

DELIMITER ;

CREATE OR REPLACE VIEW vw_tickets_detallados AS
SELECT t.id_ticket, t.estado, t.fecha_creacion, c.nombre AS nombre_cliente, s.nombre_sede AS sede_origen,
    COALESCE(e.nombre, 'SIN ASIGNAR') AS nombre_empleado, COALESCE(g.nombre, 'N/A') AS gerente_responsable
FROM ticket t JOIN cliente c ON t.id_cliente = c.id_cliente JOIN sede s ON c.id_sede = s.id_sede
LEFT JOIN empleado e ON t.id_trabajador = e.id_empleado LEFT JOIN gerente g ON s.id_gerente = g.id_gerente;

CREATE OR REPLACE VIEW vw_rendimiento_empleados AS
SELECT e.id_empleado, e.nombre, s.nombre_sede,
    COUNT(CASE WHEN t.estado = 'Finalizado' THEN 1 END) AS tickets_finalizados,
    COUNT(CASE WHEN t.estado IN ('Abierto', 'En Proceso') THEN 1 END) AS tickets_pendientes
FROM empleado e JOIN sede s ON e.id_sede = s.id_sede LEFT JOIN ticket t ON e.id_empleado = t.id_trabajador
GROUP BY e.id_empleado, e.nombre, s.nombre_sede;

INSERT INTO gerente (nombre, correo, hash_contrasena) VALUES
('Carlos Gerente Norte', 'carlos.norte@empresa.com', 'admin123'),
('Maria Gerente Centro', 'maria.centro@empresa.com', 'admin123'),
('Pedro Gerente Sur', 'pedro.sur@empresa.com', 'admin123');

INSERT INTO sede (nombre_sede, localizacion, id_gerente) VALUES
('Sede Norte', 'Av. Industrias 100, Norte', 1),
('Sede Centro', 'Calle Principal 555, Centro', 2),
('Sede Sur', 'Camino al Puerto 900, Sur', 3);

INSERT INTO empleado (nombre, correo, hash_contrasena, id_sede, id_gerente) VALUES
('Juan Perez (Norte)', 'juan.perez@empresa.com', 'empleado123', 1, 1),
('Ana Lopez (Norte)', 'ana.lopez@empresa.com', 'empleado123', 1, 1),
('Luis Gomez (Centro)', 'luis.gomez@empresa.com', 'empleado123', 2, 2),
('Diego Ruiz (Sur)', 'diego.ruiz@empresa.com', 'empleado123', 3, 3);

INSERT INTO cliente (nombre, correo, numero_telefono, id_sede, hash_contrasena) VALUES
('Cliente Norte 1', 'cliente1@gmail.com', '555-1111', 1, 'cliente123'),
('Cliente Centro 1', 'cliente2@hotmail.com', '555-2222', 2, 'cliente123'),
('Cliente Sur 1', 'cliente3@yahoo.com', '555-3333', 3, 'cliente123'),
('Cliente Norte 2', 'cliente4@outlook.com', '555-4444', 1, 'cliente123');

INSERT INTO ticket (id_cliente, id_trabajador, estado) VALUES
(1, 1, 'Abierto'),
(2, 3, 'En Proceso'),
(4, NULL, 'Abierto');