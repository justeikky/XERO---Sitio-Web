<?php
require_once __DIR__ . '/../php/conexion.php';

$mensaje = isset($_GET['actualizado']) ? 'El proveedor se actualizó correctamente.' : '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);

    if ($idProveedor > 0) {
        $consultaProductos = $conexion->prepare('SELECT COUNT(*) AS total FROM productos WHERE id_proveedor = ?');
        $consultaProductos->bind_param('i', $idProveedor);
        $consultaProductos->execute();
        $totalProductos = (int) $consultaProductos->get_result()->fetch_assoc()['total'];

        if ($totalProductos > 0) {
            $error = 'No se puede eliminar este proveedor porque tiene productos relacionados.';
        } else {
            $consulta = $conexion->prepare('DELETE FROM proveedores WHERE id_proveedor = ?');
            $consulta->bind_param('i', $idProveedor);
            if ($consulta->execute() && $consulta->affected_rows > 0) {
                $mensaje = 'El proveedor se eliminó correctamente.';
            } else {
                $error = 'No se encontró el proveedor que quieres eliminar.';
            }
        }
    } else {
        $error = 'El proveedor seleccionado no es válido.';
    }
}

$proveedores = $conexion->query(
    'SELECT pr.id_proveedor, pr.nombre_proveedor, pr.descripcion, pr.telefono, pr.correo, pr.direccion,
            COUNT(p.id_producto) AS total_productos
     FROM proveedores pr
     LEFT JOIN productos p ON p.id_proveedor = pr.id_proveedor
     GROUP BY pr.id_proveedor, pr.nombre_proveedor, pr.descripcion, pr.telefono, pr.correo, pr.direccion
     ORDER BY pr.nombre_proveedor'
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Proveedores</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="page-header">
            <div>
                <h2>Proveedores</h2>
                <p class="page-subtitle">Contactos que abastecen tu inventario</p>
            </div>
            <a href="agregar.php?tipo=proveedor" class="btn-main-action"><i class="fa-solid fa-plus"></i> Nuevo proveedor</a>
        </div>

        <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if ($proveedores->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-truck-field"></i>
                <h3>Aún no hay proveedores</h3>
                <p>Registra un proveedor para tener sus datos siempre a mano.</p>
                <a href="agregar.php?tipo=proveedor" class="btn-main-action">Añadir proveedor</a>
            </div>
        <?php else: ?>
            <div class="suppliers-grid">
                <?php while ($proveedor = $proveedores->fetch_assoc()): ?>
                    <article class="supplier-card">
                        <div class="supplier-card-header">
                            <div class="supplier-avatar"><i class="fa-solid fa-truck-field"></i></div>
                            <div>
                                <span class="supplier-label">Proveedor</span>
                                <h3><?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            </div>
                        </div>
                        <p class="supplier-description"><?php echo htmlspecialchars($proveedor['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="supplier-details">
                            <li><i class="fa-solid fa-boxes-stacked"></i><span><?php echo (int) $proveedor['total_productos']; ?> <?php echo (int) $proveedor['total_productos'] === 1 ? 'producto asociado' : 'productos asociados'; ?></span></li>
                            <?php if (!empty($proveedor['correo'])): ?><li><i class="fa-solid fa-envelope"></i><a href="mailto:<?php echo htmlspecialchars($proveedor['correo'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($proveedor['correo'], ENT_QUOTES, 'UTF-8'); ?></a></li><?php endif; ?>
                            <?php if (!empty($proveedor['telefono'])): ?><li><i class="fa-solid fa-phone"></i><span><?php echo htmlspecialchars($proveedor['telefono'], ENT_QUOTES, 'UTF-8'); ?></span></li><?php endif; ?>
                            <?php if (!empty($proveedor['direccion'])): ?><li><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($proveedor['direccion'], ENT_QUOTES, 'UTF-8'); ?></span></li><?php endif; ?>
                        </ul>
                        <div class="supplier-actions">
                            <a href="productos.php" class="supplier-products-link">Ver productos asociados <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="editar-proveedor.php?id=<?php echo (int) $proveedor['id_proveedor']; ?>" class="btn-icon" title="Editar proveedor" aria-label="Editar proveedor"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form method="post" class="delete-form" onsubmit="return confirmarEliminacion('<?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?>');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_proveedor" value="<?php echo (int) $proveedor['id_proveedor']; ?>">
                                <button type="submit" class="btn-icon danger" title="Eliminar proveedor" aria-label="Eliminar proveedor"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </article>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
    <script>
        function confirmarEliminacion(nombre) {
            return window.confirm('¿Seguro que quieres eliminar el proveedor "' + nombre + '"? Esta acción no se puede deshacer.');
        }
    </script>
</body>
</html>
