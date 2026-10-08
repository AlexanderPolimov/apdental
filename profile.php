<?php
// profile.php - Личный кабинет пациента
require_once 'includes/init.php';

$title = "Личный кабинет пациента - AP Dental";

// Проверка авторизации
if (!is_auth()) {
    $_SESSION['login_required'] = 'Пожалуйста, войдите в систему';
    header('Location: login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];

// ОБРАБОТКА ОТМЕНЫ ЗАПИСИ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_appointment'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Неверный запрос. Обновите страницу и попробуйте снова.';
    } else {
        $appointment_id = (int) $_POST['appointment_id'];

        $check_query = "SELECT appointment_id FROM appointments 
                    WHERE appointment_id = ? AND patient_id = ? AND appointment_status = 'pending'";
        $stmt = $connection->prepare($check_query);
        $stmt->bind_param('ii', $appointment_id, $patient_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $update_query = "UPDATE appointments SET appointment_status = 'cancelled' WHERE appointment_id = ?";
            $stmt = $connection->prepare($update_query);
            $stmt->bind_param('i', $appointment_id);

            if ($stmt->execute()) {
                $_SESSION['appointment_success'] = 'Запись успешно отменена';
            } else {
                $_SESSION['appointment_error'] = 'Ошибка при отмене записи';
            }
            $stmt->close();
        } else {
            $_SESSION['appointment_error'] = 'Запись не найдена или уже не может быть отменена';
        }

        header('Location: profile.php');
        exit;
    }
}

// Получаем данные пациента
$patient_query = "
    SELECT 
        patient_id,
        patient_full_name,
        patient_phone_number,
        patient_email,
        patient_gender,
        patient_birth_date,
        patient_insurance_number
    FROM patients 
    WHERE patient_id = ?
";

$stmt = $connection->prepare($patient_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$patient) {
    // Если пациент не найден - разлогиниваем
    session_destroy();
    header('Location: login.php');
    exit;
}

// Получаем предстоящие записи (текущая дата и позже)
$upcoming_query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.appointment_status,
        a.appointment_cost,
        d.dentist_id,
        d.dentist_full_name,
        s.service_name,
        s.service_cost,
        c.cabinet_name
    FROM appointments a
    LEFT JOIN dentists d ON a.dentist_id = d.dentist_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN cabinets c ON a.cabinet_id = c.cabinet_id
    WHERE a.patient_id = ?
    AND a.appointment_status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
";

