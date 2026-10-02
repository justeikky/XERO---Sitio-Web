<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/actividad.php';

$placeholdersBusqueda = [
    'categorias.php' => 'Buscar categoría...',
    'categoria.php' => 'Buscar categoría...',
    'configuracion.php' => 'Buscar en ajustes...',
    'productos.php' => 'Buscar producto...',
    'editar-producto.php' => 'Buscar producto...',
    'tiendas.php' => 'Buscar tienda...',
    'editar-tienda.php' => 'Buscar tienda...',
    'proveedores.php' => 'Buscar proveedor...',
    'informes.php' => 'Buscar en informes...',
    'buscar.php' => 'Buscar en XERO...',
];
$placeholderBusqueda = $placeholdersBusqueda[basename($_SERVER['PHP_SELF'])] ?? 'Buscar...';
$rutaBuscador = basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'php' ? 'buscar.php' : '../php/buscar.php';
$rutaHtml = basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'php' ? '../html/' : '';
$productosStockBajo = [];
$consultaStockBajo = $conexion->query('SELECT id_producto, nombre_producto, stock FROM productos WHERE stock <= 5 ORDER BY stock ASC, nombre_producto');
if ($consultaStockBajo) {
    $productosStockBajo = $consultaStockBajo->fetch_all(MYSQLI_ASSOC);
}
$actividadesRecientes = [];
$consultaActividad = $conexion->query('SELECT accion, entidad, descripcion, creado_en FROM actividad ORDER BY creado_en DESC, id_actividad DESC LIMIT 8');
if ($consultaActividad) {
    $actividadesRecientes = $consultaActividad->fetch_all(MYSQLI_ASSOC);
}
$avatarUrl = '../img/logo-xero.png';
$usuarioId = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
if ($usuarioId > 0) {
    $avatarConsulta = $conexion->prepare('SELECT avatar FROM usuario WHERE id_usuario = ?');
    if ($avatarConsulta) {
        $avatarConsulta->bind_param('i', $usuarioId);
        $avatarConsulta->execute();
        $avatarUsuario = $avatarConsulta->get_result()->fetch_assoc();
        if (!empty($avatarUsuario['avatar']) && is_file(__DIR__ . '/../' . $avatarUsuario['avatar'])) {
            $avatarUrl = '../' . $avatarUsuario['avatar'];
        }
    }
}
?>

<!-- PANEL DERECHO CON BARRA SUPERIOR -->
    <main class="main-content">
        <!-- BARRA SUPERIOR (BUSCADOR Y USUARIO) -->
        <header class="topbar">
            <button type="button" class="topbar-menu-toggle" id="sidebar-toggle" aria-label="Cerrar navegación" aria-controls="sidebar-nav" aria-expanded="true" title="Cerrar navegación">
                <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
            </button>
            <form class="search-box" action="<?php echo $rutaBuscador; ?>" method="get" role="search">
                <input id="busqueda-global" name="q" type="search" placeholder="<?php echo htmlspecialchars($placeholderBusqueda, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" aria-label="Buscar en la página">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
            </form>
                
                <!-- RECARGAR / HISTORIAL -->
                <div class="history-wrapper">
                    <button type="button" class="history-button" id="history-button" aria-label="Historial" aria-expanded="false">
                        <i class="fa-solid fa-rotate-left icon"></i>
                    </button>
                    <div class="history-panel" id="history-panel" hidden>
                        <div class="notification-panel-header"><strong>Actividad reciente</strong><span>Últimas acciones</span></div>
                        <?php if (count($actividadesRecientes) === 0): ?>
                            <p class="notification-empty">Todavía no hay actividad registrada.</p>
                        <?php else: ?>
                            <?php foreach ($actividadesRecientes as $actividad): ?>
                                <div class="history-item">
                                    <i class="fa-solid <?php echo $actividad['accion'] === 'Registró salida' ? 'fa-arrow-up' : ($actividad['accion'] === 'Registró entrada' ? 'fa-arrow-down' : 'fa-clock-rotate-left'); ?>"></i>
                                    <span><strong><?php echo htmlspecialchars($actividad['entidad'], ENT_QUOTES, 'UTF-8'); ?></strong><small><?php echo htmlspecialchars($actividad['descripcion'], ENT_QUOTES, 'UTF-8'); ?></small><time><?php echo tiempoTranscurrido($actividad['creado_en']); ?></time></span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="notification-wrapper">
                    <button type="button" class="notification-button" id="notification-button" aria-label="Notificaciones" aria-expanded="false">
                        <i class="fa-solid fa-bell icon"></i>
                        <?php if (count($productosStockBajo) > 0): ?><span class="notification-count"><?php echo count($productosStockBajo); ?></span><?php endif; ?>
                    </button>
                    <div class="notification-panel" id="notification-panel" hidden>
                        <div class="notification-panel-header">
                            <strong>Notificaciones</strong>
                            <span><?php echo count($productosStockBajo); ?> alerta(s)</span>
                        </div>
                        <?php if (count($productosStockBajo) === 0): ?>
                            <p class="notification-empty">No hay alertas de stock bajo.</p>
                        <?php else: ?>
                            <?php foreach ($productosStockBajo as $productoBajo): ?>
                                <a class="notification-item" href="<?php echo $rutaHtml; ?>movimiento.php?id=<?php echo (int) $productoBajo['id_producto']; ?>">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    <span><strong><?php echo htmlspecialchars($productoBajo['nombre_producto'], ENT_QUOTES, 'UTF-8'); ?></strong><small>Solo quedan <?php echo (int) $productoBajo['stock']; ?> unidades</small></span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- FOTO DE PERFIL -->
                <img src="<?php echo htmlspecialchars($avatarUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="Usuario" class="user-avatar">
            </div>
        </header>