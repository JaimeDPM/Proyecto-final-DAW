<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/config.php';

$data     = json_decode(file_get_contents('php://input'), true);
$password = trim($data['password'] ?? '');

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'La contraseña debe tener al menos 8 caracteres.']);
    exit;
}

try {
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
    $stmt = getDB()->prepare("UPDATE usuarios SET password = :password, must_change_password = 0 WHERE id = :id");
    $stmt->execute([':password' => $hash, ':id' => $_SESSION['usuario_id']]);
    $_SESSION['must_change_password'] = false;
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => 'Error al guardar: ' . $e->getMessage()]);
}