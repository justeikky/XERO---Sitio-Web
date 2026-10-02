<?php
require_once __DIR__ . '/../php/conexion.php';

$busqueda = trim($_GET['q'] ?? '');
$productos = $categorias = $proveedores = $tiendas = [];

if ($busqueda !== '') {
    $patron = '%' . $busqueda . '%';

    $consulta = $conexion->prepare('SELECT id_producto, nombre_producto, descripcion, precio, stock FROM productos WHERE nombre_producto LIKE ? OR descripcion LIKE ? ORDER BY nombre_producto');
    $consulta->bind_param('ss', $patron, $patron);
    $consulta->execute();
    $productos = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);

    $consulta = $conexion->prepare('SELECT id_categoria, nombre_categoria, descripcion FROM categorias WHERE nombre_categoria LIKE ? OR descripcion LIKE ? ORDER BY nombre_categoria');
    $consulta->bind_param('ss', $patron, $patron);
    $consulta->execute();
    $categorias = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);

    $consulta = $conexion->prepare('SELECT id_proveedor, nombre_proveedor, descripcion, correo FROM proveedores WHERE nombre_proveedor LIKE ? OR descripcion LIKE ? OR correo LIKE ? ORDER BY nombre_proveedor');
    $consulta->bind_param('sss', $patron, $patron, $patron);
    $consulta->execute();
    $proveedores = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);

    $consulta = $conexion->prepare('SELECT id_tienda, nombre_tienda, descripcion, direccion FROM tiendas WHERE nombre_tienda LIKE ? OR descripcion LIKE ? OR direccion LIKE ? ORDER BY nombre_tienda');
    $consulta->bind_param('sss', $patron, $patron, $patron);
    $consulta->execute();
    $tiendas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
}

$totalResultados = count($productos) + count($categorias) + count($proveedores) + count($tiendas);
$rutaHtml = '../html/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Buscar</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>
        <div class="page-header">
            <div>
                <h2>Resultados de búsqueda</h2>
                <p class="page-subtitle">
                    <?php echo $busqueda === '' ? 'Escribe algo para buscar en XERO.' : $totalResultados . ' resultado(s) para "' . htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') . '"'; ?>
                </p>
            </div>
        </div>

        <?php if ($busqueda !== '' && $totalResultados === 0): ?>
            <div class="empty-state"><i class="fa-solid fa-magnifying-glass"></i><h3>No encontramos resultados</h3><p>Prueba con otro nombre o descripción.</p></div>
        <?php elseif ($busqueda !== ''): ?>
            <div class="search-results-grid">
                <?php foreach ($productos as $producto): ?>
                    <article class="search-result-card"><span class="search-result-type"><i class="fa-solid fa-box"></i> Producto</span><h3><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></h3><p><?php echo htmlspecialchars($producto['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?></p><strong><?php echo (int) $producto['stock']; ?> unidades · $<?php echo number_format((float) $producto['precio'], 2); ?></strong><a href="<?php echo $rutaHtml; ?>movimiento.php?id=<?php echo (int) $producto['id_producto']; ?>">Ver producto <i class="fa-solid fa-arrow-right"></i></a></article>
                <?php endforeach; ?>
                <?php foreach ($categorias as $categoria): ?>
                    <article class="search-result-card"><span class="search-result-type"><i class="fa-solid fa-tags"></i> Categoría</span><h3><?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></h3><p><?php echo htmlspecialchars($categoria['descripcion'] ?: 'Sin descripción', ENT_QUOTES, 'UTF-8'); ?></p><a href="<?php echo $rutaHtml; ?>editar-categoria.php?id=<?php echo (int) $categoria['id_categoria']; ?>">Ver categoría <i class="fa-solid fa-arrow-right"></i></a></article>
                <?php endforeach; ?>
                <?php foreach ($proveedores as $proveedor): ?>
                    <article class="search-result-card"><span class="search-result-type"><i class="fa-solid fa-truck-field"></i> Proveedor</span><h3><?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></h3><p><?php echo htmlspecialchars($proveedor['descripcion'] ?: ($proveedor['correo'] ?: 'Sin descripción'), ENT_QUOTES, 'UTF-8'); ?></p><a href="<?php echo $rutaHtml; ?>editar-proveedor.php?id=<?php echo (int) $proveedor['id_proveedor']; ?>">Ver proveedor <i class="fa-solid fa-arrow-right"></i></a></article>
                <?php endforeach; ?>
                <?php foreach ($tiendas as $tienda): ?>
                    <article class="search-result-card"><span class="search-result-type"><i class="fa-solid fa-store"></i> Tienda</span><h3><?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?></h3><p><?php echo htmlspecialchars($tienda['descripcion'] ?: ($tienda['direccion'] ?: 'Sin descripción'), ENT_QUOTES, 'UTF-8'); ?></p><a href="<?php echo $rutaHtml; ?>editar-tienda.php?id=<?php echo (int) $tienda['id_tienda']; ?>">Ver tienda <i class="fa-solid fa-arrow-right"></i></a></article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
</body>
</html>
