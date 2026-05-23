<?php
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'https://mexxicanmx.online') {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();


$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($accion) {

    case 'lista':
        header('Cache-Control: public, max-age=60');
        $restaurante_id = null;

        // Prioridad 1: admin en sesión
        if (!empty($_SESSION['admin'])) {
            $restaurante_id = $_SESSION['admin']['restaurante_id'];
        }
        // Prioridad 2: rid desde URL (clientes en order.html)
        if (!$restaurante_id && !empty($_GET['rid'])) {
            $restaurante_id = (int)$_GET['rid'];
        }

        if ($restaurante_id) {
            $stmt = db()->prepare("SELECT * FROM menu WHERE restaurante_id = ? AND disponible = 1 ORDER BY categoria, nombre");
            $stmt->execute([$restaurante_id]);
        } else {
            $stmt = db()->prepare("SELECT * FROM menu WHERE disponible = 1 ORDER BY categoria, nombre");
            $stmt->execute();
        }
        responder(true, $stmt->fetchAll());

    case 'crear':
        if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
        $admin = $_SESSION['admin'];
        $nombre = trim($body['nombre'] ?? '');
        if (!$nombre) responder(false, null, 'Nombre requerido');
        
       // ── Verificar límite de productos del plan ────────────────
         verificarLimitePlan((int)$admin['restaurante_id'], 'productos');

        $stmt = db()->prepare(
            "INSERT INTO menu (restaurante_id, nombre, descripcion, precio, categoria, emoji, imagen, disponible) VALUES (?,?,?,?,?,?,?,1)"
        );
        $stmt->execute([
            $admin['restaurante_id'],
            $nombre,
            $body['descripcion'] ?? '',
            (float)($body['precio'] ?? 0),
            $body['categoria'] ?? 'comida',
            $body['emoji'] ?? '🍽️',
            $body['imagen'] ?? null,
        ]);
        responder(true, ['id' => db()->lastInsertId()], 'Platillo creado');

    case 'toggle':
        if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
        $id    = (int)($body['id'] ?? 0);
        $disp  = $body['disponible'] ? 1 : 0;
        $ridT  = (int)$_SESSION['admin']['restaurante_id'];
        db()->prepare("UPDATE menu SET disponible = ? WHERE id = ? AND restaurante_id = ?")
        ->execute([$disp, $id, $ridT]);
        responder(true, null, 'Actualizado');
        
    case 'editar':
        if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
        $id    = (int)($body['id'] ?? 0);
        $ridE  = (int)$_SESSION['admin']['restaurante_id'];
        $nombre      = trim($body['nombre'] ?? '');
        $descripcion = trim($body['descripcion'] ?? '');
        $precio      = (float)($body['precio'] ?? 0);
        $emoji       = trim($body['emoji'] ?? '🍽️');
        $categoria   = trim($body['categoria'] ?? 'comida');
        if (!$id || !$nombre) responder(false, null, 'Datos incompletos');
        db()->prepare(
            "UPDATE menu SET nombre=?, descripcion=?, precio=?, emoji=?, categoria=?
             WHERE id=? AND restaurante_id=?"
        )->execute([$nombre, $descripcion, $precio, $emoji, $categoria, $id, $ridE]);
        responder(true, null, 'Platillo actualizado');

    case 'eliminar':
        if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
        $id   = (int)($body['id'] ?? 0);
        $ridE = (int)$_SESSION['admin']['restaurante_id'];
        db()->prepare("DELETE FROM menu WHERE id = ? AND restaurante_id = ?")
        ->execute([$id, $ridE]);
        responder(true, null, 'Eliminado');

    case 'subir_imagen':
        if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
        if (empty($_FILES['imagen'])) responder(false, null, 'No se recibió imagen');

        // Verificar MIME type real del archivo (no solo el nombre)
        $finfo    = new finfo(FILEINFO_MIME_TYPE);
        $mimeReal = $finfo->file($_FILES['imagen']['tmp_name']);
        $mimesPermitidos = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($mimeReal, $mimesPermitidos)) {
            responder(false, null, 'Formato no permitido');
        }

        // Extensión basada en MIME real, no en el nombre del cliente
        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
        ];
        $ext = $extensiones[$mimeReal];

        $dir = __DIR__ . '/uploads/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Nombre aleatorio seguro, nunca el nombre original del cliente
        $nombreArchivo = 'plato_' . bin2hex(random_bytes(8)) . '.' . $ext;
        move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . $nombreArchivo);
        responder(true, ['url' => 'uploads/' . $nombreArchivo]);
        break;

