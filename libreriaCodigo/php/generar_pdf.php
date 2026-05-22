<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/PdfFiller.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => 'ID inválido']);
    exit;
}

try {
    $stmt = getDB()->prepare("
        SELECT
            dj.id,
            dj.en_calidad_de   AS decl_en_calidad_de,
            dj.tipo_entidad    AS decl_tipo_entidad,
            dj.genero          AS decl_genero,

            -- Matrícula completa del coto
            CONCAT(c.letra_provincia, '-', c.numero_matricula) AS coto_num_matricula,
            c.provincia        AS coto_provincia,
            c.municipio        AS coto_municipio,

            -- Interesado (siempre persona física)
            pi.nombre          AS int_nombre,
            pi.apellido1       AS int_apellido1,
            pi.apellido2       AS int_apellido2,
            pi.dni_nif         AS int_dni,
            pi.provincia       AS int_provincia,
            pi.municipio       AS int_municipio,
            pi.cp              AS int_cp,
            pi.tipovia         AS int_tipovia,
            pi.direccion       AS int_direccion,
            pi.numero          AS int_numero,
            pi.telefono        AS int_telefono,
            pi.telefonomovil   AS int_telefonomovil,
            pi.email           AS int_email,

            -- Representante (nullable)
            pr.nombre          AS rep_nombre,
            pr.apellido1       AS rep_apellido1,
            pr.apellido2       AS rep_apellido2,
            pr.dni_nif         AS rep_dni,
            pr.provincia       AS rep_provincia,
            pr.municipio       AS rep_municipio,
            pr.cp              AS rep_cp,
            pr.tipovia         AS rep_tipovia,
            pr.direccion       AS rep_direccion,
            pr.numero          AS rep_numero,
            pr.telefono        AS rep_telefono,
            pr.telefonomovil   AS rep_telefonomovil,
            pr.email           AS rep_email,

            -- Organizador (nullable — puede ser persona física o datos del coto PJ)
            po.nombre          AS org_nombre,
            po.apellido1       AS org_apellido1,
            po.apellido2       AS org_apellido2,
            po.dni_nif         AS org_dni,
            po.provincia       AS org_provincia,
            po.municipio       AS org_municipio,
            po.cp              AS org_cp,
            po.tipovia         AS org_tipovia,
            po.direccion       AS org_direccion,
            po.numero          AS org_numero,
            po.telefono        AS org_telefono,
            po.telefonomovil   AS org_telefonomovil,
            po.email           AS org_email,

            -- Datos PJ del coto (si el titular es el propio coto)
            c.razon_social     AS coto_razon_social,
            c.pj_nif           AS coto_pj_nif,
            c.pj_telefono      AS coto_pj_telefono,
            c.pj_telefonomovil AS coto_pj_telefonomovil,
            c.pj_email         AS coto_pj_email,
            c.pj_tipovia       AS coto_pj_tipovia,
            c.pj_direccion     AS coto_pj_direccion,
            c.pj_numero        AS coto_pj_numero,
            c.pj_municipio     AS coto_pj_municipio,
            c.pj_provincia     AS coto_pj_provincia,
            c.pj_cp            AS coto_pj_cp

        FROM declaraciones_junta dj
        INNER JOIN personas pi ON pi.id = dj.interesado_id
        LEFT  JOIN personas pr ON pr.id = dj.representante_id
        LEFT  JOIN personas po ON po.id = dj.organizador_id
        INNER JOIN cotos    c  ON c.id  = dj.coto_id
        WHERE dj.id = :id
    ");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'msg' => 'Declaración no encontrada']);
        exit;
    }

    // Si el organizador no existe (nullable) y el coto es PJ,
    // usamos los datos del propio coto como organizador
    if (empty($row['org_nombre']) && !empty($row['coto_razon_social'])) {
        $row['org_nombre']        = $row['coto_razon_social'];
        $row['org_apellido1']     = '';
        $row['org_apellido2']     = '';
        $row['org_dni']           = $row['coto_pj_nif'];
        $row['org_provincia']     = $row['coto_pj_provincia'];
        $row['org_municipio']     = $row['coto_pj_municipio'];
        $row['org_cp']            = $row['coto_pj_cp'];
        $row['org_tipovia']       = $row['coto_pj_tipovia'];
        $row['org_direccion']     = $row['coto_pj_direccion'];
        $row['org_numero']        = $row['coto_pj_numero'];
        $row['org_telefono']      = $row['coto_pj_telefono'];
        $row['org_telefonomovil'] = $row['coto_pj_telefonomovil'];
        $row['org_email']         = $row['coto_pj_email'];
    }

    $pdfData  = (new PdfFiller(PDF_TEMPLATE))->generate($row);
    $apellido = preg_replace('/[^a-zA-Z0-9_-]/', '_', $row['int_apellido1'] ?? 'declaracion');
    $filename = 'declaracion_' . $apellido . '_' . $id . '.pdf';

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($pdfData));
    header('Cache-Control: no-cache, no-store');
    echo $pdfData;

} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
