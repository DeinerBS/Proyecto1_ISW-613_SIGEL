<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';
require_once __DIR__ . '/../../utils/imagenes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/equipos.php');
}

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$equipoId = filter_var($_POST['equipo_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($equipoId === false) {
    redirect('/pages/equipos.php?error=equipo_no_encontrado');
}

$urlVer = '/pages/equipo_ver.php?id=' . $equipoId . '&';

$stmt = $conn->prepare('SELECT COUNT(*) FROM equipos WHERE id = :id AND eliminado_en IS NULL');
$stmt->execute([':id' => $equipoId]);
if ((int) $stmt->fetchColumn() === 0) {
    redirect('/pages/equipos.php?error=equipo_no_encontrado');
}

$stmt = $conn->prepare('SELECT COUNT(*) FROM equipo_imagenes WHERE equipo_id = :id');
$stmt->execute([':id' => $equipoId]);
if ((int) $stmt->fetchColumn() >= MAX_IMAGENES_EQUIPO) {
    redirect($urlVer . 'error=imagen_limite');
}

// Control #7 :
$archivo = $_FILES['imagen'] ?? null;

// Un solo archivo y subido sin errores
if (!is_array($archivo) || !isset($archivo['error']) || is_array($archivo['error'])
    || $archivo['error'] !== UPLOAD_ERR_OK) {
    redirect($urlVer . 'error=imagen_invalida');
}

// 1. Tamaño
if ($archivo['size'] > MAX_BYTES_IMAGEN) {
    redirect($urlVer . 'error=imagen_grande');
}

$extension = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
if (!in_array($extension, EXTENSIONES_IMAGEN, true)) {
    redirect($urlVer . 'error=imagen_invalida');
}

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
if (!isset(TIPOS_IMAGEN[$mime])) {
    redirect($urlVer . 'error=imagen_invalida');
}

// 4. Nombre generado por el servidor
$nombreArchivo = bin2hex(random_bytes(16)) . '.' . TIPOS_IMAGEN[$mime];

if (!move_uploaded_file($archivo['tmp_name'], rutaImagen($nombreArchivo))) {
    error_log('No se pudo guardar la imagen en storage/equipos');
    redirect($urlVer . 'error=error_servidor');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare('INSERT INTO equipo_imagenes (equipo_id, nombre_archivo) VALUES (:equipo_id, :nombre)');
    $stmt->execute([':equipo_id' => $equipoId, ':nombre' => $nombreArchivo]);
    $imagenId = (int) $conn->lastInsertId();

    registrarBitacora($conn, 'crear', 'equipo_imagenes', $imagenId, null,
        ['equipo_id' => $equipoId, 'nombre_archivo' => $nombreArchivo]);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    unlink(rutaImagen($nombreArchivo)); // sin registro en BD, el archivo esta de mas
    throw $ex;
}

redirect($urlVer . 'exito=imagen_subida');