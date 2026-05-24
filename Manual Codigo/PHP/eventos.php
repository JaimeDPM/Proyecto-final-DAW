<?php
/**
 * eventos.php
 * API CRUD completa para la gestión de eventos del calendario.
 *
 * Métodos soportados:
 *   GET  ?mes=YYYY-MM          → devuelve todos los eventos del mes indicado
 *   GET  ?proximos=1           → devuelve eventos que empiezan en los próximos 15 días
 *   GET  ?id=N                 → devuelve un evento concreto
 *   POST                       → crea un nuevo evento
 *   PUT  ?id=N                 → actualiza un evento existente
 *   DELETE ?id=N               → elimina un evento
 *
 * Control de acceso:
 *   - Eventos de tipo 'temporada': solo el admin puede crearlos/editarlos/eliminarlos
 *   - Resto de eventos: el usuario solo puede gestionar los suyos o los de sus cotos
 *
 * Respuesta JSON: { ok, data[] } o { ok, id }
 */
session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    echo json_encode(['ok' => false, 'error' => "$errstr en línea $errline"]);
    exit;
});

if (empty($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'msg' => 'No autenticado']);
    exit;
}

$usuarioId  = (int)$_SESSION['usuario_id'];
$usuarioRol = $_SESSION['usuario_rol'] ?? 'usuario';
$method     = $_SERVER['REQUEST_METHOD'];

