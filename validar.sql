-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 01-05-2026 a las 19:45:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `validar`
--

DELIMITER $$
--
-- Procedimientos
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_clientes` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO
        INSERT INTO clientes 
        (nombre, apellido_paterno, apellido_materno, curp, telefono, correo, direccion)
        VALUES 
        (
            CONCAT('Cliente', i),
            CONCAT('ApellidoP', i),
            CONCAT('ApellidoM', i),
            CONCAT('CURP', LPAD(i, 14, '0')),
            CONCAT('722', LPAD(i, 7, '0')),
            CONCAT('cliente', i, '@bancopatito.com'),
            CONCAT('Dirección de prueba número ', i)
        );

        SET i = i + 1;
    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_clientes_usuarios` ()   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE nuevo_cliente_id INT;

    WHILE i <= 600 DO

        INSERT INTO clientes 
        (
            nombre, 
            apellido_paterno, 
            apellido_materno, 
            curp, 
            telefono, 
            correo, 
            direccion
        )
        VALUES 
        (
            CONCAT('Cliente', i),
            CONCAT('ApellidoP', i),
            CONCAT('ApellidoM', i),
            CONCAT('CURP', LPAD(i, 14, '0')),
            CONCAT('722', LPAD(i, 7, '0')),
            CONCAT('cliente', i, '@bancopatito.com'),
            CONCAT('Dirección bancaria número ', i)
        );

        SET nuevo_cliente_id = LAST_INSERT_ID();

        INSERT INTO usuarios 
        (
            usuario, 
            password, 
            correo, 
            rol, 
            estado,
            id_cliente
        )
        VALUES 
        (
            CONCAT('usuario', i),
            CONCAT('pass', i),
            CONCAT('cliente', i, '@bancopatito.com'),
            'cliente',
            'activo',
            nuevo_cliente_id
        );

        SET i = i + 1;

    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_cuentas` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO
        INSERT INTO cuentas 
        (id_cliente, numero_cuenta, tipo_cuenta, saldo)
        VALUES 
        (
            i,
            CONCAT('100000', LPAD(i, 6, '0')),
            'ahorro',
            ROUND(RAND() * 50000, 2)
        );

        SET i = i + 1;
    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_cuentas_relacionadas` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO

        INSERT INTO cuentas 
        (
            id_cliente, 
            numero_cuenta, 
            tipo_cuenta, 
            saldo
        )
        VALUES 
        (
            i,
            CONCAT('100000', LPAD(i, 6, '0')),
            ELT(FLOOR(1 + RAND() * 3), 'ahorro', 'debito', 'nomina'),
            ROUND(1000 + RAND() * 50000, 2)
        );

        SET i = i + 1;

    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_prestamos` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO
        INSERT INTO prestamos 
        (id_cliente, monto, tasa_interes, plazo_meses, estado)
        VALUES 
        (
            i,
            ROUND(5000 + RAND() * 95000, 2),
            ROUND(8 + RAND() * 10, 2),
            ELT(FLOOR(1 + RAND() * 4), 12, 24, 36, 48),
            ELT(FLOOR(1 + RAND() * 4), 'pendiente', 'aprobado', 'rechazado', 'pagado')
        );

        SET i = i + 1;
    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_prestamos_relacionados` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO

        INSERT INTO prestamos 
        (
            id_cliente, 
            monto, 
            tasa_interes, 
            plazo_meses, 
            estado
        )
        VALUES 
        (
            i,
            ROUND(5000 + RAND() * 95000, 2),
            ROUND(8 + RAND() * 10, 2),
            ELT(FLOOR(1 + RAND() * 4), 12, 24, 36, 48),
            ELT(FLOOR(1 + RAND() * 4), 'pendiente', 'aprobado', 'rechazado', 'pagado')
        );

        SET i = i + 1;

    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_tarjetas` ()   BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE contador INT DEFAULT 1;

    WHILE contador <= 600 DO

        IF NOT EXISTS (
            SELECT 1 
            FROM tarjetas 
            WHERE numero_tarjeta = CONCAT('4000000000', LPAD(i, 6, '0'))
        ) THEN

            INSERT INTO tarjetas 
            (id_cuenta, numero_tarjeta, tipo_tarjeta, fecha_vencimiento, cvv)
            VALUES 
            (
                i,
                CONCAT('4000000000', LPAD(i, 6, '0')),
                'debito',
                '2029-12-31',
                LPAD(FLOOR(RAND() * 999), 3, '0')
            );

            SET contador = contador + 1;

        END IF;

        SET i = i + 1;

    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_tarjetas_relacionadas` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO

        INSERT INTO tarjetas 
        (
            id_cuenta, 
            numero_tarjeta, 
            tipo_tarjeta, 
            fecha_vencimiento, 
            cvv,
            estado
        )
        VALUES 
        (
            i,
            CONCAT('4000000000', LPAD(i, 6, '0')),
            ELT(FLOOR(1 + RAND() * 2), 'debito', 'credito'),
            DATE_ADD(CURRENT_DATE, INTERVAL 3 YEAR),
            LPAD(FLOOR(100 + RAND() * 899), 3, '0'),
            'activa'
        );

        SET i = i + 1;

    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_transacciones` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO
        INSERT INTO transacciones 
        (id_cuenta, tipo_transaccion, monto, descripcion)
        VALUES 
        (
            i,
            ELT(FLOOR(1 + RAND() * 4), 'deposito', 'retiro', 'transferencia', 'pago'),
            ROUND(100 + RAND() * 10000, 2),
            CONCAT('Transacción bancaria de prueba número ', i)
        );

        SET i = i + 1;
    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_transacciones_relacionadas` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO

        INSERT INTO transacciones 
        (
            id_cuenta, 
            tipo_transaccion, 
            monto, 
            descripcion
        )
        VALUES 
        (
            i,
            ELT(FLOOR(1 + RAND() * 4), 'deposito', 'retiro', 'transferencia', 'pago'),
            ROUND(100 + RAND() * 10000, 2),
            CONCAT('Movimiento bancario relacionado con la cuenta ', i)
        );

        SET i = i + 1;

    END WHILE;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `insertar_usuarios` ()   BEGIN
    DECLARE i INT DEFAULT 1;

    WHILE i <= 600 DO
        INSERT INTO usuarios 
        (usuario, password, correo, rol, estado)
        VALUES 
        (
            CONCAT('usuario', i),
            CONCAT('pass', i),
            CONCAT('usuario', i, '@bancopatito.com'),
            'cliente',
            'activo'
        );

        SET i = i + 1;
    END WHILE;
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
