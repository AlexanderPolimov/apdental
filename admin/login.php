<?php
require_once '../includes/init.php';

// Если уже авторизован - в админку
if (isset($_SESSION['administrator_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Неверный запрос. Попробуйте снова.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email && $password) {
            $query = "SELECT * FROM administrators WHERE administrator_email = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $admin = $result->fetch_assoc();

            if ($admin && password_verify($password, $admin['administrator_password'])) {
                session_regenerate_id(true);
                $_SESSION['administrator_id'] = $admin['administrator_id'];
                $_SESSION['administrator_full_name'] = $admin['administrator_full_name'];
                $_SESSION['administrator_email'] = $admin['administrator_email'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Неверный email или пароль';
            }
        } else {
            $error = 'Заполните все поля';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в админ-панель | AP Dental</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="login-body">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <img src="../img/logo.jpg" alt="AP Dental">
                <h1>Вход в админ-панель</h1>
                <p>AP Dental | Управление клиникой</p>
            </div>

            <?php if ($error): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Пароль</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    Войти
                </button>
            </form>

            <div class="login-footer">
                <a href="../index.php" class="login-link">
                    <i class="fas fa-arrow-left"></i> Вернуться на сайт
                </a>
            </div>
        </div>
    </div>
</body>

</html>