$stmt = $connection->prepare($upcoming_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Получаем историю записей (прошедшие или отмененные)
$history_query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.appointment_status,
        a.appointment_cost,
        d.dentist_full_name,
        s.service_name
    FROM appointments a
    LEFT JOIN dentists d ON a.dentist_id = d.dentist_id
    LEFT JOIN services s ON a.service_id = s.service_id
    WHERE a.patient_id = ? 
    AND (a.appointment_status = 'completed' OR a.appointment_status = 'cancelled')
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
    LIMIT 20
";

$stmt = $connection->prepare($history_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$history_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Подсчет статистики
$stats_query = "
    SELECT 
        COUNT(CASE WHEN appointment_status = 'pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN appointment_status = 'confirmed' THEN 1 END) as confirmed_count,
        COUNT(CASE WHEN appointment_status = 'completed' THEN 1 END) as completed_count,
        COUNT(CASE WHEN appointment_status = 'cancelled' THEN 1 END) as cancelled_count
    FROM appointments
    WHERE patient_id = ?
";

$stmt = $connection->prepare($stats_query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

include_once 'templates/header.php';
?>

<section class="section">
    <div class="container">
        <div class="profile-header">
            <div class="profile-info">
                <div class="profile-details">
                    <h1><?= htmlspecialchars($patient['patient_full_name']) ?></h1>
                    <div class="profile-email">
                        <img src="img/envelope.svg" alt="Конверт">
                        <?= htmlspecialchars($patient['patient_email']) ?>
                    </div>
                    <div class="profile-phone">
                        <img src="img/phone.svg" alt="Телефон">
                        <?= htmlspecialchars($patient['patient_phone_number']) ?>
                    </div>
                    <?php if (!empty($patient['patient_birth_date'])): ?>
                        <div class="profile-date">
                            <span class="info-label">Дата рождения:</span>
                            <span class="info-value"><?= date('d.m.Y', strtotime($patient['patient_birth_date'])) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['patient_insurance_number'])): ?>
                        <div class="profile-policy">
                            <span class="info-label">Номер полиса:</span>
                            <span class="info-value"><?= htmlspecialchars($patient['patient_insurance_number']) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($patient['patient_gender'])): ?>
                        <div class="profile-gender">
                            <span class="info-label">Пол:</span>
                            <span
                                class="info-value"><?= $patient['patient_gender'] === 'male' ? 'Мужской' : 'Женский' ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-stats">
                    <div class="profile-actions">
                        <a href="appointment.php" class="btn btn-primary">
                            <img src="img/plus.svg" alt="Плюс"> Новая запись
                        </a>
                        <a href="logout.php" class="btn btn-secondary"
                            onclick="return confirm('Вы уверены, что хотите выйти?');">
                            <img src="img/sign-out.svg" alt="Выйти"> Выйти
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard">
            <?php if (isset($_SESSION['appointment_success'])): ?>
                <div class="alert alert-success alert-dismissible">
                    <?= $_SESSION['appointment_success'] ?>
                    <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['appointment_success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['appointment_error'])): ?>
                <div class="alert alert-error alert-dismissible">
                    <?= $_SESSION['appointment_error'] ?>
                    <button type="button" class="close" onclick="this.parentElement.style.display='none'">&times;</button>
                </div>
                <?php unset($_SESSION['appointment_error']); ?>
            <?php endif; ?>

            <?php if (count($upcoming_appointments) > 0): ?>
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Ближайшие записи</h2>
                        <span class="badge"><?= count($upcoming_appointments) ?></span>
                    </div>

                    <div class="appointments-grid">
                        <?php foreach ($upcoming_appointments as $appointment):
                            $appointment_date = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                            $card_class = $appointment['appointment_status'] === 'confirmed'
                                ? 'appointment-card--confirmed'
                                : 'appointment-card--pending';
                            $day = date('d', $appointment_date);
                            $month = get_month_name(date('n', $appointment_date));
                            $time = date('H:i', $appointment_date);
                            ?>
                            <div class="appointment-card <?= $card_class ?>">
                                <div class="appointment-main">
                                    <div class="appointment-details">
                                        <h3><?= htmlspecialchars($appointment['service_name'] ?? 'Консультация') ?></h3>
                                        <div class="appointment-meta">
                                            <span class="meta-item">
                                                    <span class="date-day"><?= $day ?></span>
                                                    <span class="date-month"><?= $month ?></span>
                                            </span>
                                            <span class="meta-item">
                                                <span class="date-time"><?= $time ?></span>
                                            </span>
                                            <?php if (!empty($appointment['appointment_cost']) || !empty($appointment['service_cost'])): ?>
                                                <span class="meta-item">
                                                    <?= number_format($appointment['appointment_cost'] ?? $appointment['service_cost'], 0, ',', ' ') ?>
                                                    ₽
                                                </span>
                                            <?php endif; ?>
                                            <span class="meta-item">
                                                <img src="img/user-md.svg" alt="Врач">
                                                <?= htmlspecialchars($appointment['dentist_full_name'] ?? 'Врач') ?>
                                            </span>

                                            <?php if (!empty($appointment['cabinet_name'])): ?>
                                                <span class="meta-item">
                                                    <img src="img/door-open-solid.svg" alt="Кабинет">
                                                    <?= htmlspecialchars($appointment['cabinet_name']) ?>
                                                </span>
                                            <?php endif; ?>

                                        </div>
                                    </div>
                                </div>

                                <?php if ($appointment['appointment_status'] === 'pending'): ?>
                                    <div class="appointment-footer">
                                        <form method="POST" style="display: inline;"
                                            onsubmit="return confirm('Вы уверены, что хотите отменить запись?');">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                            <input type="hidden" name="appointment_id"
                                                value="<?= $appointment['appointment_id'] ?>">
                                            <button type="submit" name="cancel_appointment" class="btn btn-secondary btn-sm">
                                                Отменить
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($appointment['appointment_status'] === 'confirmed'): ?>
                                    <div class="appointment-footer">
                                        <div class="appointment-status-badge confirmed">
                                            <img src="img/circle-check-solid.svg" alt="Галочка" width="30px"> Запись подтверждена
                                        </div>
                                    </div>

                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (count($history_appointments) > 0): ?>
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>История записей</h2>
                    </div>

                    <div class="appointments-history">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Услуга</th>
                                    <th>Врач</th>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history_appointments as $appointment):
                                    $status_class = '';
                                    $status_text = '';

                                    switch ($appointment['appointment_status']) {
                                        case 'cancelled':
                                            $status_class = 'badge-danger';
                                            $status_text = 'Отменено';
                                            break;
                                        case 'completed':
                                            $status_class = 'badge-success';
                                            $status_text = 'Завершено';
                                            break;
                                        case 'confirmed':
                                            $status_class = 'badge-info';
                                            $status_text = 'Подтверждено';
                                            break;
                                        case 'pending':
                                            $status_class = 'badge-warning';
                                            $status_text = 'Ожидание';
                                            break;
                                    }

                                    $appointment_date = strtotime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']);
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($appointment['service_name'] ?? 'Консультация') ?></td>
                                        <td><?= htmlspecialchars($appointment['dentist_full_name'] ?? 'Не указан') ?></td>
                                        <td><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></td>
                                        <td><?= date('H:i', strtotime($appointment['appointment_time'])) ?></td>
                                        <td>
                                            <span class="badge-status <?= $status_class ?>"><?= $status_text ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (count($upcoming_appointments) === 0 && count($history_appointments) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <img src="img/calendar.svg" alt="Календарь">
                    </div>
                    <h3>У вас пока нет записей</h3>
                    <p>Запишитесь на прием и получите здоровую улыбку</p>
                    <a href="appointment.php" class="btn btn-primary">
                        Записаться на прием
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<?php include_once 'templates/footer.php'; ?>