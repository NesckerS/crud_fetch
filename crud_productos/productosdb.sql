-- ════════════════════════════════════════════════════════
-- Script SQL — productosdb
-- Curso: Desarrollo de Software VII
-- ════════════════════════════════════════════════════════

-- 1. Crear la base de datos
CREATE DATABASE IF NOT EXISTS productosdb
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- 2. Seleccionar la base de datos
USE productosdb;

-- 3. Crear la tabla productos
CREATE TABLE IF NOT EXISTS productos (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    codigo    VARCHAR(20)     NOT NULL,
    producto  VARCHAR(100)    NOT NULL,
    precio    DECIMAL(10,2)   NOT NULL,
    cantidad  INT             NOT NULL
);

-- 4. Datos de prueba (opcional)
INSERT INTO productos (codigo, producto, precio, cantidad) VALUES
    ('PROD-001', 'Laptop HP 15"',       850.00, 5),
    ('PROD-002', 'Mouse Inalámbrico',    25.99, 20),
    ('PROD-003', 'Teclado Mecánico',     75.50, 12),
    ('PROD-004', 'Monitor 24" Full HD', 220.00,  3),
    ('PROD-005', 'Auriculares Gamer',    49.99,  8);
