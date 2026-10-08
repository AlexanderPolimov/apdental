<?php
require_once '../includes/init.php';

// Проверка админа
if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Админ-панель | AP Dental";

// Статистика
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM appointments) as total_appointments,
    (SELECT COUNT(*) FROM appointments WHERE appointment_status = 'pending') as pending_appointments,
    (SELECT COUNT(*) FROM appointments WHERE appointment_status = 'confirmed') as confirmed_appointments,
    (SELECT COUNT(*) FROM appointments WHERE appointment_status = 'cancelled') as cancelled_appointments,
    (SELECT COUNT(*) FROM appointments WHERE appointment_status = 'completed') as completed_appointments,
    (SELECT COUNT(*) FROM patients) as total_patients,
    (SELECT COUNT(*) FROM dentists) as total_dentists,
    (SELECT COUNT(*) FROM services) as total_services,
    (SELECT COUNT(*) FROM service_categories) as total_categories,
    (SELECT COUNT(*) FROM reviews) as total_reviews,
    (SELECT AVG(review_rating) FROM reviews) as avg_rating";

$stats_result = mysqli_query($connection, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);

// Статистика по дням недели
$weekday_stats_sql = "SELECT 
    DAYNAME(appointment_date) as weekday,
    COUNT(*) as count
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DAYNAME(appointment_date)
    ORDER BY FIELD(DAYNAME(appointment_date), 
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$weekday_result = mysqli_query($connection, $weekday_stats_sql);
$weekday_stats = $weekday_result ? mysqli_fetch_all($weekday_result, MYSQLI_ASSOC) : [];

$today_sql = "SELECT 
    a.*, 
    p.patient_full_name, 
    d.dentist_full_name,
    s.service_name,
    c.cabinet_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN dentists d ON a.dentist_id = d.dentist_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN cabinets c ON a.cabinet_id = c.cabinet_id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
    LIMIT 5";
$today_result = mysqli_query($connection, $today_sql);
$today_appointments = mysqli_fetch_all($today_result, MYSQLI_ASSOC);

$statuses = [
    'pending' => 'Ожидание',
    'confirmed' => 'Подтверждено',
    'completed' => 'Выполнено',
    'cancelled' => 'Отменено'
];

include_once 'admin-header.php';
?>

<main class="admin-container">
    <h1 class="admin-title">Панель управления</h1>

    <div class="admin-section">
        <div class="admin-section-header">
            <h2>⚡ Быстрые действия</h2>
        </div>
        <div class="admin-actions">
            <a href="appointment-edit.php" class="admin-action-card">
                <i class="fas fa-plus-circle"></i>
                <span>Новая запись</span>
            </a>
            <a href="patient-edit.php" class="admin-action-card">
                <i class="fas fa-user-plus"></i>
                <span>Новый пациент</span>
            </a>
            <a href="appointments.php?date=<?= date('Y-m-d') ?>" class="admin-action-card">
                <i class="fas fa-calendar-check"></i>
                <span>Записи на сегодня</span>
            </a>
            <a href="dentist-edit.php" class="admin-action-card">
                <i class="fas fa-user-md"></i>
                <span>Новый врач</span>
            </a>
        </div>
    </div>

    <div class="admin-section">
        <div class="admin-section-header">
            <h2>📅 Сегодня (<?= date('d.m.Y') ?>)</h2>
            <span class="admin-badge">
                <?= count($today_appointments) ?> записей
            </span>
        </div>

        <div class="admin-today-list">
            <?php if (count($today_appointments) > 0): ?>
                <?php foreach ($today_appointments as $app): ?>
                    <div class="admin-today-item">
                        <span class="admin-time">
                            <?= date('H:i', strtotime($app['appointment_time'])) ?>
                        </span>
                        <span class="admin-patient">
                            <?= htmlspecialchars($app['patient_full_name'] ?? '—') ?>
                        </span>
                        <span class="admin-service">
                            <?= htmlspecialchars($app['service_name'] ?? '—') ?>
                        </span>
                        <span class="badge badge-<?= $app['appointment_status'] ?>">
                            <?= $statuses[$app['appointment_status']] ?? $app['appointment_status'] ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="admin-today-item empty">
                    <span class="admin-patient">На сегодня записей нет</span>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="admin-section">
        <div class="admin-section-header">
            <h2>📊 Показатели</h2>
        </div>

        <div class="admin-metrics">
            <div class="admin-metric">
                <div class="metric-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value">
                        <?= $stats['total_appointments'] ?? 0 ?>
                    </div>
                    <div class="metric-label">Записей всего</div>
                </div>
            </div>

            <div class="admin-metric">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value">
                        <?= $stats['total_patients'] ?? 0 ?>
                    </div>
                    <div class="metric-label">Пациентов</div>
                </div>
            </div>

            <div class="admin-metric">
                <div class="metric-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value">
                        <?= $stats['total_dentists'] ?? 0 ?>
                    </div>
                    <div class="metric-label">Врачей</div>
                </div>
            </div>

            <div class="admin-metric">
                <div class="metric-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="metric-content">
                    <div class="metric-value">
                        <?= number_format($stats['avg_rating'] ?? 0, 1) ?>
                    </div>
                    <div class="metric-label">Рейтинг</div>
                </div>
            </div>
        </div>
    </div>
</main>