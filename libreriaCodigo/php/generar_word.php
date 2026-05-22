<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/WordFiller.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

try {
    // JOIN con personas y cotos para obtener todos los datos
    $stmt = getDB()->prepare("
        SELECT
            dw.*,

            -- Titular del coto
            pt.nombre        AS tit_nombre,
            pt.apellido1     AS tit_apellido1,
            pt.apellido2     AS tit_apellido2,
            pt.dni_nif       AS tit_nif,

            -- Representante (nullable)
            pr.nombre        AS rep_nombre,
            pr.apellido1     AS rep_apellido1,
            pr.apellido2     AS rep_apellido2,
            pr.dni_nif       AS rep_dni,

            -- Autorizado
            pa.nombre        AS aut_nombre,
            pa.apellido1     AS aut_apellido1,
            pa.apellido2     AS aut_apellido2,
            pa.dni_nif       AS aut_nif,

            -- Coto
            c.num_matricula  AS coto_matricula,
            c.provincia      AS coto_provincia,
            c.municipio      AS coto_municipio

        FROM documentos_word dw
        INNER JOIN cotos    c  ON c.id  = dw.coto_id
        INNER JOIN personas pt ON pt.id = c.titular_id
        LEFT  JOIN personas pr ON pr.id = dw.representante_id
        INNER JOIN personas pa ON pa.id = dw.autorizado_id
        WHERE dw.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Documento no encontrado']);
        exit;
    }

    // Construir nombre completo del representante (o vacío si no hay)
    $repNombreCompleto = '';
    $repDni            = '';
    if (!empty($row['rep_nombre'])) {
        $repNombreCompleto = trim($row['rep_nombre'] . ' ' . $row['rep_apellido1'] . ' ' . $row['rep_apellido2']);
        $repDni            = $row['rep_dni'];
    }

    $titNombre = trim($row['tit_nombre'] . ' ' . $row['tit_apellido1'] . ' ' . $row['tit_apellido2']);
    $autNombre = trim($row['aut_nombre'] . ' ' . $row['aut_apellido1'] . ' ' . $row['aut_apellido2']);

    // Mapa de marcadores -> valores
    // Los marcadores en el .docx deben ser exactamente {{clave}}
    $vars = [
        '{{representante_nombre}}'  => $repNombreCompleto ?: $titNombre,
        '{{representante_dni}}'     => $repDni ?: $row['tit_nif'],
        '{{titular_nombre}}'        => $titNombre,
        '{{titular_nif}}'           => $row['tit_nif'],
        '{{coto_matricula}}'        => $row['coto_matricula'],
        '{{autorizado_nombre}}'     => $autNombre,
        '{{autorizado_nif}}'        => $row['aut_nif'],
        '{{num_peticion}}'          => $row['num_peticion']  ?? '',
        '{{temporada}}'             => $row['temporada']     ?? '',
        '{{fecha_inicio}}'          => $row['fecha_inicio']  ?? '',
        '{{fecha_fin}}'             => $row['fecha_fin']     ?? '',
        '{{especie}}'               => $row['especie']       ?? '',
        '{{modalidad}}'             => $row['modalidad']     ?? '',
        '{{cupo}}'                  => $row['cupo']          ?? '',
    ];

    $templatePath = PLANTILLAS_WORD_DIR . $row['plantilla'];
    $docData      = (new WordFiller($templatePath))->generate($vars);

    $filename = 'doc_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['aut_apellido1']) . '_' . $id . '.docx';

    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($docData));
    header('Cache-Control: no-cache, no-store');
    echo $docData;

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
