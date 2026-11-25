-- Ejecutar después de sql/crear_especificaciones_producto.sql.
-- Carga las cuatro familias solicitadas y los campos del formulario por subcategoría.
USE sys_comercial;

-- La base tiene un índice único global para nombre de categoría. Esta subcategoría
-- de ejemplo se renombra para reservar "Ropa de cama" para Hogar y cocina.
UPDATE categoria AS hija
INNER JOIN categoria AS padre ON padre.id_categoria = hija.id_padre
SET hija.nombre = 'Textiles de descanso', hija.descripcion = 'Sábanas, cobertores y textiles para camas.'
WHERE hija.nombre = 'Ropa de cama' AND padre.nombre = 'Muebles de descanso' AND padre.id_padre IS NULL;

INSERT INTO categoria (nombre, descripcion, id_padre, estado)
VALUES
  ('Línea blanca', 'Categoría principal para línea blanca.', NULL, 'activo'),
  ('Muebles de descanso', 'Categoría principal para colchones, camas y textiles de descanso.', NULL, 'activo'),
  ('Electrónica', 'Categoría principal para electrónica.', NULL, 'activo'),
  ('Hogar y cocina', 'Categoría principal para hogar y cocina.', NULL, 'activo')
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), estado = 'activo';

INSERT INTO categoria (nombre, descripcion, id_padre, estado)
SELECT c.nombre, c.descripcion, p.id_categoria, 'activo'
FROM (
SELECT 'Línea blanca' AS nombre_padre, 'Cocinas' AS nombre, 'Subcategoría de cocinas.' AS descripcion
UNION ALL SELECT 'Línea blanca', 'Hornos empotrables', 'Subcategoría de hornos empotrables.'
UNION ALL SELECT 'Línea blanca', 'Campanas extractoras', 'Subcategoría de campanas extractoras.'
UNION ALL SELECT 'Línea blanca', 'Lavadoras', 'Subcategoría de lavadoras.'
UNION ALL SELECT 'Línea blanca', 'Secadoras', 'Subcategoría de secadoras.'
UNION ALL SELECT 'Línea blanca', 'Refrigeradoras', 'Subcategoría de refrigeradoras.'
UNION ALL SELECT 'Línea blanca', 'Congeladores', 'Subcategoría de congeladores.'
UNION ALL SELECT 'Línea blanca', 'Aires acondicionados', 'Subcategoría de aires acondicionados.'
UNION ALL SELECT 'Línea blanca', 'Ventiladores', 'Subcategoría de ventiladores.'
UNION ALL SELECT 'Muebles de descanso', 'Almohadas', 'Subcategoría de almohadas.'
UNION ALL SELECT 'Muebles de descanso', 'Camas y bases', 'Subcategoría de camas y bases.'
UNION ALL SELECT 'Muebles de descanso', 'Colchones', 'Subcategoría de colchones.'
UNION ALL SELECT 'Muebles de descanso', 'Protectores y toppers', 'Subcategoría de protectores y toppers.'
UNION ALL SELECT 'Muebles de descanso', 'Textiles de descanso', 'Sábanas, cobertores y textiles para camas.'
UNION ALL SELECT 'Electrónica', 'Televisores', 'Subcategoría de televisores.'
UNION ALL SELECT 'Electrónica', 'Audio', 'Subcategoría de audio.'
UNION ALL SELECT 'Electrónica', 'Computadoras', 'Subcategoría de computadoras.'
UNION ALL SELECT 'Electrónica', 'Impresión', 'Subcategoría de impresión.'
UNION ALL SELECT 'Electrónica', 'Telefonía y tabletas', 'Subcategoría de telefonía y tabletas.'
UNION ALL SELECT 'Hogar y cocina', 'Utensilios de cocina', 'Subcategoría de utensilios de cocina.'
UNION ALL SELECT 'Hogar y cocina', 'Mesa y vajilla', 'Subcategoría de mesa y vajilla.'
UNION ALL SELECT 'Hogar y cocina', 'Baño', 'Subcategoría de baño.'
UNION ALL SELECT 'Hogar y cocina', 'Ropa de cama', 'Subcategoría de ropa de cama.') AS c
INNER JOIN categoria AS p ON p.nombre = c.nombre_padre AND p.id_padre IS NULL
ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), estado = 'activo';

