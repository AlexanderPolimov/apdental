<?php
require_once 'includes/init.php';

$title = "Кабинет врача - AP Dental";

// Проверка авторизации врача
if (!isset($_SESSION['dentist_id'])) {
    $_SESSION['login_required'] = 'Пожалуйста, войдите в систему';
    header('Location: login.php');
    exit;
}

$dentist_id = $_SESSION['dentist_id'];

// Обработка завершения приема
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_appointment'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        header('Location: dentist-profile.php');
        exit;
    }
    $appointment_id = (int) ($_POST['appointment_id'] ?? 0);

    if ($appointment_id) {
        $complete_query = "
            UPDATE appointments 
            SET appointment_status = 'completed'
            WHERE appointment_id = ? 
            AND dentist_id = ?
            AND appointment_status = 'confirmed'
        ";

        $stmt = $connection->prepare($complete_query);
        $stmt->bind_param('ii', $appointment_id, $dentist_id);
        $stmt->execute();
        $stmt->close();

        header('Location: dentist-profile.php');
        exit;
    }
}

// Получаем данные врача
$dentist_query = "
    SELECT 
        d.dentist_id,
        d.dentist_full_name,
        d.dentist_phone_number,
        d.dentist_email,
        d.dentist_experience_years,
        d.dentist_description,
        d.dentist_photo,
        s.specialization_name
    FROM dentists d
    LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
    WHERE d.dentist_id = ?
";

$stmt = $connection->prepare($dentist_query);
$stmt->bind_param('i', $dentist_id);
$stmt->execute();
$dentist = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$dentist) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Получаем сегодняшние записи
$today_query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.appointment_status,
        p.patient_id,
        p.patient_full_name,
        p.patient_phone_number,
        s.service_name,
        c.cabinet_name,
        mc.medical_card_id
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN cabinets c ON a.cabinet_id = c.cabinet_id
    LEFT JOIN medical_cards mc ON a.appointment_id = mc.appointment_id
    WHERE a.dentist_id = ? 
    AND a.appointment_date = CURDATE()
    AND a.appointment_status IN ('confirmed')
    ORDER BY a.appointment_time ASC
";

$stmt = $connection->prepare($today_query);
$stmt->bind_param('i', $dentist_id);
$stmt->execute();
$today_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Получаем предстоящие записи
$upcoming_query = "
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.appointment_status,
        p.patient_id,
        p.patient_full_name,
        p.patient_phone_number,
        s.service_name,
        c.cabinet_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN cabinets c ON a.cabinet_id = c.cabinet_id
    WHERE a.dentist_id = ? 
    AND a.appointment_date > CURDATE()
    AND a.appointment_status IN ('confirmed')
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 10
";

$stmt = $connection->prepare($upcoming_query);
$stmt->bind_param('i', $dentist_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Получаем пациентов врача (с которыми были приемы)
$search = $_GET['search'] ?? '';

$patients_query = "
    SELECT DISTINCT
        p.patient_id,
        p.patient_full_name,
        p.patient_phone_number,
        p.patient_email,
        MAX(a.appointment_date) as last_visit,
        COUNT(a.appointment_id) as total_visits
    FROM patients p
    JOIN appointments a ON p.patient_id = a.patient_id
    WHERE a.dentist_id = ?
    AND a.appointment_date <= CURDATE()
";

if (!empty($search)) {
    $search_term = '%' . $connection->real_escape_string($search) . '%';
    $patients_query .= " AND (
        p.patient_full_name LIKE '$search_term' 
        OR p.patient_phone_number LIKE '$search_term'
        OR p.patient_email LIKE '$search_term'
        OR DATE_FORMAT(a.appointment_date, '%d.%m.%Y') LIKE '$search_term'
    )";
}

$patients_query .= " GROUP BY p.patient_id ORDER BY last_visit DESC";

$stmt = $connection->prepare($patients_query);
$stmt->bind_param('i', $dentist_id);
$stmt->execute();
$my_patients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Статистика
$stats_query = "
    SELECT
        COUNT(CASE WHEN appointment_status = 'confirmed' THEN 1 END) as confirmed_count,
        COUNT(CASE WHEN appointment_date = CURDATE() THEN 1 END) as today_count,
        COUNT(CASE WHEN appointment_date > CURDATE() THEN 1 END) as upcoming_count
    FROM appointments
    WHERE dentist_id = ?
