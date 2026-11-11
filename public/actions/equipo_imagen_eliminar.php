<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';
require_once __DIR__ . '/../../utils/imagenes.php';

requirePost('/pages/equipos.php');

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    redirect('/pages/equipos.php?error=imagen_no_encontrada');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare('SELECT equipo_id, nombre_archivo FROM equipo_imagenes WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $imagen = $stmt->fetch();

    if ($imagen === false) {
        $conn->rollBack();
        redirect('/pages/equipos.php?error=imagen_no_encontrada');
    }

    $stmt = $conn->prepare('DELETE FROM equipo_imagenes WHERE id = :id');
    $stmt->execute([':id' => $id]);

    registrarBitacora($conn, 'eliminar', 'equipo_imagenes', $id, $imagen, null);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex;
}

// El archivo se borra después de confirmar en la BD
$ruta = rutaImagen($imagen['nombre_archivo']);
if (is_file($ruta)) {
    unlink($ruta);
}

redirect('/pages/equipo_ver.php?id=' . (int) $imagen['equipo_id'] . '&exito=imagen_eliminada');