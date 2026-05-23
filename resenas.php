<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/config.php';

$accion = $_GET['accion'] ?? '';

if ($accion === 'obtener') {
    try {
        $stmt = db()->query("SELECT nombre, texto, estrellas, created_at 
                             FROM resenas_plataforma 
                             WHERE activo = 1 
                             ORDER BY created_at DESC 
                             LIMIT 10");
        echo json_encode(['ok' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        echo json_encode(['ok' => false, 'error' => 'Error al obtener reseñas']);
    }
    exit;
}

if ($accion === 'guardar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body      = json_decode(file_get_contents('php://input'), true);
    $nombre    = substr(trim($body['nombre']   ?? 'Usuario anónimo'), 0, 100);
    $texto     = substr(trim($body['texto']    ?? ''), 0, 1000);
    $estrellas = max(1, min(5, intval($body['estrellas'] ?? 5)));
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '';

    if (strlen($texto) < 3) {
        echo json_encode(['ok' => false, 'error' => 'Texto muy corto']);
        exit;
    }

    try {
        db()->prepare("INSERT INTO resenas_plataforma (nombre, texto, estrellas, ip) VALUES (?,?,?,?)")
            ->execute([$nombre, $texto, $estrellas, $ip]);
        echo json_encode(['ok' => true]);
    } catch (Exception $e) {
        if (str_contains($e->getMessage(), "doesn't exist")) {
            db()->exec("CREATE TABLE IF NOT EXISTS resenas_plataforma (
                id int(11) NOT NULL AUTO_INCREMENT,
                nombre varchar(100) DEFAULT 'Usuario anónimo',
                texto text NOT NULL,
                estrellas tinyint(1) NOT NULL DEFAULT 5,
                ip varchar(50) DEFAULT NULL,
                activo tinyint(1) NOT NULL DEFAULT 1,
                created_at datetime DEFAULT current_timestamp(),
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            db()->prepare("INSERT INTO resenas_plataforma (nombre, texto, estrellas, ip) VALUES (?,?,?,?)")
                ->execute([$nombre, $texto, $estrellas, $ip]);
            echo json_encode(['ok' => true]);
        } else {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
    exit;
}

echo json_encode(['ok' => false, 'error' => 'Acción no válida']);