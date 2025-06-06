<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Перевірка авторизації
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: gallery.php');
    exit;
}

$image_id = $_POST['image_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$image_id || !is_numeric($image_id)) {
    die('Помилка: некоректний ID зображення.');
}

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Перевіряємо, чи існує зображення
    $stmt = $pdo->prepare('SELECT id FROM images WHERE id = ?');
    $stmt->execute([$image_id]);
    if (!$stmt->fetch()) {
        die('Зображення не знайдено.');
    }

    // Чи вже є лайк від цього користувача?
    $stmt = $pdo->prepare('SELECT id FROM likes WHERE user_id = ? AND image_id = ?');
    $stmt->execute([$user_id, $image_id]);
    $like = $stmt->fetch();

    if ($like) {
        // Якщо лайк вже є — видаляємо (анлайк)
        $stmt = $pdo->prepare('DELETE FROM likes WHERE id = ?');
        $stmt->execute([$like['id']]);
    } else {
        // Якщо лайка ще немає — додаємо
        $stmt = $pdo->prepare('INSERT INTO likes (user_id, image_id) VALUES (?, ?)');
        $stmt->execute([$user_id, $image_id]);
    }

    // Повертаємо назад (на попередню сторінку)
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'gallery.php';
    header('Location: ' . $redirect);
    exit;
} catch (PDOException $e) {
    die('Помилка БД: ' . htmlspecialchars($e->getMessage()));
}
?>
