<?php
$paginaActual = basename($_SERVER['PHP_SELF']);
$rutaHtml = basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'php' ? '../html/' : '';
?>

<aside class="sidebar">
        <div class="sidebar-top">
            <!-- LOGO XERO -->
            <div class="sidebar-logo">
                <img src="../img/logo-xero.png" alt="Logo">
                <span class="logo-text">XERO</span>
            </div>
            
            <!-- NAVEGACIÓN -->
            <nav class="menu" id="sidebar-nav">
                <a href="<?php echo $rutaHtml; ?>inicio.php" class="menu-item<?php echo $paginaActual === 'inicio.php' ? ' active' : ''; ?>" aria-label="Inicio" title="Inicio">
                    <span class="menu-short" aria-hidden="true">IN</span><span class="menu-label">Inicio</span>
                </a>
                <a href="<?php echo $rutaHtml; ?>productos.php" class="menu-item<?php echo in_array($paginaActual, ['productos.php', 'producto.php', 'editar-producto.php', 'agregar.php', 'movimiento.php'], true) ? ' active' : ''; ?>" aria-label="Productos" title="Productos">
                    <span class="menu-short" aria-hidden="true">PR</span><span class="menu-label">Productos</span>
                </a>
                <a href="<?php echo $rutaHtml; ?>categorias.php" class="menu-item<?php echo in_array($paginaActual, ['categorias.php', 'categoria.php', 'editar-categoria.php'], true) ? ' active' : ''; ?>" aria-label="Categorías" title="Categorías">
                    <span class="menu-short" aria-hidden="true">CA</span><span class="menu-label">Categorías</span>
                </a>
                <a href="<?php echo $rutaHtml; ?>proveedores.php" class="menu-item<?php echo in_array($paginaActual, ['proveedores.php', 'editar-proveedor.php'], true) ? ' active' : ''; ?>" aria-label="Proveedores" title="Proveedores">
                    <span class="menu-short" aria-hidden="true">PV</span><span class="menu-label">Proveedores</span>
                </a>
                <a href="<?php echo $rutaHtml; ?>tiendas.php" class="menu-item<?php echo in_array($paginaActual, ['tiendas.php', 'editar-tienda.php'], true) ? ' active' : ''; ?>" aria-label="Tiendas" title="Tiendas">
                    <span class="menu-short" aria-hidden="true">TI</span><span class="menu-label">Tiendas</span>
                </a>
               
                <a href="<?php echo $rutaHtml; ?>informes.php" class="menu-item<?php echo $paginaActual === 'informes.php' ? ' active' : ''; ?>" aria-label="Informes" title="Informes">
                    <span class="menu-short" aria-hidden="true">IF</span><span class="menu-label">Informes</span>
                </a>
                <a href="<?php echo $rutaHtml; ?>configuracion.php" class="menu-item<?php echo $paginaActual === 'configuracion.php' ? ' active' : ''; ?>" aria-label="Ajustes" title="Ajustes">
                    <span class="menu-short" aria-hidden="true">AJ</span><span class="menu-label">Ajustes</span>
                </a>
            </nav>
        </div>

        <!-- ACCIONES INFERIORES -->
        <div class="sidebar-footer">
            <a href="<?php echo $rutaHtml; ?>agregar.php" class="btn-add" aria-label="Añadir registro" title="Añadir registro"><span class="menu-short" aria-hidden="true">+</span><span class="menu-label">+ Añadir registro</span></a>
            <a href="<?php echo $rutaHtml; ?>sesion.php?cerrar_sesion=1" class="btn-logout" aria-label="Cerrar sesión" title="Cerrar sesión">
                <span aria-hidden="true">↪</span><span class="menu-label">Cerrar Sesión</span>
            </a>
        </div>
    </aside>