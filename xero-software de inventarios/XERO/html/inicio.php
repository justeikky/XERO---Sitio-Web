<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../php/autenticacion.php';
$usuarioId = usuarioAutenticadoId();
require_once __DIR__ . '/../php/conexion.php';

$moneda = 'cop';
$monedaConsulta = $conexion->prepare('SELECT moneda FROM configuracion_usuario WHERE id_usuario = ? LIMIT 1');
if ($monedaConsulta) {
    $monedaConsulta->bind_param('i', $usuarioId);
    $monedaConsulta->execute();
    $preferencias = $monedaConsulta->get_result()->fetch_assoc() ?: [];
    if (!empty($preferencias['moneda'])) {
        $moneda = strtolower((string) $preferencias['moneda']);
    }
}
$simbolosMoneda = ['cop' => 'COP $', 'usd' => 'US$', 'eur' => '€'];
$simboloMoneda = $simbolosMoneda[$moneda] ?? 'COP $';
$formatearMoneda = static function ($valor) use ($simboloMoneda): string {
    return $simboloMoneda . ' ' . number_format((float) $valor, 2, ',', '.');
};

$totalStockBajo = 0;
$consultaStockBajo = $conexion->prepare('SELECT COUNT(*) AS total FROM productos WHERE id_usuario = ? AND stock <= 5');
$consultaStockBajo->bind_param('i', $usuarioId);
$consultaStockBajo->execute();
$totalStockBajo = (int) $consultaStockBajo->get_result()->fetch_assoc()['total'];

$totalCategorias = 0;
$consultaCategorias = $conexion->prepare('SELECT COUNT(*) AS total FROM categorias WHERE id_usuario = ?');
$consultaCategorias->bind_param('i', $usuarioId);
$consultaCategorias->execute();
$totalCategorias = (int) $consultaCategorias->get_result()->fetch_assoc()['total'];

$alertasInventario = [];
$consultaAlertas = $conexion->prepare(
    'SELECT id_producto, nombre_producto, stock
     FROM productos
     WHERE id_usuario = ? AND stock <= 5
     ORDER BY stock ASC, nombre_producto ASC
     LIMIT 4'
);
$consultaAlertas->bind_param('i', $usuarioId);
$consultaAlertas->execute();
$alertasInventario = $consultaAlertas->get_result()->fetch_all(MYSQLI_ASSOC);

$valores = ['entradas' => 0, 'salidas' => 0, 'inventario' => 0];
$consultaValores = $conexion->prepare(
    'SELECT
        (SELECT COALESCE(SUM(e.cantidad * p.precio), 0)
         FROM inv_entrada e INNER JOIN productos p ON p.id_producto = e.id_producto
         WHERE p.id_usuario = ?) AS entradas,
        (SELECT COALESCE(SUM(s.cantidad * p.precio), 0)
         FROM inv_salida s INNER JOIN productos p ON p.id_producto = s.id_producto
         WHERE p.id_usuario = ?) AS salidas,
        (SELECT COALESCE(SUM(p.stock * p.precio), 0) FROM productos p WHERE p.id_usuario = ?) AS inventario'
);
if ($consultaValores) {
    $consultaValores->bind_param('iii', $usuarioId, $usuarioId, $usuarioId);
    $consultaValores->execute();
    $valores = $consultaValores->get_result()->fetch_assoc() ?: $valores;
}

$fechaInicio = date('Y-m-d', strtotime('-6 days'));
$movimientosPorDia = [];
for ($diasAtras = 6; $diasAtras >= 0; $diasAtras--) {
    $fecha = date('Y-m-d', strtotime('-' . $diasAtras . ' days'));
    $movimientosPorDia[$fecha] = ['entradas' => 0, 'salidas' => 0];
}
$consultaGrafico = $conexion->prepare(
    'SELECT fecha, SUM(entradas) AS entradas, SUM(salidas) AS salidas
     FROM (
        SELECT e.fecha_en AS fecha, SUM(e.cantidad) AS entradas, 0 AS salidas
        FROM inv_entrada e INNER JOIN productos p ON p.id_producto = e.id_producto
        WHERE p.id_usuario = ? AND e.fecha_en >= ?
        GROUP BY fecha_en
        UNION ALL
        SELECT s.fecha_sa AS fecha, 0 AS entradas, SUM(s.cantidad) AS salidas
        FROM inv_salida s INNER JOIN productos p ON p.id_producto = s.id_producto
        WHERE p.id_usuario = ? AND s.fecha_sa >= ?
        GROUP BY fecha_sa
     ) AS movimientos
     GROUP BY fecha
     ORDER BY fecha'
);
if ($consultaGrafico) {
    $consultaGrafico->bind_param('isis', $usuarioId, $fechaInicio, $usuarioId, $fechaInicio);
    $consultaGrafico->execute();
    foreach ($consultaGrafico->get_result()->fetch_all(MYSQLI_ASSOC) as $movimientoDia) {
        if (isset($movimientosPorDia[$movimientoDia['fecha']])) {
            $movimientosPorDia[$movimientoDia['fecha']] = [
                'entradas' => (int) $movimientoDia['entradas'],
                'salidas' => (int) $movimientoDia['salidas'],
            ];
        }
    }
}
$maximoDiario = 1;
$totalUnidadesPeriodo = 0;
foreach ($movimientosPorDia as $movimientoDia) {
    $maximoDiario = max($maximoDiario, $movimientoDia['entradas'], $movimientoDia['salidas']);
    $totalUnidadesPeriodo += $movimientoDia['entradas'] + $movimientoDia['salidas'];
}
$diasSemana = [1 => 'Lun', 2 => 'Mar', 3 => 'Mie', 4 => 'Jue', 5 => 'Vie', 6 => 'Sab', 7 => 'Dom'];

