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
$comment = trim($_POST['comment'] ?? '');

if (!$image_id || !is_numeric($image_id)) {
    die('Помилка: некоректний ID зображення.');
}
if ($comment === '' || mb_strlen($comment) > 255) {
    die('Помилка: коментар не може бути порожнім і має містити до 255 символів.');
}

try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Перевіряємо, чи існує зображення та отримуємо автора
    $stmt = $pdo->prepare('SELECT images.id, images.user_id, users.email, users.username, users.notify
                           FROM images
                           JOIN users ON images.user_id = users.id
                           WHERE images.id = ?');
    $stmt->execute([$image_id]);
    $img = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$img) {
        die('Зображення не знайдено.');
    }

    // Додаємо коментар
    $stmt = $pdo->prepare('INSERT INTO comments (user_id, image_id, comment) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $image_id, htmlspecialchars($comment)]);

    // Якщо автор зображення хоче отримувати сповіщення і це не він сам
    if ($img['notify'] && $img['user_id'] != $user_id) {
        $subject = "Новий коментар до вашого зображення на PhotoMix";
        $message = "Вітаємо, {$img['username']}!\n\n"
                 . "Ваше зображення отримало новий коментар:\n\n"
                 . "\"{$comment}\"\n\n"
                 . "Переглянути: http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/gallery.php";
        send_mail($img['email'], $subject, $message);
    }

    // Повертаємо назад (на попередню сторінку)
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'gallery.php';
    header('Location: ' . $redirect);
    exit;
} catch (PDOException $e) {
    die('Помилка БД: ' . htmlspecialchars($e->getMessage()));
}
?>
