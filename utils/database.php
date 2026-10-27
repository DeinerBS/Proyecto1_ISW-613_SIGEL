<?php
require_once __DIR__ . '/../config/config.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';

try {
    $conn = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $exception) {
    // Se registra el detalle, pero al usuario no se le muestra nada interno
    error_log('Error de conexión a la BD: ' . $exception->getMessage());
    http_response_code(500);
    exit('El servicio no está disponible en este momento. Intente más tarde.');
}