<?php
// ============================================================
//  planes.php  — API de planes para el super-admin
// ============================================================
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json; charset=utf-8');

// ── Super-admin: PIN secreto ─────────────────────────────────
// Cambia este PIN por uno tuyo de mínimo 8 caracteres
define('SUPERADMIN_PIN', 'mexxican2026si');

$accion = $_GET['accion'] ?? '';

// ── Verificar pin superadmin ──────────────────────────────────
function verificarSuperAdmin(): void {
    $pin = $_SERVER['HTTP_X_SUPER_PIN'] ?? ($_GET['pin'] ?? '');
    if ($pin !== SUPERADMIN_PIN) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'mensaje' => 'No autorizado']));
    }
}

// ── Listar todos los restaurantes con su plan ─────────────────
if ($accion === 'lista') {
    verificarSuperAdmin();
    $stmt = db()->query("
        SELECT
            r.id,
            r.nombre,
            r.direccion,
            a.nombre   AS admin_nombre,
            a.usuario  AS admin_usuario,
            p.plan,
            p.periodo,
            p.estado,
            p.monto,
            p.fecha_activacion,
            p.fecha_vencimiento,
            p.notas,
            p.id       AS plan_id
        FROM restaurantes r
        LEFT JOIN administradores a ON a.restaurante_id = r.id
        LEFT JOIN planes_activos  p ON p.restaurante_id = r.id
            AND p.estado IN ('activo','pendiente')
        ORDER BY r.nombre ASC
    ");
    responder(true, $stmt->fetchAll());
}

// ── Activar un plan ───────────────────────────────────────────
if ($accion === 'activar') {
    verificarSuperAdmin();
    $body          = json_decode(file_get_contents('php://input'), true) ?? [];
    $restaurante_id = (int)($body['restaurante_id'] ?? 0);
    $plan           = $body['plan']    ?? '';
    $periodo        = $body['periodo'] ?? 'mensual';
    $monto          = (float)($body['monto'] ?? 0);
    $notas          = trim($body['notas'] ?? '');

    if (!$restaurante_id || !in_array($plan, ['basico','plus','premium'])) {
        responder(false, null, 'Datos inválidos');
    }

    $dias            = $periodo === 'anual' ? 365 : 30;
    $fecha_activacion = date('Y-m-d H:i:s');
    $fecha_vencimiento = date('Y-m-d', strtotime("+{$dias} days"));

    // Cancelar planes anteriores del mismo restaurante
    db()->prepare("
        UPDATE planes_activos
        SET estado = 'cancelado'
        WHERE restaurante_id = ? AND estado IN ('activo','pendiente')
    ")->execute([$restaurante_id]);

    // Insertar plan nuevo activo
    db()->prepare("
        INSERT INTO planes_activos
            (restaurante_id, plan, periodo, estado, monto, fecha_activacion, fecha_vencimiento, notas)
        VALUES (?, ?, ?, 'activo', ?, ?, ?, ?)
    ")->execute([$restaurante_id, $plan, $periodo, $monto, $fecha_activacion, $fecha_vencimiento, $notas ?: null]);

    responder(true, [
        'fecha_vencimiento' => $fecha_vencimiento
    ], "Plan {$plan} activado hasta {$fecha_vencimiento}");
}

// ── Reset contraseña ──────────────────────────────────────────
if ($accion === 'reset_password') {
    verificarSuperAdmin();
    $body  = json_decode(file_get_contents('php://input'), true) ?? [];
    $rid   = (int)($body['restaurante_id'] ?? 0);
    $nueva = trim($body['nueva_password'] ?? '');

    if (!$rid || strlen($nueva) < 6) {
        responder(false, null, 'Datos inválidos');
    }

    $hash = password_hash($nueva, PASSWORD_DEFAULT);
    $stmt = db()->prepare("UPDATE administradores SET password = ? WHERE restaurante_id = ? AND activo = 1");
    $stmt->execute([$hash, $rid]);

    responder(true, null, 'Contraseña actualizada');
}

// ── Cancelar un plan ──────────────────────────────────────────
if ($accion === 'cancelar') {
    verificarSuperAdmin();
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $plan_id = (int)($body['plan_id'] ?? 0);
    if (!$plan_id) responder(false, null, 'ID inválido');

    db()->prepare("
        UPDATE planes_activos SET estado = 'cancelado' WHERE id = ?
    ")->execute([$plan_id]);

    responder(true, null, 'Plan cancelado');
}


// ── Plan del restaurante autenticado (sin PIN, usa sesión) ────
if ($accion === 'mi_plan') {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (empty($_SESSION['admin_id'])) {
        responder(false, null, 'No autorizado');
    }
    $rid = (int)($_SESSION['admin']['restaurante_id'] ?? 0);
    if (!$rid) responder(false, null, 'Sin restaurante');

    $stmt = db()->prepare("
        SELECT plan, periodo, estado, monto, fecha_activacion, fecha_vencimiento,
               DATEDIFF(fecha_vencimiento, CURDATE()) AS dias_restantes
        FROM planes_activos
        WHERE restaurante_id = ? AND estado = 'activo'
        ORDER BY fecha_activacion DESC
        LIMIT 1
    ");
    $stmt->execute([$rid]);
    $plan = $stmt->fetch();

    if (!$plan) {
    responder(true, null, 'Sin plan activo');
    }
    
    // Agregar límites del plan al response
    $limitesPlan = obtenerLimitesPlan($rid);
    $plan['limites'] = [
        'mesas'        => $limitesPlan['mesas'] === PHP_INT_MAX ? null : $limitesPlan['mesas'],
        'productos'    => $limitesPlan['productos'] === PHP_INT_MAX ? null : $limitesPlan['productos'],
        'pedidos_mes'  => $limitesPlan['pedidos_mes'] === PHP_INT_MAX ? null : $limitesPlan['pedidos_mes'],
        'meseros'      => $limitesPlan['meseros'] === PHP_INT_MAX ? null : $limitesPlan['meseros'],
        'estadisticas' => $limitesPlan['estadisticas'],
        'soporte'      => $limitesPlan['soporte'],
    ];
    
    responder(true, $plan);
}

responder(false, null, 'Acción no reconocida');