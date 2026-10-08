<?php
require_once '../includes/init.php';

if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$title = "Управление записями | AP Dental";

// Получаем список врачей для фильтра
$dentists_query = "SELECT dentist_id, dentist_full_name FROM dentists ORDER BY dentist_full_name";
$dentists_result = mysqli_query($connection, $dentists_query);
$dentists = mysqli_fetch_all($dentists_result, MYSQLI_ASSOC);

// Получаем список пациентов для фильтра
$clients_query = "SELECT patient_id, patient_full_name FROM patients ORDER BY patient_full_name";
$clients_result = mysqli_query($connection, $clients_query);
$clients = mysqli_fetch_all($clients_result, MYSQLI_ASSOC);

// Фильтры
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? '';
$search = $_GET['search'] ?? '';
$dentist_id = $_GET['dentist_id'] ?? '';
$client_id = $_GET['client_id'] ?? '';

// Базовый запрос
$sql = "SELECT 
    a.*, 
    p.patient_full_name, 
    p.patient_phone_number,
    d.dentist_full_name,
    s.service_name,
    s.service_cost,
    c.cabinet_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN dentists d ON a.dentist_id = d.dentist_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN cabinets c ON a.cabinet_id = c.cabinet_id
    WHERE 1=1";

$params = [];
$types = "";

if ($status_filter) {
    $sql .= " AND a.appointment_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($date_filter) {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $date_filter;
    $types .= "s";
}

if ($search) {
    $sql .= " AND (
        p.patient_full_name LIKE ? 
        OR p.patient_phone_number LIKE ? 
        OR d.dentist_full_name LIKE ?
        OR s.service_name LIKE ?
        OR c.cabinet_name LIKE ?
        OR a.appointment_status LIKE ?
        OR DATE_FORMAT(a.appointment_date, '%d.%m.%Y') LIKE ?
        OR DATE_FORMAT(a.appointment_time, '%H:%i') LIKE ?
    )";

    $search_param = "%$search%";
    // Добавляем параметр для каждого поля
    for ($i = 0; $i < 8; $i++) {
        $params[] = $search_param;
        $types .= "s";
    }
}

if ($dentist_id) {
    $sql .= " AND a.dentist_id = ?";
    $params[] = $dentist_id;
    $types .= "i";
}

if ($client_id) {
    $sql .= " AND a.patient_id = ?";
    $params[] = $client_id;
    $types .= "i";
}

$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = mysqli_prepare($connection, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Статусы для фильтра
$statuses = [
    'pending' => 'Ожидание',
    'confirmed' => 'Подтверждено',
    'completed' => 'Выполнено',
    'cancelled' => 'Отменено'
];

include_once 'admin-header.php';
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div class="admin-page-title">
            <h1>📅 Записи на прием</h1>
            <a href="appointment-edit.php" class="admin-btn admin-btn-primary">
                <i class="fas fa-plus"></i> Новая запись
            </a>
        </div>

        <div class="admin-filters">
            <form method="GET" class="filters-form">
                <div class="filter-group">
                    <label>Статус</label>
                    <select name="status" class="filter-input">
                        <option value="">Все статусы</option>
                        <?php foreach ($statuses as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $status_filter == $key ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Врач</label>
                    <select name="dentist_id" class="filter-input">
                        <option value="">Все врачи</option>
                        <?php foreach ($dentists as $dentist): ?>
                            <option value="<?= $dentist['dentist_id'] ?>" <?= $dentist_id == $dentist['dentist_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dentist['dentist_full_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group" style="position: relative;">
                    <label>Пациент</label>
                    <input type="text" id="patient-search" class="filter-input" placeholder="Поиск по имени..."
                        autocomplete="off" value="<?php
                        if ($client_id) {
                            foreach ($clients as $c) {
                                if ($c['patient_id'] == $client_id) {
                                    echo htmlspecialchars($c['patient_full_name']);
                                    break;
                                }
                            }
                        }
                        ?>">
                    <input type="hidden" name="client_id" id="client-id-hidden"
                        value="<?= htmlspecialchars($client_id) ?>">
                    <div id="patient-dropdown"
                        style="display:none; position:absolute; top:100%; left:0; right:0; background:var(--color-bg, #fff); border:1px solid #ddd; border-radius:6px; z-index:999; max-height:220px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,0.1);">
                    </div>
                </div>

                <div class="filter-group">
                    <label>Дата</label>
                    <input type="date" name="date" value="<?= $date_filter ?>" class="filter-input">
                </div>

                <div class="filter-group filter-search">
                    <label>Поиск</label>
                    <div class="search-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="" value="<?= htmlspecialchars($search) ?>"
                            class="filter-input">
                    </div>
                </div>

                <div class="filter-actions">
                    <button type="submit" class="admin-btn admin-btn-primary">Применить</button>
                    <a href="appointments.php" class="admin-btn admin-btn-outline">Сбросить</a>
                </div>
            </form>
        </div>
    </div>

    <div class="admin-table-responsive">
        <table class="admin-data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Время</th>
                    <th>Пациент</th>
                    <th>Телефон</th>
                    <th>Услуга</th>
                    <th>Врач</th>
                    <th>Кабинет</th>
                    <th>Статус</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($appointments) > 0): ?>
                    <?php foreach ($appointments as $app): ?>
                        <tr class="<?=
                            $app['appointment_status'] == 'pending' ? 'pending-row' :
                            ($app['appointment_status'] == 'confirmed' ? 'confirmed-row' :
                                ($app['appointment_status'] == 'cancelled' ? 'cancelled-row' :
                                    ($app['appointment_status'] == 'completed' ? 'completed-row' : '')))
                            ?>" data-id="<?= $app['appointment_id'] ?>">
                            <td><?= $app['appointment_id'] ?></td>
                            <td>
                                <div class="date-display" onclick="editField(this, 'date', <?= $app['appointment_id'] ?>)">
                                    <span><?= date('d.m.Y', strtotime($app['appointment_date'])) ?></span>
                                    <i class="fas fa-pencil-alt"></i>
                                </div>
                            </td>
                            <td>
                                <div class="time-display" onclick="editField(this, 'time', <?= $app['appointment_id'] ?>)">
                                    <span><?= date('H:i', strtotime($app['appointment_time'])) ?></span>
                                    <i class="fas fa-pencil-alt"></i>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($app['patient_full_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($app['patient_phone_number'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($app['service_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($app['dentist_full_name'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($app['cabinet_name'] ?? '—') ?></td>
                            <td>
                                <select onchange="updateStatus(this, <?= $app['appointment_id'] ?>)"
                                    class="admin-status-select">
                                    <?php foreach ($statuses as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $app['appointment_status'] == $key ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="javascript:void(0)" class="action-btn save" title="Сохранить изменения"
                                        onclick="saveAllChanges(<?= $app['appointment_id'] ?>)">
                                        <i class="fas fa-save"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="empty-table">
                            <div class="empty-state">
                                <div class="empty-icon">📅</div>
                                <h4>Нет записей</h4>
                                <p>По выбранным фильтрам ничего не найдено</p>
                                <a href="appointment-edit.php" class="admin-btn admin-btn-primary">
                                    Создать запись
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<script>
    window.patientData = <?= json_encode(array_map(fn($c) => [
        'id' => $c['patient_id'],
        'name' => $c['patient_full_name']
    ], $clients)) ?>;
</script>

<script src="../js/script.js"></script>