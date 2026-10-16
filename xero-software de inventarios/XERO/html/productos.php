<?php
require_once __DIR__ . '/../php/autenticacion.php';
$usuarioId = usuarioAutenticadoId();
require_once __DIR__ . '/../php/conexion.php';

$mensaje = '';
$error = '';

if (isset($_GET['actualizado'])) {
    $mensaje = 'El producto se actualizó correctamente.';
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $idProducto = (int) ($_POST['id_producto'] ?? 0);

    if ($idProducto > 0) {
        $consulta = $conexion->prepare('DELETE FROM productos WHERE id_producto = ? AND id_usuario = ?');
        $consulta->bind_param('ii', $idProducto, $usuarioId);
        if ($consulta->execute() && $consulta->affected_rows > 0) {
            $mensaje = 'El producto se eliminó correctamente.';
        } elseif ($consulta->errno !== 0) {
            $error = 'No se puede eliminar este producto porque tiene movimientos relacionados.';
        } else {
            $error = 'No se encontró el producto que quieres eliminar.';
        }
    } else {
        $error = 'El producto seleccionado no es válido.';
    }
}

$productos = $conexion->prepare(
    'SELECT p.id_producto, p.nombre_producto, p.descripcion, p.precio, p.stock,
            c.nombre_categoria, pr.nombre_proveedor
     FROM productos p
     INNER JOIN categorias c ON c.id_categoria = p.id_categoria
     INNER JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
     WHERE p.id_usuario = ? AND c.id_usuario = ? AND pr.id_usuario = ?
     ORDER BY p.id_producto DESC'
);
$productos->bind_param('iii', $usuarioId, $usuarioId, $usuarioId);
$productos->execute();
$productos = $productos->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Productos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="page-header">
            <div>
                <h2>Inventario de Productos</h2>
                <p class="page-subtitle">Productos registrados en tu inventario</p>
            </div>
            <a href="agregar.php" class="btn-main-action"><i class="fa-solid fa-plus"></i> Nuevo Producto</a>
        </div>

        <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if ($productos->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-box-open"></i>
                <h3>Aún no hay productos</h3>
                <p>Agrega tu primer producto para verlo aquí.</p>
                <a href="agregar.php" class="btn-main-action">Añadir producto</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php while ($producto = $productos->fetch_assoc()): ?>
                    <?php $stockBajo = (int) $producto['stock'] < 5; ?>
                    <article class="product-card">
                        <div class="product-card-image"><i class="fa-solid fa-box"></i></div>
                        <div class="product-card-content">
                            <div class="product-card-heading">
                                <div>
                                    <span class="product-category"><?php echo htmlspecialchars($producto['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    <h3><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                </div>
                                <span class="status-badge <?php echo $stockBajo ? 'low' : 'active'; ?>"><?php echo $stockBajo ? 'Stock bajo' : 'Disponible'; ?></span>
                            </div>
                            <p class="product-description"><?php echo htmlspecialchars($producto['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?></p>
                            <a class="product-movement-link" href="movimiento.php?id=<?php echo (int) $producto['id_producto']; ?>"><i class="fa-solid fa-arrows-rotate"></i> Registrar entrada o salida</a>
                            <div class="product-card-details">
                                <strong>$<?php echo number_format((float) $producto['precio'], 2); ?></strong>
                                <span><i class="fa-solid fa-cubes-stacked"></i> <?php echo (int) $producto['stock']; ?> unidades</span>
                            </div>
                            <div class="product-card-footer">
                                <span class="product-supplier"><i class="fa-solid fa-truck"></i> <?php echo htmlspecialchars($producto['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></span>
                                <div class="product-actions">
                                    <a class="btn-icon" href="editar-producto.php?id=<?php echo (int) $producto['id_producto']; ?>" title="Editar producto" aria-label="Editar producto"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <form method="post" class="delete-form" onsubmit="return confirmarEliminacion('<?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>');">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id_producto" value="<?php echo (int) $producto['id_producto']; ?>">
                                        <button class="btn-icon danger" type="submit" title="Eliminar producto" aria-label="Eliminar producto"><i class="fa-solid fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
    <script>
        function confirmarEliminacion(nombre) {
            return window.confirm('¿Seguro que quieres eliminar "' + nombre + '"? Esta acción no se puede deshacer.');
        }
    </script>
</body>
</html>