INSERT INTO atributo_categoria
  (id_categoria, codigo, nombre, ayuda, tipo_dato, unidad, opciones, obligatorio, orden, estado)
SELECT c.id_categoria, d.codigo, d.nombre, d.ayuda, d.tipo_dato, d.unidad, d.opciones, d.obligatorio, d.orden, 'activo'
FROM (
SELECT 'Colchones' AS subcategoria, 'tamano_colchon' AS codigo, 'Tamaño' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Individual', 'Matrimonial', 'Queen', 'King') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Colchones' AS subcategoria, 'tipo_colchon' AS codigo, 'Tipo de colchón' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Espuma', 'Resortes', 'Viscoelástico', 'Híbrido') AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Colchones' AS subcategoria, 'firmeza' AS codigo, 'Firmeza' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Suave', 'Media', 'Firme') AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Camas y bases' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Cama', 'Base', 'Cabecera') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Camas y bases' AS subcategoria, 'tamano_cama' AS codigo, 'Tamaño' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Individual', 'Matrimonial', 'Queen', 'King') AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Camas y bases' AS subcategoria, 'material_estructura' AS codigo, 'Material de estructura' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Almohadas' AS subcategoria, 'tipo_relleno' AS codigo, 'Tipo de relleno' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Fibra', 'Espuma', 'Viscoelástico', 'Pluma') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Almohadas' AS subcategoria, 'firmeza' AS codigo, 'Firmeza' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Suave', 'Media', 'Firme') AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Almohadas' AS subcategoria, 'lavable' AS codigo, 'Lavable' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Textiles de descanso' AS subcategoria, 'tamano' AS codigo, 'Tamaño' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Individual', 'Matrimonial', 'Queen', 'King') AS opciones, 0 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Textiles de descanso' AS subcategoria, 'numero_piezas' AS codigo, 'Número de piezas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'piezas' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Textiles de descanso' AS subcategoria, 'tejido' AS codigo, 'Tejido' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Protectores y toppers' AS subcategoria, 'tipo_producto' AS codigo, 'Tipo de producto' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Protector', 'Topper') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Protectores y toppers' AS subcategoria, 'tamano_colchon' AS codigo, 'Tamaño' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Individual', 'Matrimonial', 'Queen', 'King') AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Protectores y toppers' AS subcategoria, 'impermeable' AS codigo, 'Impermeable' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Cocinas' AS subcategoria, 'fuente_energia' AS codigo, 'Fuente de energía' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Gas', 'Eléctrica') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Cocinas' AS subcategoria, 'numero_quemadores' AS codigo, 'Quemadores' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Cocinas' AS subcategoria, 'incluye_horno' AS codigo, 'Incluye horno' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Cocinas' AS subcategoria, 'capacidad_horno_l' AS codigo, 'Capacidad del horno' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'L' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Hornos empotrables' AS subcategoria, 'fuente_energia' AS codigo, 'Fuente de energía' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Gas', 'Eléctrica') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Hornos empotrables' AS subcategoria, 'capacidad_l' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'L' AS unidad, NULL AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Hornos empotrables' AS subcategoria, 'ancho_empotre_cm' AS codigo, 'Ancho de empotre' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'cm' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Hornos empotrables' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Campanas extractoras' AS subcategoria, 'tipo_instalacion' AS codigo, 'Tipo de instalación' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Pared', 'Isla', 'Integrable') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Campanas extractoras' AS subcategoria, 'ancho_cm' AS codigo, 'Ancho' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'cm' AS unidad, NULL AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Campanas extractoras' AS subcategoria, 'flujo_aire_m3_h' AS codigo, 'Flujo de aire' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'm³/h' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Campanas extractoras' AS subcategoria, 'niveles_velocidad' AS codigo, 'Niveles de velocidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'niveles' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Lavadoras' AS subcategoria, 'capacidad_kg' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'kg' AS unidad, NULL AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Lavadoras' AS subcategoria, 'tipo_carga' AS codigo, 'Tipo de carga' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Superior', 'Frontal') AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Lavadoras' AS subcategoria, 'centrifugado_rpm' AS codigo, 'Velocidad de centrifugado' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'rpm' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Lavadoras' AS subcategoria, 'inverter' AS codigo, 'Motor inverter' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Lavadoras' AS subcategoria, 'voltaje_v' AS codigo, 'Voltaje' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'V' AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Secadoras' AS subcategoria, 'capacidad_kg' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'kg' AS unidad, NULL AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Secadoras' AS subcategoria, 'fuente_energia' AS codigo, 'Fuente de energía' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Gas', 'Eléctrica') AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Secadoras' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Secadoras' AS subcategoria, 'voltaje_v' AS codigo, 'Voltaje' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'V' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Secadoras' AS subcategoria, 'sensor_humedad' AS codigo, 'Sensor de humedad' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Refrigeradoras' AS subcategoria, 'capacidad_pies3' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pies³' AS unidad, NULL AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Refrigeradoras' AS subcategoria, 'descongelamiento' AS codigo, 'Tipo de descongelamiento' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('No Frost', 'Cíclico', 'Manual') AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Refrigeradoras' AS subcategoria, 'numero_puertas' AS codigo, 'Número de puertas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'puertas' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Refrigeradoras' AS subcategoria, 'inverter' AS codigo, 'Tecnología inverter' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Congeladores' AS subcategoria, 'capacidad_pies3' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pies³' AS unidad, NULL AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Congeladores' AS subcategoria, 'tipo_congelador' AS codigo, 'Tipo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Horizontal', 'Vertical') AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Congeladores' AS subcategoria, 'descongelamiento' AS codigo, 'Tipo de descongelamiento' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('No Frost', 'Manual') AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Congeladores' AS subcategoria, 'voltaje_v' AS codigo, 'Voltaje' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'V' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Aires acondicionados' AS subcategoria, 'capacidad_btu_h' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'BTU/h' AS unidad, NULL AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Aires acondicionados' AS subcategoria, 'formato' AS codigo, 'Formato' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Split', 'Portátil') AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Aires acondicionados' AS subcategoria, 'inverter' AS codigo, 'Tecnología inverter' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Aires acondicionados' AS subcategoria, 'area_recomendada_m2' AS codigo, 'Área recomendada' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'm²' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Aires acondicionados' AS subcategoria, 'voltaje_v' AS codigo, 'Voltaje' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'V' AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Ventiladores' AS subcategoria, 'tipo_ventilador' AS codigo, 'Tipo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Pedestal', 'Techo', 'Mesa') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Ventiladores' AS subcategoria, 'diametro_pulgadas' AS codigo, 'Diámetro' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Ventiladores' AS subcategoria, 'niveles_velocidad' AS codigo, 'Velocidades' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'niveles' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Ventiladores' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Ventiladores' AS subcategoria, 'control_remoto' AS codigo, 'Control remoto' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Preparación de alimentos' AS subcategoria, 'tipo_aparato' AS codigo, 'Tipo de aparato' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Licuadora', 'Procesador', 'Batidora') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Preparación de alimentos' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Preparación de alimentos' AS subcategoria, 'capacidad_l' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'L' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Preparación de alimentos' AS subcategoria, 'niveles_velocidad' AS codigo, 'Velocidades' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'niveles' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Cocción pequeña' AS subcategoria, 'tipo_aparato' AS codigo, 'Tipo de aparato' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Microondas', 'Olla arrocera', 'Freidora de aire', 'Tostadora') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Cocción pequeña' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Cocción pequeña' AS subcategoria, 'capacidad_l' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'L' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Cocción pequeña' AS subcategoria, 'temporizador' AS codigo, 'Temporizador' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Bebidas calientes y jugos' AS subcategoria, 'tipo_aparato' AS codigo, 'Tipo de aparato' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Cafetera', 'Hervidor', 'Exprimidor') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Bebidas calientes y jugos' AS subcategoria, 'capacidad_l' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'L' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Bebidas calientes y jugos' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Bebidas calientes y jugos' AS subcategoria, 'apagado_automatico' AS codigo, 'Apagado automático' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Limpieza y planchado' AS subcategoria, 'tipo_aparato' AS codigo, 'Tipo de aparato' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Aspiradora', 'Plancha') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Limpieza y planchado' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 1 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Limpieza y planchado' AS subcategoria, 'capacidad_deposito_ml' AS codigo, 'Capacidad del depósito' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'ml' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Limpieza y planchado' AS subcategoria, 'funcion_vapor' AS codigo, 'Función de vapor' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Cuidado personal' AS subcategoria, 'tipo_aparato' AS codigo, 'Tipo de aparato' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Secadora de cabello', 'Plancha alisadora', 'Afeitadora') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Cuidado personal' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Cuidado personal' AS subcategoria, 'niveles_temperatura' AS codigo, 'Niveles de temperatura' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'niveles' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Cuidado personal' AS subcategoria, 'inalambrico' AS codigo, 'Inalámbrico/recargable' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Televisores' AS subcategoria, 'pantalla_pulgadas' AS codigo, 'Tamaño de pantalla' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Televisores' AS subcategoria, 'resolucion' AS codigo, 'Resolución' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('HD', 'Full HD', '4K UHD', '8K') AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Televisores' AS subcategoria, 'smart_tv' AS codigo, 'Smart TV' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Televisores' AS subcategoria, 'puertos_hdmi' AS codigo, 'Puertos HDMI' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'puertos' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Televisores' AS subcategoria, 'frecuencia_hz' AS codigo, 'Frecuencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'Hz' AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Audio' AS subcategoria, 'tipo_audio' AS codigo, 'Tipo de equipo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Barra de sonido', 'Parlante', 'Audífonos', 'Minicomponente') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Audio' AS subcategoria, 'potencia_w' AS codigo, 'Potencia de salida' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Audio' AS subcategoria, 'bluetooth' AS codigo, 'Bluetooth' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Audio' AS subcategoria, 'autonomia_h' AS codigo, 'Autonomía' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'h' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Computadoras' AS subcategoria, 'tipo_computadora' AS codigo, 'Tipo de equipo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Laptop', 'Escritorio', 'Monitor') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Computadoras' AS subcategoria, 'procesador' AS codigo, 'Procesador' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Computadoras' AS subcategoria, 'memoria_ram_gb' AS codigo, 'Memoria RAM' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'GB' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Computadoras' AS subcategoria, 'almacenamiento_gb' AS codigo, 'Almacenamiento' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'GB' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Computadoras' AS subcategoria, 'pantalla_pulgadas' AS codigo, 'Tamaño de pantalla' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Impresión' AS subcategoria, 'tecnologia_impresion' AS codigo, 'Tecnología' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Inyección de tinta', 'Láser') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Impresión' AS subcategoria, 'color' AS codigo, 'Impresión a color' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Impresión' AS subcategoria, 'velocidad_ppm' AS codigo, 'Velocidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'páginas/min' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Impresión' AS subcategoria, 'duplex' AS codigo, 'Impresión dúplex' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Impresión' AS subcategoria, 'wifi' AS codigo, 'Conexión Wi-Fi' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Telefonía y tabletas' AS subcategoria, 'tipo_dispositivo' AS codigo, 'Tipo de dispositivo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Teléfono', 'Tableta', 'Reloj inteligente') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Telefonía y tabletas' AS subcategoria, 'pantalla_pulgadas' AS codigo, 'Tamaño de pantalla' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Telefonía y tabletas' AS subcategoria, 'almacenamiento_gb' AS codigo, 'Almacenamiento' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'GB' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Telefonía y tabletas' AS subcategoria, 'memoria_ram_gb' AS codigo, 'Memoria RAM' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'GB' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Telefonía y tabletas' AS subcategoria, 'bateria_mah' AS codigo, 'Capacidad de batería' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'mAh' AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Muebles de sala' AS subcategoria, 'tipo_mueble' AS codigo, 'Tipo de mueble' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Sofá', 'Sillón', 'Mesa de centro', 'Mueble para TV') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Muebles de sala' AS subcategoria, 'numero_plazas' AS codigo, 'Plazas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'plazas' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Muebles de sala' AS subcategoria, 'acabado' AS codigo, 'Acabado' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Muebles de sala' AS subcategoria, 'color' AS codigo, 'Color' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Muebles de comedor' AS subcategoria, 'tipo_mueble' AS codigo, 'Tipo de mueble' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Mesa', 'Silla', 'Trinchante') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Muebles de comedor' AS subcategoria, 'puestos' AS codigo, 'Puestos' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'personas' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Muebles de comedor' AS subcategoria, 'incluye_sillas' AS codigo, 'Incluye sillas' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Muebles de comedor' AS subcategoria, 'forma' AS codigo, 'Forma' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Rectangular', 'Redonda', 'Cuadrada', 'Ovalada') AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Muebles de dormitorio' AS subcategoria, 'tipo_mueble' AS codigo, 'Tipo de mueble' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Cama', 'Colchón', 'Ropero', 'Cómoda', 'Mesa de noche') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Muebles de dormitorio' AS subcategoria, 'tamano' AS codigo, 'Tamaño' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Individual', 'Matrimonial', 'Queen', 'King') AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Muebles de dormitorio' AS subcategoria, 'numero_puertas' AS codigo, 'Puertas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Muebles de dormitorio' AS subcategoria, 'numero_gavetas' AS codigo, 'Gavetas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Muebles de oficina' AS subcategoria, 'tipo_mueble' AS codigo, 'Tipo de mueble' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Escritorio', 'Silla', 'Archivero', 'Estante') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Muebles de oficina' AS subcategoria, 'altura_ajustable' AS codigo, 'Altura ajustable' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Muebles de oficina' AS subcategoria, 'capacidad_carga_kg' AS codigo, 'Capacidad de carga' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'kg' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Muebles de oficina' AS subcategoria, 'numero_gavetas' AS codigo, 'Gavetas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Herramientas eléctricas' AS subcategoria, 'tipo_herramienta' AS codigo, 'Tipo de herramienta' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Taladro', 'Esmeriladora', 'Sierra circular', 'Lijadora') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Herramientas eléctricas' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Herramientas eléctricas' AS subcategoria, 'voltaje_v' AS codigo, 'Voltaje' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'V' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Herramientas eléctricas' AS subcategoria, 'velocidad_rpm' AS codigo, 'Velocidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'rpm' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Herramientas manuales' AS subcategoria, 'tipo_herramienta' AS codigo, 'Tipo de herramienta' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Destornilladores', 'Martillo', 'Llaves', 'Cinta métrica', 'Alicate') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Herramientas manuales' AS subcategoria, 'cantidad_piezas' AS codigo, 'Piezas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Herramientas manuales' AS subcategoria, 'medida_pulgadas' AS codigo, 'Medida' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Herramientas manuales' AS subcategoria, 'material' AS codigo, 'Material' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Plomería' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Tubería', 'Llave de chorro', 'Codo', 'Cinta teflón') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Plomería' AS subcategoria, 'material' AS codigo, 'Material' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Plomería' AS subcategoria, 'diametro_pulgadas' AS codigo, 'Diámetro' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Plomería' AS subcategoria, 'longitud_m' AS codigo, 'Longitud' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'm' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Electricidad' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Cable', 'Breaker', 'Tomacorriente', 'Foco') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Electricidad' AS subcategoria, 'voltaje_v' AS codigo, 'Voltaje' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'V' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Electricidad' AS subcategoria, 'corriente_a' AS codigo, 'Corriente nominal' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'A' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Electricidad' AS subcategoria, 'longitud_m' AS codigo, 'Longitud' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'm' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Electricidad' AS subcategoria, 'calibre' AS codigo, 'Calibre' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Pinturas y acabados' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Pintura', 'Brocha', 'Rodillo', 'Thinner') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Pinturas y acabados' AS subcategoria, 'contenido_gal' AS codigo, 'Contenido' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'gal' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Pinturas y acabados' AS subcategoria, 'acabado' AS codigo, 'Acabado' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Mate', 'Satinado', 'Semibrillante', 'Brillante') AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Pinturas y acabados' AS subcategoria, 'color' AS codigo, 'Color' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Utensilios de cocina' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Ollas', 'Sartenes', 'Tabla de picar') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Utensilios de cocina' AS subcategoria, 'cantidad_piezas' AS codigo, 'Piezas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Utensilios de cocina' AS subcategoria, 'material' AS codigo, 'Material' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Utensilios de cocina' AS subcategoria, 'apto_lavavajillas' AS codigo, 'Apto para lavavajillas' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Mesa y vajilla' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Vajilla', 'Cubiertos', 'Vasos') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Mesa y vajilla' AS subcategoria, 'cantidad_piezas' AS codigo, 'Piezas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Mesa y vajilla' AS subcategoria, 'puestos' AS codigo, 'Puestos' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'personas' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Mesa y vajilla' AS subcategoria, 'material' AS codigo, 'Material' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Mesa y vajilla' AS subcategoria, 'apto_microondas' AS codigo, 'Apto para microondas' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Baño' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Toallas', 'Cortina de baño', 'Cesto para ropa') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Baño' AS subcategoria, 'cantidad_piezas' AS codigo, 'Piezas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Baño' AS subcategoria, 'material' AS codigo, 'Material' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Baño' AS subcategoria, 'color' AS codigo, 'Color' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Ropa de cama' AS subcategoria, 'tamano' AS codigo, 'Tamaño' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Individual', 'Matrimonial', 'Queen', 'King') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Ropa de cama' AS subcategoria, 'cantidad_piezas' AS codigo, 'Piezas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Ropa de cama' AS subcategoria, 'tejido' AS codigo, 'Tejido' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Ropa de cama' AS subcategoria, 'color' AS codigo, 'Color' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Ejercicio en casa' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Bicicleta estática', 'Mancuernas', 'Colchoneta', 'Banda elástica') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Ejercicio en casa' AS subcategoria, 'peso_kg' AS codigo, 'Peso' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'kg' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Ejercicio en casa' AS subcategoria, 'resistencia_niveles' AS codigo, 'Niveles de resistencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'niveles' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Ejercicio en casa' AS subcategoria, 'carga_maxima_kg' AS codigo, 'Carga máxima' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'kg' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Ciclismo' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Bicicleta', 'Casco', 'Candado') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Ciclismo' AS subcategoria, 'aro_pulgadas' AS codigo, 'Aro' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'pulgadas' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Ciclismo' AS subcategoria, 'talla' AS codigo, 'Talla' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Ciclismo' AS subcategoria, 'tipo_freno' AS codigo, 'Tipo de freno' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Disco', 'V-brake', 'No aplica') AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Fútbol' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Balón', 'Canilleras', 'Guantes de portero') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Fútbol' AS subcategoria, 'talla' AS codigo, 'Talla' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Fútbol' AS subcategoria, 'material' AS codigo, 'Material' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Fútbol' AS subcategoria, 'color' AS codigo, 'Color' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Iluminación interior' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Lámpara de techo', 'Lámpara de escritorio', 'Tira LED') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Iluminación interior' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Iluminación interior' AS subcategoria, 'flujo_luminoso_lm' AS codigo, 'Flujo luminoso' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'lm' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Iluminación interior' AS subcategoria, 'temperatura_color_k' AS codigo, 'Temperatura de color' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'K' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Iluminación interior' AS subcategoria, 'regulable' AS codigo, 'Intensidad regulable' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Iluminación exterior' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Reflector', 'Farol solar') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Iluminación exterior' AS subcategoria, 'potencia_w' AS codigo, 'Potencia' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Iluminación exterior' AS subcategoria, 'grado_ip' AS codigo, 'Grado de protección IP' AS nombre, NULL AS ayuda, 'texto' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Iluminación exterior' AS subcategoria, 'solar' AS codigo, 'Alimentación solar' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Iluminación exterior' AS subcategoria, 'sensor_movimiento' AS codigo, 'Sensor de movimiento' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Respaldo de energía' AS subcategoria, 'tipo_equipo' AS codigo, 'Tipo de equipo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('UPS', 'Regulador', 'Planta eléctrica') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Respaldo de energía' AS subcategoria, 'capacidad_va' AS codigo, 'Capacidad' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'VA' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Respaldo de energía' AS subcategoria, 'potencia_w' AS codigo, 'Potencia de salida' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'W' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Respaldo de energía' AS subcategoria, 'autonomia_min' AS codigo, 'Autonomía' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'min' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Respaldo de energía' AS subcategoria, 'combustible' AS codigo, 'Combustible' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Gasolina', 'Diésel', 'No aplica') AS opciones, 0 AS obligatorio, 5 AS orden
UNION ALL
SELECT 'Extensiones y protectores' AS subcategoria, 'tipo_articulo' AS codigo, 'Tipo de artículo' AS nombre, NULL AS ayuda, 'opcion' AS tipo_dato, NULL AS unidad, JSON_ARRAY('Extensión', 'Protector de voltaje', 'Multitoma') AS opciones, 1 AS obligatorio, 1 AS orden
UNION ALL
SELECT 'Extensiones y protectores' AS subcategoria, 'longitud_m' AS codigo, 'Longitud' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'm' AS unidad, NULL AS opciones, 0 AS obligatorio, 2 AS orden
UNION ALL
SELECT 'Extensiones y protectores' AS subcategoria, 'numero_tomas' AS codigo, 'Tomas' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'unidades' AS unidad, NULL AS opciones, 0 AS obligatorio, 3 AS orden
UNION ALL
SELECT 'Extensiones y protectores' AS subcategoria, 'corriente_max_a' AS codigo, 'Corriente máxima' AS nombre, NULL AS ayuda, 'numero' AS tipo_dato, 'A' AS unidad, NULL AS opciones, 0 AS obligatorio, 4 AS orden
UNION ALL
SELECT 'Extensiones y protectores' AS subcategoria, 'proteccion_sobretension' AS codigo, 'Protección contra sobretensión' AS nombre, NULL AS ayuda, 'booleano' AS tipo_dato, NULL AS unidad, NULL AS opciones, 0 AS obligatorio, 5 AS orden
) AS d
INNER JOIN categoria AS c ON c.nombre = d.subcategoria AND c.id_padre IS NOT NULL
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre), ayuda = VALUES(ayuda), tipo_dato = VALUES(tipo_dato),
  unidad = VALUES(unidad), opciones = VALUES(opciones), obligatorio = VALUES(obligatorio),
  orden = VALUES(orden), estado = 'activo';

