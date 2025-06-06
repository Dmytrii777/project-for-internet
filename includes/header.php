<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PhotoMix</title>
    <link rel="stylesheet" href="public/css/style.css">
    <!-- Можна додати favicon, шрифти тощо -->
</head>
<body>
<header class="site-header">
    <div class="container">
        <a href="index.php" class="logo">PhotoMix</a>
        <nav class="main-nav">
            <a href="gallery.php">Галерея</a>
            <?php if (is_logged_in()): ?>
                <a href="edit.php">Редагування</a>
                <a href="profile.php"><?= htmlspecialchars($_SESSION['username']) ?></a>
                <a href="logout.php">Вийти</a>
            <?php else: ?>
                <a href="login.php">Увійти</a>
                <a href="register.php">Реєстрація</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