case 'crear_promocion':
    if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
    $admin = $_SESSION['admin'];
    $rid   = (int)$admin['restaurante_id'];

    if (empty($_FILES['imagen'])) responder(false, null, 'No se recibió imagen');

    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($_FILES['imagen']['tmp_name']);
    if (!in_array($mimeReal, ['image/jpeg','image/png','image/webp']))
        responder(false, null, 'Formato no permitido');

    $ext = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mimeReal];
    $nombreArchivo = 'promo_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dir = __DIR__ . '/uploads/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    move_uploaded_file($_FILES['imagen']['tmp_name'], $dir . $nombreArchivo);

    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // ── Verificar acceso y límite de promociones del plan ─────
    $limitesPlan = obtenerLimitesPlan($rid);
    $maxPromos   = $limitesPlan['promociones'];

    if ($maxPromos === 0) {
        responder(false, ['upgrade_required' => true, 'plan' => $limitesPlan['plan']],
            'Las promociones no están disponibles en el plan ' . ucfirst($limitesPlan['plan']) . '. Actualiza a Plus o Premium.');
    }

    if ($maxPromos !== PHP_INT_MAX) {
        $stmtC = db()->prepare("SELECT COUNT(*) FROM promociones WHERE restaurante_id = ? AND activa = 1");
        $stmtC->execute([$rid]);
        $totalPromos = (int)$stmtC->fetchColumn();
        if ($totalPromos >= $maxPromos) {
            responder(false, ['upgrade_required' => true, 'plan' => $limitesPlan['plan']],
                "Has alcanzado el límite de {$maxPromos} promociones del plan Plus. Actualiza a Premium para publicaciones ilimitadas.");
        }
    }

    $titulo      = trim($_POST['titulo'] ?? '');

    db()->prepare("INSERT INTO promociones (restaurante_id, imagen, titulo, descripcion) VALUES (?,?,?,?)")
       ->execute([$rid, 'uploads/' . $nombreArchivo, $titulo, $descripcion]);

    responder(true, ['url' => 'uploads/' . $nombreArchivo], 'Promoción publicada');

case 'lista_promociones':
    $rid = (int)($_GET['rid'] ?? 0);
    if (!$rid) {
        if (!empty($_SESSION['admin'])) $rid = (int)$_SESSION['admin']['restaurante_id'];
    }
    if (!$rid) responder(false, null, 'Restaurante no especificado');
    $stmt = db()->prepare("SELECT * FROM promociones WHERE restaurante_id = ? AND activa = 1 ORDER BY creado_en DESC");
    $stmt->execute([$rid]);
    $promos      = $stmt->fetchAll();
    $limitesPlan = obtenerLimitesPlan($rid);
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'          => true,
        'data'        => $promos,
        'animaciones' => $limitesPlan['animaciones'],
        'plan'        => $limitesPlan['plan'],
        'mensaje'     => ''
    ]);
    exit;
    responder(true, $promos, '', ['animaciones' => $limitesPlan['animaciones'], 'plan' => $limitesPlan['plan']]);

case 'eliminar_promocion':
    if (empty($_SESSION['admin_id'])) responder(false, null, 'No autorizado');
    $id  = (int)($body['id'] ?? 0);
    $rid = (int)$_SESSION['admin']['restaurante_id'];
    db()->prepare("UPDATE promociones SET activa = 0 WHERE id = ? AND restaurante_id = ?")
       ->execute([$id, $rid]);
    responder(true, null, 'Promoción eliminada');
    default:
        responder(false, null, 'Acción no reconocida');
}