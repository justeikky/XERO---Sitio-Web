<?php
require_once __DIR__ . '/../php/conexion.php';
require_once __DIR__ . '/../php/actividad.php';

$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
$tipoMovimiento = $_GET['tipo'] ?? 'todos';
$idProducto = (int) ($_GET['id_producto'] ?? 0);
$idTienda = (int) ($_GET['id_tienda'] ?? 0);
$mensaje = isset($_GET['guardado']) ? 'El movimiento se guardó y el stock fue actualizado correctamente.' : (isset($_GET['eliminado']) ? 'El movimiento se eliminó y el stock fue recalculado.' : '');
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && ($_POST['accion'] ?? '') === 'eliminar_movimiento') {
    $idMovimiento = (int) ($_POST['id_movimiento'] ?? 0);
    $tipoEliminar = $_POST['tipo_movimiento'] ?? '';

    if ($idMovimiento <= 0 || !in_array($tipoEliminar, ['Entrada', 'Salida'], true)) {
        $error = 'El movimiento seleccionado no es válido.';
    } else {
        try {
            $conexion->begin_transaction();
            $tabla = $tipoEliminar === 'Entrada' ? 'inv_entrada' : 'inv_salida';
            $campoId = $tipoEliminar === 'Entrada' ? 'id_entrada' : 'id_salida';
            $consultaMovimiento = $conexion->prepare("SELECT id_producto, cantidad FROM {$tabla} WHERE {$campoId} = ? FOR UPDATE");
            $consultaMovimiento->bind_param('i', $idMovimiento);
            $consultaMovimiento->execute();
            $movimientoEliminar = $consultaMovimiento->get_result()->fetch_assoc();
            if (!$movimientoEliminar) {
                throw new Exception('El movimiento ya no existe.');
            }

            $consultaProducto = $conexion->prepare('SELECT nombre_producto, stock FROM productos WHERE id_producto = ? FOR UPDATE');
            $consultaProducto->bind_param('i', $movimientoEliminar['id_producto']);
            $consultaProducto->execute();
            $productoEliminar = $consultaProducto->get_result()->fetch_assoc();
            $nuevoStock = $tipoEliminar === 'Entrada'
                ? (int) $productoEliminar['stock'] - (int) $movimientoEliminar['cantidad']
                : (int) $productoEliminar['stock'] + (int) $movimientoEliminar['cantidad'];
            if ($nuevoStock < 0) {
                throw new Exception('No se puede eliminar la entrada porque dejaría el stock en negativo.');
            }

            $actualizarStock = $conexion->prepare('UPDATE productos SET stock = ? WHERE id_producto = ?');
            $actualizarStock->bind_param('ii', $nuevoStock, $movimientoEliminar['id_producto']);
            $actualizarStock->execute();
            $borrarMovimiento = $conexion->prepare("DELETE FROM {$tabla} WHERE {$campoId} = ?");
            $borrarMovimiento->bind_param('i', $idMovimiento);
            $borrarMovimiento->execute();
            registrarActividad($conexion, 'Eliminó', 'Movimiento', 'Eliminó una ' . strtolower($tipoEliminar) . ' de ' . $movimientoEliminar['cantidad'] . ' unidades de "' . $productoEliminar['nombre_producto'] . '".');
            $conexion->commit();
            $parametrosRegreso = http_build_query(array_filter([
                'fecha_inicio' => $_POST['fecha_inicio'] ?? null,
                'fecha_fin' => $_POST['fecha_fin'] ?? null,
                'tipo' => $_POST['filtro_tipo'] ?? null,
                'id_producto' => $_POST['id_producto'] ?? null,
                'id_tienda' => $_POST['id_tienda'] ?? null,
                'eliminado' => 1,
            ], static fn ($valor) => $valor !== null && $valor !== ''));
            header('Location: informes.php?' . $parametrosRegreso);
            exit;
        } catch (Throwable $exception) {
            $conexion->rollback();
            $error = $exception->getMessage();
        }
    }
}

$productos = $conexion->query('SELECT id_producto, nombre_producto FROM productos ORDER BY nombre_producto');
$tiendas = $conexion->query('SELECT id_tienda, nombre_tienda FROM tiendas ORDER BY nombre_tienda');

