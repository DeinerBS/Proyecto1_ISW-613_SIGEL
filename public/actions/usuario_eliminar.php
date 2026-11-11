<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';

requirePost('/pages/usuarios.php');

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    redirect('/pages/usuarios.php?error=usuario_no_encontrado');
}

// El botón está oculto en la propia fila, pero se valida aquí
if ($id === $_SESSION['usuario_id']) {
    redirect('/pages/usuarios.php?error=no_eliminar_propio');
}

try {
    $conn->beginTransaction();

    $stmt = $conn->prepare(
        'SELECT nombre, correo, rol_id, activo FROM usuarios WHERE id = :id AND eliminado_en IS NULL'
    );
    $stmt->execute([':id' => $id]);
    $antes = $stmt->fetch();

    if ($antes === false) {
        $conn->rollBack();
        redirect('/pages/usuarios.php?error=usuario_no_encontrado');
    }

    // No se elimina si tiene préstamos en curso
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM prestamos
         WHERE usuario_id = :id AND estado IN ('solicitado', 'aprobado', 'entregado')"
    );
    $stmt->execute([':id' => $id]);

    if ((int) $stmt->fetchColumn() > 0) {
        $conn->rollBack();
        redirect('/pages/usuarios.php?error=usuario_con_prestamos');
    }

    // Eliminación lógica
    $stmt = $conn->prepare('UPDATE usuarios SET eliminado_en = NOW() WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $stmt = $conn->prepare('DELETE FROM auth_tokens WHERE usuario_id = :id');
    $stmt->execute([':id' => $id]);

    registrarBitacora($conn, 'eliminar', 'usuarios', $id, $antes, null);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex;
}

redirect('/pages/usuarios.php?exito=usuario_eliminado');