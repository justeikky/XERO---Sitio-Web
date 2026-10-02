<?php
require_once __DIR__ . '/../php/conexion.php';
require_once __DIR__ . '/../php/actividad.php';

$tipo = $_POST['tipo'] ?? $_GET['tipo'] ?? 'producto';
$mensaje = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        if ($tipo === 'categoria') {
            $nombre = trim($_POST['nombre_categoria'] ?? '');
            $descripcion = trim($_POST['descripcion_categoria'] ?? '');
            if ($nombre === '' || mb_strlen($nombre) > 100) {
                throw new Exception('El nombre de la categoría es obligatorio y debe tener máximo 100 caracteres.');
            }
            if ($descripcion !== '' && mb_strlen($descripcion) > 255) {
                throw new Exception('La descripción de la categoría debe tener máximo 255 caracteres.');
            }
            $consulta = $conexion->prepare('INSERT INTO categorias (nombre_categoria, descripcion) VALUES (?, ?)');
            $consulta->bind_param('ss', $nombre, $descripcion);
            $consulta->execute();
            registrarActividad($conexion, 'Creó', 'Categoría', 'Creó la categoría "' . $nombre . '".');
            $mensaje = 'La categoría se guardó correctamente.';
        } elseif ($tipo === 'proveedor') {
            $nombre = trim($_POST['nombre_proveedor'] ?? '');
            $descripcion = trim($_POST['descripcion_proveedor'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $correo = trim($_POST['correo'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            if ($nombre === '' || mb_strlen($nombre) > 100) {
                throw new Exception('El nombre del proveedor es obligatorio y debe tener máximo 100 caracteres.');
            }
            if ($descripcion !== '' && mb_strlen($descripcion) > 255) {
                throw new Exception('La descripción del proveedor debe tener máximo 255 caracteres.');
            }
            if ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) {
                throw new Exception('El teléfono debe contener entre 7 y 15 dígitos.');
            }
            if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Escribe un correo electrónico válido.');
            }
            if ($direccion !== '' && mb_strlen($direccion) > 150) {
                throw new Exception('La dirección debe tener máximo 150 caracteres.');
            }
            $consulta = $conexion->prepare('INSERT INTO proveedores (nombre_proveedor, descripcion, telefono, correo, direccion) VALUES (?, ?, ?, ?, ?)');
            $consulta->bind_param('sssss', $nombre, $descripcion, $telefono, $correo, $direccion);
            $consulta->execute();
            registrarActividad($conexion, 'Creó', 'Proveedor', 'Creó el proveedor "' . $nombre . '".');
            $mensaje = 'El proveedor se guardó correctamente.';
        } elseif ($tipo === 'tienda') {
            $nombre = trim($_POST['nombre_tienda'] ?? '');
            $descripcion = trim($_POST['descripcion_tienda'] ?? '');
            $telefono = trim($_POST['telefono_tienda'] ?? '');
            $direccion = trim($_POST['direccion_tienda'] ?? '');
            if ($nombre === '' || mb_strlen($nombre) > 100) {
                throw new Exception('El nombre de la tienda es obligatorio y debe tener máximo 100 caracteres.');
            }
            if ($descripcion !== '' && mb_strlen($descripcion) > 255) {
                throw new Exception('La descripción de la tienda debe tener máximo 255 caracteres.');
            }
            if ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) {
                throw new Exception('El teléfono debe contener entre 7 y 15 dígitos.');
            }
            if ($direccion !== '' && mb_strlen($direccion) > 150) {
                throw new Exception('La dirección debe tener máximo 150 caracteres.');
            }
            $consulta = $conexion->prepare('INSERT INTO tiendas (nombre_tienda, descripcion, telefono, direccion) VALUES (?, ?, ?, ?)');
            if (!$consulta) {
                throw new Exception('No se pudo preparar el registro de la tienda: ' . $conexion->error);
            }
            $consulta->bind_param('ssss', $nombre, $descripcion, $telefono, $direccion);
            if (!$consulta->execute()) {
                throw new Exception('No se pudo guardar la tienda: ' . $consulta->error);
            }
            registrarActividad($conexion, 'Creó', 'Tienda', 'Creó la tienda "' . $nombre . '".');
            $mensaje = 'La tienda se guardó correctamente (ID: ' . $conexion->insert_id . ').';
        } elseif ($tipo === 'producto') {
            $nombre = trim($_POST['nombre_producto'] ?? '');
            $descripcion = trim($_POST['descripcion_producto'] ?? '');
            $precio = (float) ($_POST['precio'] ?? 0);
            $stock = (int) ($_POST['stock'] ?? 0);
            $idCategoria = (int) ($_POST['id_categoria'] ?? 0);
            $idProveedor = (int) ($_POST['id_proveedor'] ?? 0);
            if ($nombre === '' || $precio < 0 || $stock < 0) {
                throw new Exception('Completa los datos del producto con valores válidos.');
            }
            if (!empty($_POST['nueva_categoria'])) {
                $nombreCategoria = trim($_POST['nombre_nueva_categoria'] ?? '');
                if ($nombreCategoria === '') {
                    throw new Exception('Escribe el nombre de la nueva categoría.');
                }
                $consulta = $conexion->prepare('INSERT INTO categorias (nombre_categoria) VALUES (?)');
                $consulta->bind_param('s', $nombreCategoria);
                $consulta->execute();
                $idCategoria = $conexion->insert_id;
            }
            if (!empty($_POST['nuevo_proveedor'])) {
                $nombreProveedor = trim($_POST['nombre_nuevo_proveedor'] ?? '');
                if ($nombreProveedor === '') {
                    throw new Exception('Escribe el nombre del nuevo proveedor.');
                }
                $consulta = $conexion->prepare('INSERT INTO proveedores (nombre_proveedor) VALUES (?)');
                $consulta->bind_param('s', $nombreProveedor);
                $consulta->execute();
                $idProveedor = $conexion->insert_id;
            }
            if ($idCategoria === 0 || $idProveedor === 0) {
                throw new Exception('Selecciona una categoría y un proveedor.');
            }
            $consulta = $conexion->prepare('INSERT INTO productos (nombre_producto, descripcion, precio, stock, id_categoria, id_proveedor) VALUES (?, ?, ?, ?, ?, ?)');
            $consulta->bind_param('ssdiii', $nombre, $descripcion, $precio, $stock, $idCategoria, $idProveedor);
            $consulta->execute();
            registrarActividad($conexion, 'Creó', 'Producto', 'Creó el producto "' . $nombre . '" con ' . $stock . ' unidades.');
            $mensaje = 'El producto se guardó correctamente.';
        } else {
            throw new Exception('Selecciona un tipo de registro válido.');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$categorias = $conexion->query('SELECT id_categoria, nombre_categoria FROM categorias ORDER BY nombre_categoria');
$proveedores = $conexion->query('SELECT id_proveedor, nombre_proveedor FROM proveedores ORDER BY nombre_proveedor');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Añadir registro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="form-card">
            <h2>¿Qué quieres añadir?</h2>
            <?php if ($mensaje !== ''): ?><div class="form-message success"><?php echo htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>

            <div class="entity-selector" role="group" aria-label="Tipo de registro">
                <button type="button" class="entity-option" data-type="producto"><i class="fa-solid fa-box"></i> Producto</button>
                <button type="button" class="entity-option" data-type="categoria"><i class="fa-solid fa-tags"></i> Categoría</button>
                <button type="button" class="entity-option" data-type="proveedor"><i class="fa-solid fa-truck-field"></i> Proveedor</button>
                <button type="button" class="entity-option" data-type="tienda"><i class="fa-solid fa-store"></i> Tienda</button>
            </div>

            <form method="post" id="registro-form">
                <input type="hidden" name="tipo" id="tipo" value="<?php echo htmlspecialchars($tipo, ENT_QUOTES, 'UTF-8'); ?>">

                <section class="entity-fields" data-fields="producto">
                    <h3>Datos del producto</h3>
                    <div class="form-group"><label for="nombre_producto">Nombre del producto</label><input id="nombre_producto" name="nombre_producto" type="text" placeholder="Ej. Unisex T-shirt Blanca"></div>
                    <div class="form-group"><label for="descripcion_producto">Descripción</label><input id="descripcion_producto" name="descripcion_producto" type="text" placeholder="Descripción del producto"></div>
                    <div class="form-row">
                        <div class="form-group"><label for="precio">Precio ($)</label><input id="precio" name="precio" type="number" step="0.01" min="0" placeholder="0.00"></div>
                        <div class="form-group"><label for="stock">Stock inicial</label><input id="stock" name="stock" type="number" min="0" placeholder="0"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="id_categoria">Categoría</label>
                            <select id="id_categoria" name="id_categoria"><option value="">Seleccionar...</option><?php while ($categoria = $categorias->fetch_assoc()): ?><option value="<?php echo $categoria['id_categoria']; ?>"><?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select>
                            <button type="button" class="quick-add" data-target="nueva-categoria">+ Añadir otra categoría</button>
                            <div class="quick-fields" id="nueva-categoria"><input type="text" name="nombre_nueva_categoria" placeholder="Nombre de la nueva categoría"><label class="check-option"><input type="checkbox" name="nueva_categoria" value="1"> Usar esta nueva categoría</label></div>
                        </div>
                        <div class="form-group">
                            <label for="id_proveedor">Proveedor</label>
                            <select id="id_proveedor" name="id_proveedor"><option value="">Seleccionar...</option><?php while ($proveedor = $proveedores->fetch_assoc()): ?><option value="<?php echo $proveedor['id_proveedor']; ?>"><?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?></option><?php endwhile; ?></select>
                            <button type="button" class="quick-add" data-target="nuevo-proveedor">+ Añadir otro proveedor</button>
                            <div class="quick-fields" id="nuevo-proveedor"><input type="text" name="nombre_nuevo_proveedor" placeholder="Nombre del nuevo proveedor"><label class="check-option"><input type="checkbox" name="nuevo_proveedor" value="1"> Usar este nuevo proveedor</label></div>
                        </div>
                    </div>
                </section>

                <section class="entity-fields" data-fields="categoria">
                    <h3>Datos de la categoría</h3>
                    <div class="form-group"><label for="nombre_categoria">Nombre <span class="required-mark">*</span></label><input id="nombre_categoria" name="nombre_categoria" type="text" maxlength="100" placeholder="Ej. Ropa" required></div>
                    <div class="form-group"><label for="descripcion_categoria">Descripción</label><input id="descripcion_categoria" name="descripcion_categoria" type="text" maxlength="255" placeholder="Descripción de la categoría"></div>
                </section>

                <section class="entity-fields" data-fields="proveedor">
                    <h3>Datos del proveedor</h3>
                    <div class="form-group"><label for="nombre_proveedor">Nombre <span class="required-mark">*</span></label><input id="nombre_proveedor" name="nombre_proveedor" type="text" maxlength="100" placeholder="Nombre del proveedor" required></div>
                    <div class="form-group"><label for="descripcion_proveedor">Descripción</label><input id="descripcion_proveedor" name="descripcion_proveedor" type="text" maxlength="255" placeholder="Descripción del proveedor"></div>
                    <div class="form-row"><div class="form-group"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" inputmode="numeric" pattern="[0-9]{7,15}" maxlength="15" placeholder="Teléfono"></div><div class="form-group"><label for="correo">Correo</label><input id="correo" name="correo" type="email" maxlength="100" placeholder="correo@ejemplo.com"></div></div>
                    <div class="form-group"><label for="direccion">Dirección</label><input id="direccion" name="direccion" type="text" maxlength="150" placeholder="Dirección"></div>
                </section>

                <section class="entity-fields" data-fields="tienda">
                    <h3>Datos de la tienda</h3>
                    <div class="form-group"><label for="nombre_tienda">Nombre <span class="required-mark">*</span></label><input id="nombre_tienda" name="nombre_tienda" type="text" maxlength="100" placeholder="Ej. Tienda principal" required></div>
                    <div class="form-group"><label for="descripcion_tienda">Descripción</label><input id="descripcion_tienda" name="descripcion_tienda" type="text" maxlength="255" placeholder="Descripción de la tienda"></div>
                    <div class="form-row"><div class="form-group"><label for="telefono_tienda">Teléfono</label><input id="telefono_tienda" name="telefono_tienda" type="tel" inputmode="numeric" pattern="[0-9]{7,15}" maxlength="15" placeholder="Teléfono"></div><div class="form-group"><label for="direccion_tienda">Dirección</label><input id="direccion_tienda" name="direccion_tienda" type="text" maxlength="150" placeholder="Dirección"></div></div>
                </section>

                <div class="form-buttons"><a href="productos.php" class="btn-cancel">Cancelar</a><button type="submit" class="btn-save">Guardar registro</button></div>
            </form>
        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
    <script>
        const tipoInicial = <?php echo json_encode($tipo); ?>;
        const tipoInput = document.getElementById('tipo');
        const botonesTipo = document.querySelectorAll('.entity-option');
        const secciones = document.querySelectorAll('.entity-fields');
        function mostrarTipo(tipo) {
            tipoInput.value = tipo;
            botonesTipo.forEach((boton) => boton.classList.toggle('selected', boton.dataset.type === tipo));
            secciones.forEach((seccion) => {
                const esActiva = seccion.dataset.fields === tipo;
                seccion.classList.toggle('visible', esActiva);
                seccion.querySelectorAll('input, select, textarea').forEach((campo) => {
                    campo.disabled = !esActiva;
                });
            });
        }
        botonesTipo.forEach((boton) => boton.addEventListener('click', () => mostrarTipo(boton.dataset.type)));
        document.querySelectorAll('.quick-add').forEach((boton) => boton.addEventListener('click', () => document.getElementById(boton.dataset.target).classList.toggle('visible')));
        mostrarTipo(['producto', 'categoria', 'proveedor', 'tienda'].includes(tipoInicial) ? tipoInicial : 'producto');
    </script>
</body>
</html>