$condicionesEntrada = ['e.fecha_en BETWEEN ? AND ?'];
$condicionesSalida = ['s.fecha_sa BETWEEN ? AND ?'];
$parametrosEntrada = [$fechaInicio, $fechaFin];
$parametrosSalida = [$fechaInicio, $fechaFin];
$tiposEntrada = 'ss';
$tiposSalida = 'ss';

if ($idProducto > 0) {
    $condicionesEntrada[] = 'e.id_producto = ?';
    $condicionesSalida[] = 's.id_producto = ?';
    $parametrosEntrada[] = $idProducto;
    $parametrosSalida[] = $idProducto;
    $tiposEntrada .= 'i';
    $tiposSalida .= 'i';
}

if ($idTienda > 0) {
    $condicionesEntrada[] = 'e.id_tienda = ?';
    $condicionesSalida[] = 's.id_tienda = ?';
    $parametrosEntrada[] = $idTienda;
    $parametrosSalida[] = $idTienda;
    $tiposEntrada .= 'i';
    $tiposSalida .= 'i';
}

$entradaSql = 'SELECT e.id_entrada AS id_movimiento, e.fecha_en AS fecha, e.cantidad, "Entrada" AS tipo, p.nombre_producto, t.nombre_tienda, u.nombre AS usuario FROM inv_entrada e INNER JOIN productos p ON p.id_producto = e.id_producto LEFT JOIN tiendas t ON t.id_tienda = e.id_tienda LEFT JOIN usuario u ON u.id_usuario = e.id_usuario WHERE ' . implode(' AND ', $condicionesEntrada);
$salidaSql = 'SELECT s.id_salida AS id_movimiento, s.fecha_sa AS fecha, s.cantidad, "Salida" AS tipo, p.nombre_producto, t.nombre_tienda, u.nombre AS usuario FROM inv_salida s INNER JOIN productos p ON p.id_producto = s.id_producto LEFT JOIN tiendas t ON t.id_tienda = s.id_tienda LEFT JOIN usuario u ON u.id_usuario = s.id_usuario WHERE ' . implode(' AND ', $condicionesSalida);

if ($tipoMovimiento === 'entrada') {
    $movimientosSql = $entradaSql . ' ORDER BY fecha DESC';
    $movimientosParametros = $parametrosEntrada;
    $movimientosTipos = $tiposEntrada;
} elseif ($tipoMovimiento === 'salida') {
    $movimientosSql = $salidaSql . ' ORDER BY fecha DESC';
    $movimientosParametros = $parametrosSalida;
    $movimientosTipos = $tiposSalida;
} else {
    $movimientosSql = '(' . $entradaSql . ') UNION ALL (' . $salidaSql . ') ORDER BY fecha DESC';
    $movimientosParametros = array_merge($parametrosEntrada, $parametrosSalida);
    $movimientosTipos = $tiposEntrada . $tiposSalida;
}

function ejecutarConsulta($conexion, $sql, $tipos, $parametros) {
    $consulta = $conexion->prepare($sql);
    if (!$consulta) {
        return false;
    }
    if ($parametros) {
        $consulta->bind_param($tipos, ...$parametros);
    }
    $consulta->execute();
    return $consulta->get_result();
}

$movimientos = ejecutarConsulta($conexion, $movimientosSql, $movimientosTipos, $movimientosParametros);

$condicionResumenEntrada = implode(' AND ', $condicionesEntrada);
$condicionResumenSalida = implode(' AND ', $condicionesSalida);
$resumenSql = 'SELECT
    (SELECT COALESCE(SUM(e.cantidad), 0) FROM inv_entrada e WHERE ' . $condicionResumenEntrada . ') AS total_entradas,
    (SELECT COALESCE(SUM(s.cantidad), 0) FROM inv_salida s WHERE ' . $condicionResumenSalida . ') AS total_salidas';
$resumen = ejecutarConsulta($conexion, $resumenSql, $tiposEntrada . $tiposSalida, array_merge($parametrosEntrada, $parametrosSalida));
$resumenDatos = $resumen ? $resumen->fetch_assoc() : ['total_entradas' => 0, 'total_salidas' => 0];
$resumenDatos['total_movimientos'] = $movimientos ? $movimientos->num_rows : 0;
$variacion = (int) $resumenDatos['total_entradas'] - (int) $resumenDatos['total_salidas'];