-- Almohadas se describen por tamaño, relleno y firmeza; no por dimensiones generales.
INSERT INTO atributo_categoria
  (id_categoria, codigo, nombre, ayuda, tipo_dato, unidad, opciones, obligatorio, orden, estado)
SELECT id_categoria, 'tamano', 'Tamaño', 'Tamaño comercial de la almohada.', 'opcion', NULL,
       JSON_ARRAY('Estándar', 'Queen', 'King', 'Personalizado'), 0, 4, 'activo'
FROM categoria
WHERE nombre = 'Almohadas' AND id_padre IS NOT NULL
ON DUPLICATE KEY UPDATE
  nombre = VALUES(nombre), ayuda = VALUES(ayuda), tipo_dato = VALUES(tipo_dato),
  unidad = VALUES(unidad), opciones = VALUES(opciones), obligatorio = VALUES(obligatorio),
  orden = VALUES(orden), estado = 'activo';

-- Las filas siguientes ocultan los campos generales duplicados o poco útiles.
-- Las demás subcategorías muestran dimensiones, peso y material de forma predeterminada.
INSERT INTO categoria_campo_base
  (id_categoria, mostrar_alto_cm, mostrar_ancho_cm, mostrar_profundidad_cm, mostrar_peso_kg, mostrar_material)
