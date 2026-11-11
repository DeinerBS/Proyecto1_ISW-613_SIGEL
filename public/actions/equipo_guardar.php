<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';
require_once __DIR__ . '/../../utils/equipos.php';

requirePost('/pages/equipos.php');

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$id = null;
if (isset($_POST['id']) && $_POST['id'] !== '') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        redirect('/pages/equipos.php?error=equipo_no_encontrado');
    }
}

$urlForm = '/pages/equipo_form.php?' . ($id ? 'id=' . $id . '&' : '');

// Control #6: validar en el servidor
$codigo = $_POST['codigo'] ?? '';
$codigo = is_string($codigo) ? strtoupper(trim($codigo)) : '';
$nombre = $_POST['nombre'] ?? '';
$nombre = is_string($nombre) ? trim($nombre) : '';
$estado = $_POST['estado'] ?? '';
$categoriaId = filter_var($_POST['categoria_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$version = filter_var($_POST['version'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

// Validar datos: código, nombre, categoría y estado con expresión regular
if (
    !preg_match('/^[A-Z0-9-]{2,30}$/', $codigo)
    || $nombre === '' || mb_strlen($nombre) > 120
    || $categoriaId === false
    || !is_string($estado) || !array_key_exists($estado, ESTADOS_EQUIPO)
    || ($id !== null && $version === false)
) {
    redirect($urlForm . 'error=equipo_invalido');
}

// La categoría debe existir y ser actual, no eliminada.
$stmt = $conn->prepare('SELECT COUNT(*) FROM categorias WHERE id = :id AND eliminado_en IS NULL');
$stmt->execute([':id' => $categoriaId]);
if ((int) $stmt->fetchColumn() === 0) {
    redirect($urlForm . 'error=equipo_invalido');
}

$despues = ['codigo' => $codigo, 'nombre' => $nombre, 'categoria_id' => $categoriaId, 'estado' => $estado];

try {
    $conn->beginTransaction();

    if ($id === null) {
        $stmt = $conn->prepare(
            'INSERT INTO equipos (categoria_id, codigo, nombre, estado)
             VALUES (:categoria_id, :codigo, :nombre, :estado)'
        );
        $stmt->execute([
            ':categoria_id' => $categoriaId,
            ':codigo'       => $codigo,
            ':nombre'       => $nombre,
            ':estado'       => $estado,
        ]);
        $id = (int) $conn->lastInsertId();

        registrarBitacora($conn, 'crear', 'equipos', $id, null, $despues);
        $exito = 'equipo_creado';
    } else {
        $stmt = $conn->prepare(
            'SELECT codigo, nombre, categoria_id, estado FROM equipos WHERE id = :id AND eliminado_en IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $antes = $stmt->fetch();

        if ($antes === false) {
            $conn->rollBack();
            redirect('/pages/equipos.php?error=equipo_no_encontrado');
        }

        // Bloqueo optimista, solo actualiza si nadie cambió la versión
        $stmt = $conn->prepare(
            'UPDATE equipos
             SET codigo = :codigo, nombre = :nombre, categoria_id = :categoria_id, estado = :estado,
                 version = version + 1
             WHERE id = :id AND version = :version'
        );
        $stmt->execute([
            ':codigo'       => $codigo,
            ':nombre'       => $nombre,
            ':categoria_id' => $categoriaId,
            ':estado'       => $estado,
            ':id'           => $id,
            ':version'      => $version,
        ]);

        if ($stmt->rowCount() === 0) {
            $conn->rollBack();
            redirect($urlForm . 'error=equipo_conflicto');
        }

        registrarBitacora($conn, 'editar', 'equipos', $id, $antes, $despues);
        $exito = 'equipo_actualizado';
    }

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    //  el código ya existe 
    if (($ex->errorInfo[1] ?? null) === 1062) {
        redirect($urlForm . 'error=equipo_duplicado');
    }
    throw $ex;
}

redirect('/pages/equipos.php?exito=' . $exito);