<?php
require_once __DIR__ . '/../php/conexion.php';

$idProducto = (int) ($_GET['id'] ?? $_POST['id_producto'] ?? 0);
$error = '';

if ($idProducto <= 0) {
    header('Location: productos.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nombre = trim($_POST['nombre_producto'] ?? '');
    $descripcion = trim($_POST['descripcion_producto'] ?? '');
    $precio = (float) ($_POST['precio'] ?? 0);
    $stock = (int) ($_POST['stock'] ?? 0);
    $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
    $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);

    if ($nombre === '' || $precio < 0 || $stock < 0 || $idCategoria <= 0 || $idProveedor <= 0) {
        $error = 'Completa todos los datos del producto con valores válidos.';
    } else {
        $consulta = $conexion->prepare('UPDATE productos SET nombre_producto = ?, descripcion = ?, precio = ?, stock = ?, id_categoria = ?, id_proveedor = ? WHERE id_producto = ?');
        $consulta->bind_param('ssdiiii', $nombre, $descripcion, $precio, $stock, $idCategoria, $idProveedor, $idProducto);
        if ($consulta->execute()) {
            header('Location: productos.php?actualizado=1');
            exit;
        }
        $error = 'No se pudo actualizar el producto.';
    }
}

$consultaProducto = $conexion->prepare('SELECT nombre_producto, descripcion, precio, stock, id_categoria, id_proveedor FROM productos WHERE id_producto = ?');
$consultaProducto->bind_param('i', $idProducto);
$consultaProducto->execute();
$producto = $consultaProducto->get_result()->fetch_assoc();

if (!$producto) {
    header('Location: productos.php');
    exit;
}

$categorias = $conexion->query('SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria');
$proveedores = $conexion->query('SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Editar producto</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="form-card">
            <h2>Editar producto</h2>
            <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="id_producto" value="<?php echo $idProducto; ?>">
                <div class="form-group"><label for="nombre_producto">Nombre del producto</label><input id="nombre_producto" name="nombre_producto" type="text" value="<?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="form-group"><label for="descripcion_producto">Descripción</label><input id="descripcion_producto" name="descripcion_producto" type="text" value="<?php echo htmlspecialchars($producto['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label for="precio">Precio ($)</label><input id="precio" name="precio" type="number" step="0.01" min="0" value="<?php echo htmlspecialchars($producto['precio'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                    <div class="form-group"><label for="stock">Stock</label><input id="stock" name="stock" type="number" min="0" value="<?php echo (int) $producto['stock']; ?>" required></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label for="id_categoria">Categoría</label><select id="id_categoria" name="id_categoria" required><option value="">Seleccionar...</option><?php while ($categoria = $categorias->fetch_assoc()): ?><option value="<?php echo $categoria['id_categoria']; ?>" <?php echo (int) $producto['id_categoria'] === (int) $categoria['id_categoria'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
                    <div class="form-group"><label for="id_proveedor">Proveedor</label><select id="id_proveedor" name="id_proveedor" required><option value="">Seleccionar...</option><?php while ($proveedor = $proveedores->fetch_assoc()): ?><option value="<?php echo $proveedor['id_proveedor']; ?>" <?php echo (int) $producto['id_proveedor'] === (int) $proveedor['id_proveedor'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
                </div>
                <div class="form-buttons"><a href="productos.php" class="btn-cancel">Cancelar</a><button type="submit" class="btn-save">Guardar cambios</button></div>
            </form>
        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
</body>
</html>
