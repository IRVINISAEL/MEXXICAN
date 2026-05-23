<?php
// ============================================================
//  pedidos.php — API REST de Pedidos
// ============================================================
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'https://mexxicanmx.online') {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}  
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$method  = $_SERVER['REQUEST_METHOD'];
$accion  = $_GET['accion'] ?? '';
$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true) ?? [];

// Token del body disponible para verificarCliente
$GLOBALS['_token_body'] = $body['token'] ?? '';

switch ($accion) {

    // --------------------------------------------------------
    // REGISTRAR CLIENTE (viene del QR)
    // --------------------------------------------------------
    case 'registrar_cliente':
        if ($method !== 'POST') responder(false, null, 'Método inválido');

        $nombre = trim($body['nombre'] ?? '');
        $tipo   = $body['tipo'] ?? 'mesa';       // 'mesa' o 'para_llevar'
        $mesa   = (int)($body['mesa'] ?? 0);

        if (!$nombre) responder(false, null, 'El nombre es requerido');
        if ($tipo === 'mesa' && $mesa < 1) responder(false, null, 'Número de mesa inválido');
        
        // ── Verificar que la mesa no exceda el límite del plan ────
if ($tipo === 'mesa' && $mesa > 0) {
    $rid_mesa = (int)($body['rid'] ?? 0);
    if ($rid_mesa) {
        $limitesMesa = obtenerLimitesPlan($rid_mesa);
        $maxMesas    = $limitesMesa['mesas'];
        if ($maxMesas !== PHP_INT_MAX && $mesa > $maxMesas) {
            $nombrePlan = ucfirst($limitesMesa['plan']);
            responder(false, [
                'limite_alcanzado' => true,
                'plan'   => $limitesMesa['plan'],
                'limite' => $maxMesas,
                'mesa'   => $mesa,
            ], "La mesa {$mesa} no está disponible. El plan {$nombrePlan} solo permite hasta {$maxMesas} mesas.");
        }
    }
}

        $token = generarToken();
        $db = db();
        $stmt = $db->prepare(
            "INSERT INTO usuarios (nombre, telefono, tipo, mesa_numero, token_sesion)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nombre,
            $body['telefono'] ?? null,
            $tipo,
            $tipo === 'mesa' ? $mesa : null,
            $token
        ]);
        $userId = $db->lastInsertId();

        responder(true, [
            'token'   => $token,
            'usuario' => ['id' => $userId, 'nombre' => $nombre, 'tipo' => $tipo, 'mesa' => $mesa]
        ], "¡Bienvenido, $nombre!");

    // --------------------------------------------------------
    // CREAR PEDIDO
    // --------------------------------------------------------
    case 'crear_pedido':
        if ($method !== 'POST') responder(false, null, 'Método inválido');

        $usuario  = verificarCliente();
        $items    = $body['items'] ?? [];
        $notas    = trim($body['notas'] ?? '');

        if (empty($items)) responder(false, null, 'El pedido está vacío');

        $db     = db();
        $numero = siguienteNumeroOrden();
        $total  = 0;

        // Verificar precios del menú
        $ids = array_column($items, 'menu_id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $menuStmt = $db->prepare("SELECT * FROM menu WHERE id IN ($placeholders) AND disponible = 1");
        $menuStmt->execute($ids);
        $menuItems = [];
        foreach ($menuStmt->fetchAll() as $m) $menuItems[$m['id']] = $m;

        foreach ($items as $item) {
            if (!isset($menuItems[$item['menu_id']])) responder(false, null, 'Ítem no disponible');
            $total += $menuItems[$item['menu_id']]['precio'] * $item['cantidad'];
        }

        // Insertar pedido
        $rid = (int)($body['rid'] ?? 0);
        // ── Verificar límite de pedidos del mes del plan ──────────
        if ($rid) {
            verificarLimitePlan($rid, 'pedidos_mes');
        }

        $mesero_id = (int)($body['mesero_id'] ?? 0) ?: null;

        $db->prepare(
            "INSERT INTO pedidos (usuario_id, restaurante_id, numero_orden, tipo, mesa_numero, mesero_id, total, notas)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $usuario['id'], $rid ?: null, $numero, $usuario['tipo'],
            $usuario['mesa_numero'], $mesero_id, $total, $notas
        ]);
        $pedidoId = $db->lastInsertId();

        // Insertar ítems
        $insItem = $db->prepare(
            "INSERT INTO pedido_items (pedido_id, menu_id, cantidad, precio_unit, subtotal)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($items as $item) {
            $precio   = $menuItems[$item['menu_id']]['precio'];
            $subtotal = $precio * $item['cantidad'];
            $insItem->execute([$pedidoId, $item['menu_id'], $item['cantidad'], $precio, $subtotal]);
        }

        $pedido = obtenerPedidoCompleto($pedidoId);
        responder(true, $pedido, "Pedido #$numero enviado");

    // --------------------------------------------------------
    // MIS PEDIDOS (cliente)
    // --------------------------------------------------------
    case 'mis_pedidos':
        $usuario = verificarCliente();
        $stmt = db()->prepare(
            "SELECT p.*, GROUP_CONCAT(CONCAT(pi.cantidad,'x ',m.nombre) SEPARATOR ', ') AS resumen
             FROM pedidos p
             LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
            LEFT JOIN menu m ON m.id = pi.menu_id
             WHERE p.usuario_id = ?
             GROUP BY p.id ORDER BY p.creado_en DESC LIMIT 10"
        );
        $stmt->execute([$usuario['id']]);
        responder(true, $stmt->fetchAll());

    // --------------------------------------------------------
    // TODOS LOS PEDIDOS (admin)
    // --------------------------------------------------------
    case 'todos_pedidos':
    verificarAdmin();
    $fecha  = $_GET['fecha'] ?? date('Y-m-d');
    $estado = $_GET['estado'] ?? '';

    // Obtener restaurante_id directo de la BD con el admin_id de sesión
    $adminId = $_SESSION['admin_id'];
    $stmtA = db()->prepare("SELECT restaurante_id FROM administradores WHERE id = ?");
    $stmtA->execute([$adminId]);
    $rid = (int)($stmtA->fetchColumn() ?? 0);

    $sql  = "SELECT p.*, u.nombre AS cliente, u.telefono,
                GROUP_CONCAT(CONCAT(pi.cantidad,'x ',m.nombre,' ($',pi.precio_unit,')') SEPARATOR '\n') AS items_texto,
                SUM(pi.cantidad) AS total_items,
                CONCAT(e.nombre,' ',e.apellido) AS mesero_nombre
         FROM pedidos p
         JOIN usuarios u ON u.id = p.usuario_id
         JOIN pedido_items pi ON pi.pedido_id = p.id
         JOIN menu m ON m.id = pi.menu_id
         LEFT JOIN empleados e ON e.id = p.mesero_id
         WHERE DATE(CONVERT_TZ(p.creado_en, '+00:00', '-06:00')) = ?
         AND (p.restaurante_id = ? OR p.restaurante_id IS NULL)";
    $params = [$fecha, $rid];

    if ($estado) { $sql .= " AND p.estado = ?"; $params[] = $estado; }
    $sql .= " GROUP BY p.id ORDER BY p.creado_en ASC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    responder(true, $stmt->fetchAll());
    break;
    
    case 'editar_pedido':
    verificarAdmin();
    if ($method !== 'PUT') responder(false, null, 'Método inválido');

    $pedidoId = (int)($body['pedido_id'] ?? 0);
    $items    = $body['items'] ?? [];

    if (!$pedidoId) responder(false, null, 'Pedido inválido');

    $db = db();
    $stmtP = $db->prepare("SELECT * FROM pedidos WHERE id = ?");
    $stmtP->execute([$pedidoId]);
    $pedido = $stmtP->fetch();
    if (!$pedido) responder(false, null, 'Pedido no encontrado');
    if (in_array($pedido['estado'], ['entregado', 'cancelado'])) {
        responder(false, null, 'No se puede editar un pedido entregado o cancelado');
    }

    $ids = array_filter(array_column($items, 'menu_id'));
    $menuItems = [];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $menuStmt = $db->prepare("SELECT * FROM menu WHERE id IN ($placeholders)");
        $menuStmt->execute(array_values($ids));
        foreach ($menuStmt->fetchAll() as $m) $menuItems[$m['id']] = $m;
    }

    foreach ($items as $item) {
        $menuId   = (int)($item['menu_id'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);
        if (!$menuId) continue;

        if ($cantidad <= 0) {
            $db->prepare("DELETE FROM pedido_items WHERE pedido_id = ? AND menu_id = ?")->execute([$pedidoId, $menuId]);
        } else {
            $precio   = isset($menuItems[$menuId]) ? $menuItems[$menuId]['precio'] : 0;
            $subtotal = $precio * $cantidad;
            $stmtEx = $db->prepare("SELECT id FROM pedido_items WHERE pedido_id = ? AND menu_id = ?");
            $stmtEx->execute([$pedidoId, $menuId]);
            if ($stmtEx->fetch()) {
                $db->prepare("UPDATE pedido_items SET cantidad=?, precio_unit=?, subtotal=? WHERE pedido_id=? AND menu_id=?")
                   ->execute([$cantidad, $precio, $subtotal, $pedidoId, $menuId]);
            } else {
                $db->prepare("INSERT INTO pedido_items (pedido_id, menu_id, cantidad, precio_unit, subtotal) VALUES (?,?,?,?,?)")
                   ->execute([$pedidoId, $menuId, $cantidad, $precio, $subtotal]);
            }
        }
    }

    $stmtT = $db->prepare("SELECT SUM(subtotal) as nuevo_total FROM pedido_items WHERE pedido_id = ?");
    $stmtT->execute([$pedidoId]);
    $nuevoTotal = (float)($stmtT->fetchColumn() ?? 0);
    $db->prepare("UPDATE pedidos SET total = ? WHERE id = ?")->execute([$nuevoTotal, $pedidoId]);

    $stmtU2 = $db->prepare("SELECT usuario_id FROM pedidos WHERE id = ?");
    $stmtU2->execute([$pedidoId]);
    $pu2 = $stmtU2->fetch();
    if ($pu2 && $pu2['usuario_id']) {
        $db->prepare("INSERT INTO notificaciones (usuario_id, mensaje, tipo) VALUES (?, ?, ?)")
           ->execute([$pu2['usuario_id'], '✏️ El restaurante actualizó tu pedido. Revisa "Mis pedidos".', 'estado']);
    }

    responder(true, ['nuevo_total' => $nuevoTotal], 'Pedido actualizado correctamente');

    // --------------------------------------------------------
    // ACTUALIZAR ESTADO (admin)
    // --------------------------------------------------------
    case 'actualizar_estado':
        verificarAdmin();
        if ($method !== 'PUT') responder(false, null, 'Método inválido');

        $pedidoId = (int)($body['pedido_id'] ?? 0);
        $estado   = $body['estado'] ?? '';
        $estados  = ['pendiente','en_preparacion','listo','entregado','cancelado'];

        if (!$pedidoId || !in_array($estado, $estados)) responder(false, null, 'Datos inválidos');

        db()->prepare("UPDATE pedidos SET estado = ? WHERE id = ?")->execute([$estado, $pedidoId]);

        // Notificar al cliente sobre el cambio de estado
        $mensajes = [
            'en_preparacion' => '👨‍🍳 Tu pedido está en preparación. ¡Ya vamos!',
            'listo'          => '✅ ¡Tu pedido está listo! El mesero va en camino.',
            'entregado'      => '🎉 Pedido entregado. ¡Buen provecho!',
            'cancelado'      => '❌ Tu pedido fue cancelado. Habla con el mesero.',
        ];
        if (isset($mensajes[$estado])) {
            $stmtU = db()->prepare("SELECT usuario_id FROM pedidos WHERE id = ?");
            $stmtU->execute([$pedidoId]);
            $pu = $stmtU->fetch();
            if ($pu && $pu['usuario_id']) {
                db()->prepare(
                    "INSERT INTO notificaciones (usuario_id, mensaje, tipo) VALUES (?, ?, ?)"
                )->execute([$pu['usuario_id'], $mensajes[$estado], 'estado']);
            }
        }

        responder(true, null, 'Estado actualizado');

    // --------------------------------------------------------
    // CORTE DE CAJA
    // --------------------------------------------------------
    case 'corte_caja':
        $admin = verificarAdmin();
        // ── Verificar acceso a estadísticas del plan ──────────────
        $rid_stats = (int)($admin['restaurante_id'] ?? 0);
        if ($rid_stats) {
            verificarAccesoEstadisticas($rid_stats, 'basicas');
        }
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $db    = db();

        $stmt = $db->prepare(
            "SELECT
                COUNT(*) AS total_pedidos,
                SUM(CASE WHEN tipo='mesa' THEN total ELSE 0 END) AS total_mesa,
                SUM(CASE WHEN tipo='para_llevar' THEN total ELSE 0 END) AS total_llevar,
                SUM(total) AS gran_total
             FROM pedidos
             WHERE DATE(CONVERT_TZ(creado_en, '+00:00', '-06:00')) = ? AND estado != 'cancelado'"
        );
        $stmt->execute([$fecha]);
        $resumen = $stmt->fetch();

        // Detalle completo
        $detalle = $db->prepare(
            "SELECT p.numero_orden, p.tipo, p.mesa_numero, p.total, p.estado,
                    u.nombre AS cliente, p.creado_en,
                    GROUP_CONCAT(CONCAT(pi.cantidad,'x ',m.nombre) SEPARATOR ', ') AS items
             FROM pedidos p
             JOIN usuarios u ON u.id = p.usuario_id
             JOIN pedido_items pi ON pi.pedido_id = p.id
             JOIN menu m ON m.id = pi.menu_id
             WHERE DATE(CONVERT_TZ(p.creado_en, '+00:00', '-06:00')) = ? AND p.estado != 'cancelado'
             GROUP BY p.id ORDER BY p.numero_orden ASC"
        );
        $detalle->execute([$fecha]);

        // Top vendidos
        $top = $db->prepare(
            "SELECT m.nombre, m.emoji, SUM(pi.cantidad) AS vendidos, SUM(pi.subtotal) AS ingresos
             FROM pedido_items pi
             JOIN pedidos p ON p.id = pi.pedido_id
             JOIN menu m ON m.id = pi.menu_id
             WHERE DATE(CONVERT_TZ(p.creado_en, '+00:00', '-06:00')) = ? AND p.estado != 'cancelado'
             GROUP BY pi.menu_id ORDER BY vendidos DESC LIMIT 5"
        );
        $top->execute([$fecha]);

        responder(true, [
            'fecha'   => $fecha,
            'resumen' => $resumen,
            'pedidos' => $detalle->fetchAll(),
            'top'     => $top->fetchAll()
        ]);
        break;

    case 'solicitar_cuenta':
    $token = $body['token'] ?? '';
    if (!$token) responder(false, null, 'Token requerido');

    $stmt = db()->prepare("SELECT id, nombre, mesa_numero, tipo FROM usuarios WHERE token_sesion = ? LIMIT 1");
    $stmt->execute([$token]);
    $cliente = $stmt->fetch();
    if (!$cliente) responder(false, null, 'Cliente no encontrado');

    $stmt = db()->prepare("
        SELECT p.id, p.numero_orden, p.total, p.estado,
               GROUP_CONCAT(CONCAT(pi.cantidad,'x ',m.nombre) SEPARATOR ', ') AS items_texto
        FROM pedidos p
        LEFT JOIN pedido_items pi ON pi.pedido_id = p.id
        LEFT JOIN menu m ON m.id = pi.menu_id
        WHERE p.usuario_id = ? AND p.estado NOT IN ('cancelado', 'entregado')
        AND DATE(p.creado_en) = CURDATE()
        GROUP BY p.id ORDER BY p.creado_en ASC
    ");
    $stmt->execute([$cliente['id']]);
    $pedidos    = $stmt->fetchAll();
    $gran_total = array_sum(array_column(
        array_filter($pedidos, fn($p) => !in_array($p['estado'], ['cancelado', 'entregado'])),
        'total'
    ));
    $chk = db()->prepare("SELECT id FROM solicitudes_pago WHERE usuario_id = ? AND DATE(creado_en) = CURDATE() AND pagado = 0 LIMIT 1");
    $chk->execute([$cliente['id']]);
    if ($chk->fetch()) responder(true, null, 'Cuenta ya solicitada, el mesero viene en camino');

    // Obtener restaurante_id del pedido del cliente
    $stmtRid = db()->prepare("SELECT restaurante_id FROM pedidos WHERE usuario_id = ? AND DATE(creado_en) = CURDATE() LIMIT 1");
    $stmtRid->execute([$cliente['id']]);
    $ridRow = $stmtRid->fetch();
    $restaurante_id = $ridRow['restaurante_id'] ?? null;
    
    $propina = (int)($body['propina'] ?? 0);

    db()->prepare("
        INSERT INTO solicitudes_pago (usuario_id, nombre_cliente, mesa_numero, tipo, total, propina, items_json, restaurante_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ")->execute([
        $cliente['id'], $cliente['nombre'], $cliente['mesa_numero'],
        $cliente['tipo'], $gran_total, $propina, json_encode($pedidos), $restaurante_id
    ]);

    $msg = $propina > 0
        ? "¡Cuenta solicitada! El mesero viene en camino 🙌 — Propina: \$$propina gracias 🙏"
        : '¡Cuenta solicitada! El mesero viene en camino 🙌';
    responder(true, null, $msg);

case 'solicitudes_pago':
    verificarAdmin();
    $adminId = $_SESSION['admin_id'];
    $stmtA   = db()->prepare("SELECT restaurante_id FROM administradores WHERE id = ?");
    $stmtA->execute([$adminId]);
    $rid = (int)($stmtA->fetchColumn() ?? 0);
    $stmt = db()->prepare("
        SELECT sp.*,
               CONCAT(e.nombre,' ',e.apellido) AS mesero_nombre
        FROM solicitudes_pago sp
        LEFT JOIN pedidos p ON p.usuario_id = sp.usuario_id 
            AND DATE(p.creado_en) = CURDATE()
        LEFT JOIN empleados e ON e.id = p.mesero_id
        WHERE DATE(sp.creado_en) = CURDATE()
        AND (sp.restaurante_id = ? OR sp.restaurante_id IS NULL)
        GROUP BY sp.id
        ORDER BY sp.creado_en DESC
    ");
    $stmt->execute([$rid]);
    responder(true, $stmt->fetchAll());
    break;
    
case 'confirmar_recibido':
    $token    = $body['token'] ?? '';
    $pedidoId = (int)($body['pedido_id'] ?? 0);
    if (!$token || !$pedidoId) responder(false, null, 'Datos inválidos');

    $stmt = db()->prepare("SELECT u.id FROM usuarios u JOIN pedidos p ON p.usuario_id = u.id WHERE u.token_sesion = ? AND p.id = ? AND p.estado = 'listo'");
    $stmt->execute([$token, $pedidoId]);
    if (!$stmt->fetch()) responder(false, null, 'Pedido no encontrado o no está listo');

    db()->prepare("UPDATE pedidos SET estado = 'entregado' WHERE id = ?")->execute([$pedidoId]);
    responder(true, null, '¡Gracias! Disfruta tu orden 🍽️');
    break;

case 'marcar_pagado':
    verificarAdmin();
    $id = (int)($body['id'] ?? 0);
    if (!$id) responder(false, null, 'ID inválido');

    db()->prepare("UPDATE solicitudes_pago SET pagado = 1 WHERE id = ?")->execute([$id]);

    $stmtSol = db()->prepare("SELECT usuario_id, total, restaurante_id FROM solicitudes_pago WHERE id = ?");
    $stmtSol->execute([$id]);
    $sol = $stmtSol->fetch();

    if ($sol && $sol['usuario_id']) {
        db()->prepare(
            "UPDATE pedidos SET estado = 'entregado'
             WHERE usuario_id = ?
             AND estado NOT IN ('cancelado','entregado')
             AND DATE(creado_en) = CURDATE()"
        )->execute([$sol['usuario_id']]);

        db()->prepare(
            "INSERT INTO notificaciones (usuario_id, mensaje, tipo) VALUES (?, ?, ?)"
        )->execute([$sol['usuario_id'], '✅ Tu cuenta fue pagada, ¡gracias por visitarnos!', 'pago']);

        // ── Sumar puntos si el restaurante tiene plan Premium ──
        $rid_pago = (int)($sol['restaurante_id'] ?? 0);
        if ($rid_pago) {
            $limitesPago = obtenerLimitesPlan($rid_pago);
            if ($limitesPago['animaciones'] === true) { // solo premium
                $totalPago  = (float)($sol['total'] ?? 0);
                $puntosGanados = (int)floor($totalPago / 50); // $50 = 1 punto
                if ($puntosGanados > 0) {
                    // Buscar teléfono del cliente como identificador permanente
                    $stmtTel = db()->prepare("SELECT telefono FROM usuarios WHERE id = ?");
                    $stmtTel->execute([$sol['usuario_id']]);
                    $uRow = $stmtTel->fetch();
                    $telefono = $uRow['telefono'] ?? null;

                    if ($telefono) {
                        // Upsert puntos acumulados por teléfono + restaurante
                        $stmtPts = db()->prepare("
                            INSERT INTO puntos_clientes (restaurante_id, telefono, puntos_total, visitas)
                            VALUES (?, ?, ?, 1)
                            ON DUPLICATE KEY UPDATE
                                puntos_total = puntos_total + VALUES(puntos_total),
                                visitas      = visitas + 1,
                                updated_at   = CURRENT_TIMESTAMP
                        ");
                        $stmtPts->execute([$rid_pago, $telefono, $puntosGanados]);

                        // Notificar al cliente
                        $stmtPtsRow = db()->prepare("SELECT puntos_total FROM puntos_clientes WHERE restaurante_id = ? AND telefono = ?");
                        $stmtPtsRow->execute([$rid_pago, $telefono]);
                        $ptsRow = $stmtPtsRow->fetch();
                        $totalPts = (int)($ptsRow['puntos_total'] ?? $puntosGanados);

                        $msg = "⭐ Ganaste {$puntosGanados} punto" . ($puntosGanados > 1 ? 's' : '') . " · Total: {$totalPts}/10";
                        db()->prepare("INSERT INTO notificaciones (usuario_id, mensaje, tipo) VALUES (?, ?, ?)")
                           ->execute([$sol['usuario_id'], $msg, 'puntos']);
                    }
                }
            }
        }
    }

    responder(true, null, 'Pago registrado ✅');

case 'canjear_premio':
    $token = $body['token'] ?? '';
    if (!$token) responder(false, null, 'Token requerido');
    $stmt = db()->prepare("SELECT id, nombre, mesa_numero, tipo FROM usuarios WHERE token_sesion = ? LIMIT 1");
    $stmt->execute([$token]);
    $cliente = $stmt->fetch();
    if (!$cliente) responder(false, null, 'Cliente no encontrado');
    $rid_canje = (int)($body['rid'] ?? 0);
    $premio = $body['premio'] ?? 'Premio';
    $mesa = $cliente['mesa_numero'] ? "Mesa {$cliente['mesa_numero']}" : 'Para llevar';
    $msg = "🎁 {$cliente['nombre']} ({$mesa}) quiere canjear su premio: {$premio}";
    db()->prepare("INSERT INTO notificaciones (usuario_id, mensaje, tipo) VALUES (?, ?, ?)")
        ->execute([$cliente['id'], $msg, 'premio']);
    responder(true, null, '¡Premio solicitado! El mesero viene en camino 🎉');

case 'notif_admin_canjes':
    verificarAdmin();
    $adminId = $_SESSION['admin_id'];
    $stmtA   = db()->prepare("SELECT restaurante_id FROM administradores WHERE id = ?");
    $stmtA->execute([$adminId]);
    $rowA    = $stmtA->fetch();
    $rid_adm = $rowA['restaurante_id'] ?? 0;
    $stmt    = db()->prepare("
        SELECT n.id, n.mensaje, n.creado_en
        FROM notificaciones n
        JOIN usuarios u ON u.id = n.usuario_id
        JOIN pedidos p ON p.usuario_id = u.id AND DATE(p.creado_en) = CURDATE()
        WHERE p.restaurante_id = ? AND n.tipo = 'premio' AND n.leida = 0
        GROUP BY n.id ORDER BY n.creado_en DESC LIMIT 10
    ");
    $stmt->execute([$rid_adm]);
    responder(true, $stmt->fetchAll());
    break;
case 'mis_notificaciones':
    $token = $_GET['token'] ?? '';
    if (!$token) responder(false, null, 'Token requerido');
    $stmt = db()->prepare("SELECT id FROM usuarios WHERE token_sesion = ? LIMIT 1");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if (!$u) responder(false, null, 'No encontrado');
    $stmt2 = db()->prepare(
        "SELECT * FROM notificaciones WHERE usuario_id = ? AND leida = 0 ORDER BY creado_en DESC"
    );
    $stmt2->execute([$u['id']]);
    $notifs = $stmt2->fetchAll();
    // NO marcar como leídas aquí — el cliente las marca al abrir el panel
    responder(true, $notifs);
    break;
    
case 'marcar_notif_leidas':
    $token = $body['token'] ?? '';
    if (!$token) responder(false, null, 'Token requerido');
    $stmt = db()->prepare("SELECT id FROM usuarios WHERE token_sesion = ? LIMIT 1");
    $stmt->execute([$token]);
    $u = $stmt->fetch();
    if (!$u) responder(false, null, 'No encontrado');
    db()->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?")->execute([$u['id']]);
    responder(true, null, 'Leídas');
    break;

case 'mis_puntos':
    $token = $_GET['token'] ?? '';
    if (!$token) responder(false, null, 'Token requerido');
    $rid_pts = (int)($_GET['rid'] ?? 0);

    $stmtU = db()->prepare("SELECT id, telefono FROM usuarios WHERE token_sesion = ?");
    $stmtU->execute([$token]);
    $uPts = $stmtU->fetch();
    if (!$uPts || !$uPts['telefono'] || !$rid_pts) responder(true, ['puntos' => 0, 'meta' => 10, 'disponible' => false]);

    // Verificar que el restaurante tiene plan premium
    $limitesPts = obtenerLimitesPlan($rid_pts);
    if (!$limitesPts['animaciones']) responder(true, ['puntos' => 0, 'meta' => 10, 'disponible' => false]);

    $stmtPts = db()->prepare("SELECT puntos_total, visitas FROM puntos_clientes WHERE restaurante_id = ? AND telefono = ?");
    $stmtPts->execute([$rid_pts, $uPts['telefono']]);
    $pts = $stmtPts->fetch();

    // Obtener premio configurado
    $stmtPremio = db()->prepare("SELECT premio_nombre, puntos_meta FROM config_puntos WHERE restaurante_id = ?");
    $stmtPremio->execute([$rid_pts]);
    $config = $stmtPremio->fetch();

    responder(true, [
        'disponible'    => true,
        'puntos'        => (int)($pts['puntos_total'] ?? 0),
        'visitas'       => (int)($pts['visitas'] ?? 0),
        'meta'          => (int)($config['puntos_meta'] ?? 10),
        'premio'        => $config['premio_nombre'] ?? 'Premio sorpresa',
        'puede_canjear' => (int)($pts['puntos_total'] ?? 0) >= (int)($config['puntos_meta'] ?? 10),
    ]);

    default:
        responder(false, null, 'Acción no reconocida');
}


// ---- Helper ----
function obtenerPedidoCompleto(int $id): array {
    $db = db();
    $stmt = $db->prepare(
        "SELECT p.*, u.nombre AS cliente, u.tipo AS cliente_tipo
         FROM pedidos p
         JOIN usuarios u ON u.id = p.usuario_id
         WHERE p.id = ?"
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return [];
    $stmtI = $db->prepare(
        "SELECT pi.menu_id, m.nombre, m.emoji, pi.cantidad, pi.precio_unit AS precio, pi.subtotal
         FROM pedido_items pi
         JOIN menu m ON m.id = pi.menu_id
         WHERE pi.pedido_id = ?"
    );
    $stmtI->execute([$id]);
    $row['items'] = $stmtI->fetchAll();
    return $row;
}