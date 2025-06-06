<?php

// Перевірка, чи користувач залогінений, і редірект, якщо ні
function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

// Перевірка, чи користувач — власник ресурсу (наприклад, зображення)
function is_owner($resource_user_id) {
    return is_logged_in() && $_SESSION['user_id'] == $resource_user_id;
}

// Редірект на головну, якщо користувач вже залогінений (наприклад, для сторінок login/register)
function redirect_if_logged_in() {
    if (is_logged_in()) {
        header('Location: index.php');
        exit;
    }
}

// Можна додати функцію для перевірки ролі (якщо потрібно)
// function is_admin() { ... }

