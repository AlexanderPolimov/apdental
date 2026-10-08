<?php
session_start();

ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

$is_auth = isset($_SESSION['patient_id']);
$user_name = $is_auth ? ($_SESSION['patient_name'] ?? '') : '';
$title = "AP Dental - Стоматология";

define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASSWORD", "");
define("DB_NAME", "apdental");
define("DB_CHARSET", "utf8mb4");

$connection = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

if (!$connection) {
    error_log('DB connect error: ' . mysqli_connect_error());
    http_response_code(503);
    die('Сервис временно недоступен. Попробуйте позже.');
}

mysqli_set_charset($connection, DB_CHARSET);

// генерация токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once 'functions.php';

// Автоматическая отмена просроченных записей
if ($is_auth) {
    cancel_expired_appointments($connection, (int)$_SESSION['patient_id']); 
}

// Автоматическое завершение прошедших записей
complete_passed_appointments($connection);