";

$stmt = $connection->prepare($stats_query);
$stmt->bind_param('i', $dentist_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$stmt->close();

include_once 'templates/header.php';
?>

<section class="section">
    <div class="container">
        <!-- Profile Header -->
        <div class="profile-header dentist-profile">
            <div class="profile-info">
                <div class="profile-avatar">
                    <?php if (!empty($dentist['dentist_photo'])): ?>
                        <img src="img/<?= htmlspecialchars($dentist['dentist_photo']) ?>"
                            alt="<?= htmlspecialchars($dentist['dentist_full_name']) ?>" class="avatar-img">
                    <?php else: ?>
                        <div class="avatar-circle">
                            <?= strtoupper(mb_substr($dentist['dentist_full_name'], 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-details">
                    <h1><?= htmlspecialchars($dentist['dentist_full_name']) ?></h1>
                    <div class="profile-specialty">
                        <img src="img/stethoscope.svg" alt="Стетоскоп">
                        <?= htmlspecialchars($dentist['specialization_name'] ?? 'Стоматолог') ?>
                    </div>
                    <div class="profile-email">
                        <img src="img/envelope.svg" alt="Конверт">
                        <?= htmlspecialchars($dentist['dentist_email']) ?>
                    </div>
                    <div class="profile-phone">
                        <img src="img/phone.svg" alt="Телефон">
                        <?= htmlspecialchars($dentist['dentist_phone_number']) ?>
                    </div>
                    <div class="profile-experience">
                        <img src="img/briefcase.svg" alt="Портфель">
                        Стаж: <?= $dentist['dentist_experience_years'] ?> лет
                    </div>
                </div>
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label">Сегодня приёмов:</span>
                        <span class="stat-number"><?= $stats['today_count'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
            <div class="profile-actions">
                <a href="logout.php" class="btn btn-secondary"
                    onclick="return confirm('Вы уверены, что хотите выйти?');">
                    <img src="img/sign-out.svg" alt="Выйти"> Выйти
                </a>
            </div>
        </div>

        <div class="dashboard">
            <!-- Today's Appointments -->
            <?php if (count($today_appointments) > 0): ?>
                <div class="dashboard-section today-section">
                    <div class="section-header">
                        <h2>Приемы на сегодня</h2>
                        <span class="badge"><?= count($today_appointments) ?></span>
                    </div>

                    <div class="appointments-grid">
                        <?php foreach ($today_appointments as $appointment):
                            $appointment_time = strtotime($appointment['appointment_time']);
                            $time = date('H:i', $appointment_time);
                            ?>
                            <div class="appointment-card">
                                <div class="appointment-main">
                                    <div class="appointment-details">
                                        <h3><?= htmlspecialchars($appointment['patient_full_name'] ?? 'Пациент') ?></h3>
                                        <div class="appointment-time-badge">
                                            <img src="img/clocks.svg" alt="Время">
                                            <?= $time ?>
                                        </div>
                                        <div class="appointment-meta">
                                            <span class="meta-item">
                                                <img src="img/phone.svg" alt="Телефон">
                                                <?= htmlspecialchars($appointment['patient_phone_number'] ?? '—') ?>
                                            </span>
                                            <span class="meta-item">
                                                <img src="img/tooth-solid-full.svg" alt="Зуб">
                                                <?= htmlspecialchars($appointment['service_name'] ?? 'Консультация') ?>
                                            </span>
                                            <span class="meta-item">
                                                <img src="img/door-open.svg" alt="Кабинет">
                                                <?= htmlspecialchars($appointment['cabinet_name'] ?? '—') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="appointment-footer">
                                    <?php if ($appointment['appointment_status'] === 'confirmed'): ?>
                                        <button onclick="completeAppointment(<?= $appointment['appointment_id'] ?>)"
                                            class="btn btn-success btn-sm">
                                            Выполнить
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($appointment['medical_card_id']): ?>
                                        <a href="medical-card.php?patient=<?= $appointment['patient_id'] ?>&appointment=<?= $appointment['appointment_id'] ?>"
                                            class="btn btn-primary btn-sm">
                                            Мед. карта
                                        </a>
                                    <?php else: ?>
                                        <a href="medical-card.php?patient=<?= $appointment['patient_id'] ?>&appointment=<?= $appointment['appointment_id'] ?>"
                                            class="btn btn-primary btn-sm">
                                            Новая карта
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Empty State -->
            <?php if (count($today_appointments) === 0 && count($upcoming_appointments) === 0): ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <img src="img/calendar.svg" alt="Календарь">
                    </div>
                    <h3>Сегодня приемов нет</h3>
                </div>
            <?php endif; ?>

            <!-- Upcoming Appointments -->
            <?php if (count($upcoming_appointments) > 0): ?>
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Предстоящие приемы</h2>
                    </div>

                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Время</th>
                                <th>Пациент</th>
                                <th>Услуга</th>
                                <th>Кабинет</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($appointment['appointment_date'])) ?></td>
                                    <td><?= date('H:i', strtotime($appointment['appointment_time'])) ?></td>
                                    <td><?= htmlspecialchars($appointment['patient_full_name'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($appointment['service_name'] ?? 'Консультация') ?></td>
                                    <td><?= htmlspecialchars($appointment['cabinet_name'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- My Patients -->
            <?php if (count($my_patients) > 0 || !empty($search)): ?>
                <div class="dashboard-section">
                    <div class="section-header">
                        <h2>Мои пациенты</h2>
                    </div>

                    <div class="patient-search">
                        <form method="GET" action="dentist-profile.php" class="search-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <div class="search-input-wrapper">
                                <img src="img/user-solid-full.svg" alt="Поиск" class="search-icon">
                                <input type="text" name="search" id="patientSearch"
                                    placeholder="Поиск по ФИО, телефону, email или дате..."
                                    value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                                <?php if (!empty($search)): ?>
                                    <a href="dentist-profile.php" class="search-clear">
                                        ✕
                                    </a>
                                <?php endif; ?>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm">🔍Найти</button>
                        </form>
                    </div>

                    <?php if (count($my_patients) > 0): ?>
                        <div class="patients-grid">
                            <?php
                            $counter = 0;
                            $show_limit = 3;
                            foreach ($my_patients as $patient):
                                $counter++;
                                $is_hidden = $counter > $show_limit;
                                ?>
                                <div class="patient-card <?= $is_hidden ? 'hidden-patient' : '' ?>">
                                    <div class="patient-info">
                                        <h4><?= htmlspecialchars($patient['patient_full_name']) ?></h4>
                                        <p><img src="img/phone-solid.svg" alt="Телефон">
                                            <?= htmlspecialchars($patient['patient_phone_number']) ?></p>
                                        <p><img src="img/calendar.svg" alt="Календарь"> Последний визит:
                                            <?= date('d.m.Y', strtotime($patient['last_visit'])) ?>
                                        </p>
                                        <p><img src="img/clock.svg" alt="Часы"> Всего приемов: <?= $patient['total_visits'] ?></p>
                                    </div>
                                    <div class="patient-actions">
                                        <a href="medical-card.php?patient=<?= $patient['patient_id'] ?>"
                                            class="btn btn-outline btn-sm">
                                            <img src="img/file-medical-solid.svg" alt="Медкарта">
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($my_patients) > $show_limit && empty($search)): ?>
                            <div class="show-more-patients">
                                <button id="togglePatientsBtn" class="btn btn-outline">
                                    Показать всех пациентов (<?= count($my_patients) ?>)
                                </button>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <h4>Пациенты не найдены</h4>
                            <p>По вашему запросу "<?= htmlspecialchars($search) ?>" ничего не найдено</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    const CSRF_TOKEN = '<?= htmlspecialchars($_SESSION['csrf_token']) ?>';
</script>
<script src="./js/script.js"></script>

<?php include_once 'templates/footer.php'; ?>