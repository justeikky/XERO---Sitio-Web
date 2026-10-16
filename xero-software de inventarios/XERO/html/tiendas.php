<?php
require_once __DIR__ . '/../php/autenticacion.php';
$usuarioId = usuarioAutenticadoId();
require_once __DIR__ . '/../php/conexion.php';

$mensaje = isset($_GET['actualizada']) ? 'La tienda se actualizó correctamente.' : '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $idTienda = (int) ($_POST['id_tienda'] ?? 0);

    if ($idTienda > 0) {
        $consultaMovimientos = $conexion->prepare('SELECT (SELECT COUNT(*) FROM inv_entrada WHERE id_tienda = ? AND id_producto IN (SELECT id_producto FROM productos WHERE id_usuario = ?)) + (SELECT COUNT(*) FROM inv_salida WHERE id_tienda = ? AND id_producto IN (SELECT id_producto FROM productos WHERE id_usuario = ?)) AS total');
        $consultaMovimientos->bind_param('iiii', $idTienda, $usuarioId, $idTienda, $usuarioId);
        $consultaMovimientos->execute();
        $totalMovimientos = (int) $consultaMovimientos->get_result()->fetch_assoc()['total'];

        if ($totalMovimientos > 0) {
            $error = 'No se puede eliminar esta tienda porque tiene movimientos relacionados.';
        } else {
            $consulta = $conexion->prepare('DELETE FROM tiendas WHERE id_tienda = ? AND id_usuario = ?');
            $consulta->bind_param('ii', $idTienda, $usuarioId);
            if ($consulta->execute() && $consulta->affected_rows > 0) {
                $mensaje = 'La tienda se eliminó correctamente.';
            } else {
                $error = 'No se encontró la tienda que quieres eliminar.';
            }
        }
    } else {
        $error = 'La tienda seleccionada no es válida.';
    }
}

$tiendas = $conexion->prepare(
    'SELECT t.id_tienda, t.nombre_tienda, t.descripcion, t.telefono, t.direccion,
            (SELECT COUNT(*) FROM inv_entrada e WHERE e.id_tienda = t.id_tienda AND e.id_producto IN (SELECT id_producto FROM productos WHERE id_usuario = t.id_usuario)) AS total_entradas,
            (SELECT COUNT(*) FROM inv_salida s WHERE s.id_tienda = t.id_tienda AND s.id_producto IN (SELECT id_producto FROM productos WHERE id_usuario = t.id_usuario)) AS total_salidas,
            (SELECT COALESCE(SUM(e.cantidad), 0) FROM inv_entrada e WHERE e.id_tienda = t.id_tienda AND e.id_producto IN (SELECT id_producto FROM productos WHERE id_usuario = t.id_usuario)) AS unidades_entradas,
            (SELECT COALESCE(SUM(s.cantidad), 0) FROM inv_salida s WHERE s.id_tienda = t.id_tienda AND s.id_producto IN (SELECT id_producto FROM productos WHERE id_usuario = t.id_usuario)) AS unidades_salidas
     FROM tiendas t
     WHERE t.id_usuario = ?
     ORDER BY t.nombre_tienda'
);
$tiendas->bind_param('i', $usuarioId);
$tiendas->execute();
$tiendas = $tiendas->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Tiendas</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="page-header">
            <div>
                <h2>Ubicaciones y Tiendas</h2>
                <p class="page-subtitle">Administra las tiendas relacionadas con tus movimientos</p>
            </div>
            <a href="agregar.php?tipo=tienda" class="btn-main-action"><i class="fa-solid fa-plus"></i> Nueva tienda</a>
        </div>

        <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if ($tiendas->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-store"></i>
                <h3>Aún no hay tiendas</h3>
                <p>Crea una tienda para poder asociarla a entradas y salidas.</p>
                <a href="agregar.php?tipo=tienda" class="btn-main-action">Añadir tienda</a>
            </div>
        <?php else: ?>
            <div class="stores-grid">
                <?php while ($tienda = $tiendas->fetch_assoc()): ?>
                    <?php $totalMovimientos = (int) $tienda['total_entradas'] + (int) $tienda['total_salidas']; ?>
                    <article class="store-card">
                        <div class="store-header">
                            <div class="store-icon-wrap"><i class="fa-solid fa-store"></i></div>
                            <div>
                                <span class="store-label">Punto de inventario</span>
                                <h3><?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            </div>
                        </div>
                        <p class="store-description"><?php echo htmlspecialchars($tienda['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?></p>
                        <ul class="store-info">
                            <li><i class="fa-solid fa-arrow-down"></i><span><?php echo (int) $tienda['unidades_entradas']; ?> unidades recibidas</span></li>
                            <li><i class="fa-solid fa-arrow-up"></i><span><?php echo (int) $tienda['unidades_salidas']; ?> unidades despachadas</span></li>
                            <li><i class="fa-solid fa-clipboard-check"></i><span><?php echo $totalMovimientos; ?> movimientos registrados</span></li>
                            <?php if (!empty($tienda['telefono'])): ?><li><i class="fa-solid fa-phone"></i><span><?php echo htmlspecialchars($tienda['telefono'], ENT_QUOTES, 'UTF-8'); ?></span></li><?php endif; ?>
                            <?php if (!empty($tienda['direccion'])): ?><li><i class="fa-solid fa-location-dot"></i><span><?php echo htmlspecialchars($tienda['direccion'], ENT_QUOTES, 'UTF-8'); ?></span></li><?php endif; ?>
                        </ul>
                        <div class="store-actions">
                            <a href="informes.php?id_tienda=<?php echo (int) $tienda['id_tienda']; ?>" class="supplier-products-link">Ver informe <i class="fa-solid fa-arrow-right"></i></a>
                            <a href="editar-tienda.php?id=<?php echo (int) $tienda['id_tienda']; ?>" class="btn-icon" title="Editar tienda" aria-label="Editar tienda"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form method="post" class="delete-form" onsubmit="return confirmarEliminacion('<?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?>');">
                                <input type="hidden" name="accion" value="eliminar">
                                <input type="hidden" name="id_tienda" value="<?php echo (int) $tienda['id_tienda']; ?>">
                                <button type="submit" class="btn-icon danger" title="Eliminar tienda" aria-label="Eliminar tienda"><i class="fa-solid fa-trash"></i></button>
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
            return window.confirm('¿Seguro que quieres eliminar la tienda "' + nombre + '"? Esta acción no se puede deshacer.');
        }
    </script>
</body>
</html>