SELECT c.id_categoria, d.mostrar_alto_cm, d.mostrar_ancho_cm, d.mostrar_profundidad_cm, d.mostrar_peso_kg, d.mostrar_material
FROM (
  SELECT 'Almohadas' AS subcategoria, 0 AS mostrar_alto_cm, 0 AS mostrar_ancho_cm, 0 AS mostrar_profundidad_cm, 0 AS mostrar_peso_kg, 0 AS mostrar_material
  UNION ALL SELECT 'Textiles de descanso', 0, 0, 0, 0, 0
  UNION ALL SELECT 'Cuidado personal', 0, 0, 0, 0, 0
  UNION ALL SELECT 'Telefonía y tabletas', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Audio', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Computadoras', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Televisores', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Impresión', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Herramientas manuales', 0, 0, 0, 1, 0
  UNION ALL SELECT 'Plomería', 0, 0, 0, 1, 0
  UNION ALL SELECT 'Electricidad', 0, 0, 0, 1, 0
  UNION ALL SELECT 'Pinturas y acabados', 0, 0, 0, 1, 0
  UNION ALL SELECT 'Utensilios de cocina', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Mesa y vajilla', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Baño', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Ropa de cama', 0, 0, 0, 0, 0
  UNION ALL SELECT 'Fútbol', 0, 0, 0, 1, 0
  UNION ALL SELECT 'Iluminación interior', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Iluminación exterior', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Respaldo de energía', 1, 1, 1, 1, 0
  UNION ALL SELECT 'Extensiones y protectores', 0, 0, 0, 1, 0
) AS d
INNER JOIN categoria AS c ON c.nombre = d.subcategoria AND c.id_padre IS NOT NULL
ON DUPLICATE KEY UPDATE
  mostrar_alto_cm = VALUES(mostrar_alto_cm),
  mostrar_ancho_cm = VALUES(mostrar_ancho_cm),
  mostrar_profundidad_cm = VALUES(mostrar_profundidad_cm),
  mostrar_peso_kg = VALUES(mostrar_peso_kg),
  mostrar_material = VALUES(mostrar_material);

