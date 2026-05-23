<?php
// ============================================================
//  api/empleados.php — API de Asistencia y Empleados
// ============================================================
$allowedOrigins = ['http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin === 'https://mexxicanmx.online') {
    header("Access-Control-Allow-Origin: $origin");
    header('Access-Control-Allow-Credentials: true');
}
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';
$body   = json_decode(file_get_contents('php://input'), true) ?? [];

switch ($accion) {

    // --------------------------------------------------------
    // REGISTRAR ENTRADA (escaneo QR empleado)
    // --------------------------------------------------------
    case 'entrada':
        if ($method !== 'POST') responder(false, null, 'Método inválido');

        $empId = (int)($body['empleado_id'] ?? 0);
        $pin   = $body['pin'] ?? '';

        $stmt = db()->prepare("SELECT * FROM empleados WHERE id = ? AND activo = 1");
        $stmt->execute([$empId]);
        $emp = $stmt->fetch();

        if (!$emp) responder(false, null, 'Empleado no encontrado');
        if (!password_verify($pin, $emp['pin'])) responder(false, null, 'PIN incorrecto');

        $hoy = date('Y-m-d');
        $db  = db();

        // Verificar si ya tiene entrada hoy
        $check = $db->prepare("SELECT * FROM asistencia WHERE empleado_id = ? AND fecha = ?");
        $check->execute([$emp['id'], $hoy]);
        $registro = $check->fetch();

        if ($registro && $registro['hora_entrada']) {
            responder(false, null, 'Ya tienes registrada tu entrada de hoy');
        }

        $ahora = date('Y-m-d H:i:s');
        if ($registro) {
            $db->prepare("UPDATE asistencia SET hora_entrada = ? WHERE id = ?")->execute([$ahora, $registro['id']]);
        } else {
            $db->prepare("INSERT INTO asistencia (empleado_id, fecha, hora_entrada) VALUES (?, ?, ?)")
               ->execute([$emp['id'], $hoy, $ahora]);
        }

        responder(true, [
            'empleado' => $emp['nombre'] . ' ' . $emp['apellido'],
            'hora'     => date('H:i'),
            'fecha'    => date('d/m/Y')
        ], "¡Bienvenido, {$emp['nombre']}! Entrada registrada a las " . date('H:i'));

    // --------------------------------------------------------
    // REGISTRAR SALIDA
    // --------------------------------------------------------
    case 'salida':
        if ($method !== 'POST') responder(false, null, 'Método inválido');

        $empId      = (int)($body['empleado_id'] ?? 0);
        $pin        = $body['pin'] ?? '';
        $hora_extra = (bool)($body['hora_extra'] ?? false);

        $stmt = db()->prepare("SELECT * FROM empleados WHERE id = ? AND activo = 1");
        $stmt->execute([$empId]);
        $emp = $stmt->fetch();

        if (!$emp) responder(false, null, 'Empleado no encontrado');
        if (!password_verify($pin, $emp['pin'])) responder(false, null, 'PIN incorrecto');

        $hoy = date('Y-m-d');
        $db  = db();

        $check = $db->prepare("SELECT * FROM asistencia WHERE empleado_id = ? AND fecha = ?");
        $check->execute([$emp['id'], $hoy]);
        $registro = $check->fetch();

        if (!$registro || !$registro['hora_entrada']) {
            responder(false, null, 'No tienes entrada registrada hoy');
        }
        if ($registro['hora_salida']) {
            responder(false, null, 'Ya tienes registrada tu salida de hoy');
        }

        $entrada  = new DateTime($registro['hora_entrada']);
        $salida   = new DateTime();
        $diff     = $entrada->diff($salida);
        $horasTot = $diff->h + ($diff->i / 60) + ($diff->days * 24);

        // Jornada normal = 8 horas
        $jornadaNormal = 8.0;
        $horasNormales = min($horasTot, $jornadaNormal);
        $horasExtra    = $hora_extra ? max(0, $horasTot - $jornadaNormal) : 0;

        $pagoNormal = $horasNormales * $emp['sueldo_hora'];
        $pagoExtra  = $horasExtra  * $emp['sueldo_hora'] * $emp['hora_extra_mult'];
        $pagoTotal  = $pagoNormal + $pagoExtra;

        $ahora = date('Y-m-d H:i:s');
        $db->prepare(
            "UPDATE asistencia SET
                hora_salida = ?, horas_trabajadas = ?, horas_extra = ?,
                pago_normal = ?, pago_extra = ?, pago_total = ?, tipo_salida = ?
             WHERE id = ?"
        )->execute([
            $ahora, round($horasTot, 2), round($horasExtra, 2),
            round($pagoNormal, 2), round($pagoExtra, 2), round($pagoTotal, 2),
            $hora_extra ? 'hora_extra' : 'normal',
            $registro['id']
        ]);

        responder(true, [
            'empleado'   => $emp['nombre'] . ' ' . $emp['apellido'],
            'entrada'    => date('H:i', strtotime($registro['hora_entrada'])),
            'salida'     => date('H:i'),
            'horas'      => round($horasTot, 2),
            'horas_extra'=> round($horasExtra, 2),
            'pago_total' => number_format($pagoTotal, 2)
        ], "¡Hasta mañana, {$emp['nombre']}! Salida a las " . date('H:i'));

    // --------------------------------------------------------
    // LISTA DE ASISTENCIA (admin)
    // --------------------------------------------------------
    case 'asistencia':
        verificarAdmin();
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $stmt = db()->prepare(
            "SELECT a.*, e.nombre, e.apellido, e.puesto, e.sueldo_hora
             FROM asistencia a
             JOIN empleados e ON e.id = a.empleado_id
             WHERE a.fecha = ?
             ORDER BY a.hora_entrada ASC"
        );
        $stmt->execute([$fecha]);
        responder(true, $stmt->fetchAll());

    // --------------------------------------------------------
    // REPORTE SEMANAL (admin)
    // --------------------------------------------------------
    case 'reporte_semana':
        verificarAdmin();
        $desde = $_GET['desde'] ?? date('Y-m-d', strtotime('monday this week'));
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $stmt = db()->prepare(
            "SELECT e.nombre, e.apellido, e.puesto,
                    SUM(a.horas_trabajadas) AS total_horas,
                    SUM(a.horas_extra) AS total_extra,
                    SUM(a.pago_total) AS total_pago,
                    COUNT(a.id) AS dias_trabajados
             FROM empleados e
             LEFT JOIN asistencia a ON a.empleado_id = e.id AND a.fecha BETWEEN ? AND ?
             WHERE e.activo = 1
             GROUP BY e.id ORDER BY e.nombre ASC"
        );
        $stmt->execute([$desde, $hasta]);
        responder(true, $stmt->fetchAll());

    // --------------------------------------------------------
    // TODOS LOS EMPLEADOS
    // --------------------------------------------------------
    case 'lista':
        verificarAdmin();
        $stmt = db()->prepare("SELECT id, nombre, apellido, puesto, sueldo_hora, hora_extra_mult, activo, token_qr FROM empleados ORDER BY nombre");
        $stmt->execute();
        responder(true, $stmt->fetchAll());

    // --------------------------------------------------------
    // CREAR EMPLEADO
    // --------------------------------------------------------
    case 'crear':
        verificarAdmin();
        if ($method !== 'POST') responder(false, null, 'Método inválido');

        $nombre  = trim($body['nombre'] ?? '');
        $apell   = trim($body['apellido'] ?? '');
        $puesto  = trim($body['puesto'] ?? 'mesero');
        $pin     = trim($body['pin'] ?? '');
        $sueldo  = (float)($body['sueldo_hora'] ?? 80);
        $mult    = (float)($body['hora_extra_mult'] ?? 1.5);

        if (!$nombre || !$apell || strlen($pin) < 4) responder(false, null, 'Datos incompletos');

        $token   = generarToken(16);
        $pinHash = password_hash($pin, PASSWORD_DEFAULT);
        // Obtener restaurante_id del admin en sesión
        $adminId = $_SESSION['admin_id'] ?? 0;
        $stmtA   = db()->prepare("SELECT restaurante_id FROM administradores WHERE id = ?");
        $stmtA->execute([$adminId]);
        $rid_admin = (int)($stmtA->fetchColumn() ?? 0);
        
        // ── Verificar límite de meseros del plan ──────────────────
        if ($puesto === 'mesero') {
            verificarLimitePlan($rid_admin, 'meseros');
        }
        
        db()->prepare(
            "INSERT INTO empleados (restaurante_id, nombre, apellido, puesto, pin, sueldo_hora, hora_extra_mult, token_qr)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$rid_admin, $nombre, $apell, $puesto, $pinHash, $sueldo, $mult, $token]);
        
        responder(true, ['token_qr' => $token], 'Empleado registrado correctamente');

    break;

    // ── MESEROS DISPONIBLES (público, lo llama el cliente) ──────
case 'meseros_disponibles':
    $rid = (int)($_GET['rid'] ?? 0);
    if (!$rid) responder(false, null, 'Restaurante no especificado');
    $stmt = db()->prepare("
        SELECT e.id, e.nombre, e.apellido, e.puesto,
               COUNT(p.id) AS mesas_activas
        FROM empleados e
        INNER JOIN asistencia a ON a.empleado_id = e.id
            AND (a.fecha = CURDATE() OR a.fecha = DATE(CONVERT_TZ(NOW(), '+00:00', '-06:00')))
            AND a.hora_entrada IS NOT NULL
            AND a.hora_salida IS NULL
        LEFT JOIN pedidos p ON p.mesero_id = e.id
            AND p.estado NOT IN ('entregado','cancelado')
            AND DATE(p.creado_en) = CURDATE()
        WHERE e.restaurante_id = ? AND e.activo = 1
        AND LOWER(e.puesto) IN ('mesero','mesera','camarero','camarera','servidor','servidor de mesa')
        GROUP BY e.id
        ORDER BY mesas_activas ASC, e.nombre ASC
    ");
    $stmt->execute([$rid]);
    $data = $stmt->fetchAll();
    responder(true, $data);

case 'solicitudes_pin':
    verificarAdmin();
    $admin = $_SESSION['admin'];
    $rid   = (int)($admin['restaurante_id'] ?? 0);
    $stmt  = db()->prepare("
        SELECT * FROM solicitudes_pin
        WHERE restaurante_id = ? AND resuelta = 0
        ORDER BY fecha DESC, id DESC
    ");
    $stmt->execute([$rid]);
    responder(true, $stmt->fetchAll());

case 'resetear_pin':
    verificarAdmin();
    $empId       = (int)($body['empleado_id'] ?? 0);
    $nuevoPIN    = trim($body['nuevo_pin'] ?? '');
    $solicitudId = (int)($body['solicitud_id'] ?? 0);

    if (!$empId || strlen($nuevoPIN) < 4) responder(false, null, 'Datos inválidos');

    $hash = password_hash($nuevoPIN, PASSWORD_DEFAULT);
    db()->prepare("UPDATE empleados SET pin = ? WHERE id = ?")
       ->execute([$hash, $empId]);

    if ($solicitudId) {
        db()->prepare("UPDATE solicitudes_pin SET resuelta = 1 WHERE id = ?")
           ->execute([$solicitudId]);
    }

    responder(true, null, 'PIN actualizado correctamente');
    
    
case 'actualizar_salario':
    verificarAdmin();
    $empId  = (int)($body['empleado_id'] ?? 0);
    $sueldo = (float)($body['sueldo_hora'] ?? 0);
    $mult   = (float)($body['hora_extra_mult'] ?? 1.5);

    if (!$empId || $sueldo <= 0) responder(false, null, 'Datos inválidos');
    if ($mult < 1) responder(false, null, 'El multiplicador debe ser mínimo 1');

    // Verificar que el empleado pertenece al restaurante del admin
    $adminId = $_SESSION['admin_id'];
    $stmtA   = db()->prepare("SELECT restaurante_id FROM administradores WHERE id = ?");
    $stmtA->execute([$adminId]);
    $rid_admin = (int)($stmtA->fetchColumn() ?? 0);

    $stmtE = db()->prepare("SELECT id FROM empleados WHERE id = ? AND restaurante_id = ?");
    $stmtE->execute([$empId, $rid_admin]);
    if (!$stmtE->fetch()) responder(false, null, 'Empleado no encontrado');

    db()->prepare("UPDATE empleados SET sueldo_hora = ?, hora_extra_mult = ? WHERE id = ?")
       ->execute([$sueldo, $mult, $empId]);

    responder(true, null, 'Salario actualizado correctamente');
    
case 'olvide_pin':
    if ($method !== 'POST') responder(false, null, 'Método inválido');

    $empId = (int)($body['empleado_id'] ?? 0);
    if (!$empId) responder(false, null, 'Empleado no especificado');

    $stmt = db()->prepare("SELECT * FROM empleados WHERE id = ? AND activo = 1");
    $stmt->execute([$empId]);
    $emp = $stmt->fetch();
    if (!$emp) responder(false, null, 'Empleado no encontrado');

    $hoy  = date('Y-m-d');
    $ahora = date('Y-m-d H:i:s');
    $db   = db();

    // Verificar si ya tiene entrada hoy
    $check = $db->prepare("SELECT * FROM asistencia WHERE empleado_id = ? AND fecha = ?");
    $check->execute([$empId, $hoy]);
    $registro = $check->fetch();

    if ($registro && $registro['hora_entrada']) {
        responder(false, null, 'Ya tienes entrada registrada hoy. Habla con el administrador para resetear tu PIN.');
    }

    // Registrar entrada con hora actual
    if ($registro) {
        $db->prepare("UPDATE asistencia SET hora_entrada = ? WHERE id = ?")
           ->execute([$ahora, $registro['id']]);
    } else {
        $db->prepare("INSERT INTO asistencia (empleado_id, fecha, hora_entrada) VALUES (?, ?, ?)")
           ->execute([$empId, $hoy, $ahora]);
    }

    // Guardar solicitud de reset en tabla de notificaciones del admin
    $db->prepare("
        INSERT INTO solicitudes_pin
            (empleado_id, restaurante_id, nombre_empleado, hora_entrada_registrada, fecha)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([
        $empId,
        $emp['restaurante_id'],
        $emp['nombre'] . ' ' . $emp['apellido'],
        $ahora,
        $hoy
    ]);

    responder(true, [
        'empleado' => $emp['nombre'] . ' ' . $emp['apellido'],
        'hora'     => date('H:i'),
    ], "Entrada registrada a las " . date('H:i') . ". El administrador recibirá la solicitud para resetear tu PIN.");

    default:
        responder(false, null, 'Acción no reconocida');
}