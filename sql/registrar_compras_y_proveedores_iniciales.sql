-- Registra compras ficticias autorizadas por el usuario para probar el flujo.
-- Reemplaza las existencias iniciales por compras y sus movimientos correspondientes.
USE sys_comercial;

START TRANSACTION;

SET @usuario_demo = (SELECT MIN(id_usuario) FROM usuario WHERE estado = 'activo');
SET @tipo_compra = (
    SELECT MIN(id_tipo_movimiento)
    FROM tipo_movimiento
    WHERE nombre = 'Compra' AND naturaleza = 'entrada'
);

-- Retira únicamente los ajustes iniciales que se cargaron antes de convertirlos en compras.
DROP TEMPORARY TABLE IF EXISTS tmp_ajustes_iniciales_demo;
CREATE TEMPORARY TABLE tmp_ajustes_iniciales_demo AS
SELECT id_ajuste, id_producto
FROM ajuste_inventario
WHERE motivo LIKE 'Inventario inicial solicitado #%';

DELETE kardex
FROM kardex
INNER JOIN tmp_ajustes_iniciales_demo AS demo ON demo.id_ajuste = kardex.id_ajuste;

DELETE ajuste
FROM ajuste_inventario AS ajuste
INNER JOIN tmp_ajustes_iniciales_demo AS demo ON demo.id_ajuste = ajuste.id_ajuste;

UPDATE inventario AS i
INNER JOIN (
    SELECT id_producto, MAX(id_kardex) AS id_kardex
    FROM kardex
    INNER JOIN tmp_ajustes_iniciales_demo AS demo USING (id_producto)
    GROUP BY id_producto
) AS ultimo ON ultimo.id_producto = i.id_producto
INNER JOIN kardex AS k ON k.id_kardex = ultimo.id_kardex
SET i.existencia = k.existencias,
    i.costo_promedio = k.costo_promedio,
    i.fecha_actualizacion = CURRENT_TIMESTAMP;

DELETE i
FROM inventario AS i
INNER JOIN (SELECT DISTINCT id_producto FROM tmp_ajustes_iniciales_demo) AS demo
    ON demo.id_producto = i.id_producto
WHERE NOT EXISTS (
    SELECT 1 FROM kardex AS k WHERE k.id_producto = i.id_producto
);

-- Los datos de proveedor son ficticios; los nombres visibles no llevan etiquetas de prueba.
INSERT INTO proveedor
    (tipo_persona, nombre_razon_social, dui, nit, telefono, correo_electronico, direccion, estado)
VALUES
    ('juridica', 'Descanso y Comodidad, S.A. de C.V.', NULL, '0614-261125-701-1', '2222-7001', 'compras@descansoycomodidad.example', 'Colonia Escalón, San Salvador', 'activo'),
    ('juridica', 'Importadora Colchones Centroamérica, S.A. de C.V.', NULL, '0614-261125-702-9', '2222-7002', 'ventas@colchonescentroamerica.example', 'Zona industrial, Santa Tecla', 'activo'),
    ('juridica', 'Textiles Hogar Salvadoreño, S.A. de C.V.', NULL, '0614-261125-703-7', '2222-7003', 'pedidos@textileshogarsalvador.example', 'Antiguo Cuscatlán, La Libertad', 'activo'),
    ('juridica', 'Distribuidora Mayorista Central, S.A. de C.V.', NULL, '0614-261125-704-5', '2222-7004', 'compras@mayoristacentral.example', 'Centro de San Salvador', 'activo')
ON DUPLICATE KEY UPDATE
    id_proveedor = LAST_INSERT_ID(id_proveedor),
    nombre_razon_social = VALUES(nombre_razon_social),
    tipo_persona = VALUES(tipo_persona),
    telefono = VALUES(telefono),
    correo_electronico = VALUES(correo_electronico),
    direccion = VALUES(direccion),
    estado = 'activo';

