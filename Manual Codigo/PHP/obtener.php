<?php
/**
 * obtener.php
 * API para obtener el detalle completo de un registro por su ID.
 *
 * Método: GET
 * Parámetros:
 *   - tipo (string): 'persona' | 'coto' | 'colindantes'
 *   - id   (int):    ID del registro
 *
 * Respuesta JSON: { ok, data }
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

// Condición extra para que un usuario normal no pueda obtener
// registros que no son suyos aunque conozca el ID
$checkUsuario      = $esAdmin ? '' : 'AND usuario_id = :uid';
$checkUsuarioCoto  = $esAdmin ? '' : 'AND c.usuario_id = :uid';
$checkUsuarioDecl  = $esAdmin ? '' : 'AND dj.usuario_id = :uid';
$checkUsuarioDoc   = $esAdmin ? '' : 'AND dw.usuario_id = :uid';

try {
    $pdo = getDB();

    switch ($tipo) {

        case 'persona':
            $sql = "SELECT * FROM personas WHERE id = :id AND deleted_at IS NULL $checkUsuario";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $id];
            if (!$esAdmin) $params[':uid'] = $usuarioId;
            $stmt->execute($params);
            break;

        case 'coto':
            $sql = "
                SELECT c.*,
                       CONCAT(c.letra_provincia,'-',c.numero_matricula) AS matricula_completa,
                       p.nombre    AS tit_nombre,
                       p.apellido1 AS tit_apellido1,
                       p.apellido2 AS tit_apellido2,
                       p.dni_nif   AS tit_nif
                FROM cotos c
                LEFT JOIN personas p ON p.id = c.titular_id
                WHERE c.id = :id AND c.deleted_at IS NULL
                $checkUsuarioCoto
            ";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $id];
            if (!$esAdmin) $params[':uid'] = $usuarioId;
            $stmt->execute($params);
            break;

        case 'colindante':
        case 'colindantes':
            // Los colindantes heredan el filtro de usuario a través del coto
            $sql = "
                SELECT cc.*,
                       CONCAT(c.letra_provincia,'-',c.numero_matricula) AS coto_matricula,
                       c.municipio AS coto_municipio
                FROM cotos_colindantes cc
                INNER JOIN cotos c ON c.id = cc.coto_id
                WHERE cc.coto_id = :id
                AND c.deleted_at IS NULL
                $checkUsuarioCoto
            ";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $id];
            if (!$esAdmin) $params[':uid'] = $usuarioId;
            $stmt->execute($params);
            // Devolver array directamente
            echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
            exit;

        case 'declaracion':
            // organizador_id es opcional → LEFT JOIN en lugar de INNER JOIN
            $sql = "
                SELECT dj.*,
                       pi.nombre    AS int_nombre,    pi.apellido1 AS int_apellido1, pi.dni_nif AS int_dni,
                       pr.nombre    AS rep_nombre,    pr.apellido1 AS rep_apellido1, pr.dni_nif AS rep_dni,
                       po.nombre    AS org_nombre,    po.apellido1 AS org_apellido1, po.dni_nif AS org_dni,
                       CONCAT(c.letra_provincia,'-',c.numero_matricula) AS num_matricula,
                       c.provincia  AS coto_provincia
                FROM declaraciones_junta dj
                INNER JOIN personas pi ON pi.id = dj.interesado_id
                LEFT  JOIN personas pr ON pr.id = dj.representante_id
                LEFT  JOIN personas po ON po.id = dj.organizador_id
                INNER JOIN cotos    c  ON c.id  = dj.coto_id
                WHERE dj.id = :id AND dj.deleted_at IS NULL
                $checkUsuarioDecl
            ";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $id];
            if (!$esAdmin) $params[':uid'] = $usuarioId;
            $stmt->execute($params);
            break;

        case 'documento':
            // titular del coto puede ser persona jurídica → LEFT JOIN en personas titular
            $sql = "
                SELECT dw.*,
                       CONCAT(c.letra_provincia,'-',c.numero_matricula) AS num_matricula,
                       c.provincia  AS coto_provincia,
                       c.razon_social,
                       pt.nombre    AS tit_nombre,    pt.apellido1 AS tit_apellido1, pt.dni_nif AS tit_nif,
                       pr.nombre    AS rep_nombre,    pr.apellido1 AS rep_apellido1, pr.dni_nif AS rep_dni,
                       pa.nombre    AS aut_nombre,    pa.apellido1 AS aut_apellido1, pa.dni_nif AS aut_nif
                FROM documentos_word dw
                INNER JOIN cotos    c  ON c.id  = dw.coto_id
                LEFT  JOIN personas pt ON pt.id = c.titular_id
                LEFT  JOIN personas pr ON pr.id = dw.representante_id
                INNER JOIN personas pa ON pa.id = dw.autorizado_id
                WHERE dw.id = :id AND dw.deleted_at IS NULL
                $checkUsuarioDoc
            ";
            $stmt = $pdo->prepare($sql);
            $params = [':id' => $id];
            if (!$esAdmin) $params[':uid'] = $usuarioId;
            $stmt->execute($params);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Tipo no válido']);
            exit;
    }

    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'msg' => 'Registro no encontrado']);
        exit;
    }

    echo json_encode(['ok' => true, 'data' => $row]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}