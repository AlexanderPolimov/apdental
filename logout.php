<?php
// logout.php - Выход из аккаунта
session_start();

// Очищаем сессию
$_SESSION = array();

// Удаляем куки сессии
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Удаляем remember token
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Уничтожаем сессию
session_destroy();

// Редирект на главную
header('Location: index.php');
exit;
?>