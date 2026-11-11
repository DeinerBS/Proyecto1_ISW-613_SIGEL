<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';

requirePost('/pages/equipos.php');

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    redirect('/pages/equipos.php?error=equipo_no_encontrado');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'SELECT codigo, nombre, categoria_id, estado FROM equipos WHERE id = :id AND eliminado_en IS NULL'
    );
    $stmt->execute([':id' => $id]);
    $antes = $stmt->fetch();

    if ($antes === false) {
        $conn->rollBack();
        redirect('/pages/equipos.php?error=equipo_no_encontrado');
    }

    // No se elimina si está en un préstamo en curso
    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM prestamo_detalle d
         JOIN prestamos p ON p.id = d.prestamo_id
         WHERE d.equipo_id = :id AND p.estado IN ('solicitado', 'aprobado', 'entregado')"
    );
    $stmt->execute([':id' => $id]);

    if ((int) $stmt->fetchColumn() > 0) {
        $conn->rollBack();
        redirect('/pages/equipos.php?error=equipo_en_prestamo');
    }

    // Eliminación lógica
    $stmt = $conn->prepare('UPDATE equipos SET eliminado_en = NOW() WHERE id = :id');
    $stmt->execute([':id' => $id]);

    registrarBitacora($conn, 'eliminar', 'equipos', $id, $antes, null);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex;
}

redirect('/pages/equipos.php?exito=equipo_eliminado');