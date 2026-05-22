<?php
/**
 * plantillas_api.php
 *
 * GET    → lista todas las plantillas (todos los usuarios autenticados)
 * POST   → sube una plantilla nueva (solo admin)
 * DELETE → elimina una plantilla (solo admin)
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/config.php';

$esAdmin   = ($_SESSION['usuario_rol'] === 'admin');
$usuarioId = (int)$_SESSION['usuario_id'];
$metodo    = $_SERVER['REQUEST_METHOD'];

// Directorio donde se guardan los archivos subidos
// Debe existir y tener permisos de escritura en el servidor
define('PLANTILLAS_DIR', __DIR__ . '/../../plantillas/');

// ── GET — listar plantillas ──────────────────────────────────
if ($metodo === 'GET') {
    $tipo = $_GET['tipo'] ?? ''; // 'word', 'pdf' o vacío = todas

    try {
        if ($tipo && in_array($tipo, ['word', 'pdf'])) {
            $stmt = getDB()->prepare("
                SELECT p.id, p.tipo, p.nombre_visible, p.nombre_archivo, p.marcadores, p.created_at,
                       u.nombre AS subido_por_nombre
                FROM plantillas p
                LEFT JOIN usuarios u ON u.id = p.subido_por
                WHERE p.tipo = :tipo
                ORDER BY p.nombre_visible ASC
            ");
            $stmt->execute([':tipo' => $tipo]);
        } else {
            $stmt = getDB()->query("
                SELECT p.id, p.tipo, p.nombre_visible, p.nombre_archivo, p.marcadores, p.created_at,
                       u.nombre AS subido_por_nombre
                FROM plantillas p
                LEFT JOIN usuarios u ON u.id = p.subido_por
                ORDER BY p.tipo, p.nombre_visible ASC
            ");
        }

        $rows = $stmt->fetchAll();

        // Decodificar el JSON de marcadores para cada fila
        foreach ($rows as &$r) {
            $r['marcadores'] = $r['marcadores'] ? json_decode($r['marcadores'], true) : [];
        }

        echo json_encode(['ok' => true, 'total' => count($rows), 'data' => $rows]);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// ── POST — subir plantilla (solo admin) ─────────────────────
if ($metodo === 'POST') {
    if (!$esAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Solo el administrador puede subir plantillas']);
        exit;
    }

    $nombreVisible = trim($_POST['nombre_visible'] ?? '');
    $tipo          = trim($_POST['tipo'] ?? '');

    if (!$nombreVisible) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'El nombre de la plantilla es obligatorio']);
        exit;
    }

    if (!in_array($tipo, ['word', 'pdf'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'Tipo no válido. Usa "word" o "pdf"']);
        exit;
    }

    if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        $errores = [
            UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario',
            UPLOAD_ERR_PARTIAL    => 'El archivo se subió de forma incompleta',
            UPLOAD_ERR_NO_FILE    => 'No se recibió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal en el servidor',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor',
        ];
        $codigo = $_FILES['archivo']['error'] ?? UPLOAD_ERR_NO_FILE;
        echo json_encode(['ok' => false, 'msg' => $errores[$codigo] ?? 'Error al subir el archivo']);
        exit;
    }

    $file     = $_FILES['archivo'];
    $mime     = mime_content_type($file['tmp_name']);
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Validar extensión y tipo MIME según el tipo declarado
    $validaciones = [
        'word' => [
            'extensiones' => ['docx'],
            'mimes'       => [
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip', // algunos servidores lo detectan como ZIP
                'application/octet-stream',
            ],
        ],
        'pdf' => [
            'extensiones' => ['pdf'],
            'mimes'       => ['application/pdf'],
        ],
    ];

    if (!in_array($ext, $validaciones[$tipo]['extensiones'])) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => "Extensión no válida para tipo '$tipo'. Se esperaba ." . implode(', .', $validaciones[$tipo]['extensiones'])]);
        exit;
    }

    // Crear directorio si no existe
    if (!is_dir(PLANTILLAS_DIR)) {
        mkdir(PLANTILLAS_DIR, 0755, true);
    }

    // Nombre de archivo seguro y único
    $nombreArchivo = uniqid('plantilla_') . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreVisible) . '.' . $ext;
    $rutaDestino   = PLANTILLAS_DIR . $nombreArchivo;

    if (!move_uploaded_file($file['tmp_name'], $rutaDestino)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => 'No se pudo guardar el archivo en el servidor']);
        exit;
    }

    // Detectar marcadores {{campo}} automáticamente
    $marcadores = [];
    if ($tipo === 'word') {
        $marcadores = detectarMarcadoresWord($rutaDestino);
    }

    // Guardar en BD
    try {
        $stmt = getDB()->prepare("
            INSERT INTO plantillas (tipo, nombre_visible, nombre_archivo, marcadores, subido_por)
            VALUES (:tipo, :nombre_visible, :nombre_archivo, :marcadores, :subido_por)
        ");
        $stmt->execute([
            ':tipo'           => $tipo,
            ':nombre_visible' => $nombreVisible,
            ':nombre_archivo' => $nombreArchivo,
            ':marcadores'     => json_encode($marcadores),
            ':subido_por'     => $usuarioId,
        ]);

        $id = (int)getDB()->lastInsertId();

        echo json_encode([
            'ok'         => true,
            'id'         => $id,
            'marcadores' => $marcadores,
            'msg'        => 'Plantilla subida correctamente',
        ]);

    } catch (Throwable $e) {
        // Si falla la BD, borrar el archivo subido
        @unlink($rutaDestino);
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

// ── DELETE — eliminar plantilla (solo admin) ─────────────────
if ($metodo === 'DELETE') {
    if (!$esAdmin) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'msg' => 'Solo el administrador puede eliminar plantillas']);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'msg' => 'ID no válido']);
        exit;
    }

    try {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT nombre_archivo FROM plantillas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'msg' => 'Plantilla no encontrada']);
            exit;
        }

        // Eliminar de BD
        $pdo->prepare("DELETE FROM plantillas WHERE id = :id")->execute([':id' => $id]);

        // Eliminar archivo del servidor
        $ruta = PLANTILLAS_DIR . $row['nombre_archivo'];
        if (file_exists($ruta)) {
            @unlink($ruta);
        }

        echo json_encode(['ok' => true, 'msg' => 'Plantilla eliminada correctamente']);

    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
exit;

// ── Función: detectar marcadores {{campo}} en un .docx ───────
function detectarMarcadoresWord(string $rutaDocx): array
{
    $marcadores = [];

    $zip = new ZipArchive();
    if ($zip->open($rutaDocx) !== true) {
        return $marcadores;
    }

    // Buscar en los archivos XML principales del docx
    $archivos = ['word/document.xml', 'word/header1.xml', 'word/footer1.xml'];

    foreach ($archivos as $archivo) {
        $contenido = $zip->getFromName($archivo);
        if ($contenido === false) continue;

        // Primero limpiar tags XML para encontrar marcadores partidos por Word
        // Estrategia: extraer texto plano eliminando tags XML
        $textoPlano = preg_replace('/<[^>]+>/', '', $contenido);

        // Buscar todos los {{marcador}}
        preg_match_all('/\{\{([a-zA-Z0-9_]+)\}\}/', $textoPlano, $coincidencias);

        if (!empty($coincidencias[1])) {
            $marcadores = array_merge($marcadores, $coincidencias[1]);
        }
    }

    $zip->close();

    // Devolver lista única y ordenada
    $marcadores = array_values(array_unique($marcadores));
    sort($marcadores);

    return $marcadores;
}