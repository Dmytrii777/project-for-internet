<?php
session_start();
require_once __DIR__ . '/includes/functions.php'; // загальні функції (наприклад, is_logged_in)
require_once __DIR__ . '/includes/header.php';    // верхній колонтитул
?>

<main class="container">
    <h1>Ласкаво просимо до PhotoMix!</h1>
    <p>Цей сайт дозволяє редагувати фотографії з веб-камери або файлу, додавати рамки та ділитися результатом з іншими!</p>

    <?php if (is_logged_in()): ?>
        <nav>
            <a href="gallery.php">Галерея</a> |
            <a href="edit.php">Редагувати фото</a> |
            <a href="profile.php">Мій профіль</a> |
            <a href="logout.php">Вийти</a>
        </nav>
        <section>
            <h2>Останні зображення</h2>
            <?php include __DIR__ . '/includes/latest_images.php'; ?>
        </section>
    <?php else: ?>
        <nav>
            <a href="login.php">Увійти</a> |
            <a href="register.php">Зареєструватися</a> |
            <a href="gallery.php">Галерея</a>
        </nav>
        <section>
            <h2>Ви ще не увійшли</h2>
            <p>Будь ласка, <a href="register.php">зареєструйтеся</a> або <a href="login.php">увійдіть</a>, щоб отримати доступ до всіх функцій.</p>
        </section>
    <?php endif; ?>
</main>

<?php
require_once __DIR__ . '/includes/footer.php'; // нижній колонтитул
?>
