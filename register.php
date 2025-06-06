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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Перевірки
    if ($username === '' || $email === '' || $password === '' || $password2 === '') {
        $errors[] = "Всі поля обов'язкові.";
    }
    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        $errors[] = "Ім'я користувача має містити 3-30 латинських літер, цифр або _.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Некоректний email.";
    }
    if ($password !== $password2) {
        $errors[] = "Паролі не співпадають.";
    }
    if (strlen($password) < 6 || !preg_match('/[0-9]/', $password) || !preg_match('/[a-zA-Z]/', $password)) {
        $errors[] = "Пароль має бути не менше 6 символів і містити літери та цифри.";
    }

    if (!$errors) {
        try {
            $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            // Чи існує користувач з таким email або username?
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->fetch()) {
                $errors[] = "Такий логін або email вже зайнятий.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                // Одразу підтверджуємо акаунт (confirmed = 1), токен не потрібен
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, confirmed, notify, token) VALUES (?, ?, ?, 1, 1, NULL)");
                $stmt->execute([$username, $email, $hash]);
                $success = true;
            }
        } catch (PDOException $e) {
            $errors[] = "Помилка БД: " . htmlspecialchars($e->getMessage());
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h1>Реєстрація</h1>
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $err): ?>
                <p><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success">
            <p>Реєстрація успішна! Тепер ви можете <a href="login.php">увійти</a>.</p>
        </div>
    <?php else: ?>
        <form method="post" action="register.php" autocomplete="off">
            <label>
                Логін:<br>
                <input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,30}" title="3-30 латинських літер, цифр або _" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </label><br>
            <label>
                Email:<br>
                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </label><br>
            <label>
                Пароль:<br>
                <input type="password" name="password" required>
            </label><br>
            <label>
                Повторіть пароль:<br>
                <input type="password" name="password2" required>
            </label><br>
            <button type="submit">Зареєструватися</button>
        </form>
        <p>
            Вже маєте акаунт? <a href="login.php">Увійти</a>
        </p>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
