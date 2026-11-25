<?php
declare(strict_types=1);

require_once __DIR__ . '/seguridad.php';
requerir_permiso('perfil.contrasena.cambiar', false, true);
require_once __DIR__ . '/../conexion.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validar_origen_solicitud() || !validar_token_csrf($_POST['token_csrf'] ?? null)) {
        $error = 'La solicitud venció o no es válida. Recarga la página e intenta de nuevo.';
    } else {
        $actual = (string) ($_POST['contrasena_actual'] ?? '');
        $nueva = (string) ($_POST['contrasena_nueva'] ?? '');
        $confirmacion = (string) ($_POST['confirmacion'] ?? '');

        if ($actual === '' || $nueva === '' || $confirmacion === '') {
            $error = 'Completa todos los campos.';
        } elseif ((function_exists('mb_strlen') ? mb_strlen($nueva, 'UTF-8') : strlen($nueva)) < 12 || strlen($nueva) > 72) {
            $error = 'La nueva contraseña debe tener al menos 12 caracteres y no superar 72 bytes.';
        } elseif (!hash_equals($nueva, $confirmacion)) {
            $error = 'La nueva contraseña y su confirmación no coinciden.';
        } elseif (hash_equals($actual, $nueva)) {
            $error = 'La nueva contraseña debe ser distinta de la actual.';
        } else {
            $idUsuario = (int) $_SESSION['id_usuario'];
            $stmt = $conexion->prepare('SELECT contrasena FROM usuario WHERE id_usuario = ? AND estado = \'activo\' LIMIT 1');
            $stmt->bind_param('i', $idUsuario);
            $stmt->execute();
            $cuenta = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$cuenta || !password_verify($actual, (string) $cuenta['contrasena'])) {
                $error = 'La contraseña actual no es correcta.';
            } else {
                $hash = crear_hash_contrasena($nueva);
                $stmt = $conexion->prepare('UPDATE usuario SET contrasena = ?, debe_cambiar_contrasena = 0 WHERE id_usuario = ?');
                $stmt->bind_param('si', $hash, $idUsuario);
                $stmt->execute();
                $stmt->close();

                session_regenerate_id(true);
                $_SESSION['debe_cambiar_contrasena'] = false;
                $_SESSION['token_csrf'] = bin2hex(random_bytes(32));
                header('Location: ' . BASE_URL . 'index.php?contrasena=actualizada');
                exit;
            }
        }
    }
}

$forzado = !empty($_SESSION['debe_cambiar_contrasena']);
$tokenCsrf = obtener_token_csrf();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cambiar contraseña - Comercial X</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>vendors/simplebar/css/simplebar.css">
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/style.css">
  <script src="<?php echo BASE_URL; ?>js/config.js"></script>
  <script src="<?php echo BASE_URL; ?>js/color-modes.js"></script>
</head>
<body>
  <div class="bg-body-tertiary min-vh-100 d-flex align-items-center py-4">
    <div class="container" style="max-width: 34rem">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h1 class="h4 mb-3">Cambiar contraseña</h1>
          <?php if ($forzado): ?>
            <div class="alert alert-warning">Estás usando una contraseña temporal. Cámbiala para continuar al sistema.</div>
          <?php else: ?>
            <p class="text-body-secondary">Usa este formulario para actualizar la contraseña de tu cuenta.</p>
          <?php endif; ?>
          <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
          <form method="post" autocomplete="off">
            <input type="hidden" name="token_csrf" value="<?php echo htmlspecialchars($tokenCsrf, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
            <div class="mb-3"><label class="form-label" for="contrasenaActual">Contraseña actual</label><input class="form-control" id="contrasenaActual" name="contrasena_actual" type="password" autocomplete="off" required></div>
            <div class="mb-3"><label class="form-label" for="contrasenaNueva">Nueva contraseña</label><input class="form-control" id="contrasenaNueva" name="contrasena_nueva" type="password" minlength="12" maxlength="72" autocomplete="off" required><div class="form-text">Debe tener al menos 12 caracteres y no superar 72 bytes.</div></div>
            <div class="mb-4"><label class="form-label" for="confirmacion">Confirma la nueva contraseña</label><input class="form-control" id="confirmacion" name="confirmacion" type="password" minlength="12" maxlength="72" autocomplete="off" required></div>
            <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
          </form>
          <?php if (!$forzado): ?><a class="btn btn-link w-100 mt-2" href="<?php echo BASE_URL; ?>index.php">Volver al sistema</a><?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
