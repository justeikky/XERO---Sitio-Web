<?php
require_once __DIR__ . '/../php/conexion.php';
require_once __DIR__ . '/../php/actividad.php';

$idProducto = (int) ($_GET['id'] ?? $_POST['id_producto'] ?? 0);
$mensaje = '';
$error = '';

if ($idProducto <= 0) {
    header('Location: productos.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $tipo = $_POST['tipo_movimiento'] ?? '';
    $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT);
    $fecha = $_POST['fecha_movimiento'] ?? date('Y-m-d');
    $idTienda = (int) ($_POST['id_tienda'] ?? 0);
    $idUsuario = (int) ($_POST['id_usuario'] ?? 0);

    if (!in_array($tipo, ['entrada', 'salida'], true)) {
        $error = 'Selecciona si el movimiento es una entrada o una salida.';
    } elseif ($cantidad === false || $cantidad === null || $cantidad <= 0) {
        $error = 'La cantidad debe ser un número entero mayor que cero.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $error = 'Selecciona una fecha válida.';
    } elseif ($idTienda <= 0 || $idUsuario <= 0) {
        $error = 'Selecciona la tienda y el usuario responsable.';
    } else {
        try {
            $conexion->begin_transaction();

            $consultaProducto = $conexion->prepare('SELECT nombre_producto, stock FROM productos WHERE id_producto = ? FOR UPDATE');
            $consultaProducto->bind_param('i', $idProducto);
            $consultaProducto->execute();
            $producto = $consultaProducto->get_result()->fetch_assoc();

            if (!$producto) {
                throw new Exception('El producto no existe.');
            }
            if ($tipo === 'salida' && $cantidad > (int) $producto['stock']) {
                throw new Exception('No hay stock suficiente para registrar esta salida. Stock disponible: ' . $producto['stock'] . '.');
            }

            if ($tipo === 'entrada') {
                $consultaMovimiento = $conexion->prepare('INSERT INTO inv_entrada (fecha_en, cantidad, id_usuario, id_producto, id_tienda) VALUES (?, ?, ?, ?, ?)');
                $consultaMovimiento->bind_param('siiii', $fecha, $cantidad, $idUsuario, $idProducto, $idTienda);
                $nuevoStock = (int) $producto['stock'] + $cantidad;
            } else {
                $consultaMovimiento = $conexion->prepare('INSERT INTO inv_salida (fecha_sa, cantidad, id_usuario, id_producto, id_tienda) VALUES (?, ?, ?, ?, ?)');
                $consultaMovimiento->bind_param('siiii', $fecha, $cantidad, $idUsuario, $idProducto, $idTienda);
                $nuevoStock = (int) $producto['stock'] - $cantidad;
            }
            $consultaMovimiento->execute();

            $actualizarStock = $conexion->prepare('UPDATE productos SET stock = ? WHERE id_producto = ?');
            $actualizarStock->bind_param('ii', $nuevoStock, $idProducto);
            $actualizarStock->execute();
            registrarActividad($conexion, $tipo === 'entrada' ? 'Registró entrada' : 'Registró salida', 'Movimiento', ($tipo === 'entrada' ? 'Ingresaron ' : 'Salieron ') . $cantidad . ' unidades de "' . $producto['nombre_producto'] . '".');
            $conexion->commit();
            header('Location: informes.php?id_producto=' . $idProducto . '&fecha_inicio=' . $fecha . '&fecha_fin=' . $fecha . '&guardado=1');
            exit;
        } catch (Throwable $exception) {
            $conexion->rollback();
            $error = $exception->getMessage();
        }
    }
}

