<?php
function registrarActividad(mysqli $conexion, string $accion, string $entidad, string $descripcion, ?int $idUsuario = null): void
{
    $idUsuario = $idUsuario ?? (int) ($_SESSION['id_usuario'] ?? 0);
    if ($idUsuario <= 0) {
        throw new RuntimeException('No se puede registrar actividad sin un usuario autenticado.');
    }

    $consulta = $conexion->prepare('INSERT INTO actividad (accion, entidad, descripcion, id_usuario) VALUES (?, ?, ?, ?)');
    $consulta->bind_param('sssi', $accion, $entidad, $descripcion, $idUsuario);
    $consulta->execute();
}

function tiempoTranscurrido(string $fecha): string
{
    $segundos = max(0, time() - strtotime($fecha));
    if ($segundos < 60) {
        return 'Hace unos segundos';
    }
    $minutos = intdiv($segundos, 60);
    if ($minutos < 60) {
        return 'Hace ' . $minutos . ($minutos === 1 ? ' minuto' : ' minutos');
    }
    $horas = intdiv($minutos, 60);
    return 'Hace ' . $horas . ($horas === 1 ? ' hora' : ' horas');
}
