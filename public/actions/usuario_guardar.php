<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/usuarios.php');
}

requiereLogin($conn);
requiereRol(['admin']);
verificarCsrf();

$id = null;
if (isset($_POST['id']) && $_POST['id'] !== '') {
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        redirect('/pages/usuarios.php?error=usuario_no_encontrado');
    }
}

$urlForm = '/pages/usuario_form.php?' . ($id ? 'id=' . $id . '&' : '');

// Control #6: validar en el servidor
$nombre   = $_POST['nombre'] ?? '';
$nombre   = is_string($nombre) ? trim($nombre) : '';
$correo   = $_POST['correo'] ?? '';
$correo   = is_string($correo) ? trim($correo) : '';
$password = $_POST['password'] ?? '';
$password = is_string($password) ? $password : '';
$rolId    = filter_var($_POST['rol_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$activo   = isset($_POST['activo']) ? 1 : 0;

if (
    $nombre === '' || mb_strlen($nombre) > 100
    || $correo === '' || strlen($correo) > 150 || filter_var($correo, FILTER_VALIDATE_EMAIL) === false
    || $rolId === false
) {
    redirect($urlForm . 'error=usuario_invalido');
}

// Contraseña obligatoria al crear u opcional al editar
$cambiarPassword = $id === null || $password !== '';
if ($cambiarPassword && (strlen($password) < 8 || strlen($password) > 72)) {
    redirect($urlForm . 'error=password_invalida');
}

// El rol enviado debe existir
$stmt = $conn->prepare('SELECT nombre FROM roles WHERE id = :id');
$stmt->execute([':id' => $rolId]);
$rolNombre = $stmt->fetchColumn();

if ($rolNombre === false) {
    redirect($urlForm . 'error=usuario_invalido');
}

// El admin no puede quitarse su propio acceso
if ($id === $_SESSION['usuario_id'] && ($rolNombre !== 'admin' || $activo !== 1)) {
    redirect($urlForm . 'error=no_modificar_propio');
}

// Guardar cambios y registrar en bitácora sin exponer el password en la bitácora
$despues = ['nombre' => $nombre, 'correo' => $correo, 'rol_id' => $rolId, 'activo' => $activo];

try {
    $conn->beginTransaction();

    if ($id === null) {
        $stmt = $conn->prepare(
            'INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo)
             VALUES (:rol_id, :nombre, :correo, :hash, :activo)'
        );
        $stmt->execute([
            ':rol_id' => $rolId,
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':hash'   => password_hash($password, PASSWORD_DEFAULT),
            ':activo' => $activo,
        ]);
        $id = (int) $conn->lastInsertId();

        registrarBitacora($conn, 'crear', 'usuarios', $id, null, $despues);
        $exito = 'usuario_creado';
    } else {
        $stmt = $conn->prepare(
            'SELECT nombre, correo, rol_id, activo FROM usuarios WHERE id = :id AND eliminado_en IS NULL'
        );
        $stmt->execute([':id' => $id]);
        $antes = $stmt->fetch();

        if ($antes === false) {
            $conn->rollBack();
            redirect('/pages/usuarios.php?error=usuario_no_encontrado');
        }

        $stmt = $conn->prepare(
            'UPDATE usuarios SET nombre = :nombre, correo = :correo, rol_id = :rol_id, activo = :activo
             WHERE id = :id'
        );
        $stmt->execute([
            ':nombre' => $nombre,
            ':correo' => $correo,
            ':rol_id' => $rolId,
            ':activo' => $activo,
            ':id'     => $id,
        ]);

        if ($cambiarPassword) {
            $stmt = $conn->prepare('UPDATE usuarios SET password_hash = :hash WHERE id = :id');
            $stmt->execute([':hash' => password_hash($password, PASSWORD_DEFAULT), ':id' => $id]);

            // Con una contraseña nueva se revocan sus sesiones "Recordarme"
            $stmt = $conn->prepare('DELETE FROM auth_tokens WHERE usuario_id = :id');
            $stmt->execute([':id' => $id]);

            $despues['password_cambiada'] = true;
        }

        registrarBitacora($conn, 'editar', 'usuarios', $id, $antes, $despues);
        $exito = 'usuario_actualizado';
    }

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // El correo ya existe, violación de restricción unica
    if (($ex->errorInfo[1] ?? null) === 1062) {
        redirect($urlForm . 'error=correo_duplicado');
    }
    throw $ex;
}

redirect('/pages/usuarios.php?exito=' . $exito);