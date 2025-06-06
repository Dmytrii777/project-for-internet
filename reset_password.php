<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$errors = [];
$success = false;

// Якщо користувач вже залогінений — перенаправляємо
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? '';
$show_reset_form = false;

// 1. Запит на скидання пароля (email)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$token) {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введіть коректний email.";
    } else {
        try {
            $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // Не розкриваємо, що email не знайдено
                $success = true;
            } else {
                // Генеруємо токен для скидання
                $reset_token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE users SET token = :token WHERE id = :id");
                $stmt->execute([':token' => $reset_token, ':id' => $user['id']]);

                // Надсилаємо листа
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/reset_password.php?token=" . urlencode($reset_token);
                $subject = "Скидання пароля на PhotoMix";
                $message = "Вітаємо, {$user['username']}!\n\nЩоб скинути пароль, перейдіть за посиланням:\n$reset_link";
                send_mail($email, $subject, $message);

                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Помилка БД: " . htmlspecialchars($e->getMessage());
        }
    }
}

// 2. Перехід за посиланням з токеном
if ($token) {
    try {
        $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE token = :token LIMIT 1");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $errors[] = "Посилання недійсне або вже використане.";
        } else {
            $show_reset_form = true;
            // Обробка зміни пароля
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
                $password = $_POST['password'] ?? '';
                $password2 = $_POST['password2'] ?? '';
                if ($password === '' || $password2 === '') {
                    $errors[] = "Введіть новий пароль двічі.";
                } elseif ($password !== $password2) {
                    $errors[] = "Паролі не співпадають.";
                } elseif (strlen($password) < 6 || !preg_match('/[0-9]/', $password) || !preg_match('/[a-zA-Z]/', $password)) {
                    $errors[] = "Пароль має бути не менше 6 символів і містити літери та цифри.";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = :pass, token = NULL WHERE id = :id");
                    $stmt->execute([':pass' => $hash, ':id' => $user['id']]);
                    $success = true;
                    $show_reset_form = false;
                }
            }
        }
    } catch (PDOException $e) {
        $errors[] = "Помилка БД: " . htmlspecialchars($e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h1>Скидання пароля</h1>
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $err): ?>
                <p><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($success && !$show_reset_form): ?>
        <div class="success">
            <p>Інструкції для скидання пароля надіслано на вашу пошту (якщо email існує у системі).<br>
            Якщо ви щойно змінили пароль — тепер можете <a href="login.php">увійти</a>.</p>
        </div>
    <?php elseif ($show_reset_form): ?>
        <form method="post" action="reset_password.php?token=<?= urlencode($token) ?>">
            <label>
                Новий пароль:<br>
                <input type="password" name="password" required>
            </label><br>
            <label>
                Повторіть пароль:<br>
                <input type="password" name="password2" required>
            </label><br>
            <button type="submit">Змінити пароль</button>
        </form>
    <?php else: ?>
        <form method="post" action="reset_password.php" autocomplete="off">
            <label>
                Введіть email для скидання пароля:<br>
                <input type="email" name="email" required>
            </label><br>
            <button type="submit">Надіслати інструкцію</button>
        </form>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
