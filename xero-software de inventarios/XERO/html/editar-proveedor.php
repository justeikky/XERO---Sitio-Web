<?php
require_once __DIR__ . '/../php/conexion.php';

$idProveedor = (int) ($_GET['id'] ?? $_POST['id_proveedor'] ?? 0);
$error = '';

if ($idProveedor <= 0) {
    header('Location: proveedores.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nombre = trim($_POST['nombre_proveedor'] ?? '');
    $descripcion = trim($_POST['descripcion_proveedor'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $error = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
    } elseif ($descripcion !== '' && mb_strlen($descripcion) > 255) {
        $error = 'La descripción debe tener máximo 255 caracteres.';
    } elseif ($telefono !== '' && !preg_match('/^\d{7,15}$/', $telefono)) {
        $error = 'El teléfono debe contener entre 7 y 15 dígitos.';
    } elseif ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'Escribe un correo electrónico válido.';
    } elseif ($direccion !== '' && mb_strlen($direccion) > 150) {
        $error = 'La dirección debe tener máximo 150 caracteres.';
    } else {
        $consulta = $conexion->prepare('UPDATE proveedores SET nombre_proveedor = ?, descripcion = ?, telefono = ?, correo = ?, direccion = ? WHERE id_proveedor = ?');
        $consulta->bind_param('sssssi', $nombre, $descripcion, $telefono, $correo, $direccion, $idProveedor);
        if ($consulta->execute()) {
            header('Location: proveedores.php?actualizado=1');
            exit;
        }
        $error = 'No se pudo actualizar el proveedor.';
    }
}

$consultaProveedor = $conexion->prepare('SELECT nombre_proveedor, descripcion, telefono, correo, direccion FROM proveedores WHERE id_proveedor = ?');
$consultaProveedor->bind_param('i', $idProveedor);
$consultaProveedor->execute();
$proveedor = $consultaProveedor->get_result()->fetch_assoc();

if (!$proveedor) {
    header('Location: proveedores.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Editar proveedor</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="form-card">
            <h2>Editar proveedor</h2>
            <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="id_proveedor" value="<?php echo $idProveedor; ?>">
                <div class="form-group"><label for="nombre_proveedor">Nombre <span class="required-mark">*</span></label><input id="nombre_proveedor" name="nombre_proveedor" type="text" maxlength="100" value="<?php echo htmlspecialchars($proveedor['nombre_proveedor'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="form-group"><label for="descripcion_proveedor">Descripción</label><input id="descripcion_proveedor" name="descripcion_proveedor" type="text" maxlength="255" value="<?php echo htmlspecialchars($proveedor['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-row">
                    <div class="form-group"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" inputmode="numeric" pattern="[0-9]{7,15}" maxlength="15" value="<?php echo htmlspecialchars($proveedor['telefono'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                    <div class="form-group"><label for="correo">Correo</label><input id="correo" name="correo" type="email" maxlength="100" value="<?php echo htmlspecialchars($proveedor['correo'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                </div>
                <div class="form-group"><label for="direccion">Dirección</label><input id="direccion" name="direccion" type="text" maxlength="150" value="<?php echo htmlspecialchars($proveedor['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-buttons"><a href="proveedores.php" class="btn-cancel">Cancelar</a><button type="submit" class="btn-save">Guardar cambios</button></div>
            </form>
        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
</body>
</html>
