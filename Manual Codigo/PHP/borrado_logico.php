<?php
/**
 * borrado_logico.php
 * API para el borrado lógico de registros (soft delete).
 * Marca el campo deleted_at con la fecha actual en lugar de eliminar el registro físicamente.
 * Los registros eliminados se pueden restaurar desde la papelera (solo admin).
 *
 * Método: DELETE
 * Parámetros GET:
 *   - tipo (string): 'persona' | 'coto'
 *   - id   (int):    ID del registro a eliminar
 *
 * Respuesta JSON: { ok }
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/config.php';

$tipo      = $_GET['tipo'] ?? '';
$id        = (int)($_GET['id'] ?? 0);
$usuarioId = (int)$_SESSION['usuario_id'];
$esAdmin   = ($_SESSION['usuario_rol'] === 'admin');

if ($id <= 0 || empty($tipo)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos']);
    exit;
}

// Tablas permitidas para borrado lógico
$tablas = [
    'persona'     => 'personas',
    'coto'        => 'cotos',
    'declaracion' => 'declaraciones_junta',
    'documento'   => 'documentos_word',
    'evento'      => 'eventos',
];

if (!array_key_exists($tipo, $tablas)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Tipo no válido']);
    exit;
}

try {
    $pdo   = getDB();
    $tabla = $tablas[$tipo];

    // Admin puede borrar cualquier registro; usuario solo los suyos
    if ($esAdmin) {
        $sql  = "UPDATE `$tabla` SET deleted_at = NOW() WHERE id = :id AND deleted_at IS NULL";
        $params = [':id' => $id];
    } else {
        $sql  = "UPDATE `$tabla` SET deleted_at = NOW() WHERE id = :id AND usuario_id = :uid AND deleted_at IS NULL";
        $params = [':id' => $id, ':uid' => $usuarioId];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Registro no encontrado o ya estaba borrado']);
        exit;
    }

    echo json_encode(['ok' => true, 'msg' => 'Registro eliminado correctamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}