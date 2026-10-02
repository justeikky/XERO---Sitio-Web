<?php
require_once __DIR__ . '/../php/conexion.php';

$mensaje = isset($_GET['actualizada']) ? 'La categoría se actualizó correctamente.' : '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'eliminar') {
    $idCategoria = (int) ($_POST['id_categoria'] ?? 0);

    if ($idCategoria > 0) {
        $consulta = $conexion->prepare('DELETE FROM categorias WHERE id_categoria = ?');
        $consulta->bind_param('i', $idCategoria);
        if ($consulta->execute() && $consulta->affected_rows > 0) {
            $mensaje = 'La categoría se eliminó correctamente.';
        } elseif ($consulta->errno !== 0) {
            $error = 'No se puede eliminar esta categoría porque tiene productos relacionados.';
        } else {
            $error = 'No se encontró la categoría que quieres eliminar.';
        }
    } else {
        $error = 'La categoría seleccionada no es válida.';
    }
}

$categorias = $conexion->query(
    'SELECT c.id_categoria, c.nombre_categoria, c.descripcion, COUNT(p.id_producto) AS total_productos
     FROM categorias c
     LEFT JOIN productos p ON p.id_categoria = c.id_categoria
     GROUP BY c.id_categoria, c.nombre_categoria, c.descripcion
     ORDER BY c.nombre_categoria'
);
$imagenesCategoria = ['prod1.jpg', 'prod2.jpg', 'prod3.jpg', 'prod4.jpg'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Categorías</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="page-header">
            <div>
                <h2>Categorías de Productos</h2>
                <p class="page-subtitle">Organiza y administra las categorías de tu inventario</p>
            </div>
            <a href="agregar.php?tipo=categoria" class="btn-main-action"><i class="fa-solid fa-plus"></i> Nueva Categoría</a>
        </div>

        <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <?php if ($categorias->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fa-solid fa-tags"></i>
                <h3>Aún no hay categorías</h3>
                <p>Crea una categoría para organizar tus productos.</p>
                <a href="agregar.php?tipo=categoria" class="btn-main-action">Añadir categoría</a>
            </div>
        <?php else: ?>
            <div class="categories-grid">
                <?php $indiceImagen = 0; while ($categoria = $categorias->fetch_assoc()): ?>
                    <article class="category-card">
                        <div class="category-icon" aria-label="Categoría <?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="fa-solid fa-tags"></i>
                        </div>
                        <div class="category-info">
                            <h3><?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><?php echo (int) $categoria['total_productos']; ?> <?php echo (int) $categoria['total_productos'] === 1 ? 'producto disponible' : 'productos disponibles'; ?></p>
                            <span class="category-description"><?php echo htmlspecialchars($categoria['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?></span>
                            <div class="category-actions">
                                <a href="productos.php" class="btn-category-view">Ver artículos</a>
                                <a href="editar-categoria.php?id=<?php echo (int) $categoria['id_categoria']; ?>" class="btn-icon" title="Editar categoría" aria-label="Editar categoría"><i class="fa-solid fa-pen-to-square"></i></a>
                                <form method="post" class="delete-form" onsubmit="return confirmarEliminacion('<?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?>');">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id_categoria" value="<?php echo (int) $categoria['id_categoria']; ?>">
                                    <button type="submit" class="btn-icon danger" title="Eliminar categoría" aria-label="Eliminar categoría"><i class="fa-solid fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php $indiceImagen++; endwhile; ?>
            </div>
        <?php endif; ?>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
    <script>
        function confirmarEliminacion(nombre) {
            return window.confirm('¿Seguro que quieres eliminar la categoría "' + nombre + '"? Esta acción no se puede deshacer.');
        }
    </script>
</body>
</html>
