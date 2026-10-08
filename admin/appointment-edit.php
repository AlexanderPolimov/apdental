<?php
require_once '../includes/init.php';

if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Новая запись | AP Dental";

$prefill_patient_id = $_GET['patient_id'] ?? '';
$prefill_treatment_plan = $_GET['treatment_plan'] ?? '';
$prefill_diagnosis = $_GET['diagnosis'] ?? '';
$from_medcard = isset($_GET['from_medcard']);

// Если есть предзаполненный план лечения, парсим его для удобства
$prefill_services = [];
if ($prefill_treatment_plan) {
    $lines = explode("\n", $prefill_treatment_plan);
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line)) {
            $prefill_services[] = $line;
        }
    }
}

// Получаем списки для формы
$patients_query = "SELECT patient_id, patient_full_name, patient_phone_number FROM patients ORDER BY patient_full_name";
$patients_result = mysqli_query($connection, $patients_query);
$patients = mysqli_fetch_all($patients_result, MYSQLI_ASSOC);

// Получаем список врачей с их категориями услуг
$dentists_query = "SELECT d.dentist_id, d.dentist_full_name, 
                          GROUP_CONCAT(dsc.service_category_id) as category_ids
                   FROM dentists d
                   LEFT JOIN dentists_service_categories dsc ON d.dentist_id = dsc.dentist_id
                   GROUP BY d.dentist_id
                   ORDER BY d.dentist_full_name";
$dentists_result = mysqli_query($connection, $dentists_query);
$dentists = mysqli_fetch_all($dentists_result, MYSQLI_ASSOC);

// Получаем список услуг с категориями
$services_query = "SELECT s.service_id, s.service_name, s.service_cost, s.service_duration, 
                          s.service_category_id, sc.service_category_name  
                   FROM services s
                   LEFT JOIN service_categories sc ON s.service_category_id = sc.service_category_id
                   ORDER BY sc.service_category_name, s.service_name";
$services_result = mysqli_query($connection, $services_query);

if (!$services_result) {
    die("Ошибка запроса услуг: " . mysqli_error($connection));
}

$services = mysqli_fetch_all($services_result, MYSQLI_ASSOC);

// Группируем услуги по категориям
$services_by_category = [];
foreach ($services as $service) {
    $cat_id = $service['service_category_id'] ?? 0;
    $cat_name = $service['service_category_name'] ?? 'Без категории';

    if (!isset($services_by_category[$cat_id])) {
        $services_by_category[$cat_id] = [
            'name' => $cat_name,
            'services' => []
        ];
    }
    $services_by_category[$cat_id]['services'][] = $service;
}

// Получаем услуги сгруппированные по врачам
$services_by_dentist = [];
foreach ($dentists as $dentist) {
    $dentist_id = $dentist['dentist_id'];
    $services_by_dentist[$dentist_id] = [];

    // Получаем категории врача
    $category_ids = $dentist['category_ids'] ? explode(',', $dentist['category_ids']) : [];

    if (!empty($category_ids)) {
        // Получаем услуги из категорий врача
        $placeholders = implode(',', array_fill(0, count($category_ids), '?'));
        $services_query = "SELECT s.service_id, s.service_name, s.service_cost, s.service_duration,
                                  sc.service_category_name
                           FROM services s
                           LEFT JOIN service_categories sc ON s.service_category_id = sc.service_category_id
                           WHERE s.service_category_id IN ($placeholders)
                           ORDER BY sc.service_category_name, s.service_name";

        $stmt = mysqli_prepare($connection, $services_query);
        $types = str_repeat('i', count($category_ids));
        mysqli_stmt_bind_param($stmt, $types, ...$category_ids);
        mysqli_stmt_execute($stmt);
        $services_result = mysqli_stmt_get_result($stmt);
        $services_by_dentist[$dentist_id] = mysqli_fetch_all($services_result, MYSQLI_ASSOC);
    }
}

// Получаем связь врачей с категориями услуг
$dentist_categories_query = "SELECT dentist_id, service_category_id 
                             FROM dentists_service_categories";
