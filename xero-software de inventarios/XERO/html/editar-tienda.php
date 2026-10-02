<?php
require_once __DIR__ . '/../php/conexion.php';

$idTienda = (int) ($_GET['id'] ?? $_POST['id_tienda'] ?? 0);
$error = '';

if ($idTienda <= 0) {
    header('Location: tiendas.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nombre = trim($_POST['nombre_tienda'] ?? '');
    $descripcion = trim($_POST['descripcion_tienda'] ?? '');
    $telefono = trim($_POST['telefono_tienda'] ?? '');
    $direccion = trim($_POST['direccion_tienda'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $error = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
    } elseif ($descripcion !== '' && mb_strlen($descripcion) > 255) {
        $error = 'La descripción debe tener máximo 255 caracteres.';
    } elseif ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) {
        $error = 'El teléfono debe contener entre 7 y 15 dígitos.';
    } elseif ($direccion !== '' && mb_strlen($direccion) > 150) {
        $error = 'La dirección debe tener máximo 150 caracteres.';
    } else {
        $consulta = $conexion->prepare('UPDATE tiendas SET nombre_tienda = ?, descripcion = ?, telefono = ?, direccion = ? WHERE id_tienda = ?');
        $consulta->bind_param('ssssi', $nombre, $descripcion, $telefono, $direccion, $idTienda);
        if ($consulta->execute()) {
            header('Location: tiendas.php?actualizada=1');
            exit;
        }
        $error = 'No se pudo actualizar la tienda.';
    }
}

$consultaTienda = $conexion->prepare('SELECT nombre_tienda, descripcion, telefono, direccion FROM tiendas WHERE id_tienda = ?');
$consultaTienda->bind_param('i', $idTienda);
$consultaTienda->execute();
$tienda = $consultaTienda->get_result()->fetch_assoc();

if (!$tienda) {
    header('Location: tiendas.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Editar tienda</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>
        <div class="form-card">
            <h2>Editar tienda</h2>
            <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="id_tienda" value="<?php echo $idTienda; ?>">
                <div class="form-group"><label for="nombre_tienda">Nombre <span class="required-mark">*</span></label><input id="nombre_tienda" name="nombre_tienda" type="text" maxlength="100" value="<?php echo htmlspecialchars($tienda['nombre_tienda'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="form-group"><label for="descripcion_tienda">Descripción</label><input id="descripcion_tienda" name="descripcion_tienda" type="text" maxlength="255" value="<?php echo htmlspecialchars($tienda['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-row"><div class="form-group"><label for="telefono_tienda">Teléfono</label><input id="telefono_tienda" name="telefono_tienda" type="tel" inputmode="numeric" pattern="[0-9]{7,15}" maxlength="15" value="<?php echo htmlspecialchars($tienda['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div><div class="form-group"><label for="direccion_tienda">Dirección</label><input id="direccion_tienda" name="direccion_tienda" type="text" maxlength="150" value="<?php echo htmlspecialchars($tienda['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div></div>
                <div class="form-buttons"><a href="tiendas.php" class="btn-cancel">Cancelar</a><button type="submit" class="btn-save">Guardar cambios</button></div>
            </form>
        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
</body>
</html>
