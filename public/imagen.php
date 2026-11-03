<?php
require_once __DIR__ . '/../utils/database.php';
require_once __DIR__ . '/../utils/auth.php';
require_once __DIR__ . '/../utils/imagenes.php';

requiereLogin($conn);
requiereRol(['admin', 'operador']);

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(404);
    exit;
}

// Solo imágenes de equipos vigentes
$stmt = $conn->prepare(
    'SELECT i.nombre_archivo
     FROM equipo_imagenes i
     JOIN equipos e ON e.id = i.equipo_id
     WHERE i.id = :id AND e.eliminado_en IS NULL'
);
$stmt->execute([':id' => $id]);
$nombreArchivo = $stmt->fetchColumn();

$ruta = $nombreArchivo !== false ? rutaImagen($nombreArchivo) : '';
if ($ruta === '' || !is_file($ruta)) {
    http_response_code(404);
    exit;
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($ruta);
if (!isset(TIPOS_IMAGEN[$mime])) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($ruta));
header('X-Content-Type-Options: nosniff'); // el navegador no adivina otro tipo
readfile($ruta);