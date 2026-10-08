<?php
require_once 'includes/init.php';

$title = "Запись на прием - AP Dental";

// Если не авторизован - отправляем на регистрацию
if (!is_auth()) {
    $_SESSION['login_required'] = 'Пожалуйста, войдите или зарегистрируйтесь для записи';
    header('Location: register.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];
$success = '';
$error = '';

// Получаем ТОЛЬКО КОНСУЛЬТАЦИИ (услуги с ценой 0)
$consultations_query = "
    SELECT 
        s.service_id,
        s.service_name,
        s.service_cost,
        s.service_duration,
        s.service_category_id,
        sc.service_category_name
    FROM services s
    LEFT JOIN service_categories sc ON s.service_category_id = sc.service_category_id
    WHERE s.service_cost = 0
    ORDER BY sc.service_category_name, s.service_name
";

$consultations_result = mysqli_query($connection, $consultations_query);
if (!$consultations_result) {
    error_log('Consultations query error: ' . mysqli_error($connection));
    $consultations = [];
} else {
    while ($row = mysqli_fetch_assoc($consultations_result)) {
        $consultations[] = $row;
    }
}

// Получаем список врачей
$dentists_query = "
    SELECT 
        d.dentist_id,
        d.dentist_full_name,
        s.specialization_name
    FROM dentists d
    LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
    ORDER BY d.dentist_full_name
";
$dentists_result = mysqli_query($connection, $dentists_query);
$dentists = [];
while ($row = mysqli_fetch_assoc($dentists_result)) {
    $dentists[] = $row;
}

// Получаем данные пациента для предзаполнения формы
$patient_query = "SELECT patient_full_name, patient_phone_number FROM patients WHERE patient_id = ?";
$stmt = $connection->prepare($patient_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Получаем связи врачей с категориями услуг
$dentist_categories_query = "
    SELECT 
        dsc.dentist_id,
        dsc.service_category_id
    FROM dentists_service_categories dsc
";
$dentist_categories_result = mysqli_query($connection, $dentist_categories_query);
$dentist_categories = [];
while ($row = mysqli_fetch_assoc($dentist_categories_result)) {
    $dentist_categories[] = $row;
}

// Группируем врачей по категориям
$dentists_by_category = [];
foreach ($dentist_categories as $dc) {
    $dentists_by_category[$dc['service_category_id']][] = $dc['dentist_id'];
}

// Обработка формы записи
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Неверный запрос. Обновите страницу и попробуйте снова.';
    } else {
        $consultation_id = (int) ($_POST['consultation_id'] ?? 0);
        $dentist_id = (int) ($_POST['dentist_id'] ?? 0);
        $appointment_date = $_POST['appointment_date'] ?? '';
        $appointment_time = $_POST['appointment_time'] ?? '';

        $errors = [];

        // Валидация
        if ($consultation_id <= 0) {
            $errors[] = 'Выберите консультацию';
        }

        if ($dentist_id <= 0) {
            $errors[] = 'Выберите врача';
        }

        if (empty($appointment_date)) {
            $errors[] = 'Выберите дату';
        } elseif (strtotime($appointment_date) < strtotime(date('Y-m-d', strtotime('+1 day')))) {
            $errors[] = 'Запись возможна только на следующий день и позже';
        } elseif (strtotime($appointment_date) > strtotime('+3 months')) {
            $errors[] = 'Запись возможна не более чем на 3 месяца вперед';
        }

        if (empty($appointment_time)) {
            $errors[] = 'Выберите время';
        }

        // Проверка свободного времени
        if (empty($errors)) {
            $check_query = "
            SELECT appointment_id 
            FROM appointments 
            WHERE dentist_id = ? 
            AND appointment_date = ? 
            AND appointment_time = ?
            AND appointment_status NOT IN ('cancelled')
        ";
            $stmt = $connection->prepare($check_query);
            $stmt->bind_param('iss', $dentist_id, $appointment_date, $appointment_time);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = 'Это время уже занято';
            }
            $stmt->close();
        }

        // Получаем свободный кабинет, который подходит для данной услуги
        $cabinet_id = null;
        if (empty($errors)) {
            // Сначала получаем ID категории выбранной услуги
            $category_query = "
            SELECT service_category_id 
            FROM services 
            WHERE service_id = ?
        ";
            $stmt = $connection->prepare($category_query);
            $stmt->bind_param('i', $consultation_id);
            $stmt->execute();
            $category_result = $stmt->get_result();
            $category_id = $category_result->fetch_assoc()['service_category_id'];
            $stmt->close();

            // Получаем кабинеты, которые подходят для этой категории услуг
            $cabinet_query = "
            SELECT c.cabinet_id 
            FROM cabinets c
            INNER JOIN cabinets_service_categories csc ON c.cabinet_id = csc.cabinet_id
            WHERE csc.service_category_id = ?
            AND c.cabinet_id NOT IN (
                SELECT cabinet_id 
                FROM appointments 
                WHERE appointment_date = ? 
                AND appointment_time = ?
                AND appointment_status NOT IN ('cancelled', 'отменен')
            )
            LIMIT 1
        ";

            $stmt = $connection->prepare($cabinet_query);
            $stmt->bind_param('iss', $category_id, $appointment_date, $appointment_time);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                $cabinet_id = $row['cabinet_id'];
                $stmt->close();
            } else {
                $stmt->close(); // Закрываем первый запрос

                // Если нет специализированных кабинетов, ищем любой свободный
                $fallback_query = "
                SELECT cabinet_id 
                FROM cabinets 
                WHERE cabinet_id NOT IN (
                    SELECT cabinet_id 
                    FROM appointments 
                    WHERE appointment_date = ? 
                    AND appointment_time = ?
                    AND appointment_status NOT IN ('cancelled', 'отменен')
                )
                LIMIT 1
            ";
                $stmt = $connection->prepare($fallback_query);
                $stmt->bind_param('ss', $appointment_date, $appointment_time);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $cabinet_id = $row['cabinet_id'];
                } else {
                    $errors[] = 'Нет свободных кабинетов на выбранное время';
                }
                $stmt->close();
            }
        }

        // Если нет ошибок - создаем запись
        if (empty($errors) && $cabinet_id) {
            $insert_query = "
            INSERT INTO appointments (
                appointment_date, 
                appointment_time, 
                appointment_status, 
                patient_id, 
                dentist_id, 
                service_id,
                cabinet_id
            ) VALUES (?, ?, 'pending', ?, ?, ?, ?)
        ";

            $stmt = $connection->prepare($insert_query);
            $stmt->bind_param(
                'ssiiii',
                $appointment_date,
                $appointment_time,
                $patient_id,
                $dentist_id,
                $consultation_id,
                $cabinet_id
            );

            if ($stmt->execute()) {
                header('Location: profile.php');
                exit;
            } else {
                $error = 'Ошибка при создании записи. Пожалуйста, попробуйте еще раз.';
            }
            $stmt->close();
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

include_once 'templates/header.php';
?>
<section class="appointment-section">
    <div class="container">
        <div class="appointment-wrapper">
            <div class="appointment-card">
                <div class="appointment-header">
                    <h1 class="appointment-title">Запись на консультацию</h1>
                    <p class="appointment-subtitle">Выберите направление и врача для первичного приема</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <?php if (isset($_SESSION['appointment_success'])): ?>
                    <div class="alert alert-success"><?= $_SESSION['appointment_success'] ?></div>
                    <?php unset($_SESSION['appointment_success']); ?>
                <?php endif; ?>

                <form method="POST" action="" class="appointment-form" id="appointmentForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <!-- Консультация -->
                    <div class="form-group">
                        <label for="consultation_id" class="form-label">Выберите консультацию <span
                                class="text-danger">*</span></label>
                        <select name="consultation_id" id="consultation_id" class="form-control" required>
                            <option value="">-- Выберите консультацию --</option>
                            <?php foreach ($consultations as $consultation): ?>
                                <option value="<?= $consultation['service_id'] ?>"
                                    data-category="<?= $consultation['service_category_id'] ?>"
                                    <?= ($_POST['consultation_id'] ?? '') == $consultation['service_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($consultation['service_category_name']) ?> -
                                    <?= htmlspecialchars($consultation['service_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Врач -->
                    <div class="form-group">
                        <label for="dentist_id" class="form-label">Выберите врача <span
                                class="text-danger">*</span></label>
                        <select name="dentist_id" id="dentist_id" class="form-control" required>
                            <option value="">-- Выберите врача --</option>
                            <?php foreach ($dentists as $dentist): ?>
                                <option value="<?= $dentist['dentist_id'] ?>" <?= ($_POST['dentist_id'] ?? '') == $dentist['dentist_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dentist['dentist_full_name']) ?>
                                    (<?= htmlspecialchars($dentist['specialization_name'] ?? 'Стоматолог') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Дата -->
                    <div class="form-group">
                        <label for="appointment_date" class="form-label">Выберите дату <span
                                class="text-danger">*</span></label>
                        <input type="date" id="appointment_date" name="appointment_date" class="form-control"
                            min="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($_POST['appointment_date'] ?? '') ?>"
                            required>
                    </div>

                    <!-- Время -->
                    <div class="form-group">
                        <label for="appointment_time" class="form-label">Выберите время <span
                                class="text-danger">*</span></label>
                        <select name="appointment_time" id="appointment_time" class="form-control" required disabled>
                            <option value="">-- Сначала выберите дату и врача --</option>
                        </select>
                        <div class="loading-spinner" id="timeLoading" style="display: none;">
                            <i class="fas fa-spinner fa-spin"></i> Загрузка...
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-check"></i> Записаться на консультацию
                        </button>
                    </div>
                </form>
                <div class="appointment-info">
                    <h3>Как это работает?</h3>
                    <ul>
                        <li>Вы записываетесь на первичную консультацию</li>
                        <li>Врач проведет осмотр и поставит диагноз</li>
                        <li>После консультации вам будет назначено лечение</li>
                        <li>Все последующие приемы будут доступны в личном кабинете</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    // Данные о врачах по категориям для динамической загрузки
    window.dentistsByCategory = <?= json_encode($dentists_by_category) ?>;

    // Полный список врачей с информацией
    window.allDentists = <?= json_encode($dentists) ?>;
</script>

<script src="./js/script.js"></script>

<?php include_once 'templates/footer.php'; ?>