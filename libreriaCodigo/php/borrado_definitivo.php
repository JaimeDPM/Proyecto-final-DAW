<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

// Solo el admin puede usar este endpoint
if ($_SESSION['usuario_rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'msg' => 'Acceso denegado. Solo el administrador puede realizar borrados definitivos.']);
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

// Tablas permitidas y sus dependencias que hay que borrar antes
// 'dependencias' => [tabla_hija => columna_fk]
$tablas = [
    'persona' => [
        'tabla'        => 'personas',
        'dependencias' => [] // No se borra físicamente si tiene cotos, declaraciones o documentos vinculados
    ],
    'coto' => [
        'tabla'        => 'cotos',
        'dependencias' => [
            'cotos_colindantes' => 'coto_id',
        ]
    ],
    'declaracion' => [
        'tabla'        => 'declaraciones_junta',
        'dependencias' => []
    ],
    'documento' => [
        'tabla'        => 'documentos_word',
        'dependencias' => []
    ],
    'evento' => [
        'tabla'        => 'eventos',
        'dependencias' => []
    ],
];

if (!array_key_exists($tipo, $tablas)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'msg' => 'Tipo no válido']);
    exit;
}

try {
    $pdo   = getDB();
    $tabla = $tablas[$tipo]['tabla'];

    // Verificar que el registro existe Y está en la papelera (deleted_at IS NOT NULL)
    // Solo se permite borrar definitivamente lo que ya ha pasado por borrado lógico
    $check = $pdo->prepare("SELECT id FROM `$tabla` WHERE id = :id");
    $check->execute([':id' => $id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Registro no encontrado']);
        exit;
    }

    $checkPapelera = $pdo->prepare("SELECT id FROM `$tabla` WHERE id = :id AND deleted_at IS NOT NULL");
    $checkPapelera->execute([':id' => $id]);
    if (!$checkPapelera->fetch()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'msg' => 'El registro debe estar en la papelera antes de poder eliminarse definitivamente. Usa primero el borrado lógico.']);
        exit;
    }

    // Para personas: verificar que no tiene registros activos vinculados
    if ($tipo === 'persona') {
        $vinculaciones = [
            ['tabla' => 'cotos',               'col' => 'titular_id'],
            ['tabla' => 'declaraciones_junta', 'col' => 'interesado_id'],
            ['tabla' => 'declaraciones_junta', 'col' => 'representante_id'],
            ['tabla' => 'declaraciones_junta', 'col' => 'organizador_id'],
            ['tabla' => 'documentos_word',     'col' => 'representante_id'],
            ['tabla' => 'documentos_word',     'col' => 'autorizado_id'],
        ];

        foreach ($vinculaciones as $v) {
            $sqlCheck = "SELECT COUNT(*) as total FROM `{$v['tabla']}` WHERE `{$v['col']}` = :id AND deleted_at IS NULL";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([':id' => $id]);
            $total = $stmtCheck->fetch()['total'];
            if ($total > 0) {
                http_response_code(409);
                echo json_encode([
                    'ok'  => false,
                    'msg' => "No se puede eliminar: esta persona tiene $total registro(s) activo(s) vinculado(s) en {$v['tabla']}. Bórralos primero."
                ]);
                exit;
            }
        }
    }

    // Para cotos: verificar que no tiene declaraciones o documentos activos vinculados
    if ($tipo === 'coto') {
        $vinculaciones = [
            ['tabla' => 'declaraciones_junta', 'col' => 'coto_id'],
            ['tabla' => 'documentos_word',     'col' => 'coto_id'],
            ['tabla' => 'eventos',             'col' => 'coto_id'],
        ];

        foreach ($vinculaciones as $v) {
            $sqlCheck = "SELECT COUNT(*) as total FROM `{$v['tabla']}` WHERE `{$v['col']}` = :id AND deleted_at IS NULL";
            $stmtCheck = $pdo->prepare($sqlCheck);
            $stmtCheck->execute([':id' => $id]);
            $total = $stmtCheck->fetch()['total'];
            if ($total > 0) {
                http_response_code(409);
                echo json_encode([
                    'ok'  => false,
                    'msg' => "No se puede eliminar: este coto tiene $total registro(s) activo(s) en {$v['tabla']}. Bórralos primero."
                ]);
                exit;
            }
        }
    }

    // Borrar dependencias primero (p.ej. colindantes de un coto)
    foreach ($tablas[$tipo]['dependencias'] as $tablaHija => $columnaFk) {
        $pdo->prepare("DELETE FROM `$tablaHija` WHERE `$columnaFk` = :id")
            ->execute([':id' => $id]);
    }

    // Borrado físico definitivo
    $stmt = $pdo->prepare("DELETE FROM `$tabla` WHERE id = :id");
    $stmt->execute([':id' => $id]);

    echo json_encode(['ok' => true, 'msg' => 'Registro eliminado definitivamente']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}