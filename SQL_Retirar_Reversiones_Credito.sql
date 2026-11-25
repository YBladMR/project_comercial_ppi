-- Retira el soporte opcional de reversión de pagos de crédito.
-- Los pagos originales y sus distribuciones no se eliminan.
-- Ejecutar solo si previamente se aplicó SQL_Creditos_Reversiones.sql.

DROP TABLE IF EXISTS anulacion_pago_credito;

ALTER TABLE pago
  DROP FOREIGN KEY fk_pago_usuario_anula,
  DROP INDEX idx_pago_usuario_anula,
  DROP COLUMN motivo_anulacion,
  DROP COLUMN id_usuario_anula,
  DROP COLUMN fecha_anulacion,
  DROP COLUMN estado_pago;

DELETE rp
FROM rol_permiso AS rp
INNER JOIN permiso AS p ON p.id_permiso = rp.id_permiso
WHERE p.codigo = 'creditos.anular_pago';

DELETE FROM permiso WHERE codigo = 'creditos.anular_pago';
