<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

$errors = [];

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errors[] = "Введіть логін та пароль.";
    } else {
        try {
            $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username OR email = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $errors[] = "Невірний логін або пароль.";
            } elseif (!$user['confirmed']) {
                $errors[] = "Підтвердіть email перед входом.";
            } else {
                // Вхід успішний
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Помилка БД: " . htmlspecialchars($e->getMessage());
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h1>Вхід</h1>
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $err): ?>
                <p><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post" action="login.php" autocomplete="off">
        <label>
            Логін або email:<br>
            <input type="text" name="username" required autofocus>
        </label><br>
        <label>
            Пароль:<br>
            <input type="password" name="password" required>
        </label><br>
        <button type="submit">Увійти</button>
    </form>
    <p>
        <a href="reset_password.php">Забули пароль?</a>
    </p>
    <p>
        Ще не маєте акаунта? <a href="register.php">Реєстрація</a>
    </p>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
