-- Conserva las cuatro familias solicitadas y reconcilia el inventario con el kardex real.
-- Retira únicamente las unidades de demostración agregadas por la carga inicial anterior.
USE sys_comercial;

START TRANSACTION;

-- Primero se quitan las subcategorías de familias no utilizadas. Las tablas de
-- atributos se limpian mediante sus claves foráneas en cascada.
DELETE hija
FROM categoria AS hija
LEFT JOIN categoria AS padre ON padre.id_categoria = hija.id_padre
WHERE hija.id_padre IS NOT NULL
  AND (
      padre.id_categoria IS NULL
      OR padre.id_padre IS NOT NULL
      OR padre.nombre NOT IN ('Línea blanca', 'Muebles de descanso', 'Electrónica', 'Hogar y cocina')
  );

DELETE FROM categoria
WHERE id_padre IS NULL
  AND nombre NOT IN ('Línea blanca', 'Muebles de descanso', 'Electrónica', 'Hogar y cocina');

DROP TEMPORARY TABLE IF EXISTS tmp_existencias_demostracion;
CREATE TEMPORARY TABLE tmp_existencias_demostracion AS
SELECT id_ajuste, id_producto
FROM ajuste_inventario
WHERE motivo LIKE 'Existencia inicial de demostración #%';

DELETE kardex
FROM kardex
INNER JOIN tmp_existencias_demostracion AS demo ON demo.id_ajuste = kardex.id_ajuste;

DELETE ajuste
FROM ajuste_inventario AS ajuste
INNER JOIN tmp_existencias_demostracion AS demo ON demo.id_ajuste = ajuste.id_ajuste;

-- Restaura el último saldo registrado antes de la existencia de demostración.
UPDATE inventario AS i
INNER JOIN (
    SELECT id_producto, MAX(id_kardex) AS id_kardex
    FROM kardex
    INNER JOIN tmp_existencias_demostracion AS demo USING (id_producto)
    GROUP BY id_producto
) AS ultimo ON ultimo.id_producto = i.id_producto
INNER JOIN kardex AS k ON k.id_kardex = ultimo.id_kardex
SET i.existencia = k.existencias,
    i.costo_promedio = k.costo_promedio,
    i.fecha_actualizacion = CURRENT_TIMESTAMP;

-- Si un producto solo tenía el ajuste de demostración, no conserva un registro vacío.
DELETE i
FROM inventario AS i
INNER JOIN (SELECT DISTINCT id_producto FROM tmp_existencias_demostracion) AS demo
    ON demo.id_producto = i.id_producto
WHERE NOT EXISTS (
    SELECT 1 FROM kardex AS k WHERE k.id_producto = i.id_producto
);

DROP TEMPORARY TABLE tmp_existencias_demostracion;
COMMIT;
