<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['usuario_id']) || $_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acceso denegado']);
    exit;
}

require_once __DIR__ . '/config.php';

$tipo = $_GET['tipo'] ?? '';

$tiposValidos = ['persona', 'coto', 'declaracion', 'documento', 'evento'];
if (!in_array($tipo, $tiposValidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Tipo no válido']);
    exit;
}

try {
    $pdo = getDB();

    switch ($tipo) {

        case 'persona':
            $stmt = $pdo->query("
                SELECT p.id, p.nombre, p.apellido1, p.apellido2, p.dni_nif, p.deleted_at,
                       u.nombre AS usuario_nombre
                FROM personas p
                LEFT JOIN usuarios u ON u.id = p.usuario_id
                WHERE p.deleted_at IS NOT NULL
                ORDER BY p.deleted_at DESC
            ");
            break;

        case 'coto':
            $stmt = $pdo->query("
                SELECT c.id, c.letra_provincia, c.numero_matricula, c.municipio, c.deleted_at,
                       COALESCE(CONCAT(p.nombre,' ',p.apellido1), c.razon_social) AS titular,
                       u.nombre AS usuario_nombre
                FROM cotos c
                LEFT JOIN personas p ON p.id = c.titular_id
                LEFT JOIN usuarios u ON u.id = c.usuario_id
                WHERE c.deleted_at IS NOT NULL
                ORDER BY c.deleted_at DESC
            ");
            break;

        case 'declaracion':
            $stmt = $pdo->query("
                SELECT dj.id, dj.plantilla_pdf, dj.deleted_at,
                       u.nombre AS usuario_nombre
                FROM declaraciones_junta dj
                LEFT JOIN usuarios u ON u.id = dj.usuario_id
                WHERE dj.deleted_at IS NOT NULL
                ORDER BY dj.deleted_at DESC
            ");
            break;

        case 'documento':
            $stmt = $pdo->query("
                SELECT dw.id, dw.plantilla, dw.deleted_at,
                       u.nombre AS usuario_nombre
                FROM documentos_word dw
                LEFT JOIN usuarios u ON u.id = dw.usuario_id
                WHERE dw.deleted_at IS NOT NULL
                ORDER BY dw.deleted_at DESC
            ");
            break;

        case 'evento':
            $stmt = $pdo->query("
                SELECT e.id, e.titulo, e.tipo, e.deleted_at,
                       u.nombre AS usuario_nombre
                FROM eventos e
                LEFT JOIN usuarios u ON u.id = e.usuario_id
                WHERE e.deleted_at IS NOT NULL
                ORDER BY e.deleted_at DESC
            ");
            break;
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'data' => $rows]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}