try {
    $pdo = getDB();

    // GET: proximos 15 dias
    if (isset($_GET['proximos'])) {
        $hoy = date('Y-m-d');
        $fin = date('Y-m-d', strtotime('+15 days'));

        $stmt = $pdo->prepare("
            SELECT e.id, e.titulo, e.tipo, e.icono, e.fecha_inicio, e.fecha_fin,
                   e.comentario, e.recurrente, e.coto_id, e.usuario_id,
                   CONCAT(c.letra_provincia,'-',c.numero_matricula) AS coto_matricula
            FROM eventos e
            LEFT JOIN cotos c ON c.id = e.coto_id
            WHERE e.recurrente = 0
              AND e.fecha_inicio BETWEEN :hoy AND :fin
              AND (e.usuario_id = :uid OR e.tipo = 'temporada'
                   OR EXISTS (SELECT 1 FROM cotos cc WHERE cc.id = e.coto_id AND cc.usuario_id = :uid2))
            ORDER BY e.fecha_inicio
        ");
        $stmt->execute([':hoy' => $hoy, ':fin' => $fin, ':uid' => $usuarioId, ':uid2' => $usuarioId]);
        $rows = $stmt->fetchAll();

        $stmt2 = $pdo->prepare("
            SELECT e.id, e.titulo, e.tipo, e.icono, e.fecha_inicio, e.fecha_fin,
                   e.comentario, e.recurrente, e.coto_id, e.usuario_id,
                   CONCAT(c.letra_provincia,'-',c.numero_matricula) AS coto_matricula
            FROM eventos e
            LEFT JOIN cotos c ON c.id = e.coto_id
            WHERE e.recurrente = 1
              AND (e.usuario_id = :uid OR e.tipo = 'temporada'
                   OR EXISTS (SELECT 1 FROM cotos cc WHERE cc.id = e.coto_id AND cc.usuario_id = :uid2))
        ");
        $stmt2->execute([':uid' => $usuarioId, ':uid2' => $usuarioId]);
        $recurrentes = $stmt2->fetchAll();

        $anyoActual = (int)date('Y');
        foreach ($recurrentes as $ev) {
            $mmdd     = substr($ev['fecha_inicio'], 5);
            $fechaEst = $anyoActual . '-' . $mmdd;
            if ($fechaEst < $hoy) $fechaEst = ($anyoActual + 1) . '-' . $mmdd;
            if ($fechaEst >= $hoy && $fechaEst <= $fin) {
                $ev['fecha_inicio'] = $fechaEst;
                $rows[] = $ev;
            }
        }

        usort($rows, fn($a, $b) => strcmp($a['fecha_inicio'], $b['fecha_inicio']));
        echo json_encode(['ok' => true, 'data' => $rows]);
        exit;
    }

    // GET: eventos del mes
    if ($method === 'GET') {
        $mes = $_GET['mes'] ?? date('Y-m');
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Formato de mes invalido']);
            exit;
        }
        $inicio  = $mes . '-01';
        $fin     = date('Y-m-t', strtotime($inicio));
        $anyoMes = (int)substr($mes, 0, 4);

        $stmt = $pdo->prepare("
            SELECT e.id, e.titulo, e.tipo, e.icono, e.fecha_inicio, e.fecha_fin,
                   e.comentario, e.recurrente, e.coto_id, e.usuario_id,
                   CONCAT(c.letra_provincia,'-',c.numero_matricula) AS coto_matricula
            FROM eventos e
            LEFT JOIN cotos c ON c.id = e.coto_id
            WHERE e.recurrente = 0
              AND (e.fecha_inicio BETWEEN :inicio AND :fin
               OR (e.fecha_fin IS NOT NULL AND e.fecha_fin >= :inicio2 AND e.fecha_inicio <= :fin2))
              AND (e.usuario_id = :uid OR e.tipo = 'temporada'
                   OR EXISTS (SELECT 1 FROM cotos cc WHERE cc.id = e.coto_id AND cc.usuario_id = :uid2))
            ORDER BY e.fecha_inicio
        ");
        $stmt->execute([
            ':inicio' => $inicio, ':fin' => $fin,
            ':inicio2' => $inicio, ':fin2' => $fin,
            ':uid' => $usuarioId, ':uid2' => $usuarioId,
        ]);
        $rows = $stmt->fetchAll();

        $stmt2 = $pdo->prepare("
            SELECT e.id, e.titulo, e.tipo, e.icono, e.fecha_inicio, e.fecha_fin,
                   e.comentario, e.recurrente, e.coto_id, e.usuario_id,
                   CONCAT(c.letra_provincia,'-',c.numero_matricula) AS coto_matricula
            FROM eventos e
            LEFT JOIN cotos c ON c.id = e.coto_id
            WHERE e.recurrente = 1
              AND (e.usuario_id = :uid OR e.tipo = 'temporada'
                   OR EXISTS (SELECT 1 FROM cotos cc WHERE cc.id = e.coto_id AND cc.usuario_id = :uid2))
        ");
        $stmt2->execute([':uid' => $usuarioId, ':uid2' => $usuarioId]);
        $recurrentes = $stmt2->fetchAll();

        foreach ($recurrentes as $ev) {
            $mmdd            = substr($ev['fecha_inicio'], 5);
            $fechaProyectada = $anyoMes . '-' . $mmdd;
            if ($fechaProyectada >= $inicio && $fechaProyectada <= $fin) {
                $ev['fecha_inicio'] = $fechaProyectada;
                $rows[] = $ev;
            }
        }

        usort($rows, fn($a, $b) => strcmp($a['fecha_inicio'], $b['fecha_inicio']));
        echo json_encode(['ok' => true, 'data' => $rows]);
        exit;
    }

    $body = [];
    if (in_array($method, ['POST', 'PUT'])) {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
    }

    // POST: crear evento
    if ($method === 'POST') {
        $titulo      = trim($body['titulo']     ?? '');
        $tipo        = $body['tipo']            ?? '';
        $icono       = $body['icono']           ?: null;
        $fechaInicio = $body['fecha_inicio']    ?? '';
        $fechaFin    = $body['fecha_fin']       ?: null;
        $cotoId      = $body['coto_id']         ?: null;
        $comentario  = trim($body['comentario'] ?? '') ?: null;
        $recurrente  = !empty($body['recurrente']) ? 1 : 0;

        if (!$titulo || !$tipo || !$fechaInicio) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'titulo, tipo y fecha_inicio son obligatorios']);
            exit;
        }
        if (!in_array($tipo, ['caceria', 'tramite', 'precinto', 'temporada'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'msg' => 'Tipo no valido']);
            exit;
        }
        if ($tipo === 'temporada' && $usuarioRol !== 'admin') {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'Solo el administrador puede crear eventos de temporada']);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO eventos (titulo, tipo, icono, fecha_inicio, fecha_fin, coto_id, comentario, recurrente, usuario_id)
            VALUES (:titulo, :tipo, :icono, :fecha_inicio, :fecha_fin, :coto_id, :comentario, :recurrente, :usuario_id)
        ");
        $stmt->execute([
            ':titulo'       => $titulo,
            ':tipo'         => $tipo,
            ':icono'        => $icono,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin,
            ':coto_id'      => $cotoId,
            ':comentario'   => $comentario,
            ':recurrente'   => $recurrente,
            ':usuario_id'   => $usuarioId,
        ]);
        echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
        exit;
    }

    // PUT: editar evento
    if ($method === 'PUT') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'ID invalido']); exit; }

        $check = $pdo->prepare('SELECT usuario_id, tipo FROM eventos WHERE id = :id');
        $check->execute([':id' => $id]);
        $ev = $check->fetch();
        if (!$ev) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Evento no encontrado']); exit; }
        if ($usuarioRol !== 'admin' && (int)$ev['usuario_id'] !== $usuarioId) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'No tienes permisos para editar este evento']);
            exit;
        }

        $titulo      = trim($body['titulo']     ?? '');
        $tipo        = $body['tipo']            ?? '';
        $icono       = $body['icono']           ?: null;
        $fechaInicio = $body['fecha_inicio']    ?? '';
        $fechaFin    = $body['fecha_fin']       ?: null;
        $cotoId      = $body['coto_id']         ?: null;
        $comentario  = trim($body['comentario'] ?? '') ?: null;
        $recurrente  = !empty($body['recurrente']) ? 1 : 0;

        $stmt = $pdo->prepare("
            UPDATE eventos
            SET titulo=:titulo, tipo=:tipo, icono=:icono, fecha_inicio=:fecha_inicio,
                fecha_fin=:fecha_fin, coto_id=:coto_id, comentario=:comentario, recurrente=:recurrente
            WHERE id=:id
        ");
        $stmt->execute([
            ':titulo'       => $titulo,
            ':tipo'         => $tipo,
            ':icono'        => $icono,
            ':fecha_inicio' => $fechaInicio,
            ':fecha_fin'    => $fechaFin,
            ':coto_id'      => $cotoId,
            ':comentario'   => $comentario,
            ':recurrente'   => $recurrente,
            ':id'           => $id,
        ]);
        echo json_encode(['ok' => true]);
        exit;
    }

    // DELETE: eliminar evento
    if ($method === 'DELETE') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'ID invalido']); exit; }

        $check = $pdo->prepare('SELECT usuario_id FROM eventos WHERE id = :id');
        $check->execute([':id' => $id]);
        $ev = $check->fetch();
        if (!$ev) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Evento no encontrado']); exit; }
        if ($usuarioRol !== 'admin' && (int)$ev['usuario_id'] !== $usuarioId) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'msg' => 'No tienes permisos para eliminar este evento']);
            exit;
        }

        $pdo->prepare('DELETE FROM eventos WHERE id = :id')->execute([':id' => $id]);
        echo json_encode(['ok' => true]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['ok' => false, 'msg' => 'Metodo no permitido']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}