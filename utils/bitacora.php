<?php
// Bitácora de auditoría.
function registrarBitacora(PDO $conn, $accion, $entidad, $entidadId, ?array $antes = null, ?array $despues = null)
{
    $stmt = $conn->prepare(
        'INSERT INTO bitacora (usuario_id, accion, entidad, entidad_id, valores_antes, valores_despues, ip)
         VALUES (:usuario_id, :accion, :entidad, :entidad_id, :antes, :despues, :ip)'
    );
    $stmt->execute([
        ':usuario_id' => $_SESSION['usuario_id'] ?? null,
        ':accion'     => $accion,
        ':entidad'    => $entidad,
        ':entidad_id' => $entidadId,
        ':antes'      => $antes === null ? null : json_encode($antes, JSON_UNESCAPED_UNICODE),
        ':despues'    => $despues === null ? null : json_encode($despues, JSON_UNESCAPED_UNICODE),
        ':ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}