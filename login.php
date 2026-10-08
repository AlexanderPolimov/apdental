<?php
// login.php - Страница входа
require_once 'includes/init.php';

$title = "Вход в личный кабинет - AP Dental";

$user_type = $_GET['type'] ?? 'patient';

// Если уже авторизован - редирект в соответствующий кабинет
if (isset($_SESSION['patient_id'])) {
    header('Location: profile.php');
    exit;
}
if (isset($_SESSION['dentist_id'])) {
    header('Location: dentist-profile.php');
    exit;
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $user_type = $_POST['user_type'] ?? 'patient';
    $remember = isset($_POST['remember']);

    $errors = [];

    if (empty($email)) {
        $errors['email'] = 'Введите email';
    }

    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    }

    if (empty($errors)) {
        if ($user_type === 'patient') {
            $patient = login_patient($connection, $email, $password);

            if ($patient) {
                // Успешный вход
                $_SESSION['patient_id'] = $patient['patient_id'];
                $_SESSION['patient_name'] = $patient['patient_full_name'];
                $_SESSION['patient_email'] = $patient['patient_email'];

                // Запомнить меня (30 дней)
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + 2592000, '/');
                    // Здесь можно сохранить токен в БД
                }

                $_SESSION['login_success'] = 'Добро пожаловать, ' . $patient['patient_full_name'] . '!';
                header('Location: profile.php');
                exit;
            } else {
                $errors['login'] = 'Неверный email или пароль';
            }
        } else {
            // Вход для врача
            $query = "SELECT * FROM dentists WHERE dentist_email = ?";
            $stmt = $connection->prepare($query);
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $dentist = $result->fetch_assoc();
            $stmt->close();

            if ($dentist && password_verify($password, $dentist['dentist_password'])) {
                $_SESSION['dentist_id'] = $dentist['dentist_id'];
                $_SESSION['dentist_name'] = $dentist['dentist_full_name'];
                $_SESSION['dentist_email'] = $dentist['dentist_email'];

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('dentist_remember', $token, time() + 2592000, '/');
                }

                $_SESSION['login_success'] = 'Добро пожаловать, д-р ' . $dentist['dentist_full_name'] . '!';
                header('Location: dentist-profile.php');
                exit;
            } else {
                $errors['login'] = 'Неверный email или пароль';
            }
        }
    }
}

include_once 'templates/header.php';
?>

<div class="login-page">
    <div class="container">
        <div class="login-wrapper">
            <div class="login-card">
                <div class="login-header">
                    <h1 class="login-title">Вход в личный кабинет</h1>
                </div>

                <?php if (!empty($errors['login'])): ?>
                    <div class="alert-error"><?= $errors['login'] ?></div>
                <?php endif; ?>

                <form method="POST" action="" class="login-form">
                    <div class="form-group">
                        <label class="form-label">Тип пользователя</label>
                        <div class="user-type-selector">
                            <select name="user_type" class="form-control">
                                <option value="patient" <?= $user_type == 'patient' ? 'selected' : '' ?>>Пациент</option>
                                <option value="dentist" <?= $user_type == 'dentist' ? 'selected' : '' ?>>Врач</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus
                            class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                            placeholder="Введите ваш email">
                        <?php if (isset($errors['email'])): ?>
                            <span class="invalid-feedback"><?= $errors['email'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" id="password" name="password" required
                            class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                            placeholder="Введите пароль">
                        <?php if (isset($errors['password'])): ?>
                            <span class="invalid-feedback"><?= $errors['password'] ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Войти
                    </button>
                </form>

                <div class="login-footer">
                    <p>Нет аккаунта?
                        <a href="register.php" class="register-link">Зарегистрироваться</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>