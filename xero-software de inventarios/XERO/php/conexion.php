<?php

$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "xero";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conexion = new mysqli(
        $servidor,
        $usuario,
        $password,
        $base_datos
    );
    $conexion->set_charset('utf8mb4');
} catch (mysqli_sql_exception $excepcion) {
    error_log('Error de conexión a la base de datos XERO: ' . $excepcion->getMessage());
    http_response_code(503);
    exit('No fue posible conectar con la base de datos XERO. Verifica que MySQL esté iniciado y que la configuración sea correcta.');
}

?>
