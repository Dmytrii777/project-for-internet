<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit.php');
    exit;
}

// Перевірка даних
$photo = $_POST['photo'] ?? '';
$overlay = $_POST['overlay'] ?? '';

if (!$photo || !$overlay) {
    die("Помилка: не всі дані передані.");
}

// Перевірка накладки
$overlay_path = __DIR__ . '/public/overlays/' . basename($overlay);
if (!file_exists($overlay_path) || !preg_match('/\.png$/i', $overlay)) {
    die("Помилка: некоректна накладка.");
}

// Декодування base64 фото
if (strpos($photo, 'data:image/png;base64,') === 0) {
    $photo = substr($photo, strlen('data:image/png;base64,'));
}
$photo_data = base64_decode($photo);

if ($photo_data === false) {
    die("Помилка: не вдалося декодувати зображення.");
}

// Створюємо зображення з фото
$photo_img = imagecreatefromstring($photo_data);
if (!$photo_img) {
    die("Помилка: не вдалося створити зображення.");
}

// Завантажуємо накладку
$overlay_img = imagecreatefrompng($overlay_path);
if (!$overlay_img) {
    imagedestroy($photo_img);
    die("Помилка: не вдалося завантажити накладку.");
}

// Масштабуємо накладку під розмір фото (320x240)
$photo_w = imagesx($photo_img);
$photo_h = imagesy($photo_img);
$overlay_resized = imagecreatetruecolor($photo_w, $photo_h);
imagealphablending($overlay_resized, false);
imagesavealpha($overlay_resized, true);
imagecopyresampled($overlay_resized, $overlay_img, 0, 0, 0, 0, $photo_w, $photo_h, imagesx($overlay_img), imagesy($overlay_img));

// Накладаємо накладку на фото
imagealphablending($photo_img, true);
imagesavealpha($photo_img, true);
imagecopy($photo_img, $overlay_resized, 0, 0, 0, 0, $photo_w, $photo_h);

// Зберігаємо файл
$filename = uniqid('img_') . '.png';
$save_path = __DIR__ . '/public/uploads/' . $filename;
if (!imagepng($photo_img, $save_path)) {
    imagedestroy($photo_img);
    imagedestroy($overlay_img);
    imagedestroy($overlay_resized);
    die("Помилка: не вдалося зберегти зображення.");
}

// Запис у БД
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->prepare("INSERT INTO images (user_id, filename) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $filename]);
} catch (PDOException $e) {
    // Якщо сталася помилка — видаляємо файл
    @unlink($save_path);
    die("Помилка БД: " . htmlspecialchars($e->getMessage()));
}

// Очищення пам'яті
imagedestroy($photo_img);
imagedestroy($overlay_img);
imagedestroy($overlay_resized);

// Повертаємося на edit.php (або можна зробити повідомлення)
header('Location: edit.php?success=1');
exit;
