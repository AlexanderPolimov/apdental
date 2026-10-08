<?php
require_once '../includes/init.php';

// Проверка авторизации администратора
if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Новый пациент | AP Dental";
$error = '';
$success = '';

// Получаем ID пациента для редактирования
$patient_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $patient_id > 0;
$patient_data = [];

// Если редактирование - загружаем данные пациента
if ($is_edit) {
    $sql = "SELECT * FROM patients WHERE patient_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $patient_data = mysqli_fetch_assoc($result);

    if (!$patient_data) {
        $_SESSION['error'] = "Пациент не найден";
        header('Location: patients.php');
        exit;
    }

    $title = "Редактирование пациента | AP Dental";
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Неверный запрос. Обновите страницу и попробуйте снова.';
    } else {
        $patient_full_name = trim($_POST['patient_full_name'] ?? '');
        $patient_phone_number = trim($_POST['patient_phone_number'] ?? '');
        $patient_email = trim($_POST['patient_email'] ?? '');
        $patient_birth_date = !empty($_POST['patient_birth_date']) ? $_POST['patient_birth_date'] : null;
        $patient_gender = $_POST['patient_gender'] ?? '';
        $patient_insurance_number = trim($_POST['patient_insurance_number'] ?? '');
        $patient_password = $_POST['patient_password'] ?? '';

        $errors = [];

        // Валидация ФИО
        if (empty($patient_full_name)) {
            $errors[] = "Введите ФИО пациента";
        } elseif (strlen($patient_full_name) < 3) {
            $errors[] = "ФИО должно содержать минимум 3 символа";
        }

        // Валидация телефона
        if (empty($patient_phone_number)) {
            $errors[] = "Введите номер телефона";
        } else {
            $clean_phone = preg_replace('/[^0-9]/', '', $patient_phone_number);
            if (strlen($clean_phone) < 10) {
                $errors[] = "Введите корректный номер телефона";
            } else {
                if (strlen($clean_phone) === 10) {
                    $patient_phone_number = '7' . $clean_phone;
                } elseif (strlen($clean_phone) === 11 && $clean_phone[0] === '8') {
                    $patient_phone_number = '7' . substr($clean_phone, 1);
                } else {
                    $patient_phone_number = $clean_phone;
                }
            }
        }

        // Валидация email
        if (!empty($patient_email) && !filter_var($patient_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Введите корректный email адрес";
        }

        // Валидация даты рождения
        if (!empty($patient_birth_date)) {
            if ($patient_birth_date > date('Y-m-d')) {
                $errors[] = "Дата рождения не может быть в будущем";
            }

            // Проверка возраста
            $birth_timestamp = strtotime($patient_birth_date);
            $age = (time() - $birth_timestamp) / (365 * 24 * 60 * 60);
            if ($age < 18) {
                $errors[] = "Пациент должен быть старше 18 лет";
            }
        }

        // Валидация номера полиса
        if (!$is_edit && empty($patient_insurance_number)) {
            $errors[] = "Введите номер полиса";
        } elseif (!empty($patient_insurance_number)) {
            $clean_insurance = preg_replace('/[^0-9]/', '', $patient_insurance_number);
            if (strlen($clean_insurance) < 16) {
                $errors[] = "Номер полиса должен содержать 16 цифр";
            }
        }

        // Проверка уникальности номера полиса
        if (!empty($patient_insurance_number) && empty($errors)) {
            $clean_insurance = preg_replace('/[^0-9]/', '', $patient_insurance_number);

            if (!$is_edit) {
                $check_sql = "SELECT patient_id FROM patients WHERE REPLACE(REPLACE(REPLACE(patient_insurance_number, ' ', ''), '-', ''), '_', '') = ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $clean_insurance);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Пациент с таким номером полиса уже существует";
                }
            } else {
                $check_sql = "SELECT patient_id FROM patients WHERE REPLACE(REPLACE(REPLACE(patient_insurance_number, ' ', ''), '-', ''), '_', '') = ? AND patient_id != ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $clean_insurance, $patient_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Пациент с таким номером полиса уже существует";
                }
            }
        }

        // Проверка уникальности email
        if (!empty($patient_email) && empty($errors)) {
            if (!$is_edit) {
                $check_sql = "SELECT patient_id FROM patients WHERE patient_email = ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $patient_email);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Пациент с таким email уже существует";
                }
            } else {
                $check_sql = "SELECT patient_id FROM patients WHERE patient_email = ? AND patient_id != ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $patient_email, $patient_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Пациент с таким email уже существует";
                }
            }
        }

        // Проверка уникальности телефона
        if (!empty($patient_phone_number) && empty($errors)) {
            $clean_phone = preg_replace('/[^0-9]/', '', $patient_phone_number);

            if (!$is_edit) {
                $check_sql = "SELECT patient_id FROM patients WHERE REPLACE(REPLACE(REPLACE(patient_phone_number, ' ', ''), '-', ''), '(', '') = ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $clean_phone);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Пациент с таким номером телефона уже существует";
                }
            } else {
                $check_sql = "SELECT patient_id FROM patients WHERE REPLACE(REPLACE(REPLACE(patient_phone_number, ' ', ''), '-', ''), '(', '') = ? AND patient_id != ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $clean_phone, $patient_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Пациент с таким номером телефона уже существует";
                }
            }
        }

        // Валидация пароля
        if (!$is_edit) {
            if (empty($patient_password)) {
                $errors[] = "Введите пароль";
            } elseif (strlen($patient_password) < 6) {
                $errors[] = "Пароль должен быть не менее 6 символов";
            }
        } elseif (!empty($patient_password) && strlen($patient_password) < 6) {
            $errors[] = "Пароль должен быть не менее 6 символов";
        }

        // Если нет ошибок - выполняем операцию
        if (empty($errors)) {
            if (!$is_edit) {
                // Добавление нового пациента
                $hashed_password = password_hash($patient_password, PASSWORD_DEFAULT);
                $clean_insurance = preg_replace('/[^0-9]/', '', $patient_insurance_number);

                $sql = "INSERT INTO patients 
                    (patient_full_name, patient_phone_number, patient_email, patient_birth_date, 
                     patient_gender, patient_insurance_number, patient_password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssss",
                    $patient_full_name,
                    $patient_phone_number,
                    $patient_email,
                    $patient_birth_date,
                    $patient_gender,
                    $clean_insurance,
                    $hashed_password
                );

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = "Пациент успешно добавлен";
                    header('Location: patients.php');
                    exit;
                } else {
                    $error = "Ошибка при добавлении пациента: " . mysqli_error($connection);
                }
            } else {
                // Обновление данных пациента
                $clean_insurance = !empty($patient_insurance_number) ? preg_replace('/[^0-9]/', '', $patient_insurance_number) : null;

                $sql = "UPDATE patients SET 
                    patient_full_name = ?,
                    patient_phone_number = ?,
                    patient_email = ?,
                    patient_birth_date = ?,
                    patient_gender = ?,
                    patient_insurance_number = ?";

                $params = [
                    $patient_full_name,
                    $patient_phone_number,
                    $patient_email,
                    $patient_birth_date,
                    $patient_gender,
                    $clean_insurance
                ];
                $types = "ssssss";

                // Если указан новый пароль
                if (!empty($patient_password)) {
                    $hashed_password = password_hash($patient_password, PASSWORD_DEFAULT);
                    $sql .= ", patient_password = ?";
                    $params[] = $hashed_password;
                    $types .= "s";
                }

                $sql .= " WHERE patient_id = ?";
                $params[] = $patient_id;
                $types .= "i";

                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$params);

                if (mysqli_stmt_execute($stmt)) {
                    $_SESSION['success'] = "Данные пациента успешно обновлены";
                    header('Location: patients.php');
                    exit;
                } else {
                    $error = "Ошибка при обновлении данных пациента: " . mysqli_error($connection);
                }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

