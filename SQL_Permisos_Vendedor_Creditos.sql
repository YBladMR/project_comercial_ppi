-- Permisos para el rol operativo Vendedor.
-- Ejecutar si SQL_Creditos_Modulo.sql ya se había aplicado antes de incluir esta asignación.

INSERT INTO permiso (codigo, descripcion)
VALUES
  ('ventas.consultar', 'Consultar ventas registradas'),
  ('ventas.registrar', 'Registrar ventas al contado y al crédito'),
  ('ventas.anular', 'Anular ventas al contado'),
  ('creditos.consultar', 'Consultar créditos, planes y estados de cuenta'),
  ('creditos.registrar_pago', 'Registrar pagos de créditos')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT r.id_rol, p.id_permiso
FROM rol AS r
CROSS JOIN permiso AS p
WHERE LOWER(TRIM(r.nombre)) = 'vendedor'
  AND p.codigo IN (
    'ventas.consultar',
    'ventas.registrar',
    'creditos.consultar',
    'creditos.registrar_pago'
  );
