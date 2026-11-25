<?php
require_once __DIR__ . '/autenticacion/seguridad.php';
requerir_permiso('panel.ver');
require_once __DIR__ . '/config.php';

$titulo_pagina = 'Inicio';
$contrasenaActualizada = ($_GET['contrasena'] ?? '') === 'actualizada';
$ventasHoy = null;
$comprasHoy = null;
$productosActivos = null;
$stockBajo = null;

$zonaHoraria = new DateTimeZone('America/El_Salvador');
$inicioHoy = new DateTimeImmutable('today', $zonaHoraria);
$inicioManana = $inicioHoy->modify('+1 day');
$fechaInicio = $inicioHoy->format('Y-m-d');
$fechaFin = $inicioManana->format('Y-m-d');

try {
    require_once __DIR__ . '/conexion.php';

    if (tiene_permiso('ventas.consultar')) {
        $stmt = $conexion->prepare(
            "SELECT COUNT(*) AS cantidad, IFNULL(SUM(total), 0) AS monto
             FROM venta
             WHERE fecha_hora >= ? AND fecha_hora < ? AND estado <> 'anulado'"
        );
        $stmt->bind_param('ss', $fechaInicio, $fechaFin);
        $stmt->execute();
        $ventasHoy = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (tiene_permiso('compras.consultar')) {
        $stmt = $conexion->prepare(
            "SELECT COUNT(*) AS cantidad, IFNULL(SUM(total), 0) AS monto
             FROM compra
             WHERE fecha >= ? AND fecha < ? AND estado = 'registrada'"
        );
        $stmt->bind_param('ss', $fechaInicio, $fechaFin);
        $stmt->execute();
        $comprasHoy = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (tiene_permiso('productos.consultar')) {
        $resultado = $conexion->query("SELECT COUNT(*) AS cantidad FROM producto WHERE estado = 'activo'");
        $productosActivos = $resultado->fetch_assoc()['cantidad'] ?? 0;
        $resultado->free();
    }

    if (tiene_permiso('inventario.consultar')) {
        $resultado = $conexion->query(
            "SELECT COUNT(*) AS cantidad
             FROM producto AS p
             LEFT JOIN inventario AS i ON i.id_producto = p.id_producto
             WHERE p.estado = 'activo'
               AND IFNULL(i.existencia, 0) <= p.stock_minimo"
        );
        $stockBajo = $resultado->fetch_assoc()['cantidad'] ?? 0;
        $resultado->free();
    }

    $conexion->close();
} catch (Throwable $error) {
    error_log('Panel principal: ' . $error->getMessage());
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($titulo_pagina, ENT_QUOTES, 'UTF-8'); ?> - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/vendors/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/inicio.css?v=<?php echo filemtime(__DIR__ . '/css/inicio.css'); ?>">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" crossorigin="anonymous">
</head>
<body>
  <?php include __DIR__ . '/Barra_Lateral.php'; ?>

  <div class="body flex-grow-1 px-3">
    <main class="container-lg inicio-panel">
      <?php if ($contrasenaActualizada): ?>
        <div class="alert alert-success" role="status">Tu contraseña se actualizó correctamente.</div>
      <?php endif; ?>

      <header class="inicio-cabecera">
        <div>
          <p class="inicio-etiqueta">CASA AURELIA</p>
          <h1>Resumen del día</h1>
        </div>
        <span class="inicio-fecha">Hoy · <?php echo date('d/m/Y'); ?></span>
      </header>

      <section class="inicio-resumen" aria-label="Resumen">
        <?php if ($ventasHoy !== null): ?>
          <article class="inicio-tarjeta">
            <span class="inicio-icono"><i class="bi bi-receipt"></i></span>
            <div><p>Ventas de hoy</p><strong><?php echo (int) $ventasHoy['cantidad']; ?></strong><small>$<?php echo number_format((float) $ventasHoy['monto'], 2); ?></small></div>
          </article>
        <?php endif; ?>

        <?php if ($comprasHoy !== null): ?>
          <article class="inicio-tarjeta">
            <span class="inicio-icono"><i class="bi bi-bag"></i></span>
            <div><p>Compras de hoy</p><strong><?php echo (int) $comprasHoy['cantidad']; ?></strong><small>$<?php echo number_format((float) $comprasHoy['monto'], 2); ?></small></div>
          </article>
        <?php endif; ?>

        <?php if ($productosActivos !== null): ?>
          <article class="inicio-tarjeta">
            <span class="inicio-icono"><i class="bi bi-box-seam"></i></span>
            <div><p>Productos activos</p><strong><?php echo (int) $productosActivos; ?></strong><small>En el catálogo</small></div>
          </article>
        <?php endif; ?>

        <?php if ($stockBajo !== null): ?>
          <article class="inicio-tarjeta">
            <span class="inicio-icono"><i class="bi bi-exclamation-circle"></i></span>
            <div><p>Existencias bajas</p><strong><?php echo (int) $stockBajo; ?></strong><small>En el mínimo o menos</small></div>
          </article>
        <?php endif; ?>
      </section>

    </main>
  </div>

  <footer class="footer px-4"><div>Comercial X &copy; 2026.</div></footer>
  </div>

  <script src="<?php echo BASE_URL; ?>vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?php echo BASE_URL; ?>vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>
