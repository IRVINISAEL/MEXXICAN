<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/config.php';

$accion = $_GET['accion'] ?? '';

// ── Obtener todos los momentos ──────────────────────────────
if ($accion === 'todos') {
    $ip    = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt  = db()->query("
        SELECT m.id, m.titulo, m.lugar, m.autor, m.device,
               m.estrellas, m.resena, m.imagen_path,
               m.likes, m.oficial, m.created_at,
               DATE_FORMAT(m.created_at, '%e %b %Y') AS fecha_fmt,
               IF(ml.id IS NOT NULL, 1, 0) AS ya_di_like
        FROM momentos m
        LEFT JOIN momentos_likes ml
               ON ml.momento_id = m.id AND ml.ip = " . db()->quote($ip) . "
        WHERE m.activo = 1
        ORDER BY m.oficial DESC, m.created_at DESC
        LIMIT 50
    ");
    echo json_encode(['ok' => true, 'data' => $stmt->fetchAll()]);
    exit;
}

// ── Dar / quitar like ──────────────────────────────────────
if ($accion === 'like') {
    $id = (int)($_GET['id'] ?? 0);
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!$id) { echo json_encode(['ok' => false, 'mensaje' => 'ID inválido']); exit; }

    try {
        // Intentar insertar like
        db()->prepare("INSERT INTO momentos_likes (momento_id, ip) VALUES (?, ?)")
            ->execute([$id, $ip]);
        db()->prepare("UPDATE momentos SET likes = likes + 1 WHERE id = ?")
            ->execute([$id]);
        $liked = true;
    } catch (PDOException $e) {
        // UNIQUE KEY = ya lo dio, quitarlo
        db()->prepare("DELETE FROM momentos_likes WHERE momento_id = ? AND ip = ?")
            ->execute([$id, $ip]);
        db()->prepare("UPDATE momentos SET likes = GREATEST(0, likes - 1) WHERE id = ?")
            ->execute([$id]);
        $liked = false;
    }

    $stmt = db()->prepare("SELECT likes FROM momentos WHERE id = ?");
    $stmt->execute([$id]);
    $likes = (int)$stmt->fetchColumn();

    echo json_encode(['ok' => true, 'liked' => $liked, 'likes' => $likes]);
    exit;
}

// ── Publicar nuevo momento ─────────────────────────────────
if ($accion === 'publicar') {
    // Solo POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']); exit;
    }

    $titulo   = trim($_POST['titulo']   ?? '');
    $lugar    = trim($_POST['lugar']    ?? '');
    $autor    = trim($_POST['autor']    ?? 'Usuario anónimo');
    $device   = trim($_POST['device']   ?? '📱 Mi celular');
    $estrellas = (int)($_POST['estrellas'] ?? 5);
    $resena   = trim($_POST['resena']   ?? '');

    // Validar campos obligatorios
    if (!$titulo || !$lugar) {
        echo json_encode(['ok' => false, 'mensaje' => 'Título y restaurante son obligatorios']); exit;
    }
    if ($estrellas < 1 || $estrellas > 5) $estrellas = 5;

    // Procesar imagen si viene
    $imagen_path = null;
    if (!empty($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext_ok = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $mime   = mime_content_type($_FILES['foto']['tmp_name']);
        if (!in_array($mime, $ext_ok)) {
            echo json_encode(['ok' => false, 'mensaje' => 'Solo se permiten imágenes JPG, PNG, WEBP o GIF']); exit;
        }
        if ($_FILES['foto']['size'] > 8 * 1024 * 1024) {
            echo json_encode(['ok' => false, 'mensaje' => 'La imagen no debe superar 8 MB']); exit;
        }

        $ext      = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION) ?: 'jpg';
        $nombre   = 'momento_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $destino  = __DIR__ . '/uploads/' . $nombre;

        if (!is_dir(__DIR__ . '/uploads')) mkdir(__DIR__ . '/uploads', 0755, true);

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $destino)) {
            $imagen_path = 'uploads/' . $nombre;
        }
    }

    try {
        $stmt = db()->prepare("
            INSERT INTO momentos (titulo, lugar, autor, device, estrellas, resena, imagen_path)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $titulo,
            $lugar,
            $autor    ?: 'Usuario anónimo',
            $device   ?: '📱 Mi celular',
            $estrellas,
            $resena   ?: null,
            $imagen_path
        ]);
        $nuevo_id = (int)db()->lastInsertId();
        echo json_encode(['ok' => true, 'id' => $nuevo_id, 'mensaje' => '¡Momento publicado!']);
    } catch (PDOException $e) {
        echo json_encode(['ok' => false, 'mensaje' => 'Error al guardar: ' . $e->getMessage()]);
    }
    exit;
}

// ── Stats generales ────────────────────────────────────────
if ($accion === 'stats') {
    $stmt = db()->query("
        SELECT
            COUNT(*) AS total_fotos,
            COUNT(DISTINCT lugar) AS total_lugares,
            SUM(likes) AS total_likes
        FROM momentos WHERE activo = 1
    ");
    $data = $stmt->fetch();
    echo json_encode(['ok' => true, 'data' => $data]);
    exit;
}

echo json_encode(['ok' => false, 'mensaje' => 'Acción no reconocida']);