$stockCondicion = '';
$stockParametros = [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin];
$stockTipos = 'ssss';
if ($idProducto > 0) {
    $stockCondicion .= ' WHERE p.id_producto = ?';
    $stockParametros[] = $idProducto;
    $stockTipos .= 'i';
}
$stockSql = 'SELECT p.nombre_producto, p.stock,
    (SELECT COALESCE(SUM(e.cantidad), 0) FROM inv_entrada e WHERE e.id_producto = p.id_producto AND e.fecha_en BETWEEN ? AND ?' . ($idTienda > 0 ? ' AND e.id_tienda = ?' : '') . ') AS entradas,
    (SELECT COALESCE(SUM(s.cantidad), 0) FROM inv_salida s WHERE s.id_producto = p.id_producto AND s.fecha_sa BETWEEN ? AND ?' . ($idTienda > 0 ? ' AND s.id_tienda = ?' : '') . ') AS salidas
    FROM productos p' . $stockCondicion . ' ORDER BY p.nombre_producto';
if ($idTienda > 0) {
    $stockParametros = [$fechaInicio, $fechaFin, $idTienda, $fechaInicio, $fechaFin, $idTienda];
    $stockTipos = 'ssiss i';
    $stockTipos = str_replace(' ', '', $stockTipos);
    if ($idProducto > 0) {
        $stockParametros[] = $idProducto;
        $stockTipos .= 'i';
    }
}
$stock = ejecutarConsulta($conexion, $stockSql, $stockTipos, $stockParametros);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Informes de inventario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="page-header report-header">
            <div>
                <h2>Informes de inventario</h2>
                <p class="page-subtitle">Entradas, salidas y stock del periodo seleccionado</p>
            </div>
            <button type="button" class="btn-main-action" id="imprimir-informe"><i class="fa-solid fa-print"></i> Imprimir informe</button>
        </div>
        <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

        <form method="get" class="report-filters">
            <div class="form-group"><label for="fecha_inicio">Desde</label><input id="fecha_inicio" name="fecha_inicio" type="date" value="<?php echo htmlspecialchars($fechaInicio, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="form-group"><label for="fecha_fin">Hasta</label><input id="fecha_fin" name="fecha_fin" type="date" value="<?php echo htmlspecialchars($fechaFin, ENT_QUOTES, 'UTF-8'); ?>"></div>
            <div class="form-group"><label for="tipo">Movimiento</label><select id="tipo" name="tipo"><option value="todos" <?php echo $tipoMovimiento === 'todos' ? 'selected' : ''; ?>>Entradas y salidas</option><option value="entrada" <?php echo $tipoMovimiento === 'entrada' ? 'selected' : ''; ?>>Solo entradas</option><option value="salida" <?php echo $tipoMovimiento === 'salida' ? 'selected' : ''; ?>>Solo salidas</option></select></div>
            <div class="form-group"><label for="id_producto">Producto</label><select id="id_producto" name="id_producto"><option value="0">Todos los productos</option><?php while ($producto = $productos->fetch_assoc()): ?><option value="<?php echo $producto['id_producto']; ?>" <?php echo $idProducto === (int) $producto['id_producto'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($producto['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
            <div class="form-group"><label for="id_tienda">Tienda</label><select id="id_tienda" name="id_tienda"><option value="0">Todas las tiendas</option><?php while ($tienda = $tiendas->fetch_assoc()): ?><option value="<?php echo $tienda['id_tienda']; ?>" <?php echo $idTienda === (int) $tienda['id_tienda'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select></div>
            <button type="submit" class="btn-save"><i class="fa-solid fa-filter"></i> Filtrar</button>
        </form>

        <section class="report-summary">
            <article class="report-stat report-stat-entry"><i class="fa-solid fa-arrow-down"></i><div><strong><?php echo (int) $resumenDatos['total_entradas']; ?></strong><span>Unidades entradas</span></div></article>
            <article class="report-stat report-stat-exit"><i class="fa-solid fa-arrow-up"></i><div><strong><?php echo (int) $resumenDatos['total_salidas']; ?></strong><span>Unidades salidas</span></div></article>
            <article class="report-stat report-stat-balance"><i class="fa-solid fa-scale-balanced"></i><div><strong><?php echo $variacion >= 0 ? '+' : ''; ?><?php echo $variacion; ?></strong><span>Balance del periodo</span></div></article>
            <article class="report-stat report-stat-total"><i class="fa-solid fa-list-check"></i><div><strong><?php echo (int) $resumenDatos['total_movimientos']; ?></strong><span>Movimientos registrados</span></div></article>
        </section>

        <section class="report-panel">
            <div class="report-panel-heading"><div><h3>Detalle de movimientos</h3><p>Qué producto se movió, cuándo y desde qué tienda</p></div></div>
            <div class="report-table-wrap">
                <table class="report-table">
                    <thead><tr><th>Fecha</th><th>Tipo</th><th>Producto</th><th>Tienda</th><th>Cantidad</th><th>Usuario</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php if (!$movimientos || $movimientos->num_rows === 0): ?><tr><td colspan="7" class="report-empty">No hay movimientos para los filtros seleccionados.</td></tr><?php else: while ($movimiento = $movimientos->fetch_assoc()): ?><tr><td><?php echo date('d/m/Y', strtotime($movimiento['fecha'])); ?></td><td><span class="movement-badge <?php echo strtolower($movimiento['tipo']); ?>"><?php echo $movimiento['tipo']; ?></span></td><td><strong><?php echo htmlspecialchars($movimiento['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo htmlspecialchars($movimiento['nombre_tienda'] ?: 'Tienda no asignada', ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $movimiento['cantidad']; ?> unidades</td><td><?php echo htmlspecialchars($movimiento['usuario'] ?: 'Usuario no identificado', ENT_QUOTES, 'UTF-8'); ?></td><td><form method="post" class="report-delete-form" onsubmit="return confirmarEliminacionMovimiento('<?php echo htmlspecialchars($movimiento['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?>');"><input type="hidden" name="accion" value="eliminar_movimiento"><input type="hidden" name="id_movimiento" value="<?php echo (int) $movimiento['id_movimiento']; ?>"><input type="hidden" name="tipo_movimiento" value="<?php echo htmlspecialchars($movimiento['tipo'], ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="fecha_inicio" value="<?php echo htmlspecialchars($fechaInicio, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="fecha_fin" value="<?php echo htmlspecialchars($fechaFin, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="filtro_tipo" value="<?php echo htmlspecialchars($tipoMovimiento, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="id_producto" value="<?php echo $idProducto; ?>"><input type="hidden" name="id_tienda" value="<?php echo $idTienda; ?>"><button type="submit" class="btn-icon danger" title="Eliminar movimiento" aria-label="Eliminar movimiento"><i class="fa-solid fa-trash"></i></button></form></td></tr><?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="report-panel stock-report">
            <div class="report-panel-heading"><div><h3>Stock por producto</h3><p>Stock actual y proyección después del movimiento del periodo</p></div></div>
            <div class="report-table-wrap"><table class="report-table"><thead><tr><th>Producto</th><th>Stock actual</th><th>Entradas del periodo</th><th>Salidas del periodo</th><th>Proyección</th></tr></thead><tbody><?php while ($filaStock = $stock->fetch_assoc()): $proyeccion = (int) $filaStock['stock'] + (int) $filaStock['entradas'] - (int) $filaStock['salidas']; ?><tr><td><strong><?php echo htmlspecialchars($filaStock['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo (int) $filaStock['stock']; ?> unidades</td><td class="text-entry">+<?php echo (int) $filaStock['entradas']; ?></td><td class="text-exit">-<?php echo (int) $filaStock['salidas']; ?></td><td><strong><?php echo $proyeccion; ?> unidades</strong></td></tr><?php endwhile; ?></tbody></table></div>
        </section>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
    <script>
        function confirmarEliminacionMovimiento(nombre) {
            return window.confirm('¿Seguro que quieres eliminar el movimiento de "' + nombre + '"? El stock será recalculado.');
        }
    </script>
</body>
</html>
