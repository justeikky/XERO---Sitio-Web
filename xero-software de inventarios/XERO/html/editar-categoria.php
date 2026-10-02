<?php
require_once __DIR__ . '/../php/conexion.php';

$idCategoria = (int) ($_GET['id'] ?? $_POST['id_categoria'] ?? 0);
$error = '';

if ($idCategoria <= 0) {
    header('Location: categorias.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $nombre = trim($_POST['nombre_categoria'] ?? '');
    $descripcion = trim($_POST['descripcion_categoria'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $error = 'El nombre es obligatorio y debe tener máximo 100 caracteres.';
    } elseif ($descripcion !== '' && mb_strlen($descripcion) > 255) {
        $error = 'La descripción debe tener máximo 255 caracteres.';
    } else {
        $consulta = $conexion->prepare('UPDATE categorias SET nombre_categoria = ?, descripcion = ? WHERE id_categoria = ?');
        $consulta->bind_param('ssi', $nombre, $descripcion, $idCategoria);
        if ($consulta->execute()) {
            header('Location: categorias.php?actualizada=1');
            exit;
        }
        $error = 'No se pudo actualizar la categoría.';
    }
}

$consultaCategoria = $conexion->prepare('SELECT nombre_categoria, descripcion FROM categorias WHERE id_categoria = ?');
$consultaCategoria->bind_param('i', $idCategoria);
$consultaCategoria->execute();
$categoria = $consultaCategoria->get_result()->fetch_assoc();

if (!$categoria) {
    header('Location: categorias.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>XERO - Editar categoría</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../php/menu.php'; ?>
    <?php include __DIR__ . '/../php/encabezado.php'; ?>

        <div class="form-card">
            <h2>Editar categoría</h2>
            <?php if ($error !== ''): ?><div class="form-message error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div><?php endif; ?>
            <form method="post">
                <input type="hidden" name="id_categoria" value="<?php echo $idCategoria; ?>">
                <div class="form-group"><label for="nombre_categoria">Nombre <span class="required-mark">*</span></label><input id="nombre_categoria" name="nombre_categoria" type="text" maxlength="100" value="<?php echo htmlspecialchars($categoria['nombre_categoria'], ENT_QUOTES, 'UTF-8'); ?>" required></div>
                <div class="form-group"><label for="descripcion_categoria">Descripción</label><input id="descripcion_categoria" name="descripcion_categoria" type="text" maxlength="255" value="<?php echo htmlspecialchars($categoria['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div>
                <div class="form-buttons"><a href="categorias.php" class="btn-cancel">Cancelar</a><button type="submit" class="btn-save">Guardar cambios</button></div>
            </form>
        </div>
        <?php include __DIR__ . '/../php/footer.php'; ?>
    </main>
</body>
</html>
