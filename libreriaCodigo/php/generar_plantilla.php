<?php
/**
 * generar_plantilla.php
 *
 * POST → recibe plantilla_id + coto_id + persona_id + campos libres
 *        genera el .docx rellenado y lo devuelve como descarga directa.
 *        No guarda nada en BD.
 */
session_start();

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WordFiller.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$plantillaId   = (int)($body['plantilla_id']   ?? 0);
$cotoId        = (int)($body['coto_id']        ?? 0);
$personaId     = (int)($body['persona_id']     ?? 0);
$autorizadoId  = (int)($body['autorizado_id']  ?? 0);
$organizadorId = (int)($body['organizador_id'] ?? 0);
$camposLibres  = $body['campos_libres']        ?? [];

if ($plantillaId <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'Selecciona una plantilla']);
    exit;
}

define('PLANTILLAS_DIR', __DIR__ . '/../../plantillas/');

try {
    $pdo = getDB();

    // ── 1. Obtener datos de la plantilla ─────────────────────
    $stmt = $pdo->prepare("SELECT * FROM plantillas WHERE id = :id");
    $stmt->execute([':id' => $plantillaId]);
    $plantilla = $stmt->fetch();

    if (!$plantilla) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Plantilla no encontrada']);
        exit;
    }

    if ($plantilla['tipo'] !== 'word') {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Esta plantilla no es de tipo Word']);
        exit;
    }

    // ── 2. Obtener datos del coto + titular ──────────────────
    $datosCoto    = [];
    $datosTitular = [];

    if ($cotoId > 0) {
        $stmt = $pdo->prepare("
            SELECT
                c.*,
                CONCAT(c.letra_provincia, '-', c.numero_matricula) AS coto_matricula,
                p.nombre     AS tit_nombre,
                p.apellido1  AS tit_apellido1,
                p.apellido2  AS tit_apellido2,
                p.dni_nif    AS tit_nif,
                p.telefono   AS tit_telefono,
                p.email      AS tit_email,
                p.tipovia    AS tit_tipovia,
                p.direccion  AS tit_direccion,
                p.numero     AS tit_numero,
                p.portal     AS tit_portal,
                p.escalera   AS tit_escalera,
                p.piso       AS tit_piso,
                p.puerta     AS tit_puerta,
                p.municipio  AS tit_municipio,
                p.provincia  AS tit_provincia,
                p.cp         AS tit_cp
            FROM cotos c
            LEFT JOIN personas p ON p.id = c.titular_id
            WHERE c.id = :id AND c.deleted_at IS NULL
        ");
        $stmt->execute([':id' => $cotoId]);
        $datosCoto = $stmt->fetch() ?: [];
    }

    // ── 3. Obtener datos de la persona seleccionada ──────────
    $datosPersona = [];

    if ($personaId > 0) {
        $stmt = $pdo->prepare("
            SELECT * FROM personas
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $personaId]);
        $datosPersona = $stmt->fetch() ?: [];
    }

    // ── 4. Obtener datos del autorizado ──────────────────────
    $datosAutorizado = [];
    if ($autorizadoId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM personas WHERE id = :id AND deleted_at IS NULL");
        $stmt->execute([':id' => $autorizadoId]);
        $datosAutorizado = $stmt->fetch() ?: [];
    } elseif ($personaId > 0) {
        // Si no se selecciona autorizado específico, usar la persona seleccionada
        $datosAutorizado = $datosPersona;
    }

    // ── 6. Obtener datos del organizador ─────────────────────
    // Solo se consulta si la plantilla tiene marcadores {{organizador_*}}
    $datosOrganizador = [];

    if ($organizadorId > 0) {
        $stmt = $pdo->prepare("
            SELECT * FROM personas
            WHERE id = :id AND deleted_at IS NULL
        ");
        $stmt->execute([':id' => $organizadorId]);
        $datosOrganizador = $stmt->fetch() ?: [];
    }
    // ── Determinar si el titular es persona jurídica o física ──
    // Si hay razon_social en el coto → persona jurídica, usamos pj_*
    // Si no → persona física, usamos los datos de la tabla personas via titular_id
    $esPJ = !empty($datosCoto['razon_social']);

    // ── Helper: construir dirección completa ─────────────────
    // Combina tipovia, direccion, numero, portal, escalera, piso, puerta
    function buildDireccion(array $d, string $prefix = ''): string {
        $p = fn($k) => $d[$prefix . $k] ?? '';
        $partes = array_filter([
            trim($p('tipovia') . ' ' . $p('direccion')),
            $p('numero') ? 'nº ' . $p('numero') : '',
            $p('portal') ? 'Portal ' . $p('portal') : '',
            $p('escalera') ? 'Esc. ' . $p('escalera') : '',
            $p('piso') ? $p('piso') . 'º' : '',
            $p('puerta') ? $p('puerta') : '',
        ]);
        return implode(', ', $partes);
    }

    // Dirección completa del titular persona física
    $titDireccion = $esPJ
        ? buildDireccion($datosCoto, 'pj_')
        : buildDireccion([
            'tipovia'   => $datosCoto['tit_tipovia']   ?? '',
            'direccion' => $datosCoto['tit_direccion'] ?? '',
            'numero'    => $datosCoto['tit_numero']    ?? '',
            'portal'    => $datosCoto['tit_portal']    ?? '',
            'escalera'  => $datosCoto['tit_escalera']  ?? '',
            'piso'      => $datosCoto['tit_piso']      ?? '',
            'puerta'    => $datosCoto['tit_puerta']    ?? '',
        ]);

    // Dirección completa de la persona seleccionada
    $persDireccion = buildDireccion($datosPersona);

    // Dirección completa del organizador
    $orgDireccion = buildDireccion($datosOrganizador);

    // Datos del coto
    $vars = [
        // Coto
        '{{coto_matricula}}'      => $datosCoto['coto_matricula']    ?? '',
        '{{coto_provincia}}'      => $datosCoto['provincia']         ?? '',
        '{{coto_municipio}}'      => $datosCoto['municipio']         ?? '',

        // Titular unificado — funciona para persona física y jurídica
        '{{titular_nombre}}'      => $esPJ
                                       ? ($datosCoto['razon_social'] ?? '')
                                       : trim(($datosCoto['tit_nombre']   ?? '') . ' ' .
                                              ($datosCoto['tit_apellido1'] ?? '') . ' ' .
                                              ($datosCoto['tit_apellido2'] ?? '')),
        '{{titular_nif}}'         => $esPJ ? ($datosCoto['pj_nif']      ?? '') : ($datosCoto['tit_nif']      ?? ''),
        '{{titular_telefono}}'    => $esPJ ? ($datosCoto['pj_telefono']  ?? '') : ($datosCoto['tit_telefono']  ?? ''),
        '{{titular_email}}'       => $esPJ ? ($datosCoto['pj_email']     ?? '') : ($datosCoto['tit_email']     ?? ''),
        '{{titular_direccion}}'   => $titDireccion,
        '{{titular_municipio}}'   => $esPJ ? ($datosCoto['pj_municipio'] ?? '') : ($datosCoto['tit_municipio'] ?? ''),
        '{{titular_provincia}}'   => $esPJ ? ($datosCoto['pj_provincia'] ?? '') : ($datosCoto['tit_provincia'] ?? ''),
        '{{titular_cp}}'          => $esPJ ? ($datosCoto['pj_cp']        ?? '') : ($datosCoto['tit_cp']        ?? ''),

        // Marcadores específicos persona jurídica (por compatibilidad)
        '{{razon_social}}'        => $datosCoto['razon_social']       ?? '',
        '{{pj_nif}}'              => $datosCoto['pj_nif']             ?? '',
        '{{pj_telefono}}'         => $datosCoto['pj_telefono']        ?? '',
        '{{pj_email}}'            => $datosCoto['pj_email']           ?? '',
        '{{pj_direccion}}'        => $datosCoto['pj_direccion']       ?? '',
        '{{pj_municipio}}'        => $datosCoto['pj_municipio']       ?? '',
        '{{pj_provincia}}'        => $datosCoto['pj_provincia']       ?? '',
        '{{pj_cp}}'               => $datosCoto['pj_cp']              ?? '',

        // Persona seleccionada (representante / autorizado / firmante según plantilla)
        '{{persona_nombre}}'      => trim(($datosPersona['nombre']    ?? '') . ' ' .
                                         ($datosPersona['apellido1']  ?? '') . ' ' .
                                         ($datosPersona['apellido2']  ?? '')),
        '{{persona_nif}}'         => $datosPersona['dni_nif']         ?? '',
        '{{persona_telefono}}'    => $datosPersona['telefono']        ?? '',
        '{{persona_movil}}'       => $datosPersona['telefonomovil']   ?? '',
        '{{persona_email}}'       => $datosPersona['email']           ?? '',
        '{{persona_direccion}}'   => $persDireccion,
        '{{persona_municipio}}'   => $datosPersona['municipio']       ?? '',
        '{{persona_provincia}}'   => $datosPersona['provincia']       ?? '',
        '{{persona_cp}}'          => $datosPersona['cp']              ?? '',

        // Alias útiles para compatibilidad con plantillas existentes
        '{{representante_nombre}}' => trim(($datosPersona['nombre']   ?? '') . ' ' .
                                          ($datosPersona['apellido1'] ?? '') . ' ' .
                                          ($datosPersona['apellido2'] ?? '')),
        '{{representante_dni}}'    => $datosPersona['dni_nif']        ?? '',
        '{{autorizado_nombre}}'    => trim(($datosAutorizado['nombre']   ?? '') . ' ' .
                                          ($datosAutorizado['apellido1'] ?? '') . ' ' .
                                          ($datosAutorizado['apellido2'] ?? '')),
        '{{autorizado_nif}}'       => $datosAutorizado['dni_nif']        ?? '',

        // Fecha actual
        '{{fecha_hoy}}'            => date('d/m/Y'),
        '{{fecha_hoy_larga}}'      => strftime_es(time()),

        // Organizador / Capitán de la cacería
        '{{organizador_nombre}}'   => trim(($datosOrganizador['nombre']    ?? '') . ' ' .
                                          ($datosOrganizador['apellido1']  ?? '') . ' ' .
                                          ($datosOrganizador['apellido2']  ?? '')),
        '{{organizador_nif}}'      => $datosOrganizador['dni_nif']         ?? '',
        '{{organizador_telefono}}' => $datosOrganizador['telefono']        ?? '',
        '{{organizador_movil}}'    => $datosOrganizador['telefonomovil']   ?? '',
        '{{organizador_email}}'    => $datosOrganizador['email']           ?? '',
        '{{organizador_direccion}}'=> $orgDireccion,
        '{{organizador_municipio}}'=> $datosOrganizador['municipio']       ?? '',
        '{{organizador_provincia}}'=> $datosOrganizador['provincia']       ?? '',
        '{{organizador_cp}}'       => $datosOrganizador['cp']              ?? '',
    ];

    // Añadir los campos libres que escribió el usuario
    // (sobreescriben si hay conflicto, permitiendo que el usuario corrija un dato de BD)
    foreach ($camposLibres as $clave => $valor) {
        $vars['{{' . $clave . '}}'] = trim((string)$valor);
    }

    // ── 5. Generar el documento ───────────────────────────────
    $rutaPlantilla = PLANTILLAS_DIR . $plantilla['nombre_archivo'];
    $docData       = (new WordFiller($rutaPlantilla))->generate($vars);

    // ── 6. Devolver como descarga ─────────────────────────────
    $nombreDescarga = preg_replace('/[^a-zA-Z0-9_-]/', '_', $plantilla['nombre_visible']) . '_' . date('Ymd') . '.docx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $nombreDescarga . '"');
    header('Content-Length: ' . strlen($docData));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    echo $docData;

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}

// ── Helper: fecha en español ─────────────────────────────────
function strftime_es(int $timestamp): string
{
    $meses = ['enero','febrero','marzo','abril','mayo','junio',
              'julio','agosto','septiembre','octubre','noviembre','diciembre'];
    $d = (int)date('j', $timestamp);
    $m = $meses[(int)date('n', $timestamp) - 1];
    $a = date('Y', $timestamp);
    return "a {$d} de {$m} de {$a}";
}