<?php
/**
 * buscar.php
 * API de búsqueda de registros.
 *
 * Método: GET
 * Parámetros:
 *   - tipo (string): 'personas' | 'cotos' | 'declaraciones' | 'documentos'
 *   - q    (string): texto de búsqueda. Si está vacío, devuelve los primeros 100 registros (carga inicial)
 *
 * Respuesta JSON: { ok, total, data[] }
 *
 * Control de acceso:
 *   - Admin: ve todos los registros del sistema
 *   - Usuario: solo ve sus propios registros (filtrado por usuario_id)
 */
session_start();
header('Content-Type: application/json; charset=utf-8');

// Verificar sesión activa
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/config.php';

$tipo         = $_GET['tipo'] ?? 'personas';
$q            = trim($_GET['q'] ?? '');
$usuarioId    = (int)$_SESSION['usuario_id'];
$esAdmin      = ($_SESSION['usuario_rol'] === 'admin');
$cargaInicial = ($q === ''); // Sin texto = cargar todos (limitado a 100)

// Validar longitud mínima del texto de búsqueda
if (!$cargaInicial && strlen($q) < 2) {
    echo json_encode(['ok' => false, 'msg' => 'Introduce al menos 2 caracteres']);
    exit;
}

// Filtro de usuario: admin ve todo, usuario solo lo suyo
$filtroUsuario     = $esAdmin ? '' : 'AND usuario_id = :uid';
$filtroUsuarioCoto = $esAdmin ? '' : 'AND c.usuario_id = :uid';

try {
    $pdo  = getDB();
    $like = '%' . $q . '%'; // Patrón LIKE para búsqueda parcial

    switch ($tipo) {

        case 'personas':
            if ($cargaInicial) {
                // Carga inicial: devuelve las primeras 100 personas ordenadas alfabéticamente
                $sql = "
                    SELECT id, nombre, apellido1, apellido2, dni_nif
                    FROM personas
                    WHERE deleted_at IS NULL
                    $filtroUsuario
                    ORDER BY apellido1, nombre
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $esAdmin ? $stmt->execute() : $stmt->execute([':uid' => $usuarioId]);
            } else {
                // Búsqueda por nombre, apellidos o DNI
                $sql = "
                    SELECT id, nombre, apellido1, apellido2, dni_nif
                    FROM personas
                    WHERE deleted_at IS NULL
                    $filtroUsuario
                    AND (nombre LIKE :q1 OR apellido1 LIKE :q2
                      OR apellido2 LIKE :q3 OR dni_nif LIKE :q4)
                    ORDER BY apellido1, nombre
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $params = [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
                if (!$esAdmin) $params[':uid'] = $usuarioId;
                $stmt->execute($params);
            }
            break;

        case 'cotos':
            if ($cargaInicial) {
                // Carga inicial: devuelve los primeros 100 cotos con datos del titular
                $sql = "
                    SELECT c.id,
                           CONCAT(c.letra_provincia,'-',c.numero_matricula) AS num_matricula,
                           c.municipio,
                           COALESCE(CONCAT(p.nombre,' ',p.apellido1), c.razon_social) AS titular
                    FROM cotos c
                    LEFT JOIN personas p ON p.id = c.titular_id
                    WHERE c.deleted_at IS NULL
                    $filtroUsuarioCoto
                    ORDER BY c.letra_provincia, c.numero_matricula
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $esAdmin ? $stmt->execute() : $stmt->execute([':uid' => $usuarioId]);
            } else {
                // Búsqueda por matrícula, municipio, provincia o nombre del titular
                $sql = "
                    SELECT c.id,
                           CONCAT(c.letra_provincia,'-',c.numero_matricula) AS num_matricula,
                           c.municipio,
                           COALESCE(CONCAT(p.nombre,' ',p.apellido1), c.razon_social) AS titular
                    FROM cotos c
                    LEFT JOIN personas p ON p.id = c.titular_id
                    WHERE c.deleted_at IS NULL
                    $filtroUsuarioCoto
                    AND (c.letra_provincia LIKE :q1
                      OR c.numero_matricula LIKE :q2
                      OR CONCAT(c.letra_provincia,'-',c.numero_matricula) LIKE :q3
                      OR c.municipio LIKE :q4 OR c.provincia LIKE :q5
                      OR p.apellido1 LIKE :q6 OR p.nombre LIKE :q7
                      OR c.razon_social LIKE :q8)
                    ORDER BY c.letra_provincia, c.numero_matricula
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $params = [':q1'=>$like,':q2'=>$like,':q3'=>$like,':q4'=>$like,
                           ':q5'=>$like,':q6'=>$like,':q7'=>$like,':q8'=>$like];
                if (!$esAdmin) $params[':uid'] = $usuarioId;
                $stmt->execute($params);
            }
            break;

        case 'declaraciones':
            // Búsqueda en declaraciones de la Junta CyL
            if ($cargaInicial) {
                $sql = "
                    SELECT id, plantilla_pdf
                    FROM declaraciones_junta
                    WHERE deleted_at IS NULL
                    $filtroUsuario
                    ORDER BY id DESC
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $esAdmin ? $stmt->execute() : $stmt->execute([':uid' => $usuarioId]);
            } else {
                $sql = "
                    SELECT id, plantilla_pdf
                    FROM declaraciones_junta
                    WHERE deleted_at IS NULL
                    $filtroUsuario
                    AND (plantilla_pdf LIKE :q1 OR CAST(id AS CHAR) LIKE :q2)
                    ORDER BY id DESC
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $params = [':q1' => $like, ':q2' => $like];
                if (!$esAdmin) $params[':uid'] = $usuarioId;
                $stmt->execute($params);
            }
            break;

        case 'documentos':
            // Búsqueda en documentos Word generados
            if ($cargaInicial) {
                $sql = "
                    SELECT id, plantilla
                    FROM documentos_word
                    WHERE deleted_at IS NULL
                    $filtroUsuario
                    ORDER BY id DESC
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $esAdmin ? $stmt->execute() : $stmt->execute([':uid' => $usuarioId]);
            } else {
                $sql = "
                    SELECT id, plantilla
                    FROM documentos_word
                    WHERE deleted_at IS NULL
                    $filtroUsuario
                    AND (plantilla LIKE :q1 OR CAST(id AS CHAR) LIKE :q2)
                    ORDER BY id DESC
                    LIMIT 100
                ";
                $stmt = $pdo->prepare($sql);
                $params = [':q1' => $like, ':q2' => $like];
                if (!$esAdmin) $params[':uid'] = $usuarioId;
                $stmt->execute($params);
            }
            break;

        default:
            echo json_encode(['ok' => false, 'msg' => 'Tipo de búsqueda no válido']);
            exit;
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'total' => count($rows), 'data' => $rows]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
