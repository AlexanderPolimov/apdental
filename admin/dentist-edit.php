<?php
require_once '../includes/init.php';

// Проверка авторизации администратора
if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Новый врач | AP Dental";
$error = '';
$success = '';

// Получаем ID врача для редактирования (если передан)
$dentist_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_edit = $dentist_id > 0;
$dentist_data = [];
$selected_categories = [];

// Если редактирование - загружаем данные врача
if ($is_edit) {
    $sql = "SELECT * FROM dentists WHERE dentist_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $dentist_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $dentist_data = mysqli_fetch_assoc($result);

    if (!$dentist_data) {
        $_SESSION['error'] = "Врач не найден";
        header('Location: dentists.php');
        exit;
    }

    $title = "Редактирование врача | AP Dental";

    // Загружаем категории услуг врача
    $selected_categories = [];
    $cat_sql = "SELECT service_category_id FROM dentists_service_categories WHERE dentist_id = ?";
    $cat_stmt = mysqli_prepare($connection, $cat_sql);
    mysqli_stmt_bind_param($cat_stmt, "i", $dentist_id);
    mysqli_stmt_execute($cat_stmt);
    $cat_result = mysqli_stmt_get_result($cat_stmt);
    while ($row = mysqli_fetch_assoc($cat_result)) {
        $selected_categories[] = $row['service_category_id'];
    }
    mysqli_stmt_close($cat_stmt);
}

// Получаем список специализаций для выпадающего списка
$specializations = [];
$spec_sql = "SELECT specialization_id, specialization_name FROM specializations ORDER BY specialization_name";
$spec_result = mysqli_query($connection, $spec_sql);
while ($row = mysqli_fetch_assoc($spec_result)) {
    $specializations[] = $row;
}