$dentist_categories_result = mysqli_query($connection, $dentist_categories_query);
$dentist_categories = [];

if ($dentist_categories_result) {
    while ($row = mysqli_fetch_assoc($dentist_categories_result)) {
        if (!isset($dentist_categories[$row['dentist_id']])) {
            $dentist_categories[$row['dentist_id']] = [];
        }
        $dentist_categories[$row['dentist_id']][] = (int) $row['service_category_id'];
    }
}

$cabinets_query = "SELECT cabinet_id, cabinet_name FROM cabinets ORDER BY cabinet_name";
$cabinets_result = mysqli_query($connection, $cabinets_query);
$cabinets = mysqli_fetch_all($cabinets_result, MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Неверный запрос. Обновите страницу и попробуйте снова.';
    } else {
        $patient_id = $_POST['patient_id'] ?? null;
        $appointments_data = $_POST['appointments'] ?? [];
        $errors = [];
        $success_count = 0;

        if (!$patient_id) {
            $errors[] = "Выберите пациента";
        }

        if (empty($appointments_data)) {
            $errors[] = "Добавьте хотя бы один прием";
        }

        if (empty($errors)) {
            $services_map = [];
            $s = mysqli_query($connection, 'SELECT service_id, service_duration, service_cost, service_category_id FROM services');
            while ($r = mysqli_fetch_assoc($s)) {
                $services_map[$r['service_id']] = $r;
            }

            foreach ($appointments_data as $index => $appointment) {
                $dentist_id = $appointment['dentist_id'] ?? null;
                $service_id = $appointment['service_id'] ?? null;
                $cabinet_id = $appointment['cabinet_id'] ?? null;
                $appointment_date = $appointment['appointment_date'] ?? null;
                $appointment_time = $appointment['appointment_time'] ?? null;

                // Получаем длительность услуги
                $service_data = $services_map[$service_id] ?? null;
                if (!$service_data) {
                    $errors[] = "Прием #" . ($index + 1) . ": Услуга не найдена";
                    continue;
                }
                $duration = $service_data['service_duration'] ?? 60;
                $category_id = $service_data['service_category_id'] ?? null;

                // Проверка минимальной даты
                if (strtotime($appointment_date) < strtotime(date('Y-m-d', strtotime('+1 day')))) {
                    $errors[] = "Прием #" . ($index + 1) . ": Запись возможна только на следующий день и позже";
                }

                // Проверка максимальной даты
                if (strtotime($appointment_date) > strtotime('+3 months')) {
                    $errors[] = "Прием #" . ($index + 1) . ": Запись возможна не более чем на 3 месяца вперед";
                }

                // ПРОВЕРКА ВРЕМЕНИ РАБОТЫ
                $time_start = $appointment_time . ':00';
                $hour = (int) substr($appointment_time, 0, 2);

                if ($hour < 9 || $hour > 20) {
                    $errors[] = "Прием #" . ($index + 1) . ": Часы работы с 09:00 до 21:00 (последняя запись в 20:00)";
                    continue;
                }

                $time_end = date('H:i:s', strtotime("+{$duration} minutes", strtotime($time_start)));

                if (strtotime($time_end) > strtotime('21:00:00')) {
                    $errors[] = "Прием #" . ($index + 1) . ": Время окончания выходит за рабочие часы";
                    continue;
                }

                // ПРОВЕРКА ЗАНЯТОСТИ ВРАЧА
                $check_sql = "SELECT a.appointment_time, s.service_duration 
                          FROM appointments a 
                          LEFT JOIN services s ON a.service_id = s.service_id 
                          WHERE a.dentist_id = ? 
                          AND a.appointment_date = ? 
                          AND a.appointment_status NOT IN ('cancelled', 'отменен')";

                $check_stmt = mysqli_prepare($connection, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "is", $dentist_id, $appointment_date);
                mysqli_stmt_execute($check_stmt);
                $check_result = mysqli_stmt_get_result($check_stmt);

                $new_start = strtotime($time_start);
                $new_end = strtotime($time_end);
                $time_conflict = false;

                while ($existing = mysqli_fetch_assoc($check_result)) {
                    $existing_start = strtotime($existing['appointment_time']);
                    $existing_duration = $existing['service_duration'] ?? 60;
                    $existing_end = strtotime("+{$existing_duration} minutes", $existing_start);

                    // Проверка пересечения
                    if (
                        ($new_start >= $existing_start && $new_start < $existing_end) ||
                        ($new_end > $existing_start && $new_end <= $existing_end) ||
                        ($new_start <= $existing_start && $new_end >= $existing_end)
                    ) {
                        $time_conflict = true;
                        $errors[] = "Прием #" . ($index + 1) . ": Время пересекается с записью к врачу в " . date('H:i', $existing_start);
                        break;
                    }
                }

                if ($time_conflict) {
                    continue;
                }

                // ПРОВЕРКА ЗАНЯТОСТИ КАБИНЕТОВ
                if ($category_id) {
                    $cabinet_check_sql = "
                    SELECT COUNT(*) as busy_count
                    FROM appointments a
                    WHERE a.cabinet_id = ?
                    AND a.appointment_date = ?
                    AND a.appointment_status NOT IN ('cancelled', 'отменен')
                    AND TIME_FORMAT(a.appointment_time, '%H:%i') = ?
                ";

                    $cabinet_stmt = mysqli_prepare($connection, $cabinet_check_sql);
                    mysqli_stmt_bind_param($cabinet_stmt, "iss", $cabinet_id, $appointment_date, $appointment_time);
                    mysqli_stmt_execute($cabinet_stmt);
                    $cabinet_result = mysqli_stmt_get_result($cabinet_stmt);
                    $cabinet_row = mysqli_fetch_assoc($cabinet_result);

                    if ($cabinet_row['busy_count'] > 0) {
                        $errors[] = "Прием #" . ($index + 1) . ": Кабинет уже занят на это время";
                        continue;
                    }
                }

                // СОЗДАНИЕ ЗАПИСИ
                $appointment_status = 'confirmed';
                $appointment_cost = $service_data['service_cost'] ?? 0;

                $sql = "INSERT INTO appointments (patient_id, dentist_id, service_id, cabinet_id, 
                    appointment_date, appointment_time, appointment_status, appointment_cost) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param(
                    $stmt,
                    "iiiisssd",
                    $patient_id,
                    $dentist_id,
                    $service_id,
                    $cabinet_id,
                    $appointment_date,
                    $appointment_time,
                    $appointment_status,
                    $appointment_cost
                );

                if (mysqli_stmt_execute($stmt)) {
                    $success_count++;
                } else {
                    error_log('Appointment create error: ' . mysqli_error($connection));
                    $errors[] = 'Прием #' . ($index + 1) . ': Ошибка создания.';
                }
            }

            if ($success_count > 0) {
                $_SESSION['success'] = "Создано записей: $success_count";
                header('Location: appointments.php');
                exit;
            }
        }
    }
}

