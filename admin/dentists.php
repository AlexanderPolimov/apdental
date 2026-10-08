<?php
require_once '../includes/init.php';

if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Управление врачами | AP Dental";

$search = $_GET['search'] ?? '';

$sql = "SELECT 
    d.*,
    s.specialization_name,
    (SELECT COUNT(*) FROM appointments WHERE dentist_id = d.dentist_id) as total_appointments
    FROM dentists d
    LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
    WHERE 1=1";

if ($search) {
    $sql .= " AND d.dentist_full_name LIKE ?";
    $search_param = "%$search%";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "s", $search_param);
} else {
    $stmt = mysqli_prepare($connection, $sql);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$dentists = mysqli_fetch_all($result, MYSQLI_ASSOC);

include_once 'admin-header.php';
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div class="admin-page-title">
            <h1>👨‍⚕️ Врачи</h1>
            <a href="dentist-edit.php" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> Новый врач
            </a>
        </div>

        <div class="admin-filters">
            <form method="GET" class="filters-form">
                <div class="filter-group filter-search" style="flex: 1;">
                    <label>Поиск</label>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Имя врача"
                            value="<?= htmlspecialchars($search) ?>" class="filter-input">
                    </div>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">Найти</button>
                    <a href="dentists.php" class="admin-btn admin-btn-outline">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <div class="dentists-grid">
        <?php if (count($dentists) > 0): ?>
            <?php foreach ($dentists as $dentist): ?>
                <div class="doctor-card">
                    <div class="dentist-card-header">
                        <div class="doctor-image">
                            <img src="../img/<?= htmlspecialchars($dentist['dentist_photo']) ?>"
                                alt="<?= htmlspecialchars($dentist['dentist_full_name']) ?>">
                        </div>
                    </div>

                    <div class="dentist-card-body">
                        <h3><?= htmlspecialchars($dentist['dentist_full_name']) ?></h3>
                        <div class="dentist-specialty">
                            <?= htmlspecialchars($dentist['specialization_name'] ?? 'Стоматолог') ?>
                        </div>

                        <div class="dentist-meta">
                            <div class="meta-item">
                                <i class="fas fa-briefcase"></i>
                                <span>Стаж: <?= $dentist['dentist_experience_years'] ?? 0 ?> лет</span>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-calendar-check"></i>
                                <span>Приемов: <?= $dentist['total_appointments'] ?? 0 ?></span>
                            </div>
                        </div>

                        <?php if ($dentist['dentist_description']): ?>
                            <div class="dentist-description">
                                <?= htmlspecialchars(mb_substr($dentist['dentist_description'], 0, 150)) ?>..
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="dentist-card-footer">
                        <a href="dentist-edit.php?id=<?= $dentist['dentist_id'] ?>" class="card-action" title="Редактировать">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="appointments.php?dentist_id=<?= $dentist['dentist_id'] ?>" class="card-action"
                            title="Записи к врачу">
                            <i class="fas fa-calendar-alt"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <div class="empty-icon">👨‍⚕️</div>
                <h4>Нет врачей</h4>
                <p><?= $search ? 'Ничего не найдено' : 'Добавьте первого врача' ?></p>
                <a href="dentist-edit.php" class="admin-btn admin-btn-primary">
                    Добавить врача
                </a>
            </div>
        <?php endif; ?>
    </div>
</main>