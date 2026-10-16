<?php
require_once __DIR__ . '/../php/autenticacion.php';
$usuarioId = usuarioAutenticadoId();
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
    if (!in_array($tipo, ['entrada', 'salida'], true)) {
        $error = 'Selecciona si el movimiento es una entrada o una salida.';
    } elseif ($cantidad === false || $cantidad === null || $cantidad <= 0) {
        $error = 'La cantidad debe ser un número entero mayor que cero.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
        $error = 'Selecciona una fecha válida.';
    } elseif ($idTienda <= 0) {
        $error = 'Selecciona una tienda de tu cuenta.';
    } else {
        try {
            $conexion->begin_transaction();

            $consultaProducto = $conexion->prepare('SELECT nombre_producto, stock FROM productos WHERE id_producto = ? AND id_usuario = ? FOR UPDATE');
            $consultaProducto->bind_param('ii', $idProducto, $usuarioId);
            $consultaProducto->execute();
            $producto = $consultaProducto->get_result()->fetch_assoc();

            if (!$producto) {
                throw new Exception('El producto no existe en tu inventario.');
            }

            $consultaTienda = $conexion->prepare('SELECT id_tienda FROM tiendas WHERE id_tienda = ? AND id_usuario = ?');
            $consultaTienda->bind_param('ii', $idTienda, $usuarioId);
            $consultaTienda->execute();
            if (!$consultaTienda->get_result()->fetch_assoc()) {
                throw new Exception('La tienda seleccionada no pertenece a tu cuenta.');
            }
            if ($tipo === 'salida' && $cantidad > (int) $producto['stock']) {
                throw new Exception('No hay stock suficiente para registrar esta salida. Stock disponible: ' . $producto['stock'] . '.');
            }

            if ($tipo === 'entrada') {
                $consultaMovimiento = $conexion->prepare('INSERT INTO inv_entrada (fecha_en, cantidad, id_usuario, id_producto, id_tienda) VALUES (?, ?, ?, ?, ?)');
                $consultaMovimiento->bind_param('siiii', $fecha, $cantidad, $usuarioId, $idProducto, $idTienda);
                $nuevoStock = (int) $producto['stock'] + $cantidad;
            } else {
                $consultaMovimiento = $conexion->prepare('INSERT INTO inv_salida (fecha_sa, cantidad, id_usuario, id_producto, id_tienda) VALUES (?, ?, ?, ?, ?)');
                $consultaMovimiento->bind_param('siiii', $fecha, $cantidad, $usuarioId, $idProducto, $idTienda);
                $nuevoStock = (int) $producto['stock'] - $cantidad;
            }
            $consultaMovimiento->execute();

            $actualizarStock = $conexion->prepare('UPDATE productos SET stock = ? WHERE id_producto = ? AND id_usuario = ?');
            $actualizarStock->bind_param('iii', $nuevoStock, $idProducto, $usuarioId);
            $actualizarStock->execute();
            registrarActividad($conexion, $tipo === 'entrada' ? 'Registró entrada' : 'Registró salida', 'Movimiento', ($tipo === 'entrada' ? 'Ingresaron ' : 'Salieron ') . $cantidad . ' unidades de "' . $producto['nombre_producto'] . '".', $usuarioId);
            $conexion->commit();
            header('Location: informes.php?id_producto=' . $idProducto . '&fecha_inicio=' . $fecha . '&fecha_fin=' . $fecha . '&guardado=1');
            exit;
        } catch (Throwable $exception) {
            $conexion->rollback();
            $error = $exception->getMessage();
        }
    }
}

$consultaProducto = $conexion->prepare('SELECT nombre_producto, stock FROM productos WHERE id_producto = ? AND id_usuario = ?');
$consultaProducto->bind_param('ii', $idProducto, $usuarioId);
$consultaProducto->execute();
$producto = $consultaProducto->get_result()->fetch_assoc();
if (!$producto) {
    header('Location: productos.php');
    exit;
}
$tiendas = $conexion->prepare('SELECT id_tienda, nombre_tienda FROM tiendas WHERE id_usuario = ? ORDER BY nombre_tienda');
$tiendas->bind_param('i', $usuarioId);
$tiendas->execute();
$tiendas = $tiendas->get_result();
$puedeRegistrar = $tiendas->num_rows > 0;
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
                    <div class="form-group"><label>Responsable</label><input type="text" value="<?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" disabled></div>
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
