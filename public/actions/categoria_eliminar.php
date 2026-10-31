<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/categorias.php');
}

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    redirect('/pages/categorias.php?error=categoria_no_encontrada');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'SELECT nombre, dias_max_prestamo FROM categorias WHERE id = :id AND eliminado_en IS NULL'
    );
    $stmt->execute([':id' => $id]);
    $antes = $stmt->fetch();

    if ($antes === false) {
        $conn->rollBack();
        redirect('/pages/categorias.php?error=categoria_no_encontrada');
    }

    // No se elimina si tiene equipos asignados
    $stmt = $conn->prepare('SELECT COUNT(*) FROM equipos WHERE categoria_id = :id AND eliminado_en IS NULL');
    $stmt->execute([':id' => $id]);

    if ((int) $stmt->fetchColumn() > 0) {
        $conn->rollBack();
        redirect('/pages/categorias.php?error=categoria_con_equipos');
    }

    // Eliminación lógica
    $stmt = $conn->prepare('UPDATE categorias SET eliminado_en = NOW() WHERE id = :id');
    $stmt->execute([':id' => $id]);

    registrarBitacora($conn, 'eliminar', 'categorias', $id, $antes, null);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex; 
}

redirect('/pages/categorias.php?exito=categoria_eliminada');