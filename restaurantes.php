 <?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/config.php';

$accion = $_GET['accion'] ?? '';

// ── Obtener nombre por ID ──
if ($accion === 'nombre') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false]); exit; }
    $stmt = db()->prepare("SELECT nombre FROM restaurantes WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    echo json_encode($r ? ['ok' => true, 'nombre' => $r['nombre']] : ['ok' => false]);
    exit;
}

// ── Buscar restaurante por nombre ──
if ($accion === 'buscar') {
    $nombre = trim($_GET['nombre'] ?? '');
    if (!$nombre) { echo json_encode(['ok' => false, 'data' => []]); exit; }
    $stmt = db()->prepare(
        "SELECT id, nombre, tipo, direccion 
         FROM restaurantes 
         WHERE nombre LIKE ? AND activo = 1 
         ORDER BY nombre ASC 
         LIMIT 5"
    );
    $stmt->execute(['%' . $nombre . '%']);
    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Obtener puntuación de un restaurante ──
if ($accion === 'puntuacion') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false]); exit; }

    $stmt = db()->prepare(
        "SELECT ROUND(AVG(estrellas), 1) AS promedio, COUNT(*) AS total
         FROM calificaciones WHERE restaurante_id = ?"
    );
    $stmt->execute([$id]);
    $data = $stmt->fetch();
    echo json_encode([
        'ok'       => true,
        'promedio' => $data['promedio'] ?? 0,
        'total'    => (int)($data['total'] ?? 0)
    ]);
    exit;
}

// ── Calificar restaurante ──
if ($accion === 'calificar') {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $rid      = (int)($body['restaurante_id'] ?? 0);
    $token    = trim($body['token'] ?? '');
    $estrellas = (int)($body['estrellas'] ?? 0);
    $comentario = trim($body['comentario'] ?? '');

    if (!$rid || !$token || $estrellas < 1 || $estrellas > 5) {
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']); exit;
    }

    try {
        db()->prepare(
            "INSERT INTO calificaciones (restaurante_id, token_sesion, estrellas, comentario)
             VALUES (?, ?, ?, ?)"
        )->execute([$rid, $token, $estrellas, $comentario ?: null]);

        echo json_encode(['ok' => true, 'mensaje' => '¡Gracias por tu calificación!']);
    } catch (Exception $e) {
        // UNIQUE KEY violation = ya calificó
        echo json_encode(['ok' => false, 'mensaje' => 'Ya calificaste este restaurante en esta visita']);
    }
    exit;
}

// ── Obtener tema visual del restaurante ──
if ($accion === 'tema') {
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { echo json_encode(['ok' => false]); exit; }
    $stmt = db()->prepare("SELECT tema_color, tema_nombre FROM restaurantes WHERE id = ?");
    $stmt->execute([$id]);
    $r = $stmt->fetch();
    echo json_encode(['ok' => true, 'tema_color' => $r['tema_color'] ?? null, 'tema_nombre' => $r['tema_nombre'] ?? 'dorado']);
    exit;
}

// ── Guardar tema visual (admin) ──
if ($accion === 'guardar_tema') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']); exit; }
    $body       = json_decode(file_get_contents('php://input'), true) ?? [];
    $rid        = (int)$_SESSION['admin']['restaurante_id'];
    $temaColor  = trim($body['tema_color'] ?? '');
    $temaNombre = trim($body['tema_nombre'] ?? 'dorado');

    // Solo premium
    $limites = obtenerLimitesPlan($rid);
    if (!$limites['animaciones']) {
        echo json_encode(['ok' => false, 'upgrade_required' => true, 'mensaje' => 'El tema visual solo está disponible en el plan Premium.']);
        exit;
    }

    db()->prepare("UPDATE restaurantes SET tema_color = ?, tema_nombre = ? WHERE id = ?")
       ->execute([$temaColor ?: null, $temaNombre, $rid]);
    echo json_encode(['ok' => true, 'mensaje' => 'Tema guardado']);
    exit;
}

