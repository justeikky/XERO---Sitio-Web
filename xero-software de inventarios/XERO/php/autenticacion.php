<?php

function usuarioAutenticadoId(): int
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    $usuarioId = isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : 0;
    if ($usuarioId <= 0) {
        $rutaInicioSesion = basename(dirname($_SERVER['SCRIPT_FILENAME'])) === 'php'
            ? '../html/sesion.php?sesion_expirada=1'
            : 'sesion.php?sesion_expirada=1';
        header('Location: ' . $rutaInicioSesion);
        exit;
    }

    return $usuarioId;
}
