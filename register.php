<?php
// register.php - Страница регистрации
require_once 'includes/init.php';

$title = "Регистрация - AP Dental";

// Если уже авторизован - редирект на главную
if (is_auth()) {
    header('Location: index.php');
    exit;
}

// Обработка формы регистрации
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Получаем данные из формы
    $full_name = trim($_POST['full_name'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirmation = $_POST['password_confirmation'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $insurance_number = trim($_POST['insurance_number'] ?? '');
    $gender = $_POST['gender'] ?? 'male';

    // Валидация
    if (empty($full_name)) {
        $errors['full_name'] = 'Введите полное имя';
    }

    if (empty($phone_number)) {
        $errors['phone_number'] = 'Введите номер телефона';
    } else {
        // Очищаем номер от всего кроме цифр
        $clean_phone = preg_replace('/[^0-9]/', '', $phone_number);
        if (strlen($clean_phone) < 10) {
            $errors['phone_number'] = 'Введите корректный номер телефона';
        } else {
            if (strlen($clean_phone) === 10) {
                $phone_number = '7' . $clean_phone;
            } elseif (strlen($clean_phone) === 11 && $clean_phone[0] === '8') {
                $phone_number = '7' . substr($clean_phone, 1);
            } else {
                $phone_number = $clean_phone;
            }
        }
    }

    if (empty($email)) {
        $errors['email'] = 'Введите email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    }

    if (empty($password)) {
        $errors['password'] = 'Введите пароль';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Пароль должен быть не менее 6 символов';
    }

    if ($password !== $password_confirmation) {
        $errors['password_confirmation'] = 'Пароли не совпадают';
    }

    // Валидация даты рождения
    if (empty($birth_date)) {
        $errors['birth_date'] = 'Введите дату рождения';
    } else {
        $birth = DateTime::createFromFormat('Y-m-d', $birth_date);

        if (!$birth || $birth->format('Y-m-d') !== $birth_date) {
            $errors['birth_date'] = 'Введите корректную дату рождения';
        } else {
            $age = (new DateTime())->diff($birth)->y;
            if ($birth >= new DateTime()) {
                $errors['birth_date'] = 'Дата не может быть в будущем';
            } elseif ($age > 120) {
                $errors['birth_date'] = 'Некорректная дата рождения';
            } elseif ($age < 18) {
                $errors['birth_date'] = 'Регистрация с 18 лет';
            }
        }
    }

    if (empty($insurance_number)) {
        $errors['insurance_number'] = 'Введите номер страхового полиса';
    } elseif (!preg_match('/^[0-9]{16}$/', preg_replace('/[^0-9]/', '', $insurance_number))) {
        $errors['insurance_number'] = 'Введите корректный номер полиса (16 цифр)';
    }

    // Проверка уникальности email
    if (empty($errors['email'])) {
        $check_query = "SELECT patient_id FROM patients WHERE patient_email = ?";
        $stmt = $connection->prepare($check_query);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['email'] = 'Этот email уже зарегистрирован';
        }
        $stmt->close();
    }

    // Проверка уникальности телефона
    if (empty($errors['phone_number'])) {
        $check_query = "SELECT patient_id FROM patients WHERE patient_phone_number = ?";
        $stmt = $connection->prepare($check_query);
        $stmt->bind_param('s', $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $errors['phone_number'] = 'Этот номер телефона уже зарегистрирован';
        }
        $stmt->close();
    }

    // Если нет ошибок - регистрируем
    if (empty($errors)) {
        $patient_id = register_patient(
            $connection,
            $full_name,
            $phone_number,
            $email,
            $password,
            $gender,
            $birth_date,
            $insurance_number
        );

        if ($patient_id) {
            // Автоматический вход после регистрации
            session_regenerate_id(true);
            $_SESSION['patient_id'] = $patient_id;
            $_SESSION['patient_name'] = $full_name;
            $_SESSION['patient_email'] = $email;
            $_SESSION['register_success'] = 'Регистрация успешно завершена! Добро пожаловать в AP Dental!';

            // Редирект в личный кабинет
            header('Location: profile.php');
            exit;
        } else {
            $errors['register'] = 'Ошибка при регистрации. Пожалуйста, попробуйте позже.';
        }
    }
}

include_once 'templates/header.php';
?>

<div class="register-page">
    <div class="container">
        <div class="register-wrapper">
            <div class="register-card">
                <div class="register-header">
                    <h1 class="register-title">Регистрация</h1>
                </div>
                <?php if (!empty($errors)): ?>
                    <div class="alert-error">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" class="register-form">
                    <!-- ОБЩИЕ ПОЛЯ -->
                    <div class="form-group">
                        <label for="full_name" class="form-label">Полное имя</label>
                        <input type="text" id="full_name" name="full_name"
                            value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required
                            class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                            placeholder="Введите ваше полное имя">
                        <?php if (isset($errors['full_name'])): ?>
                            <span class="invalid-feedback"><?= $errors['full_name'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone_number" class="form-label">Номер телефона</label>
                        <input type="tel" id="phone_number" name="phone_number"
                            value="<?= htmlspecialchars($_POST['phone_number'] ?? '') ?>" required
                            class="form-control <?= isset($errors['phone_number']) ? 'is-invalid' : '' ?>"
                            placeholder="+7 (___) ___-__-__">
                        <?php if (isset($errors['phone_number'])): ?>
                            <span class="invalid-feedback"><?= $errors['phone_number'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" id="email" name="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
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

                    <div class="form-group">
                        <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" required
                            class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                            placeholder="Повторите пароль">
                        <?php if (isset($errors['password_confirmation'])): ?>
                            <span class="invalid-feedback"><?= $errors['password_confirmation'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="gender" class="form-label">Пол</label>
                        <select id="gender" name="gender" class="form-control">
                            <option value="male">Мужской</option>
                            <option value="female">Женский</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="birth_date" class="form-label">Дата рождения</label>
                        <input type="date" id="birth_date" name="birth_date"
                            value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>" required
                            class="form-control <?= isset($errors['birth_date']) ? 'is-invalid' : '' ?>">
                        <?php if (isset($errors['birth_date'])): ?>
                            <span class="invalid-feedback"><?= $errors['birth_date'] ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="insurance_number" class="form-label">Номер страхового полиса</label>
                        <input type="text" id="insurance_number" name="insurance_number"
                            value="<?= htmlspecialchars($_POST['insurance_number'] ?? '') ?>" required
                            class="form-control <?= isset($errors['insurance_number']) ? 'is-invalid' : '' ?>"
                            placeholder="Введите номер страхового полиса">
                        <?php if (isset($errors['insurance_number'])): ?>
                            <span class="invalid-feedback"><?= $errors['insurance_number'] ?></span>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        Зарегистрироваться
                    </button>
                </form>

                <div class="register-footer">
                    <p>
                        Уже есть аккаунт?
                        <a href="login.php" class="login-link">Войти</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./js/script.js"></script>

<?php include_once 'templates/footer.php'; ?>