-- Ampliación incremental del módulo de créditos.
-- Ejecutar una sola vez en cada copia de la base comercial_ppi.
-- Este cambio parte del esquema actual y asume que aún no se han registrado
-- créditos ni pagos. Verifica SELECT COUNT(*) FROM credito antes de ejecutarlo.

ALTER TABLE credito
  ADD COLUMN capital_financiado DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER prima,
  ADD COLUMN saldo_capital DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER capital_financiado,
  ADD COLUMN fecha_primer_vencimiento DATE NULL AFTER fecha_aprobacion,
  ADD COLUMN fecha_ultimo_calculo_interes DATE NULL AFTER fecha_primer_vencimiento,
  MODIFY COLUMN tasa_interes DECIMAL(5,2) NOT NULL COMMENT 'Tasa ordinaria anual en porcentaje',
  MODIFY COLUMN tasa_interes_moratoria DECIMAL(5,2) NOT NULL DEFAULT 5.00 COMMENT 'Tasa moratoria anual sobre capital vencido';

ALTER TABLE plan_pago
  ADD COLUMN capital_proyectado DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER monto_cuota,
  ADD COLUMN interes_proyectado DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER capital_proyectado,
  ADD COLUMN saldo_capital_proyectado DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER interes_proyectado;

ALTER TABLE pago
  MODIFY COLUMN numero_comprobante VARCHAR(30) NULL,
  ADD COLUMN tipo_pago ENUM('prima','cuota','abono_capital','liquidacion') NOT NULL DEFAULT 'cuota' AFTER monto,
  ADD COLUMN interes_corriente DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER tipo_pago,
  ADD COLUMN abono_capital DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER interes_corriente;

ALTER TABLE estado_cuenta
  ADD COLUMN abono_mora DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER abono_interes;

-- Estado real por cuota. No modifica las fechas ni los importes de plan_pago.
CREATE TABLE credito_cuota_estado (
  id_plan_pago INT UNSIGNED NOT NULL,
  capital_pendiente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  interes_pendiente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  mora_pendiente DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  fecha_ultimo_calculo_mora DATE NOT NULL,
  PRIMARY KEY (id_plan_pago),
  CONSTRAINT fk_credito_cuota_estado_plan
    FOREIGN KEY (id_plan_pago) REFERENCES plan_pago (id_plan_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Distribución auditable de cada pago entre una o más cuotas y conceptos.
CREATE TABLE aplicacion_pago_credito (
  id_aplicacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_pago INT UNSIGNED NOT NULL,
  id_plan_pago INT UNSIGNED NULL,
  concepto ENUM('interes_corriente','interes_moratorio','capital') NOT NULL,
  monto DECIMAL(12,2) NOT NULL,
  PRIMARY KEY (id_aplicacion),
  KEY idx_aplicacion_pago (id_pago),
  KEY idx_aplicacion_plan (id_plan_pago),
  CONSTRAINT fk_aplicacion_pago
    FOREIGN KEY (id_pago) REFERENCES pago (id_pago),
  CONSTRAINT fk_aplicacion_plan
    FOREIGN KEY (id_plan_pago) REFERENCES plan_pago (id_plan_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Los permisos de crédito siguen el alcance de los permisos de ventas actuales.
INSERT INTO permiso (codigo, descripcion)
VALUES
  ('ventas.consultar', 'Consultar ventas registradas'),
  ('ventas.registrar', 'Registrar ventas al contado y al crédito'),
  ('ventas.anular', 'Anular ventas al contado'),
  ('creditos.consultar', 'Consultar créditos, planes y estados de cuenta'),
  ('creditos.registrar_pago', 'Registrar pagos de créditos')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion);

INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT DISTINCT rp.id_rol, nuevo.id_permiso
FROM rol_permiso AS rp
INNER JOIN permiso AS actual ON actual.id_permiso = rp.id_permiso
INNER JOIN permiso AS nuevo
  ON nuevo.codigo = 'creditos.consultar'
WHERE actual.codigo IN ('ventas.consultar', 'ventas.registrar');

INSERT IGNORE INTO rol_permiso (id_rol, id_permiso)
SELECT DISTINCT rp.id_rol, nuevo.id_permiso
FROM rol_permiso AS rp
INNER JOIN permiso AS actual ON actual.id_permiso = rp.id_permiso
INNER JOIN permiso AS nuevo ON nuevo.codigo = 'creditos.registrar_pago'
WHERE actual.codigo = 'ventas.registrar';

