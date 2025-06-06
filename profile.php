<?php
session_start();
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/database.php';

if (!is_logged_in()) {
    header('Location: login.php');
    exit;
}

$errors = [];
$success = false;
$user_id = $_SESSION['user_id'];

// Завантаження поточних даних користувача
try {
    $pdo = new PDO($DB_DSN, $DB_USER, $DB_PASSWORD, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $stmt = $pdo->prepare("SELECT username, email, notify FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    $errors[] = "Помилка БД: " . htmlspecialchars($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Оновлення профілю
    if (isset($_POST['update_profile'])) {
        $new_username = trim($_POST['username'] ?? '');
        $new_email = trim($_POST['email'] ?? '');
        $notify = isset($_POST['notify']) ? 1 : 0;

        if ($new_username === '' || $new_email === '') {
            $errors[] = "Всі поля обов'язкові.";
        }
        if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $new_username)) {
            $errors[] = "Ім'я користувача має містити 3-30 латинських літер, цифр або _.";
        }
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Некоректний email.";
        }

        // Чи змінились логін або email?
        if (!$errors && ($new_username !== $user['username'] || $new_email !== $user['email'])) {
            // Чи не зайняті нові логін/email?
            $stmt = $pdo->prepare("SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id");
            $stmt->execute([':username' => $new_username, ':email' => $new_email, ':id' => $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "Такий логін або email вже зайнятий.";
            }
        }

        if (!$errors) {
            // Якщо email змінився — скидаємо підтвердження і генеруємо новий токен
            if ($new_email !== $user['email']) {
                $token = bin2hex(random_bytes(32));
                $stmt = $pdo->prepare("UPDATE users SET username = :username, email = :email, confirmed = 0, token = :token, notify = :notify WHERE id = :id");
                $stmt->execute([
                    ':username' => $new_username,
                    ':email' => $new_email,
                    ':token' => $token,
                    ':notify' => $notify,
                    ':id' => $user_id
                ]);
                // Надіслати новий лист для підтвердження
                $confirm_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/confirm.php?token=" . urlencode($token);
                $subject = "Підтвердження нового email на PhotoMix";
                $message = "Вітаємо, $new_username!\n\nЩоб підтвердити новий email, перейдіть за посиланням:\n$confirm_link";
                send_mail($new_email, $subject, $message);
                $success = "Профіль оновлено. Перевірте новий email для підтвердження.";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET username = :username, notify = :notify WHERE id = :id");
                $stmt->execute([
                    ':username' => $new_username,
                    ':notify' => $notify,
                    ':id' => $user_id
                ]);
                $success = "Профіль оновлено.";
            }
            $_SESSION['username'] = $new_username;
            // Оновлюємо дані для форми
            $user['username'] = $new_username;
            $user['email'] = $new_email;
            $user['notify'] = $notify;
        }
    }

    // Оновлення пароля
    if (isset($_POST['update_password'])) {
        $old_password = $_POST['old_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $new_password2 = $_POST['new_password2'] ?? '';

        if ($old_password === '' || $new_password === '' || $new_password2 === '') {
            $errors[] = "Всі поля обов'язкові для зміни пароля.";
        } elseif ($new_password !== $new_password2) {
            $errors[] = "Нові паролі не співпадають.";
        } elseif (strlen($new_password) < 6 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[a-zA-Z]/', $new_password)) {
            $errors[] = "Новий пароль має бути не менше 6 символів і містити літери та цифри.";
        } else {
            // Перевіряємо старий пароль
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row || !password_verify($old_password, $row['password'])) {
                $errors[] = "Старий пароль невірний.";
            } else {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = :pass WHERE id = :id");
                $stmt->execute([':pass' => $hash, ':id' => $user_id]);
                $success = "Пароль успішно змінено.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<main class="container">
    <h1>Мій профіль</h1>
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $err): ?>
                <p><?= htmlspecialchars($err) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success">
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <h2>Зміна профілю</h2>
    <form method="post" action="profile.php" autocomplete="off">
        <input type="hidden" name="update_profile" value="1">
        <label>
            Логін:<br>
            <input type="text" name="username" required pattern="[a-zA-Z0-9_]{3,30}" value="<?= htmlspecialchars($user['username']) ?>">
        </label><br>
        <label>
            Email:<br>
            <input type="email" name="email" required value="<?= htmlspecialchars($user['email']) ?>">
        </label><br>
        <label>
            <input type="checkbox" name="notify" value="1" <?= $user['notify'] ? 'checked' : '' ?>>
            Отримувати email-сповіщення про нові коментарі до моїх зображень
        </label><br>
        <button type="submit">Оновити профіль</button>
    </form>

    <h2>Зміна пароля</h2>
    <form method="post" action="profile.php" autocomplete="off">
        <input type="hidden" name="update_password" value="1">
        <label>
            Старий пароль:<br>
            <input type="password" name="old_password" required>
        </label><br>
        <label>
            Новий пароль:<br>
            <input type="password" name="new_password" required>
        </label><br>
        <label>
            Повторіть новий пароль:<br>
            <input type="password" name="new_password2" required>
        </label><br>
        <button type="submit">Змінити пароль</button>
    </form>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
