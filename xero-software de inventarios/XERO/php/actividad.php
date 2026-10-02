<?php
function registrarActividad(mysqli $conexion, string $accion, string $entidad, string $descripcion, int $idUsuario = 1): void
{
    $consulta = $conexion->prepare('INSERT INTO actividad (accion, entidad, descripcion, id_usuario) VALUES (?, ?, ?, ?)');
    if ($consulta) {
        $consulta->bind_param('sssi', $accion, $entidad, $descripcion, $idUsuario);
        $consulta->execute();
    }
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