include_once 'admin-header.php';
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div class="admin-page-title">
            <h1>📝 Новая запись на прием</h1>
            <a href="appointments.php" class="admin-btn admin-btn-outline">
                <i class="fas fa-arrow-left"></i> Назад к записям
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-error">
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="admin-form-container">
        <form method="POST" action="" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <?php if ($from_medcard): ?>
                <div class="alert-info">
                    <strong>ℹ️ Запись создаётся на основе плана лечения из медкарты</strong>
                    <?php if ($prefill_diagnosis): ?>
                        <div>
                            <strong>Диагноз:</strong>
                            <?= htmlspecialchars($prefill_diagnosis) ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($prefill_services)): ?>
                        <div>
                            <strong>План лечения:</strong>
                            <?php foreach ($prefill_services as $service): ?>
                                <?= htmlspecialchars($service) ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <div class="form-group" style="position: relative;">
                <label>Пациент *</label>
                <input type="text" id="patient-search" class="form-control" placeholder="Поиск по имени..."
                    autocomplete="off" value="<?php
                    if ($prefill_patient_id) {
                        foreach ($patients as $p) {
                            if ($p['patient_id'] == $prefill_patient_id) {
                                echo htmlspecialchars($p['patient_full_name']);
                                break;
                            }
                        }
                    }
                    ?>">
                <input type="hidden" name="patient_id" id="client-id-hidden"
                    value="<?= htmlspecialchars($prefill_patient_id) ?>">
                <div id="patient-dropdown"
                    style="display:none; position:absolute; top:100%; left:0; right:0; background:var(--color-bg, #fff); border:1px solid #ddd; border-radius:6px; z-index:999; max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                </div>
            </div>

            <div id="appointmentsContainer">
                <div class="appointment-card" data-index="0">
                    <div class="card-header">
                        <h3>Прием #1</h3>
                        <button type="button" class="remove-btn" onclick="removeAppointment(this)"
                            style="display: none;">
                            <i class="fas fa-trash"></i> Удалить
                        </button>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Врач</label>
                            <select name="appointments[0][dentist_id]" class="form-control dentist-select"
                                data-index="0" required>
                                <option value="">-- Выберите врача --</option>
                                <?php foreach ($dentists as $dentist): ?>
                                    <option value="<?= $dentist['dentist_id'] ?>"
                                        data-categories="<?= htmlspecialchars($dentist['category_ids']) ?>">
                                        <?= htmlspecialchars($dentist['dentist_full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Услуга</label>
                            <select name="appointments[0][service_id]" class="form-control service-select"
                                data-index="0" required>
                                <option value="">-- Выберите услугу --</option>
                                <?php foreach ($services_by_category as $cat_id => $category): ?>
                                    <optgroup label="<?= htmlspecialchars($category['name']) ?>">
                                        <?php foreach ($category['services'] as $service): ?>
                                            <option value="<?= $service['service_id'] ?>">
                                                <?= htmlspecialchars($service['service_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Кабинет</label>
                            <select name="appointments[0][cabinet_id]" class="form-control" required>
                                <option value="">-- Выберите кабинет --</option>
                                <?php foreach ($cabinets as $cabinet): ?>
                                    <option value="<?= $cabinet['cabinet_id'] ?>">
                                        <?= htmlspecialchars($cabinet['cabinet_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Дата</label>
                            <input type="date" name="appointments[0][appointment_date]"
                                class="form-control appointment-date" required>
                        </div>

                        <div class="form-group">
                            <label>Время</label>
                            <select name="appointments[0][appointment_time]"
                                class="form-control appointment-time-select" data-index="0" required disabled>
                                <option value="">-- Сначала выберите услугу, врача и дату --</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="admin-form-actions">
                <button type="submit" class="admin-btn admin-btn-primary">
                    <i class="fas fa-save"></i> Создать запись
                </button>
                <a href="appointments.php" class="admin-btn admin-btn-outline">
                    Отмена
                </a>

                <button type="button" class="admin-btn admin-btn-secondary add-appointment-btn"
                    onclick="addAppointment()">
                    <i class="fas fa-plus"></i> Добавить еще прием
                </button>
            </div>
        </form>
    </div>
</main>

<?php
$js_services = [];
foreach ($services_by_dentist as $did => $svcs) {
    $js_services[$did] = array_map(fn($s) => [
        'id' => (int) $s['service_id'],
        'name' => $s['service_name'],
        'category' => $s['service_category_name'] ?? 'Без категории',
    ], $svcs);
}
?>
<script>
    window.patientData = <?= json_encode(array_map(fn($p) => [
        'id' => $p['patient_id'],
        'name' => $p['patient_full_name']
    ], $patients), JSON_UNESCAPED_UNICODE) ?>;
    window.servicesByDentist = <?= json_encode($js_services, JSON_UNESCAPED_UNICODE) ?>;
</script>

<script src="../js/script.js"></script>