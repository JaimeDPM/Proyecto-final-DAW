<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
if (empty($_SESSION['usuario_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'No autenticado']); exit; }
require_once __DIR__ . '/config.php';

$tipo   = $_GET['tipo'] ?? '';
$id     = (int)($_GET['id'] ?? 0);
$method = $_SERVER['REQUEST_METHOD'];

if ($id <= 0 || empty($tipo)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Parámetros inválidos']); exit; }

try {
    $pdo = getDB();

    if ($method === 'GET') {
        switch ($tipo) {
            case 'persona': $stmt = $pdo->prepare('SELECT * FROM personas WHERE id = :id'); break;
            case 'coto':    $stmt = $pdo->prepare('SELECT * FROM cotos WHERE id = :id'); break;
            default: http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Tipo no válido']); exit;
        }
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'No encontrado']); exit; }
        echo json_encode(['ok'=>true,'data'=>$row]);
        exit;
    }

    if ($method === 'PUT') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($tipo === 'persona') {
            $stmt = $pdo->prepare("
                UPDATE personas SET
                    nombre=:nombre, apellido1=:apellido1, apellido2=:apellido2,
                    dni_nif=:dni_nif, telefono=:telefono, telefonomovil=:telefonomovil,
                    email=:email, tipovia=:tipovia, direccion=:direccion, numero=:numero,
                    portal=:portal, escalera=:escalera, piso=:piso, puerta=:puerta,
                    cp=:cp, localidad=:localidad, municipio=:municipio, provincia=:provincia,
                    notas=:notas
                WHERE id=:id
            ");
            $stmt->execute([
                ':nombre'=>$body['nombre']??'', ':apellido1'=>$body['apellido1']??'',
                ':apellido2'=>$body['apellido2']?:null, ':dni_nif'=>$body['dni_nif']??'',
                ':telefono'=>$body['telefono']??'', ':telefonomovil'=>$body['telefonomovil']??'',
                ':email'=>$body['email']??'', ':tipovia'=>$body['tipovia']??'',
                ':direccion'=>$body['direccion']??'', ':numero'=>$body['numero']?:null,
                ':portal'=>$body['portal']?:null, ':escalera'=>$body['escalera']?:null,
                ':piso'=>$body['piso']?:null, ':puerta'=>$body['puerta']?:null,
                ':cp'=>$body['cp']??'', ':localidad'=>$body['localidad']??'',
                ':municipio'=>$body['municipio']??'', ':provincia'=>$body['provincia']??'',
                ':notas'=>$body['notas']?:null, ':id'=>$id,
            ]);
        } elseif ($tipo === 'coto') {
            $stmt = $pdo->prepare("
                UPDATE cotos SET
                    letra_provincia=:letra_provincia, numero_matricula=:numero_matricula,
                    provincia=:provincia, municipio=:municipio,
                    titular_id=:titular_id, razon_social=:razon_social,
                    pj_nif=:pj_nif, pj_telefono=:pj_telefono, pj_telefonomovil=:pj_telefonomovil,
                    pj_email=:pj_email, pj_tipovia=:pj_tipovia, pj_direccion=:pj_direccion,
                    pj_numero=:pj_numero, pj_portal=:pj_portal, pj_escalera=:pj_escalera,
                    pj_piso=:pj_piso, pj_puerta=:pj_puerta, pj_cp=:pj_cp,
                    pj_localidad=:pj_localidad, pj_municipio=:pj_municipio, pj_provincia=:pj_provincia,
                    notas=:notas
                WHERE id=:id
            ");
            $stmt->execute([
                ':letra_provincia'=>$body['letra_provincia']??'',
                ':numero_matricula'=>$body['numero_matricula']??'',
                ':provincia'=>$body['provincia']??'', ':municipio'=>$body['municipio']?:null,
                ':titular_id'=>$body['titular_id']?:null, ':razon_social'=>$body['razon_social']?:null,
                ':pj_nif'=>$body['pj_nif']?:null, ':pj_telefono'=>$body['pj_telefono']?:null,
                ':pj_telefonomovil'=>$body['pj_telefonomovil']?:null, ':pj_email'=>$body['pj_email']?:null,
                ':pj_tipovia'=>$body['pj_tipovia']?:null, ':pj_direccion'=>$body['pj_direccion']?:null,
                ':pj_numero'=>$body['pj_numero']?:null, ':pj_portal'=>$body['pj_portal']?:null,
                ':pj_escalera'=>$body['pj_escalera']?:null, ':pj_piso'=>$body['pj_piso']?:null,
                ':pj_puerta'=>$body['pj_puerta']?:null, ':pj_cp'=>$body['pj_cp']?:null,
                ':pj_localidad'=>$body['pj_localidad']?:null, ':pj_municipio'=>$body['pj_municipio']?:null,
                ':pj_provincia'=>$body['pj_provincia']?:null, ':notas'=>$body['notas']?:null,
                ':id'=>$id,
            ]);

            // Actualizar colindantes: borrar los anteriores e insertar los nuevos
            $pdo->prepare("DELETE FROM cotos_colindantes WHERE coto_id = :coto_id")->execute([':coto_id' => $id]);
            if (!empty($body['colindantes'])) {
                $stmtC = $pdo->prepare("INSERT INTO cotos_colindantes (coto_id, provincia, numero_coto, menos_500m, notas) VALUES (:coto_id, :provincia, :numero_coto, :menos_500m, :notas)");
                foreach ($body['colindantes'] as $c) {
                    $stmtC->execute([
                        ':coto_id'    => $id,
                        ':provincia'  => $c['provincia'] ?? null,
                        ':numero_coto'=> $c['numero_coto'] ?? null,
                        ':menos_500m' => $c['menos_500m'] ?? 0,
                        ':notas'      => $c['notas'] ?? null,
                    ]);
                }
            }
        }
        echo json_encode(['ok'=>true]);
        exit;
    }

    http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'Método no permitido']);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}