<?php
require_once '../includes/init.php';

if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Управление пациентами | AP Dental";

$search = $_GET['search'] ?? '';

$sql = "SELECT 
    p.*,
    (SELECT COUNT(*) FROM appointments 
     WHERE patient_id = p.patient_id 
     AND appointment_status = 'completed'
     AND appointment_date <= CURDATE()) as total_visits,
    (SELECT MAX(appointment_date) FROM appointments 
     WHERE patient_id = p.patient_id 
     AND appointment_status = 'completed'
     AND appointment_date <= CURDATE()) as last_visit
    FROM patients p
    WHERE 1=1";

if ($search) {
    $sql .= " AND (p.patient_full_name LIKE ? OR p.patient_phone_number LIKE ? OR p.patient_email LIKE ?)";
    $search_param = "%$search%";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $search_param);
} else {
    $stmt = mysqli_prepare($connection, $sql);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$patients = mysqli_fetch_all($result, MYSQLI_ASSOC);

include_once 'admin-header.php';
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div class="admin-page-title">
            <h1>👥 Пациенты</h1>
            <a href="patient-edit.php" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> Новый пациент
            </a>
        </div>

        <div class="admin-filters">
            <form method="GET" class="filters-form">
                <div class="filter-group filter-search" style="flex: 1;">
                    <label>Поиск</label>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Имя, телефон или email"
                            value="<?= htmlspecialchars($search) ?>" class="filter-input">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">Найти</button>
                    <a href="patients.php" class="admin-btn admin-btn-outline">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Пациент</th>
                    <th>Телефон</th>
                    <th>Email</th>
                    <th>Дата рождения</th>
                    <th>Визитов</th>
                    <th>Последний визит</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($patients) > 0): ?>
                    <?php foreach ($patients as $patient): ?>
                        <tr>
                            <td><?= $patient['patient_id'] ?></td>
                            <td>
                                <div class="patient-info">
                                    <div>
                                        <strong><?= htmlspecialchars($patient['patient_full_name']) ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($patient['patient_phone_number'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($patient['patient_email'] ?? '—') ?></td>
                            <td><?= $patient['patient_birth_date'] ? date('d.m.Y', strtotime($patient['patient_birth_date'])) : '—' ?>
                            </td>
                            <td><span class="visit-count"><?= $patient['total_visits'] ?? 0 ?></span></td>
                            <td><?= $patient['last_visit'] ? date('d.m.Y', strtotime($patient['last_visit'])) : '—' ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="medical-card-view.php?patient_id=<?= $patient['patient_id'] ?>" class="action-btn"
                                        title="Медкарта" style="color: #17a2b8;">
                                        <i class="fas fa-notes-medical"></i>
                                    </a>
                                    <a href="patient-edit.php?id=<?= $patient['patient_id'] ?>" class="action-btn"
                                        title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="appointments.php?client_id=<?= $patient['patient_id'] ?>" class="action-btn"
                                        title="История записей">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="empty-table">
                            <div class="empty-state">
                                <div class="empty-icon">👤</div>
                                <h4>Нет пациентов</h4>
                                <p><?= $search ? 'Ничего не найдено' : 'Добавьте первого пациента' ?></p>
                                <a href="patient-edit.php" class="admin-btn admin-btn-primary">
                                    Добавить пациента
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>