<?php
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->query("SELECT filename FROM images ORDER BY created_at DESC LIMIT 5");
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($images):
        echo '<div style="display:flex;gap:10px;">';
        foreach ($images as $img) {
            echo '<img src="public/uploads/' . htmlspecialchars($img['filename']) . '" width="80" style="border-radius:4px;border:1px solid #ccc;">';
        }
        echo '</div>';
    else:
        echo '<p>Ще немає зображень.</p>';
    endif;
} catch (PDOException $e) {
    echo '<p>Не вдалося завантажити зображення.</p>';
}
?>