// ── Config puntos (admin) ──
if ($accion === 'config_puntos') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) { echo json_encode(['ok' => false]); exit; }
    $body        = json_decode(file_get_contents('php://input'), true) ?? [];
    $rid         = (int)$_SESSION['admin']['restaurante_id'];
    $premioNombre = trim($body['premio_nombre'] ?? 'Premio sorpresa');
    $puntosMeta   = max(1, (int)($body['puntos_meta'] ?? 10));

    $limites = obtenerLimitesPlan($rid);
    if (!$limites['animaciones']) {
        echo json_encode(['ok' => false, 'upgrade_required' => true, 'mensaje' => 'El sistema de puntos solo está disponible en el plan Premium.']);
        exit;
    }

    db()->prepare("INSERT INTO config_puntos (restaurante_id, premio_nombre, puntos_meta)
                   VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE premio_nombre = VALUES(premio_nombre), puntos_meta = VALUES(puntos_meta)")
       ->execute([$rid, $premioNombre, $puntosMeta]);
    echo json_encode(['ok' => true, 'mensaje' => 'Configuración guardada']);
    exit;
}    

// ── Ver puntos de clientes (admin) ──
if ($accion === 'clientes_puntos') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) { echo json_encode(['ok' => false]); exit; }
    $rid = (int)$_SESSION['admin']['restaurante_id'];
    $stmt = db()->prepare("SELECT telefono, puntos_total, visitas, updated_at FROM puntos_clientes WHERE restaurante_id = ? ORDER BY puntos_total DESC");
    $stmt->execute([$rid]);
    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Resetear puntos de un cliente (admin) ──
if ($accion === 'resetear_puntos') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']); exit; }
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $rid   = (int)$_SESSION['admin']['restaurante_id'];
    $tel   = trim($body['telefono'] ?? '');
    if (!$tel || !$rid) { echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos']); exit; }
    db()->prepare("UPDATE puntos_clientes SET puntos_total = 0 WHERE restaurante_id = ? AND telefono = ?")
       ->execute([$rid, $tel]);
    echo json_encode(['ok' => true, 'mensaje' => 'Puntos reseteados correctamente']);
    exit;
}

// ── Guardar redes sociales (admin) ──
// ── Guardar redes sociales (admin) ──
if ($accion === 'guardar_redes') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) { echo json_encode(['ok' => false, 'mensaje' => 'No autorizado']); exit; }
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $rid  = (int)$_SESSION['admin']['restaurante_id'];

    $campos = ['facebook','instagram','whatsapp','tiktok','twitter'];
    $vals   = array_map(fn($c) => trim($body[$c] ?? '') ?: null, $campos);

    $chk = db()->prepare("SELECT id FROM restaurantes_redes WHERE restaurante_id = ?");
    $chk->execute([$rid]);
    if ($chk->fetch()) {
        $sets = implode(', ', array_map(fn($c) => "$c = ?", $campos));
        $params = [...$vals, $rid];
        db()->prepare("UPDATE restaurantes_redes SET $sets WHERE restaurante_id = ?")->execute($params);
    } else {
        $cols = implode(', ', $campos);
        $phs  = implode(', ', array_fill(0, count($campos), '?'));
        db()->prepare("INSERT INTO restaurantes_redes (restaurante_id, $cols) VALUES (?, $phs)")
            ->execute([$rid, ...$vals]);
    }
    echo json_encode(['ok' => true, 'mensaje' => 'Redes guardadas']);
    exit;
}

// ── Obtener redes sociales ──
if ($accion === 'get_redes') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $rid = !empty($_SESSION['admin_id']) 
        ? (int)$_SESSION['admin']['restaurante_id'] 
        : (int)($_GET['rid'] ?? 0);
    if (!$rid) { echo json_encode(['ok' => false]); exit; }
    $stmt = db()->prepare("SELECT facebook, instagram, whatsapp, tiktok, twitter FROM restaurantes_redes WHERE restaurante_id = ?");
    $stmt->execute([$rid]);
    $data = $stmt->fetch();
    echo json_encode(['ok' => true, 'data' => $data ?: []]);
    exit;
}

// ── Obtener todos los restaurantes para el mapa ──
if ($accion === 'todos') {
    $stmt = db()->query("
        SELECT id, nombre, tipo, direccion, lat, lng 
        FROM restaurantes 
        WHERE activo = 1
    ");
    $data = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

echo json_encode(['ok' => false, 'mensaje' => 'Acción no reconocida']);