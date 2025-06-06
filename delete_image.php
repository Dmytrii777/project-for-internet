<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

// Перевірка авторизації
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit.php');
    exit;
}

$image_id = $_POST['image_id'] ?? null;
if (!$image_id || !is_numeric($image_id)) {
    die('Помилка: некоректний ID зображення.');
}

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Перевіряємо, чи існує зображення і чи належить користувачу
    $stmt = $pdo->prepare('SELECT filename, user_id FROM images WHERE id = ?');
    $stmt->execute([$image_id]);
    $image = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$image) {
        die('Зображення не знайдено.');
    }

    if (!is_owner($image['user_id'])) {
        header('HTTP/1.1 403 Forbidden');
        die('Доступ заборонено.');
    }

    // Видаляємо файл
    $file_path = __DIR__ . '/public/uploads/' . $image['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Видаляємо записи з лайками та коментарями
    $pdo->prepare('DELETE FROM likes WHERE image_id = ?')->execute([$image_id]);
    $pdo->prepare('DELETE FROM comments WHERE image_id = ?')->execute([$image_id]);

    // Видаляємо запис зображення
    $pdo->prepare('DELETE FROM images WHERE id = ?')->execute([$image_id]);

    header('Location: edit.php?deleted=1');
    exit;
} catch (PDOException $e) {
    die('Помилка БД: ' . htmlspecialchars($e->getMessage()));
}
?>
