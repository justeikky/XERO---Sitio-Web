<?php
session_start();
require_once __DIR__ . '/../php/conexion.php';

if (isset($_GET['cerrar_sesion']) && $_GET['cerrar_sesion'] === '1') {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    session_start();
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
    header('Location: sesion.php?cerrada=1');
    exit;
}

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

$errorLogin = '';
$mensajeSesion = '';
if (($_GET['sesion_expirada'] ?? '') === '1') {
    $mensajeSesion = 'Tu sesión anterior ya no está activa. Inicia sesión de nuevo.';
} elseif (($_GET['cerrada'] ?? '') === '1') {
    $mensajeSesion = 'Sesión cerrada correctamente. Inicia sesión de nuevo.';
}
$telefono = '';
$correo = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = strtolower(trim($_POST['correo'] ?? ''));
    $contrasena = $_POST['contrasena'] ?? '';
    $tokenEnviado = $_POST['csrf_token'] ?? '';

    if (!is_string($tokenEnviado) || !hash_equals($_SESSION['login_csrf'], $tokenEnviado)) {
        $errorLogin = 'La sesión del formulario expiró. Actualiza la página e inténtalo de nuevo.';
    } elseif ($telefono === '' || mb_strlen($telefono, 'UTF-8') > 30 || !filter_var($correo, FILTER_VALIDATE_EMAIL) || mb_strlen($correo, 'UTF-8') > 100 || !is_string($contrasena)) {
        $errorLogin = 'Revisa el teléfono, el correo electrónico y la contraseña.';
    } else {
        try {
            $consultaUsuario = $conexion->prepare('SELECT id_usuario, nombre, correo, contrasena, rol FROM usuario WHERE telefono = ? AND correo = ? LIMIT 1');
            $consultaUsuario->bind_param('ss', $telefono, $correo);
            $consultaUsuario->execute();
            $usuario = $consultaUsuario->get_result()->fetch_assoc();

            if (!$usuario || !password_verify($contrasena, $usuario['contrasena'])) {
                $errorLogin = 'Los datos ingresados no coinciden con una cuenta. Revisa la información o crea una cuenta.';
            } else {
                $_SESSION = [];
                session_regenerate_id(true);
                $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
                $_SESSION['nombre_usuario'] = $usuario['nombre'];
                $_SESSION['correo_usuario'] = $usuario['correo'];
                $_SESSION['rol_usuario'] = $usuario['rol'];
                unset($_SESSION['login_csrf']);

                header('Location: inicio.php');
                exit;
            }
        } catch (mysqli_sql_exception $excepcion) {
            $errorLogin = 'No se pudo iniciar sesión. Inténtalo de nuevo más tarde.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - XERO</title>
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
                <h2>¡Bienvenido!</h2>
                <p>Gestiona tu inventario con rapidez, seguridad y claridad.</p>
            </div>

            <div class="login-form-wrap">
                <?php if ($mensajeSesion !== ''): ?>
                    <div class="login-error" role="status"><?php echo htmlspecialchars($mensajeSesion, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if ($errorLogin !== ''): ?>
                    <div class="login-error" role="alert"><?php echo htmlspecialchars($errorLogin, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php if (strpos($errorLogin, 'no coinciden') !== false): ?>
                        <p class="login-register-hint">¿Aún no tienes una cuenta? <a href="registro.php">Regístrate</a>.</p>
                    <?php endif; ?>
                <?php endif; ?>

                <form action="sesion.php" class="login-form" method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-group">
                        <label for="telefono-login">Teléfono</label>
                        <input id="telefono-login" name="telefono" type="tel" placeholder="Tu número de teléfono" value="<?php echo htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8'); ?>" maxlength="30" autocomplete="tel" required>
                    </div>

                    <div class="form-group">
                        <label for="correo-login">Correo electrónico</label>
                        <input id="correo-login" name="correo" type="email" placeholder="ejemplo@correo.com" value="<?php echo htmlspecialchars($correo, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" autocomplete="email" required>
                    </div>

                    <div class="form-group">
                        <label for="contrasena-login">Contraseña</label>
                        <input id="contrasena-login" name="contrasena" type="password" placeholder="Tu contraseña" autocomplete="current-password" required>
                    </div>

                    <button type="submit" class="btn-auth">Entrar a mi sesión</button>
                </form>
            </div>
        </section>

        <div class="login-footer">
            <p>¿No tienes una cuenta? <a href="registro.php">Regístrate</a></p>
        </div>
    </main>

    <?php include __DIR__ . '/../php/footer.php'; ?>
</body>
</html>