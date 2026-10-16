<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../php/autenticacion.php';
$usuarioId = usuarioAutenticadoId();
require_once __DIR__ . '/../php/conexion.php';
require_once __DIR__ . '/../php/actividad.php';

$usuarioConsulta = $conexion->prepare('SELECT nombre, correo, telefono, rol, avatar FROM usuario WHERE id_usuario = ?');
$usuarioConsulta->bind_param('i', $usuarioId);
$usuarioConsulta->execute();
$usuario = $usuarioConsulta->get_result()->fetch_assoc();
if (!$usuario) {
    unset($_SESSION['id_usuario'], $_SESSION['nombre_usuario'], $_SESSION['correo_usuario'], $_SESSION['rol_usuario']);
    session_regenerate_id(true);
    header('Location: sesion.php?sesion_expirada=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Configuración</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

    <?php
    $mensaje = '';
    $error = '';

    $preferenciasConsulta = $conexion->prepare('SELECT idioma, moneda, notificaciones_inventario, alertas_correo FROM configuracion_usuario WHERE id_usuario = ?');
    $preferenciasConsulta->bind_param('i', $usuarioId);
    $preferenciasConsulta->execute();
    $preferencias = $preferenciasConsulta->get_result()->fetch_assoc() ?: [
        'idioma' => 'es',
        'moneda' => 'usd',
        'notificaciones_inventario' => 0,
        'alertas_correo' => 0,
    ];

    $crearPreferencias = $conexion->prepare(
        'INSERT INTO configuracion_usuario (id_usuario, idioma, moneda, notificaciones_inventario, alertas_correo)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE idioma = VALUES(idioma), moneda = VALUES(moneda), notificaciones_inventario = VALUES(notificaciones_inventario), alertas_correo = VALUES(alertas_correo)'
    );
    $crearPreferencias->bind_param('issii', $usuarioId, $preferencias['idioma'], $preferencias['moneda'], $preferencias['notificaciones_inventario'], $preferencias['alertas_correo']);
    $crearPreferencias->execute();

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'perfil') {
        $nombre = trim($_POST['nombre'] ?? '');
        $correo = trim($_POST['correo'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');
        if ($nombre === '' || mb_strlen($nombre) > 80) {
            $error = 'El nombre es obligatorio y debe tener máximo 80 caracteres.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo) > 100) {
            $error = 'Escribe un correo electrónico válido.';
        } elseif ($telefono !== '' && mb_strlen($telefono) > 30) {
            $error = 'El teléfono debe tener máximo 30 caracteres.';
        } else {
            $guardarPerfil = $conexion->prepare('UPDATE usuario SET nombre = ?, correo = ?, telefono = ? WHERE id_usuario = ?');
            $guardarPerfil->bind_param('sssi', $nombre, $correo, $telefono, $usuarioId);
            if ($guardarPerfil->execute()) {
                $usuario['nombre'] = $nombre;
                $usuario['correo'] = $correo;
                $usuario['telefono'] = $telefono;
                registrarActividad($conexion, 'Actualizó', 'Perfil', 'Actualizó sus datos de perfil.', $usuarioId);
                $mensaje = 'Los datos del perfil se guardaron correctamente.';
            } else {
                $error = 'No se pudieron guardar los datos del perfil.';
            }
        }
    } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'preferencias') {
        $idioma = in_array($_POST['idioma'] ?? '', ['es', 'en'], true) ? $_POST['idioma'] : 'es';
        $moneda = in_array($_POST['moneda'] ?? '', ['usd', 'eur', 'cop'], true) ? $_POST['moneda'] : 'usd';
        $notificaciones = isset($_POST['notificaciones_inventario']) ? 1 : 0;
        $alertasCorreo = isset($_POST['alertas_correo']) ? 1 : 0;
        $guardarPreferencias = $conexion->prepare(
            'INSERT INTO configuracion_usuario (id_usuario, idioma, moneda, notificaciones_inventario, alertas_correo)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE idioma = VALUES(idioma), moneda = VALUES(moneda), notificaciones_inventario = VALUES(notificaciones_inventario), alertas_correo = VALUES(alertas_correo)'
        );
        $guardarPreferencias->bind_param('issii', $usuarioId, $idioma, $moneda, $notificaciones, $alertasCorreo);
        if ($guardarPreferencias->execute()) {
            $preferencias = ['idioma' => $idioma, 'moneda' => $moneda, 'notificaciones_inventario' => $notificaciones, 'alertas_correo' => $alertasCorreo];
            $mensaje = 'Las preferencias se guardaron correctamente.';
            registrarActividad($conexion, 'Actualizó', 'Preferencias', 'Actualizó sus preferencias del sistema.', $usuarioId);
        } else {
            $error = 'No se pudieron guardar las preferencias.';
        }
    } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_cuenta') {
        $movimientosConsulta = $conexion->prepare('SELECT (SELECT COUNT(*) FROM inv_entrada e INNER JOIN productos p ON p.id_producto = e.id_producto WHERE p.id_usuario = ?) + (SELECT COUNT(*) FROM inv_salida s INNER JOIN productos p ON p.id_producto = s.id_producto WHERE p.id_usuario = ?) AS total');
        $movimientosConsulta->bind_param('ii', $usuarioId, $usuarioId);
        $movimientosConsulta->execute();
        $totalMovimientos = (int) $movimientosConsulta->get_result()->fetch_assoc()['total'];

        if ($totalMovimientos > 0) {
            $error = 'No se puede eliminar la cuenta porque tiene movimientos de inventario asociados.';
        } else {
            $rutaAvatar = !empty($usuario['avatar']) ? __DIR__ . '/../' . $usuario['avatar'] : '';
            $conexion->begin_transaction();
            foreach (['productos', 'categorias', 'proveedores', 'tiendas', 'actividad', 'configuracion_usuario'] as $tablaUsuario) {
                $eliminarDatos = $conexion->prepare("DELETE FROM {$tablaUsuario} WHERE id_usuario = ?");
                $eliminarDatos->bind_param('i', $usuarioId);
                $eliminarDatos->execute();
            }
            $eliminarCuenta = $conexion->prepare('DELETE FROM usuario WHERE id_usuario = ?');
            $eliminarCuenta->bind_param('i', $usuarioId);
            if (!$eliminarCuenta->execute() || $eliminarCuenta->affected_rows === 0) {
                $conexion->rollback();
                $error = 'No se pudo eliminar la cuenta.';
            } else {
                $conexion->commit();
                if ($rutaAvatar !== '' && is_file($rutaAvatar)) {
                    @unlink($rutaAvatar);
                }
                $_SESSION = [];
                session_destroy();
                header('Location: sesion.php?cuenta_eliminada=1');
                exit;
            }
        }
    } elseif (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_FILES['avatar'])) {
        $archivo = $_FILES['avatar'];
        $tiposPermitidos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $error = 'No se pudo subir la imagen. Intenta nuevamente.';
        } elseif ($archivo['size'] > 2 * 1024 * 1024) {
            $error = 'La imagen no puede superar los 2 MB.';
        } else {
            $tipoArchivo = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
            if (!isset($tiposPermitidos[$tipoArchivo])) {
                $error = 'Solo se permiten imágenes JPG o PNG.';
            } else {
                $nombreArchivo = 'usuario-' . $usuarioId . '-' . bin2hex(random_bytes(8)) . '.' . $tiposPermitidos[$tipoArchivo];
                $rutaAbsoluta = __DIR__ . '/../img/avatars/' . $nombreArchivo;
                $rutaBaseDatos = 'img/avatars/' . $nombreArchivo;

                if (!move_uploaded_file($archivo['tmp_name'], $rutaAbsoluta)) {
                    $error = 'No se pudo guardar la imagen en el servidor.';
                } else {
                    $guardarAvatar = $conexion->prepare('UPDATE usuario SET avatar = ? WHERE id_usuario = ?');
                    $guardarAvatar->bind_param('si', $rutaBaseDatos, $usuarioId);
                    if ($guardarAvatar->execute()) {
                        $usuario['avatar'] = $rutaBaseDatos;
                        $mensaje = 'La imagen de perfil se actualizó correctamente.';
                        registrarActividad($conexion, 'Actualizó', 'Avatar', 'Actualizó su imagen de perfil.');
                    } else {
                        @unlink($rutaAbsoluta);
                        $error = 'No se pudo guardar la ruta del avatar en la base de datos.';
                    }
                }
            }
        }
    }
    ?>

    <?php include __DIR__ . '/../php/menu.php'; ?>

    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="page-header">
            <h2>Configuración del Sistema</h2>
        </div>

        <!-- CONTENEDOR DE AJUSTES -->
        <div class="settings-container">
            
            <!-- PERFIL DE USUARIO -->
            <div class="settings-card">
                <div class="profile-header-panel">
                    <div class="profile-header-copy">
                        <span class="profile-header-kicker">Cuenta</span>
                        <h3>Perfil de Usuario</h3>
                        <p>Actualiza tu foto, datos personales y credenciales de acceso.</p>
                    </div>
                    <div class="profile-header-icon" aria-label="Foto de perfil">
                        <span>📷</span>
                    </div>
                </div>
                <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
                <form class="profile-upload" method="post" enctype="multipart/form-data">
                    <div class="profile-image-wrap">
                        <?php
                        $avatarPerfil = $usuario['avatar'] ?? '';
                        if ($avatarPerfil === '' || !is_file(__DIR__ . '/../' . $avatarPerfil)) {
                            $avatarPerfil = 'img/logo-xero.png';
                        }
                        ?>
                        <img src="../<?php echo htmlspecialchars($avatarPerfil, ENT_QUOTES, 'UTF-8'); ?>" alt="Avatar de <?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?>" class="profile-avatar" id="avatar-preview">
                    </div>
                    <div class="profile-upload-btn">
                        <label for="avatar-input" class="btn-secondary">Cambiar imagen</label>
                        <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png" hidden required>
                        <span class="upload-hint">JPG o PNG. Máximo 2MB.</span>
                    </div>
                    <button type="submit" class="btn-save">Guardar imagen</button>
                </form>
                <form class="settings-form" method="post">
                    <input type="hidden" name="accion" value="perfil">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nombre Completo</label>
                            <input type="text" name="nombre" maxlength="80" value="<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="correo" maxlength="100" value="<?php echo htmlspecialchars($usuario['correo'], ENT_QUOTES, 'UTF-8'); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Rol de Usuario</label>
                            <input type="text" value="<?php echo htmlspecialchars($usuario['rol'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" maxlength="30" value="<?php echo htmlspecialchars($usuario['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        </div>
                    </div>
                    <div class="form-buttons">
                        <button type="submit" class="btn-save">Guardar Cambios</button>
                    </div>
                </form>
            </div>

            <!-- PREFERENCIAS DEL SISTEMA -->
            <div class="settings-card">
                <h3>Preferencias Generales</h3>
                <form class="settings-form" method="post">
                    <input type="hidden" name="accion" value="preferencias">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Idioma del Sistema</label>
                            <select id="idioma-sistema" name="idioma">
                                <option value="es" <?php echo ($preferencias['idioma'] ?? 'es') === 'es' ? 'selected' : ''; ?>>Español</option>
                                <option value="en" <?php echo ($preferencias['idioma'] ?? 'es') === 'en' ? 'selected' : ''; ?>>Inglés</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Moneda por Defecto</label>
                            <select name="moneda">
                                <option value="usd" <?php echo ($preferencias['moneda'] ?? 'usd') === 'usd' ? 'selected' : ''; ?>>USD ($)</option>
                                <option value="eur" <?php echo ($preferencias['moneda'] ?? 'usd') === 'eur' ? 'selected' : ''; ?>>EUR (€)</option>
                                <option value="cop" <?php echo ($preferencias['moneda'] ?? 'usd') === 'cop' ? 'selected' : ''; ?>>COP ($)</option>
                            </select>
                        </div>
                    </div>

                    <div class="setting-toggle">
                        <div>
                            <h4>Notificaciones de Inventario</h4>
                            <p>Recibir alertas cuando un producto tenga stock bajo (menos de 5 unidades).</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="notificaciones_inventario" <?php echo !empty($preferencias['notificaciones_inventario']) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="setting-toggle">
                        <div>
                            <h4>Alertas por Correo</h4>
                            <p>Enviar un resumen diario de las ventas e inventario al correo.</p>
                        </div>
                        <label class="switch">
                            <input type="checkbox" name="alertas_correo" <?php echo !empty($preferencias['alertas_correo']) ? 'checked' : ''; ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="form-buttons">
                        <button type="submit" class="btn-save">Guardar Preferencias</button>
                    </div>
                </form>
            </div>

            <div class="settings-card danger-zone">
                <h3>Última instancia</h3>
                <p>Eliminar tu cuenta borrará tus preferencias y tu perfil. Esta acción no se puede deshacer.</p>
                <form method="post" onsubmit="return confirm('¿Estás seguro de que quieres eliminar tu cuenta? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="accion" value="eliminar_cuenta">
                    <button type="submit" class="btn-delete">Eliminar cuenta</button>
                </form>
            </div>

        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>

</body>
</html>