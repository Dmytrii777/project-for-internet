<?php

// Перевірка, чи користувач залогінений
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Відправка email (можна розширити для HTML)
function send_mail($to, $subject, $message) {
    $headers = "From: PhotoMix <no-reply@" . $_SERVER['SERVER_NAME'] . ">\r\n";
    // Для HTML-листів можна додати: $headers .= "Content-type: text/html; charset=utf-8\r\n";
    return mail($to, $subject, $message, $headers);
}

// Генерація CSRF-токена (опціонально)
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Перевірка CSRF-токена (опціонально)
function check_csrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Безпечний вивід тексту (для шаблонів)
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// Очищення рядка від зайвих пробілів
function clean($string) {
    return trim($string);
}

// Генерація випадкового токена (для підтвердження, скидання пароля)
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}
