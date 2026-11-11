<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';

requirePost('/pages/categorias.php');

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

// Sin id = crear y con id = editar
$id = null;
if (isset($_POST['id']) && $_POST['id'] !== '') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        redirect('/pages/categorias.php?error=categoria_no_encontrada');
    }
}

$urlForm = '/pages/categoria_form.php?' . ($id ? 'id=' . $id . '&' : '');

// Control #6: validación en el servidor
$nombre = $_POST['nombre'] ?? '';
$nombre = is_string($nombre) ? trim($nombre) : '';
$dias = filter_var($_POST['dias_max_prestamo'] ?? '', FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1, 'max_range' => 90]]);

if ($nombre === '' || mb_strlen($nombre) > 80 || $dias === false) {
    redirect($urlForm . 'error=categoria_invalida');
}

$despues = ['nombre' => $nombre, 'dias_max_prestamo' => $dias];

// Cambio + bitácora, se guardan los dos o no guarda nada
try {
    $conn->beginTransaction();

    if ($id === null) {
        $stmt = $conn->prepare('INSERT INTO categorias (nombre, dias_max_prestamo) VALUES (:nombre, :dias)');
        $stmt->execute([':nombre' => $nombre, ':dias' => $dias]);
        $id = (int) $conn->lastInsertId();

        registrarBitacora($conn, 'crear', 'categorias', $id, null, $despues);
        $exito = 'categoria_creada';
    } else {
        $stmt = $conn->prepare(
            'SELECT nombre, dias_max_prestamo FROM categorias WHERE id = :id AND eliminado_en IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $antes = $stmt->fetch();

        if ($antes === false) {
            $conn->rollBack();
            redirect('/pages/categorias.php?error=categoria_no_encontrada');
        }

        $stmt = $conn->prepare('UPDATE categorias SET nombre = :nombre, dias_max_prestamo = :dias WHERE id = :id');
        $stmt->execute([':nombre' => $nombre, ':dias' => $dias, ':id' => $id]);

        registrarBitacora($conn, 'editar', 'categorias', $id, $antes, $despues);
        $exito = 'categoria_actualizada';
    }

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    //  valor repetido en columna UNIQUE
    if (($ex->errorInfo[1] ?? null) === 1062) {
        redirect($urlForm . 'error=categoria_duplicada');
    }
    throw $ex; // control #11: lo atiende el manejador global
}

redirect('/pages/categorias.php?exito=' . $exito);