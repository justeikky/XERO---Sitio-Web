<?php
session_start();
require_once __DIR__ . '/../php/conexion.php';

if (empty($_SESSION['registro_csrf'])) {
    $_SESSION['registro_csrf'] = bin2hex(random_bytes(32));
}

$errores = [];
$nombre = '';
$apellidos = '';
$telefono = '';
$correo = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $contrasena = $_POST['contrasena'] ?? '';
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    $nombreCompleto = trim($nombre . ' ' . $apellidos);

    if (!is_string($tokenEnviado) || !hash_equals($_SESSION['registro_csrf'], $tokenEnviado)) {
        $errores[] = 'La sesión del formulario expiró. Actualiza la página e inténtalo de nuevo.';
    } elseif ($nombre === '' || $apellidos === '') {
        $errores[] = 'Escribe tu nombre y tus apellidos.';
    } elseif (mb_strlen($nombreCompleto, 'UTF-8') > 80) {
        $errores[] = 'El nombre completo no puede superar los 80 caracteres.';
    } elseif ($telefono === '' || mb_strlen($telefono, 'UTF-8') > 30) {
        $errores[] = 'Escribe un teléfono de hasta 30 caracteres.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo, 'UTF-8') > 100) {
        $errores[] = 'Escribe un correo electrónico válido de hasta 100 caracteres.';
    } elseif (!is_string($contrasena) || strlen($contrasena) < 8 || strlen($contrasena) > 72) {
        $errores[] = 'La contraseña debe tener entre 8 y 72 caracteres.';
    } else {
        try {
            $buscarCorreo = $conexion->prepare('SELECT id_usuario FROM usuario WHERE correo = ? LIMIT 1');
            $buscarCorreo->bind_param('s', $correo);
            $buscarCorreo->execute();

            if ($buscarCorreo->get_result()->num_rows > 0) {
                $errores[] = 'Ya existe una cuenta con ese correo electrónico.';
            } else {
                $contrasenaHash = password_hash($contrasena, PASSWORD_DEFAULT);
                $rol = 'Usuario';
                $guardarUsuario = $conexion->prepare('INSERT INTO usuario (nombre, correo, telefono, contrasena, rol) VALUES (?, ?, ?, ?, ?)');
                $guardarUsuario->bind_param('sssss', $nombreCompleto, $correo, $telefono, $contrasenaHash, $rol);
                $guardarUsuario->execute();
                $idUsuario = (int) $conexion->insert_id;

                $guardarPreferencias = $conexion->prepare(
                    'INSERT INTO configuracion_usuario (id_usuario, idioma, moneda, notificaciones_inventario, alertas_correo)
                     VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE idioma = VALUES(idioma), moneda = VALUES(moneda), notificaciones_inventario = VALUES(notificaciones_inventario), alertas_correo = VALUES(alertas_correo)'
                );
                $guardarPreferencias->bind_param('issii', $idUsuario, $idioma = 'es', $moneda = 'usd', $notificaciones = 0, $alertas = 0);
                $guardarPreferencias->execute();

                $_SESSION = [];
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = $idUsuario;
                $_SESSION['nombre_usuario'] = $nombreCompleto;
                $_SESSION['correo_usuario'] = $correo;
                $_SESSION['rol_usuario'] = $rol;

                unset($_SESSION['registro_csrf']);
                header('Location: inicio.php');
                exit;
            }
        } catch (mysqli_sql_exception $excepcion) {
            $errores[] = 'No se pudo crear la cuenta. Revisa tus datos e inténtalo de nuevo.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - XERO</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body class="auth-page login-page">
    <main class="login-shell">
        <header class="login-header">
            <div class="login-brand">
                <div class="login-mark"><img src="../img/logo-xero.png" alt="Logo de XERO"></div>
                <div>
                    <span class="login-eyebrow">Sistema de gestión</span>
                    <h1>XERO</h1>
                    <p>Control inteligente de inventario</p>
                </div>
            </div>
        </header>

        <section class="login-content">
            <div class="login-welcome">
                <h2>Crear cuenta</h2>
                <p>Registra tu negocio y empieza a gestionar inventario con claridad, seguimiento y control.</p>
            </div>

            <div class="login-form-wrap">
                <?php if ($errores): ?>
                    <div class="register-error" role="alert">
                        <?php foreach ($errores as $error): ?>
                            <p><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="registro.php" class="login-form register-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['registro_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input id="nombre" name="nombre" type="text" placeholder="Tu nombre" value="<?php echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'); ?>" maxlength="80" autocomplete="given-name" required>
                        </div>
                        <div class="form-group">
                            <label for="apellidos">Apellidos</label>
                            <input id="apellidos" name="apellidos" type="text" placeholder="Tus apellidos" value="<?php echo htmlspecialchars($apellidos, ENT_QUOTES, 'UTF-8'); ?>" maxlength="80" autocomplete="family-name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input id="telefono" name="telefono" type="tel" placeholder="Tu número de teléfono" value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>" maxlength="30" autocomplete="tel" required>
                    </div>

                    <div class="form-group">
                        <label for="correo">Correo electrónico</label>
                        <input id="correo" name="correo" type="email" placeholder="ejemplo@correo.com" value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="contrasena">Contraseña</label>
                        <input id="contrasena" name="contrasena" type="password" placeholder="Crea una contraseña" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>

                    <button type="submit" class="btn-auth">Registrarse</button>
                </form>
            </div>
        </section>

        <div class="login-footer">
            <p>¿Ya tienes una cuenta? <a href="sesion.php">Inicia sesión</a></p>
        </div>
    </main>

    <?php include __DIR__ . '/../php/footer.php'; ?>
</body>
</html>