$consultaProducto = $conexion->prepare('SELECT nombre_producto, stock FROM productos WHERE id_producto = ?');
$consultaProducto->bind_param('i', $idProducto);
$consultaProducto->execute();
$producto = $consultaProducto->get_result()->fetch_assoc();
if (!$producto) {
    header('Location: productos.php');
    exit;
}
$tiendas = $conexion->query('SELECT id_tienda, nombre_tienda FROM tiendas ORDER BY nombre_tienda');
$usuarios = $conexion->query('SELECT id_usuario, nombre FROM usuario ORDER BY nombre');
$usuarioCreado = false;
if ($usuarios && $usuarios->num_rows === 0) {
    $hashAdmin = password_hash('AdminXero2026!', PASSWORD_DEFAULT);
    $crearUsuario = $conexion->prepare("INSERT INTO usuario (nombre, correo, telefono, contrasena, rol) VALUES ('Administrador XERO', 'admin@xero.local', '0000000000', ?, 'Administrador')");
    $crearUsuario->bind_param('s', $hashAdmin);
    $crearUsuario->execute();
    $usuarioCreado = true;
    $usuarios = $conexion->query('SELECT id_usuario, nombre FROM usuario ORDER BY nombre');
}
$puedeRegistrar = $tiendas && $tiendas->num_rows > 0 && $usuarios && $usuarios->num_rows > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Registrar movimiento</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>
        <div class="form-card movement-form-card">
            <div class="movement-product-heading"><i class="fa-solid fa-box"></i><div><span>Movimiento de inventario</span><h2><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></h2><small>Stock actual: <strong><?php echo (int) $producto['stock']; ?> unidades</strong></small></div></div>
            <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($usuarioCreado): ?><div class="form-message success"><strong>Responsable inicial creado.</strong><span>Se usará Administrador XERO como responsable hasta que exista autenticación de usuarios.</span></div><?php endif; ?>
            <?php if (!$puedeRegistrar): ?><div class="form-message warning"><strong>Falta una tienda para registrar el movimiento.</strong><span>Crea una tienda desde <a href="agregar.php?tipo=tienda">Añadir registro</a> y vuelve a esta pantalla.</span></div><?php endif; ?>
            <form method="post" id="movimiento-form">
                <input type="hidden" name="id_producto" value="<?php echo $idProducto; ?>">
                <div class="movement-type-selector">
                    <label class="movement-type movement-entry"><input type="radio" name="tipo_movimiento" value="entrada" required><i class="fa-solid fa-arrow-down"></i><strong>Entrada</strong><span>Sumar unidades al stock</span></label>
                    <label class="movement-type movement-exit"><input type="radio" name="tipo_movimiento" value="salida" required><i class="fa-solid fa-arrow-up"></i><strong>Salida</strong><span>Restar unidades del stock</span></label>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="cantidad">Cantidad <span class="required-mark">*</span></label><input id="cantidad" name="cantidad" type="number" min="1" step="1" required placeholder="Ej. 10"></div>
                    <div class="form-group"><label for="fecha_movimiento">Fecha <span class="required-mark">*</span></label><input id="fecha_movimiento" name="fecha_movimiento" type="date" value="<?php echo htmlspecialchars($_POST['fecha_movimiento'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="id_tienda">Tienda <span class="required-mark">*</span></label><select id="id_tienda" name="id_tienda" required><option value="">Seleccionar tienda...</option><?php while ($tienda = $tiendas->fetch_assoc()): ?><option value="<?php echo $tienda['id_tienda']; ?>" <?php echo (int) ($_POST['id_tienda'] ?? 0) === (int) $tienda['id_tienda'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
                    <div class="form-group"><label for="id_usuario">Responsable <span class="required-mark">*</span></label><select id="id_usuario" name="id_usuario" required><option value="">Seleccionar usuario...</option><?php while ($usuario = $usuarios->fetch_assoc()): ?><option value="<?php echo $usuario['id_usuario']; ?>" <?php echo (int) ($_POST['id_usuario'] ?? 0) === (int) $usuario['id_usuario'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
                </div>
                <div class="form-buttons"><a href="productos.php" class="btn-cancel">Cancelar</a><button type="submit" class="btn-save" <?php echo $puedeRegistrar ? '' : 'disabled'; ?>><i class="fa-solid fa-floppy-disk"></i> Registrar movimiento</button></div>
            </form>
        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
    <script>
        document.querySelectorAll('.movement-type input').forEach((input) => input.addEventListener('change', () => {
            document.querySelectorAll('.movement-type').forEach((card) => card.classList.toggle('selected', card.querySelector('input').checked));
        }));
    </script>
</body>
</html>