SET @proveedor_demo_1 = (SELECT id_proveedor FROM proveedor WHERE nit = '0614-261125-701-1' LIMIT 1);
SET @proveedor_demo_2 = (SELECT id_proveedor FROM proveedor WHERE nit = '0614-261125-702-9' LIMIT 1);
SET @proveedor_demo_3 = (SELECT id_proveedor FROM proveedor WHERE nit = '0614-261125-703-7' LIMIT 1);
SET @proveedor_demo_4 = (SELECT id_proveedor FROM proveedor WHERE nit = '0614-261125-704-5' LIMIT 1);

DROP TEMPORARY TABLE IF EXISTS tmp_compras_demo;
CREATE TEMPORARY TABLE tmp_compras_demo (
    grupo TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    id_proveedor INT UNSIGNED NOT NULL,
    fecha DATETIME NOT NULL,
    id_compra INT UNSIGNED NULL
);

INSERT INTO tmp_compras_demo (grupo, id_proveedor, fecha)
VALUES
    (1, @proveedor_demo_1, '2026-11-25 08:00:00'),
    (2, @proveedor_demo_2, '2026-11-25 08:15:00'),
    (3, @proveedor_demo_3, '2026-11-25 08:30:00'),
    (4, @proveedor_demo_4, '2026-11-25 08:45:00');

DROP TEMPORARY TABLE IF EXISTS tmp_lineas_compra_demo;
CREATE TEMPORARY TABLE tmp_lineas_compra_demo (
    grupo TINYINT UNSIGNED NOT NULL,
    id_producto INT UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    costo_unitario DECIMAL(8,2) NOT NULL,
    PRIMARY KEY (grupo, id_producto)
);

INSERT INTO tmp_lineas_compra_demo (grupo, id_producto, cantidad, costo_unitario)
VALUES
    (1, 1, 1, 100.00),
    (1, 2, 1, 103.85),
    (1, 3, 2, 203.85),
    (1, 4, 1, 268.46),
    (2, 5, 2, 353.08),
    (2, 6, 1, 122.31),
    (2, 7, 2, 168.46),
    (2, 8, 1, 214.62),
    (3, 11, 1, 16.92),
    (3, 12, 2, 21.54),
    (3, 13, 1, 29.23),
    (4, 14, 2, 37.69),
    (4, 15, 1, 18.46),
    (4, 16, 2, 76.15);

INSERT INTO compra (id_proveedor, id_usuario, fecha, subtotal, iva_credito, total)
SELECT
    grupos.id_proveedor,
    @usuario_demo,
    grupos.fecha,
    totales.subtotal,
    ROUND(totales.subtotal * 0.13, 2),
    ROUND(totales.subtotal + ROUND(totales.subtotal * 0.13, 2), 2)
FROM tmp_compras_demo AS grupos
INNER JOIN (
    SELECT grupo, ROUND(SUM(cantidad * costo_unitario), 2) AS subtotal
    FROM tmp_lineas_compra_demo
    GROUP BY grupo
) AS totales ON totales.grupo = grupos.grupo
WHERE NOT EXISTS (
    SELECT 1 FROM compra AS existente
    WHERE existente.id_proveedor = grupos.id_proveedor
      AND existente.fecha = grupos.fecha
);

UPDATE tmp_compras_demo AS grupos
INNER JOIN compra AS compra_existente
    ON compra_existente.id_proveedor = grupos.id_proveedor
   AND compra_existente.fecha = grupos.fecha
SET grupos.id_compra = compra_existente.id_compra;

DROP TEMPORARY TABLE IF EXISTS tmp_movimientos_compra_demo;
CREATE TEMPORARY TABLE tmp_movimientos_compra_demo AS
SELECT
    grupos.id_compra,
    grupos.fecha,
    lineas.id_producto,
    lineas.cantidad,
    lineas.costo_unitario,
    COALESCE(inventario.existencia, 0) AS existencia_anterior,
    COALESCE(inventario.costo_promedio, 0) AS costo_anterior,
    producto.margen_ganancia,
    @usuario_demo AS id_usuario
