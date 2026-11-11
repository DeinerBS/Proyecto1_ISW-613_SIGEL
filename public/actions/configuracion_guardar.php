<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';
require_once __DIR__ . '/../../utils/horario.php';

requirePost('/pages/configuracion.php');

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$inicio = $_POST['hora_inicio'] ?? '';
$fin    = $_POST['hora_fin'] ?? '';

// Formato HH:MM y el inicio antes que el fin
if (!horaValida($inicio) || !horaValida($fin) || $inicio >= $fin) {
    redirect('/pages/configuracion.php?error=horario_invalido');
}

try {
    $conn->beginTransaction();

    $antes = obtenerHorario($conn);

    $stmt = $conn->prepare('UPDATE configuracion SET valor = :valor WHERE clave = :clave');
    $stmt->execute([':valor' => $inicio, ':clave' => 'hora_inicio']);
    $stmt->execute([':valor' => $fin, ':clave' => 'hora_fin']);

    registrarBitacora($conn, 'editar', 'configuracion', null, $antes, ['inicio' => $inicio, 'fin' => $fin]);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex;
}

redirect('/pages/configuracion.php?exito=horario_actualizado');