include_once 'admin-header.php';
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div class="admin-page-title">
            <h1><?= $is_edit ? '✏️ Редактирование пациента' : '👤 Новый пациент' ?></h1>
            <a href="patients.php" class="admin-btn admin-btn-outline">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="admin-form-container">
        <form method="POST" action="" id="patientForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="form-group">
                <label>ФИО пациента <span class="required">*</span></label>
                <input type="text" name="patient_full_name" class="form-control" required
                    value="<?= htmlspecialchars($_POST['patient_full_name'] ?? $patient_data['patient_full_name'] ?? '') ?>"
                    placeholder="Иванов Иван Иванович" minlength="3">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Телефон <span class="required">*</span></label>
                    <input type="tel" name="patient_phone_number" class="form-control" required
                        value="<?= htmlspecialchars($_POST['patient_phone_number'] ?? $patient_data['patient_phone_number'] ?? '') ?>"
                        placeholder="+7 (999) 123-45-67" id="phoneInput">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="patient_email" class="form-control"
                        value="<?= htmlspecialchars($_POST['patient_email'] ?? $patient_data['patient_email'] ?? '') ?>"
                        placeholder="ivanov@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Дата рождения</label>
                    <input type="date" name="patient_birth_date" class="form-control"
                        value="<?= htmlspecialchars($_POST['patient_birth_date'] ?? $patient_data['patient_birth_date'] ?? '') ?>"
                        max="<?= date('Y-m-d') ?>" min="1900-01-01">
                </div>

                <div class="form-group">
                    <label>Пол</label>
                    <select name="patient_gender" class="form-control">
                        <option value="">-- Не указан --</option>
                        <option value="male" <?= (($_POST['patient_gender'] ?? $patient_data['patient_gender'] ?? '') == 'male') ? 'selected' : '' ?>>Мужской</option>
                        <option value="female" <?= (($_POST['patient_gender'] ?? $patient_data['patient_gender'] ?? '') == 'female') ? 'selected' : '' ?>>Женский</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Номер страхового полиса
                        <?= !$is_edit ? '<span class="required">*</span>' : '' ?>
                    </label>
                    <input type="text" name="patient_insurance_number" class="form-control" <?= !$is_edit ? 'required' : '' ?>
                        value="<?= htmlspecialchars($_POST['patient_insurance_number'] ?? $patient_data['patient_insurance_number'] ?? '') ?>"
                        maxlength="16">
                </div>
                
                <div class="form-group">
                    <label>Пароль
                        <?= !$is_edit ? '<span class="required">*</span>' : '' ?>
                    </label>
                    <input type="password" name="patient_password" class="form-control" <?= !$is_edit ? 'required' : '' ?>
                        placeholder="<?= $is_edit ? 'Оставьте пустым, чтобы не менять' : 'Минимум 6 символов' ?>" minlength="6">
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> <?= $is_edit ? 'Сохранить изменения' : 'Добавить пациента' ?>
                </button>
                <a href="patients.php" class="admin-btn admin-btn-outline">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</main>

<script src="../js/script.js"></script>