FROM tmp_lineas_compra_demo AS lineas
INNER JOIN tmp_compras_demo AS grupos ON grupos.grupo = lineas.grupo
INNER JOIN producto ON producto.id_producto = lineas.id_producto AND producto.estado = 'activo'
LEFT JOIN inventario ON inventario.id_producto = lineas.id_producto
LEFT JOIN kardex AS movimiento_existente
    ON movimiento_existente.id_compra = grupos.id_compra
   AND movimiento_existente.id_producto = lineas.id_producto
WHERE movimiento_existente.id_kardex IS NULL;

INSERT INTO detalle_compra (id_compra, id_producto, cantidad, costo_unitario, subtotal)
SELECT grupos.id_compra, lineas.id_producto, lineas.cantidad,
       lineas.costo_unitario, ROUND(lineas.cantidad * lineas.costo_unitario, 2)
FROM tmp_lineas_compra_demo AS lineas
INNER JOIN tmp_compras_demo AS grupos ON grupos.grupo = lineas.grupo
WHERE NOT EXISTS (
    SELECT 1 FROM detalle_compra AS detalle
    WHERE detalle.id_compra = grupos.id_compra
      AND detalle.id_producto = lineas.id_producto
);

INSERT INTO inventario (id_producto, existencia, costo_promedio)
SELECT
    id_producto,
    existencia_anterior + cantidad,
    CASE
        WHEN existencia_anterior = 0 THEN costo_unitario
        ELSE ROUND(((existencia_anterior * costo_anterior) + (cantidad * costo_unitario))
                   / (existencia_anterior + cantidad), 2)
    END
FROM tmp_movimientos_compra_demo
ON DUPLICATE KEY UPDATE
    existencia = VALUES(existencia),
    costo_promedio = VALUES(costo_promedio);

UPDATE producto AS p
INNER JOIN inventario AS i ON i.id_producto = p.id_producto
INNER JOIN tmp_movimientos_compra_demo AS movimiento ON movimiento.id_producto = p.id_producto
SET p.precio_venta = ROUND(i.costo_promedio * (1 + (p.margen_ganancia / 100)), 2);

INSERT INTO kardex
    (id_producto, id_tipo_movimiento, id_usuario, id_compra, fecha_hora, cantidad,
     costo_unitario, existencias, costo_promedio, saldo_total)
SELECT
    movimiento.id_producto,
    @tipo_compra,
    movimiento.id_usuario,
    movimiento.id_compra,
    movimiento.fecha,
    movimiento.cantidad,
    movimiento.costo_unitario,
    movimiento.existencia_anterior + movimiento.cantidad,
    CASE
        WHEN movimiento.existencia_anterior = 0 THEN movimiento.costo_unitario
        ELSE ROUND(((movimiento.existencia_anterior * movimiento.costo_anterior)
                    + (movimiento.cantidad * movimiento.costo_unitario))
                   / (movimiento.existencia_anterior + movimiento.cantidad), 2)
    END,
    ROUND(
        (movimiento.existencia_anterior + movimiento.cantidad)
        * CASE
            WHEN movimiento.existencia_anterior = 0 THEN movimiento.costo_unitario
            ELSE ROUND(((movimiento.existencia_anterior * movimiento.costo_anterior)
                        + (movimiento.cantidad * movimiento.costo_unitario))
                       / (movimiento.existencia_anterior + movimiento.cantidad), 2)
          END,
        2
    )
FROM tmp_movimientos_compra_demo AS movimiento;

DROP TEMPORARY TABLE tmp_movimientos_compra_demo;
DROP TEMPORARY TABLE tmp_lineas_compra_demo;
DROP TEMPORARY TABLE tmp_compras_demo;
DROP TEMPORARY TABLE tmp_ajustes_iniciales_demo;
COMMIT;
