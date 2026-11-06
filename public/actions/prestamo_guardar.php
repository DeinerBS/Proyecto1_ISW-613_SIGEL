<?php
require_once __DIR__ . '/../../utils/database.php';
require_once __DIR__ . '/../../utils/auth.php';
require_once __DIR__ . '/../../utils/csrf.php';
require_once __DIR__ . '/../../utils/bitacora.php';
require_once __DIR__ . '/../../utils/horario.php';
require_once __DIR__ . '/../../utils/prestamos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/pages/prestamo_form.php');
}

requiereLogin($conn);
requiereRol(['operador']);
verificarCsrf();

$urlForm = '/pages/prestamo_form.php?';

//  Control #6: validación de la entrada 
$profesorId = filter_var($_POST['usuario_id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$inicio = leerFechaHora($_POST['fecha_inicio'] ?? '');
$fin    = leerFechaHora($_POST['fecha_fin'] ?? '');

// Equipos: array de enteros, NO repetidos
$equipoIds = [];
$recibidos = $_POST['equipos'] ?? [];
if (is_array($recibidos)) {
    foreach ($recibidos as $valor) {
        $eqId = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($eqId === false) {
            redirect($urlForm . 'error=prestamo_invalido');
        }
        $equipoIds[] = $eqId;
    }
}
$equipoIds = array_unique($equipoIds);
sort($equipoIds); // orden fijo al bloquear para evitar bloqueos cruzados

if (
    $profesorId === false || $inicio === false || $fin === false || $fin <= $inicio
    || !$equipoIds || count($equipoIds) > MAX_EQUIPOS_PRESTAMO
) {
    redirect($urlForm . 'error=prestamo_invalido');
}

if ($inicio < new DateTime()) {
    redirect($urlForm . 'error=prestamo_fecha_pasada');
}

// Regla 5: inicio y entrega dentro del horario
if (!dentroDeHorario($conn, $inicio) || !dentroDeHorario($conn, $fin)) {
    redirect($urlForm . 'error=fuera_de_horario');
}

// El préstamo se asigna a un profesor activo
$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM usuarios u
     JOIN roles r ON r.id = u.rol_id
     WHERE u.id = :id AND r.nombre = 'profesor' AND u.activo = 1 AND u.eliminado_en IS NULL"
);
$stmt->execute([':id' => $profesorId]);
if ((int) $stmt->fetchColumn() === 0) {
    redirect($urlForm . 'error=prestamo_invalido');
}

$inicioBD = $inicio->format('Y-m-d H:i:s');
$finBD    = $fin->format('Y-m-d H:i:s');
$duracionDias = ($fin->getTimestamp() - $inicio->getTimestamp()) / 86400;
$marcadores = implode(',', array_fill(0, count($equipoIds), '?'));

try {
    $conn->beginTransaction();

    // FOR UPDATE: bloquea estos equipos hasta el commit para evitar préstamos simultáneos
    $stmt = $conn->prepare(
        "SELECT e.id, e.estado, c.dias_max_prestamo
         FROM equipos e
         JOIN categorias c ON c.id = e.categoria_id
         WHERE e.id IN ($marcadores) AND e.eliminado_en IS NULL
         ORDER BY e.id
         FOR UPDATE"
    );
    $stmt->execute($equipoIds);
    $equipos = $stmt->fetchAll();

    // Aqui poner sleep(10) para probar bloqueos cruzados 
    // sleep(10);

    // Todos deben existir
    if (count($equipos) !== count($equipoIds)) {
        $conn->rollBack();
        redirect($urlForm . 'error=equipo_no_disponible');
    }

    foreach ($equipos as $eq) {
        if ($eq['estado'] !== 'operativo') {
            $conn->rollBack();
            redirect($urlForm . 'error=equipo_no_disponible');
        }
        if ($duracionDias > (int) $eq['dias_max_prestamo']) {
            $conn->rollBack();
            redirect($urlForm . 'error=prestamo_excede_dias');
        }
    }

    // Traslape: existente.inicio < nuevo.fin AND existente.fin > nuevo.inicio
    $stmt = $conn->prepare(
        "SELECT COUNT(*)
         FROM prestamo_detalle d
         JOIN prestamos p ON p.id = d.prestamo_id
         WHERE d.equipo_id IN ($marcadores)
           AND p.estado IN ('solicitado', 'aprobado', 'entregado')
           AND p.fecha_inicio < ?
           AND p.fecha_fin > ?"
    );
    $stmt->execute(array_merge($equipoIds, [$finBD, $inicioBD]));

    if ((int) $stmt->fetchColumn() > 0) {
        $conn->rollBack();
        redirect($urlForm . 'error=prestamo_traslape');
    }

    $stmt = $conn->prepare(
        "INSERT INTO prestamos (usuario_id, operador_id, estado, fecha_inicio, fecha_fin)
         VALUES (:usuario_id, :operador_id, 'solicitado', :inicio, :fin)"
    );
    $stmt->execute([
        ':usuario_id'  => $profesorId,
        ':operador_id' => $_SESSION['usuario_id'],
        ':inicio'      => $inicioBD,
        ':fin'         => $finBD,
    ]);
    $prestamoId = (int) $conn->lastInsertId();

    $stmt = $conn->prepare('INSERT INTO prestamo_detalle (prestamo_id, equipo_id) VALUES (:prestamo_id, :equipo_id)');
    foreach ($equipoIds as $eqId) {
        $stmt->execute([':prestamo_id' => $prestamoId, ':equipo_id' => $eqId]);
    }

    registrarBitacora($conn, 'crear', 'prestamos', $prestamoId, null, [
        'usuario_id'   => $profesorId,
        'fecha_inicio' => $inicioBD,
        'fecha_fin'    => $finBD,
        'estado'       => 'solicitado',
        'equipos'      => $equipoIds,
    ]);

    $conn->commit();
} catch (PDOException $ex) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    throw $ex;
}

redirect('/pages/dashboard.php?exito=prestamo_creado');