-- Ejecutar una sola vez en la base sys_comercial.
-- La tabla categoria ya debe contener las categorías y subcategorías.
USE sys_comercial;

-- Define qué campos debe mostrar el formulario para cada subcategoría.
CREATE TABLE IF NOT EXISTS atributo_categoria (
    id_atributo INT UNSIGNED NOT NULL AUTO_INCREMENT,
    id_categoria INT UNSIGNED NOT NULL,
    codigo VARCHAR(60) NOT NULL COMMENT 'Identificador estable, por ejemplo capacidad_kg o potencia_w',
    nombre VARCHAR(100) NOT NULL COMMENT 'Etiqueta visible en el formulario',
    ayuda VARCHAR(255) NULL COMMENT 'Texto de ayuda opcional para el usuario',
    tipo_dato ENUM('texto', 'numero', 'booleano', 'opcion') NOT NULL DEFAULT 'texto',
    unidad VARCHAR(30) NULL COMMENT 'Unidad para valores numéricos, por ejemplo kg, W, L o BTU/h',
    opciones JSON NULL COMMENT 'Lista JSON para tipo_dato=opcion, por ejemplo ["Gas", "Eléctrica"]',
    obligatorio TINYINT(1) NOT NULL DEFAULT 0,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo',
    PRIMARY KEY (id_atributo),
    UNIQUE KEY uq_atributo_categoria_codigo (id_categoria, codigo),
    KEY ix_atributo_categoria_formulario (id_categoria, estado, orden),
    CONSTRAINT fk_atributo_categoria_categoria
        FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Guarda el valor de cada atributo para cada producto.
CREATE TABLE IF NOT EXISTS producto_especificacion (
    id_producto INT UNSIGNED NOT NULL,
    id_atributo INT UNSIGNED NOT NULL,
    valor VARCHAR(500) NOT NULL,
    actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id_producto, id_atributo),
    KEY ix_producto_especificacion_atributo (id_atributo),
    CONSTRAINT fk_producto_especificacion_producto
        FOREIGN KEY (id_producto) REFERENCES producto (id_producto)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_producto_especificacion_atributo
        FOREIGN KEY (id_atributo) REFERENCES atributo_categoria (id_atributo)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indica qué campos generales del producto aplican en cada subcategoría.
-- Si no hay una fila para una categoría nueva, la aplicación los muestra todos.
CREATE TABLE IF NOT EXISTS categoria_campo_base (
    id_categoria INT UNSIGNED NOT NULL,
    mostrar_alto_cm TINYINT(1) NOT NULL DEFAULT 1,
    mostrar_ancho_cm TINYINT(1) NOT NULL DEFAULT 1,
    mostrar_profundidad_cm TINYINT(1) NOT NULL DEFAULT 1,
    mostrar_peso_kg TINYINT(1) NOT NULL DEFAULT 1,
    mostrar_material TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id_categoria),
    CONSTRAINT fk_categoria_campo_base_categoria
        FOREIGN KEY (id_categoria) REFERENCES categoria (id_categoria)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ejemplos de atributos para agregar después de crear la subcategoría Lavadoras.
-- INSERT INTO atributo_categoria
--     (id_categoria, codigo, nombre, tipo_dato, unidad, opciones, obligatorio, orden)
-- SELECT id_categoria, 'capacidad_kg', 'Capacidad', 'numero', 'kg', NULL, 1, 1
-- FROM categoria WHERE nombre = 'Lavadoras';
--
-- INSERT INTO atributo_categoria
--     (id_categoria, codigo, nombre, tipo_dato, unidad, opciones, obligatorio, orden)
-- SELECT id_categoria, 'tipo_carga', 'Tipo de carga', 'opcion', NULL,
--        JSON_ARRAY('Carga superior', 'Carga frontal'), 1, 2
-- FROM categoria WHERE nombre = 'Lavadoras';
--
-- INSERT INTO atributo_categoria
--     (id_categoria, codigo, nombre, tipo_dato, unidad, opciones, obligatorio, orden)
-- SELECT id_categoria, 'potencia_w', 'Potencia', 'numero', 'W', NULL, 1, 1
-- FROM categoria WHERE nombre = 'Secadoras';
--
-- Al guardar un producto, el formulario inserta un valor por atributo en
-- producto_especificacion. El backend debe validar que el atributo pertenezca
-- a la misma subcategoría que producto.id_categoria y validar el valor según
-- atributo_categoria.tipo_dato.