// Получаем список категорий услуг для множественного выбора
$service_categories = [];
$cat_sql = "SELECT service_category_id, service_category_name FROM service_categories ORDER BY service_category_name";
$cat_result = mysqli_query($connection, $cat_sql);
while ($row = mysqli_fetch_assoc($cat_result)) {
    $service_categories[] = $row;
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Неверный запрос. Обновите страницу и попробуйте снова.';
    } else {
        $dentist_full_name = trim($_POST['dentist_full_name'] ?? '');
        $dentist_phone_number = trim($_POST['dentist_phone_number'] ?? '');
        $dentist_email = trim($_POST['dentist_email'] ?? '');
        $dentist_experience_years = (int) ($_POST['dentist_experience_years'] ?? 0);
        $dentist_description = trim($_POST['dentist_description'] ?? '');
        $specialization_id = !empty($_POST['specialization_id']) ? (int) $_POST['specialization_id'] : null;
        $dentist_password = $_POST['dentist_password'] ?? '';
        $service_category_ids = $_POST['service_category_ids'] ?? [];

        // Обработка загрузки фото
        $dentist_photo = $dentist_data['dentist_photo'] ?? '';
        $upload_error = '';

        $errors = [];

        // Валидация ФИО
        if (empty($dentist_full_name)) {
            $errors[] = "Введите ФИО врача";
        } elseif (strlen($dentist_full_name) < 3) {
            $errors[] = "ФИО должно содержать минимум 3 символа";
        }

        // Валидация телефона
        if (empty($dentist_phone_number)) {
            $errors[] = "Введите номер телефона";
        } else {
            $clean_phone = preg_replace('/[^0-9]/', '', $dentist_phone_number);
            if (strlen($clean_phone) < 10) {
                $errors[] = "Введите корректный номер телефона";
            } else {
                if (strlen($clean_phone) === 10) {
                    $dentist_phone_number = '7' . $clean_phone;
                } elseif (strlen($clean_phone) === 11 && $clean_phone[0] === '8') {
                    $dentist_phone_number = '7' . substr($clean_phone, 1);
                } else {
                    $dentist_phone_number = $clean_phone;
                }
            }
        }

        // Валидация email
        if (!empty($dentist_email) && !filter_var($dentist_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Введите корректный email адрес";
        }

        // Валидация стажа
        if ($dentist_experience_years < 0 || $dentist_experience_years > 60) {
            $errors[] = "Стаж должен быть от 0 до 60 лет";
        }

        // Проверка выбора специализации
        if (empty($specialization_id)) {
            $errors[] = "Выберите специализацию";
        }

        // Проверка выбора категорий услуг
        if (empty($service_category_ids)) {
            $errors[] = "Выберите хотя бы одну категорию услуг";
        }

        // Проверка уникальности email (если указан)
        if (!empty($dentist_email) && empty($errors)) {
            if (!$is_edit) {
                $check_sql = "SELECT dentist_id FROM dentists WHERE dentist_email = ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $dentist_email);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Врач с таким email уже существует";
                }
            } else {
                $check_sql = "SELECT dentist_id FROM dentists WHERE dentist_email = ? AND dentist_id != ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $dentist_email, $dentist_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Врач с таким email уже существует";
                }
            }
        }

        // Проверка уникальности телефона
        if (!empty($dentist_phone_number) && empty($errors)) {
            $clean_phone = preg_replace('/[^0-9]/', '', $dentist_phone_number);

            if (!$is_edit) {
                $check_sql = "SELECT dentist_id FROM dentists WHERE REPLACE(REPLACE(REPLACE(dentist_phone_number, ' ', ''), '-', ''), '(', '') = ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $clean_phone);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Врач с таким номером телефона уже существует";
                }
            } else {
                $check_sql = "SELECT dentist_id FROM dentists WHERE REPLACE(REPLACE(REPLACE(dentist_phone_number, ' ', ''), '-', ''), '(', '') = ? AND dentist_id != ?";
                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "si", $clean_phone, $dentist_id);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);
                if (mysqli_num_rows($check_result) > 0) {
                    $errors[] = "Врач с таким номером телефона уже существует";
                }
            }
        }

        // Валидация пароля (только для нового врача или если пароль указан при редактировании)
        if (!$is_edit) {
            if (empty($dentist_password)) {
                $errors[] = "Введите пароль";
            } elseif (strlen($dentist_password) < 6) {
                $errors[] = "Пароль должен быть не менее 6 символов";
            }
        } elseif (!empty($dentist_password) && strlen($dentist_password) < 6) {
            $errors[] = "Пароль должен быть не менее 6 символов";
        }

        // Обработка загрузки фото
        if (isset($_FILES['dentist_photo']) && $_FILES['dentist_photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
            $file_type = mime_content_type($_FILES['dentist_photo']['tmp_name']);

            if (!in_array($file_type, $allowed_types)) {
                $errors[] = "Разрешены только изображения (JPEG, PNG)";
            } else {
                $max_size = 2 * 1024 * 1024; // 2MB
                if ($_FILES['dentist_photo']['size'] > $max_size) {
                    $errors[] = "Размер файла не должен превышать 2MB";
                } else {
                    // Генерируем уникальное имя файла
                    $mime_to_ext = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
                    $file_type = mime_content_type($_FILES['dentist_photo']['tmp_name']);
                    $ext = $mime_to_ext[$file_type] ?? 'jpg';
                    $new_filename = 'doctor_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $upload_path = '../img/' . $new_filename;

                    if (move_uploaded_file($_FILES['dentist_photo']['tmp_name'], $upload_path)) {
                        // Удаляем старое фото, если оно есть и отличается от стандартного
                        if ($is_edit && !empty($dentist_data['dentist_photo']) && $dentist_data['dentist_photo'] !== 'default-doctor.jpg') {
                            $old_photo_path = '../img/' . $dentist_data['dentist_photo'];
                            if (file_exists($old_photo_path)) {
                                unlink($old_photo_path);
                            }
                        }
                        $dentist_photo = $new_filename;
                    } else {
                        $errors[] = "Ошибка при загрузке фото";
                    }
                }
            }
        } elseif ($is_edit && empty($_FILES['dentist_photo']['name'])) {
            // Если фото не загружено при редактировании - оставляем старое
            $dentist_photo = $dentist_data['dentist_photo'] ?? 'default-doctor.jpg';
        } elseif (!$is_edit && empty($dentist_photo)) {
            // Для нового врача, если фото не загружено - ставим дефолтное
            $dentist_photo = 'default-doctor.jpg';
        }

        // Если нет ошибок - выполняем операцию
        if (empty($errors)) {
            if (!$is_edit) {
                // Добавление нового врача
                $hashed_password = password_hash($dentist_password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO dentists 
                    (dentist_full_name, dentist_phone_number, dentist_email, dentist_experience_years, 
                     dentist_description, dentist_photo, specialization_id, dentist_password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    "sssissis",
                    $dentist_full_name,
                    $dentist_phone_number,
                    $dentist_email,
                    $dentist_experience_years,
                    $dentist_description,
                    $dentist_photo,
                    $specialization_id,
                    $hashed_password
                );

                if (mysqli_stmt_execute($stmt)) {
                    $new_dentist_id = mysqli_insert_id($connection);

                    // Добавляем связи с категориями услуг
                    if (!empty($service_category_ids)) {
                        $insert_link_sql = "INSERT INTO dentists_service_categories (dentist_id, service_category_id) VALUES (?, ?)";
                        $insert_link_stmt = mysqli_prepare($connection, $insert_link_sql);

                        foreach ($service_category_ids as $category_id) {
                            mysqli_stmt_bind_param($insert_link_stmt, "ii", $new_dentist_id, $category_id);
                            mysqli_stmt_execute($insert_link_stmt);
                        }
                        mysqli_stmt_close($insert_link_stmt);
                    }

                    $_SESSION['success'] = "Врач успешно добавлен";
                    header('Location: dentists.php');
                    exit;
                } else {
                    $error = "Ошибка при добавлении врача: " . mysqli_error($connection);
                }
            } else {
                // Обновление данных врача
                $sql = "UPDATE dentists SET 
                    dentist_full_name = ?,
                    dentist_phone_number = ?,
                    dentist_email = ?,
                    dentist_experience_years = ?,
                    dentist_description = ?,
                    dentist_photo = ?,
                    specialization_id = ?";

                $params = [
                    $dentist_full_name,
                    $dentist_phone_number,
                    $dentist_email,
                    $dentist_experience_years,
                    $dentist_description,
                    $dentist_photo,
                    $specialization_id
                ];
                $types = "sssissi";

                // Если указан новый пароль
                if (!empty($dentist_password)) {
                    $hashed_password = password_hash($dentist_password, PASSWORD_DEFAULT);
                    $sql .= ", dentist_password = ?";
                    $params[] = $hashed_password;
                    $types = "sssissis";
                }
                $sql .= " WHERE dentist_id = ?";
                $params[] = $dentist_id;
                $types .= "i";

                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, $types, ...$params);

                if (mysqli_stmt_execute($stmt)) {
                    // Удаляем старую связь
                    $delete_sql = "DELETE FROM dentists_service_categories WHERE dentist_id = ?";
                    $delete_stmt = mysqli_prepare($connection, $delete_sql);
                    mysqli_stmt_bind_param($delete_stmt, "i", $dentist_id);
                    mysqli_stmt_execute($delete_stmt);
                    mysqli_stmt_close($delete_stmt);

                    if (!empty($service_category_ids)) {
                        $insert_link_sql = "INSERT INTO dentists_service_categories (dentist_id, service_category_id) VALUES (?, ?)";
                        $insert_link_stmt = mysqli_prepare($connection, $insert_link_sql);

                        foreach ($service_category_ids as $category_id) {
                            mysqli_stmt_bind_param($insert_link_stmt, "ii", $dentist_id, $category_id);
                            mysqli_stmt_execute($insert_link_stmt);
                        }
                        mysqli_stmt_close($insert_link_stmt);
                    }

                    $_SESSION['success'] = "Данные врача успешно обновлены";
                    header('Location: dentists.php');
                    exit;
                } else {
                    $error = "Ошибка при обновлении данных врача: " . mysqli_error($connection);
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
            <h1><?= $is_edit ? '✏️ Редактирование врача' : '👨‍⚕️ Новый врач' ?></h1>
            <a href="dentists.php" class="admin-btn admin-btn-outline">
                <i class="fas fa-arrow-left"></i> Назад к списку
            </a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert-success">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="admin-form-container">
        <form method="POST" action="" id="dentistForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="form-group">
                <label>ФИО врача <span class="required">*</span></label>
                <input type="text" name="dentist_full_name" class="form-control" required
                    value="<?= htmlspecialchars($_POST['dentist_full_name'] ?? $dentist_data['dentist_full_name'] ?? '') ?>"
                    placeholder="Иванов Иван Иванович" minlength="3">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Телефон <span class="required">*</span></label>
                    <input type="tel" name="dentist_phone_number" class="form-control" required
                        value="<?= htmlspecialchars($_POST['dentist_phone_number'] ?? $dentist_data['dentist_phone_number'] ?? '') ?>"
                        placeholder="+7 (999) 123-45-67" id="phoneInput">
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="dentist_email" class="form-control"
                        value="<?= htmlspecialchars($_POST['dentist_email'] ?? $dentist_data['dentist_email'] ?? '') ?>"
                        placeholder="ivanov@example.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Стаж (лет) <span class="required">*</span></label>
                    <input type="number" name="dentist_experience_years" class="form-control" required
                        value="<?= htmlspecialchars($_POST['dentist_experience_years'] ?? $dentist_data['dentist_experience_years'] ?? 0) ?>"
                        min="0" max="60" step="1">
                </div>

                <div class="form-group">
                    <label>Специализация <span class="required">*</span></label>
                    <select name="specialization_id" class="form-control" required>
                        <option value="">-- Выберите специализацию --</option>
                        <?php foreach ($specializations as $spec): ?>
                            <option value="<?= $spec['specialization_id'] ?>" <?= (($_POST['specialization_id'] ?? $dentist_data['specialization_id'] ?? '') == $spec['specialization_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($spec['specialization_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Категории услуг <span class="required">*</span></label>
                    <select name="service_category_ids[]" class="form-control" multiple required
                        style="min-height: 150px; padding: 10px;">
                        <?php foreach ($service_categories as $category): ?>
                            <option value="<?= $category['service_category_id'] ?>"
                                <?= (in_array($category['service_category_id'], $selected_categories)) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($category['service_category_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-text text-muted">Удерживайте Ctrl (или Cmd на Mac) для выбора нескольких
                        категорий</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Фото врача</label>
                    <?php if ($is_edit && !empty($dentist_data['dentist_photo']) && $dentist_data['dentist_photo'] !== 'default-doctor.jpg'): ?>
                        <div class="current-photo">
                            <p>Текущее фото:</p>
                            <img src="../img/<?= htmlspecialchars($dentist_data['dentist_photo']) ?>" alt="Фото врача"
                                style="max-width: 150px; border-radius: 8px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="dentist_photo" class="form-control"
                        accept="image/jpeg,image/png,image/jpg">
                    <small class="form-text text-muted">Рекомендуемый размер: до 2MB. Форматы: JPEG, PNG</small>
                </div>

                <div class="form-group">
                    <label>Пароль
                        <?= !$is_edit ? '<span class="required">*</span>' : '' ?>
                    </label>
                    <input type="password" name="dentist_password" class="form-control" <?= !$is_edit ? 'required' : '' ?>
                        placeholder="<?= $is_edit ? 'Оставьте пустым, чтобы не менять' : 'Минимум 6 символов' ?>" minlength="6">
                </div>
            </div>

            <div class="form-group">
                <label>Описание / Квалификация</label>
                <textarea name="dentist_description" class="form-control" rows="5"
                    placeholder="Образование, повышение квалификации, награды..."><?= htmlspecialchars($_POST['dentist_description'] ?? $dentist_data['dentist_description'] ?? '') ?></textarea>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> <?= $is_edit ? 'Сохранить изменения' : 'Добавить врача' ?>
                </button>
                <a href="dentists.php" class="admin-btn admin-btn-outline">
                    Отмена
                </a>
            </div>
        </form>
    </div>
</main>

<script src="../js/script.js"></script>