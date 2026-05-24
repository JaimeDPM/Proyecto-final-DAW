<?php
/**
 * nuevo.php
 * API para la creación de nuevos registros en la base de datos.
 *
 * Método: POST
 * Parámetro GET:
 *   - tipo (string): 'persona' | 'coto'
 * Body JSON: campos del registro según el tipo
 *
 * Respuesta JSON: { ok, id }
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

// Verificar sesión activa
if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok'=>false,'msg'=>'No autenticado']);
    exit;
}

require_once __DIR__ . '/config.php';

$tipo = $_GET['tipo'] ?? '';

// Solo se aceptan peticiones POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
    exit;
}

// Leer el body JSON de la petición
$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$usuarioId = (int)$_SESSION['usuario_id'];

try {
    $pdo = getDB();

    if ($tipo === 'persona') {
        // Insertar nueva persona física en la tabla personas
        $stmt = $pdo->prepare("
            INSERT INTO personas (usuario_id, nombre, apellido1, apellido2, dni_nif, telefono, telefonomovil,
                email, tipovia, direccion, numero, portal, escalera, piso, puerta,
                cp, localidad, municipio, provincia, notas)
            VALUES (:usuario_id, :nombre, :apellido1, :apellido2, :dni_nif, :telefono, :telefonomovil,
                :email, :tipovia, :direccion, :numero, :portal, :escalera, :piso, :puerta,
                :cp, :localidad, :municipio, :provincia, :notas)
        ");
        $stmt->execute([
            ':usuario_id'    => $usuarioId,
            ':nombre'        => $body['nombre']        ?? '',
            ':apellido1'     => $body['apellido1']     ?? '',
            ':apellido2'     => $body['apellido2']     ?: null,
            ':dni_nif'       => $body['dni_nif']       ?? '',
            ':telefono'      => $body['telefono']      ?? '',
            ':telefonomovil' => $body['telefonomovil'] ?? '',
            ':email'         => $body['email']         ?? '',
            ':tipovia'       => $body['tipovia']       ?? '',
            ':direccion'     => $body['direccion']     ?? '',
            ':numero'        => $body['numero']        ?: null,
            ':portal'        => $body['portal']        ?: null,
            ':escalera'      => $body['escalera']      ?: null,
            ':piso'          => $body['piso']          ?: null,
            ':puerta'        => $body['puerta']        ?: null,
            ':cp'            => $body['cp']            ?? '',
            ':localidad'     => $body['localidad']     ?? '',
            ':municipio'     => $body['municipio']     ?? '',
            ':provincia'     => $body['provincia']     ?? '',
            ':notas'         => $body['notas']         ?: null,
        ]);
        echo json_encode(['ok'=>true, 'id'=>(int)$pdo->lastInsertId()]);

    } elseif ($tipo === 'coto') {
        // Insertar nuevo coto de caza con sus datos de titular
        // Si titular_id tiene valor = titular persona física; si no = persona jurídica (campos pj_*)
        $stmt = $pdo->prepare("
            INSERT INTO cotos (usuario_id, letra_provincia, numero_matricula, provincia, municipio,
                titular_id, razon_social, pj_nif, pj_telefono, pj_telefonomovil, pj_email,
                pj_tipovia, pj_direccion, pj_numero, pj_portal, pj_escalera, pj_piso, pj_puerta,
                pj_cp, pj_localidad, pj_municipio, pj_provincia, notas)
            VALUES (:usuario_id, :letra_provincia, :numero_matricula, :provincia, :municipio,
                :titular_id, :razon_social, :pj_nif, :pj_telefono, :pj_telefonomovil, :pj_email,
                :pj_tipovia, :pj_direccion, :pj_numero, :pj_portal, :pj_escalera, :pj_piso, :pj_puerta,
                :pj_cp, :pj_localidad, :pj_municipio, :pj_provincia, :notas)
        ");
        $stmt->execute([
            ':usuario_id'        => $usuarioId,
            ':letra_provincia'   => $body['letra_provincia']   ?? '',
            ':numero_matricula'  => $body['numero_matricula']  ?? '',
            ':provincia'         => $body['provincia']         ?? '',
            ':municipio'         => $body['municipio']         ?: null,
            ':titular_id'        => $body['titular_id']        ?: null,
            ':razon_social'      => $body['razon_social']      ?: null,
            ':pj_nif'            => $body['pj_nif']            ?: null,
            ':pj_telefono'       => $body['pj_telefono']       ?: null,
            ':pj_telefonomovil'  => $body['pj_telefonomovil']  ?: null,
            ':pj_email'          => $body['pj_email']          ?: null,
            ':pj_tipovia'        => $body['pj_tipovia']        ?: null,
            ':pj_direccion'      => $body['pj_direccion']      ?: null,
            ':pj_numero'         => $body['pj_numero']         ?: null,
            ':pj_portal'         => $body['pj_portal']         ?: null,
            ':pj_escalera'       => $body['pj_escalera']       ?: null,
            ':pj_piso'           => $body['pj_piso']           ?: null,
            ':pj_puerta'         => $body['pj_puerta']         ?: null,
            ':pj_cp'             => $body['pj_cp']             ?: null,
            ':pj_localidad'      => $body['pj_localidad']      ?: null,
            ':pj_municipio'      => $body['pj_municipio']      ?: null,
            ':pj_provincia'      => $body['pj_provincia']      ?: null,
            ':notas'             => $body['notas']             ?: null,
        ]);
        $cotoId = (int)$pdo->lastInsertId();

        // Insertar los cotos colindantes asociados al coto recién creado
        if (!empty($body['colindantes'])) {
            $stmtC = $pdo->prepare("
                INSERT INTO cotos_colindantes (coto_id, provincia, numero_coto, menos_500m, notas)
                VALUES (:coto_id, :provincia, :numero_coto, :menos_500m, :notas)
            ");
            foreach ($body['colindantes'] as $c) {
                $stmtC->execute([
                    ':coto_id'    => $cotoId,
                    ':provincia'  => $c['provincia']  ?? null,
                    ':numero_coto'=> $c['numero_coto']?? null,
                    ':menos_500m' => $c['menos_500m'] ?? 0,
                    ':notas'      => $c['notas']       ?? null,
                ]);
            }
        }

        echo json_encode(['ok'=>true, 'id'=>$cotoId]);

    } else {
        http_response_code(400);
        echo json_encode(['ok'=>false, 'msg'=>'Tipo no válido']);
    }

} catch (PDOException $e) {
    // Error de clave duplicada (matrícula o DNI ya existe)
    if ($e->getCode() == 23000) {
        http_response_code(409);
        echo json_encode(['ok'=>false, 'msg'=>'Ya existe un registro con esa matrícula o DNI.']);
    } else {
        http_response_code(500);
        echo json_encode(['ok'=>false, 'msg'=>$e->getMessage()]);
    }
}
