<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$success = false;
$error = '';

$token = $_GET['token'] ?? '';

if ($token === '') {
    $error = "Некоректне посилання.";
} else {
    try {
        $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        // Знаходимо користувача з таким токеном
        $stmt = $pdo->prepare("SELECT id, confirmed FROM users WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "Посилання недійсне або вже використане.";
        } elseif ($user['confirmed']) {
            $error = "Акаунт вже підтверджено.";
        } else {
            // Підтверджуємо акаунт і очищаємо токен
            $stmt = $pdo->prepare("UPDATE users SET confirmed = 1, token = NULL WHERE id = :id");
            $stmt->execute([':id' => $user['id']]);
            $success = true;
        }
    } catch (PDOException $e) {
        $error = "Помилка БД: " . htmlspecialchars($e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h1>Підтвердження акаунта</h1>
    <?php if ($success): ?>
        <div class="success">
            <p>Ваш акаунт успішно підтверджено! Тепер ви можете <a href="login.php">увійти</a>.</p>
        </div>
    <?php else: ?>
        <div class="error">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
