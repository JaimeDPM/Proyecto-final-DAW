<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/config.php';

$tipo = $_GET['tipo'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

if ($id <= 0 || empty($tipo)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Parámetros inválidos']);
    exit;
}

$tablas = [
    'persona' => 'personas',
    'coto'    => 'cotos',
];

if (!array_key_exists($tipo, $tablas)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Tipo no válido']);
    exit;
}

try {
    $pdo   = getDB();
    $tabla = $tablas[$tipo];

    $stmt = $pdo->prepare("DELETE FROM `$tabla` WHERE id = :id AND deleted_at IS NOT NULL");
    $stmt->execute([':id' => $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Registro no encontrado o no estaba en la papelera']);
        exit;
    }

    echo json_encode(['ok' => true, 'msg' => 'Registro eliminado definitivamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}