$tiendasUsuario = [];
$consultaTiendas = $conexion->prepare(
    'SELECT t.id_tienda, t.nombre_tienda,
            COALESCE(e.unidades, 0) AS entradas,
            COALESCE(s.unidades, 0) AS salidas
     FROM tiendas t
     LEFT JOIN (
        SELECT e.id_tienda, SUM(e.cantidad) AS unidades
        FROM inv_entrada e INNER JOIN productos p ON p.id_producto = e.id_producto
        WHERE p.id_usuario = ? GROUP BY e.id_tienda
     ) AS e ON e.id_tienda = t.id_tienda
     LEFT JOIN (
        SELECT s.id_tienda, SUM(s.cantidad) AS unidades
        FROM inv_salida s INNER JOIN productos p ON p.id_producto = s.id_producto
        WHERE p.id_usuario = ? GROUP BY s.id_tienda
     ) AS s ON s.id_tienda = t.id_tienda
     WHERE t.id_usuario = ? AND (e.id_tienda IS NOT NULL OR s.id_tienda IS NOT NULL)
     ORDER BY t.nombre_tienda
     LIMIT 6'
);
if ($consultaTiendas) {
    $consultaTiendas->bind_param('iii', $usuarioId, $usuarioId, $usuarioId);
    $consultaTiendas->execute();
    $tiendasUsuario = $consultaTiendas->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Inicio</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="dashboard-heading">
            <div>
                <h2 class="section-title">Resumen de inventario</h2>
                <p class="dashboard-subtitle">Actividad y existencias registradas en XERO</p>
            </div>
        </div>

        <div class="dashboard-grid">
            <section class="card dashboard-panel movement-chart-panel">
                <div class="card-header dashboard-card-heading">
                    <div>
                        <h3>Entradas y salidas</h3>
                        <p>Unidades por día · últimos 7 días</p>
                    </div>
                    <a href="informes.php" class="see-more">VER INFORMES</a>
                </div>
                <div class="chart-legend" aria-hidden="true">
                    <span><i class="legend-dot entry-dot"></i>Entradas</span>
                    <span><i class="legend-dot exit-dot"></i>Salidas</span>
                </div>
                <?php if ($totalUnidadesPeriodo === 0): ?><p class="chart-empty">Sin movimientos de entrada o salida en este periodo.</p><?php endif; ?>
                <div class="movement-chart" role="img" aria-label="Comparación diaria de unidades de entrada y salida del usuario">
                    <?php foreach ($movimientosPorDia as $fecha => $cantidades): ?>
                        <?php
                        $alturaEntrada = (int) round(($cantidades['entradas'] / $maximoDiario) * 100);
                        $alturaSalida = (int) round(($cantidades['salidas'] / $maximoDiario) * 100);
                        ?>
                        <div class="chart-day" title="<?php echo htmlspecialchars(date('d/m', strtotime($fecha)), ENT_QUOTES, 'UTF-8'); ?>: <?php echo $cantidades['entradas']; ?> entradas, <?php echo $cantidades['salidas']; ?> salidas">
                            <div class="chart-bars">
                                <span class="chart-bar entry-bar" style="height: <?php echo $alturaEntrada; ?>%"></span>
                                <span class="chart-bar exit-bar" style="height: <?php echo $alturaSalida; ?>%"></span>
                            </div>
                            <span class="chart-day-label"><?php echo $diasSemana[(int) date('N', strtotime($fecha))]; ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="card dashboard-panel inventory-alert-panel">
                <div class="card-header dashboard-card-heading">
                    <div>
                        <h3><i class="fa-solid fa-triangle-exclamation"></i> Alertas de Inventario</h3>
                        <p><?php echo $totalStockBajo; ?> producto(s) con 5 unidades o menos</p>
                    </div>
                    <a href="productos.php" class="see-more">VER MÁS</a>
                </div>
                <?php if (!$alertasInventario): ?>
                    <div class="dashboard-empty">No hay productos con stock bajo.</div>
                <?php else: ?>
                    <ul class="inventory-alert-list">
                        <?php foreach ($alertasInventario as $alerta): ?>
                            <?php
                            $stock = (int) $alerta['stock'];
                            $claseAlerta = $stock <= 3 ? 'alert-critical' : 'alert-low';
                            $etiquetaAlerta = $stock <= 0 ? 'AGOTADO' : ($stock <= 3 ? 'URGENTE' : 'BAJO STOCK');
                            ?>
                            <li class="inventory-alert <?php echo $claseAlerta; ?>">
                                <span class="alert-icon"><i class="fa-solid <?php echo $stock <= 0 ? 'fa-circle-xmark' : 'fa-triangle-exclamation'; ?>"></i></span>
                                <span class="alert-details">
                                    <strong><?php echo htmlspecialchars($alerta['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <small><?php echo $stock <= 0 ? 'No quedan unidades.' : 'Stock crítico: quedan ' . $stock . ' unidades.'; ?></small>
                                </span>
                                <span class="alert-badge"><?php echo $etiquetaAlerta; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <div class="dashboard-grid mt-20 dashboard-secondary-grid">
            <section class="card dashboard-panel inventory-count-panel">
                <div class="card-header dashboard-card-heading">
                    <div>
                        <h3>Número de inventario</h3>
                        <p>Resumen de existencias y clasificación</p>
                    </div>
                </div>
                <ul class="inventory-list">
                    <li class="danger"><span>Artículos con stock bajo</span><strong><?php echo $totalStockBajo; ?></strong></li>
                    <li><span>Categorías de artículos</span><strong><?php echo $totalCategorias; ?></strong></li>
                </ul>
            </section>

            <section class="card dashboard-panel stores-panel">
                <div class="card-header dashboard-card-heading">
                    <div>
                        <h3>Tiendas con tu actividad</h3>
                        <p>Tiendas asociadas a tus entradas y salidas</p>
                    </div>
                    <a href="tiendas.php" class="see-more">VER TODAS</a>
                </div>
                <?php if (!$tiendasUsuario): ?>
                    <div class="dashboard-empty">Todavía no hay movimientos tuyos asociados a una tienda.</div>
                <?php else: ?>
                    <div class="stores-table-wrap">
                        <table class="stores-table dashboard-stores-table">
                            <thead><tr><th>Tienda</th><th>Entradas</th><th>Salidas</th></tr></thead>
                            <tbody>
                                <?php foreach ($tiendasUsuario as $tienda): ?>
                                    <tr>
                                        <td><a href="informes.php?id_tienda=<?php echo (int) $tienda['id_tienda']; ?>"><?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?></a></td>
                                        <td><?php echo (int) $tienda['entradas']; ?></td>
                                        <td><?php echo (int) $tienda['salidas']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <section class="dashboard-value-section mt-20">
            <div class="dashboard-section-heading">
                <div>
                    <h2 class="section-subtitle">Valor del inventario</h2>
                    <p>Entradas y salidas calculadas con el precio actual de cada producto.</p>
                </div>
            </div>
            <div class="stats-grid dashboard-value-grid">
                <article class="stat-card value-entry">
                    <span class="value-icon"><i class="fa-solid fa-arrow-down"></i></span>
                    <div><p>VALOR TOTAL DE ENTRADAS</p><h2><?php echo htmlspecialchars($formatearMoneda($valores['entradas']), ENT_QUOTES, 'UTF-8'); ?></h2></div>
                </article>
                <article class="stat-card value-exit">
                    <span class="value-icon"><i class="fa-solid fa-arrow-up"></i></span>
                    <div><p>VALOR TOTAL DE SALIDAS</p><h2><?php echo htmlspecialchars($formatearMoneda($valores['salidas']), ENT_QUOTES, 'UTF-8'); ?></h2></div>
                </article>
                <article class="stat-card value-stock">
                    <span class="value-icon"><i class="fa-solid fa-boxes-stacked"></i></span>
                    <div><p>VALOR TOTAL DE INVENTARIO</p><h2><?php echo htmlspecialchars($formatearMoneda($valores['inventario']), ENT_QUOTES, 'UTF-8'); ?></h2></div>
                </article>
            </div>
        </section>

        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
</body>
</html>