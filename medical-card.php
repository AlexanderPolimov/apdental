<?php
// medical-card.php
require_once 'includes/init.php';

if (!isset($_SESSION['dentist_id'])) {
    header('Location: login.php');
    exit;
}

$dentist_id = $_SESSION['dentist_id'];
$patient_id = $_GET['patient'] ?? 0;
$appointment_id = $_GET['appointment'] ?? 0;

if (!$patient_id) {
    header('Location: dentist-profile.php');
    exit;
}

// Проверяем, что пациент действительно был у этого врача
$check_query = "SELECT COUNT(*) as cnt FROM appointments 
                WHERE dentist_id = ? AND patient_id = ?";
$check_stmt = $connection->prepare($check_query);
$check_stmt->bind_param('ii', $dentist_id, $patient_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result()->fetch_assoc();

if ($check_result['cnt'] == 0) {
    $_SESSION['error'] = "У вас нет доступа к карте этого пациента";
    header('Location: dentist-profile.php');
    exit;
}

// Получаем данные пациента
$patient_query = "SELECT * FROM patients WHERE patient_id = ?";
$patient_stmt = $connection->prepare($patient_query);
$patient_stmt->bind_param('i', $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

// Получаем ВСЕ записи медкарты для этого пациента (историю)
$history_query = "SELECT mc.*, a.appointment_date, a.appointment_time, s.service_name,
                         d.dentist_full_name
                  FROM medical_cards mc
                  LEFT JOIN appointments a ON mc.appointment_id = a.appointment_id
                  LEFT JOIN services s ON a.service_id = s.service_id
                  LEFT JOIN dentists d ON mc.dentist_id = d.dentist_id
                  WHERE mc.patient_id = ?
                  ORDER BY mc.created_at DESC";
$history_stmt = $connection->prepare($history_query);
$history_stmt->bind_param('i', $patient_id);
$history_stmt->execute();
$medical_history = $history_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Получаем запись для ТЕКУЩЕГО приема (если есть appointment_id)
$current_entry = null;
$current_appointment_date = date('Y-m-d');

if ($appointment_id) {
    // Получаем дату и статус приема
    $appointment_check_query = "SELECT appointment_date, appointment_status FROM appointments WHERE appointment_id = ? AND dentist_id = ?";
    $appointment_check_stmt = $connection->prepare($appointment_check_query);
    $appointment_check_stmt->bind_param('ii', $appointment_id, $dentist_id);
    $appointment_check_stmt->execute();
    $appointment_data = $appointment_check_stmt->get_result()->fetch_assoc();

    if (!$appointment_data) {
        $_SESSION['error'] = "Прием не найден";
        header('Location: dentist-profile.php');
        exit;
    }

    $current_appointment_date = $appointment_data['appointment_date'];

    // Получаем существующую запись медкарты для этого приема
    $entry_query = "SELECT * FROM medical_cards WHERE patient_id = ? AND appointment_id = ?";
    $entry_stmt = $connection->prepare($entry_query);
    $entry_stmt->bind_param('ii', $patient_id, $appointment_id);
    $entry_stmt->execute();
    $current_entry = $entry_stmt->get_result()->fetch_assoc();

    // Проверяем, можно ли заполнять медкарту (только если дата приема сегодня или раньше)
    $current_date = date('Y-m-d');
    if ($appointment_data['appointment_date'] > $current_date) {
        $_SESSION['error'] = "Медицинскую карту можно заполнить только после наступления приема";
        header("Location: medical-card.php?patient=$patient_id");
        exit;
    }

    // Также можно разрешить заполнять только для подтвержденных или завершенных приемов
    if (!in_array($appointment_data['appointment_status'], ['confirmed', 'completed'])) {
        $_SESSION['error'] = "Медицинскую карту можно заполнить только для подтвержденных приемов";
        header("Location: medical-card.php?patient=$patient_id");
        exit;
    }
}

// Обработка сохранения
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $diagnosis = $_POST['diagnosis'] ?? '';
    $treatment_plan = $_POST['treatment_plan'] ?? '';
    $prescriptions = $_POST['prescriptions'] ?? '';
    $recommendations = $_POST['recommendations'] ?? '';

    if ($appointment_id) {
        if ($current_entry) {
            // Обновляем существующую запись для этого приема
            $update_query = "UPDATE medical_cards SET 
                            medical_card_diagnosis = ?,
                            medical_card_treatment_plan = ?,
                            medical_card_prescriptions = ?,
                            medical_card_recommendations = ?,
                            updated_at = CURRENT_TIMESTAMP
                            WHERE medical_card_id = ?";
            $update_stmt = $connection->prepare($update_query);
            $update_stmt->bind_param('ssssi', $diagnosis, $treatment_plan, $prescriptions, $recommendations, $current_entry['medical_card_id']);
            $update_stmt->execute();

            $_SESSION['success'] = "Запись приема обновлена";
        } else {
            // Создаем новую запись для этого приема
            $insert_query = "INSERT INTO medical_cards 
                            (patient_id, dentist_id, appointment_id, 
                             medical_card_diagnosis, medical_card_treatment_plan, 
                             medical_card_prescriptions, medical_card_recommendations) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $connection->prepare($insert_query);
            $insert_stmt->bind_param(
                'iiissss',
                $patient_id,
                $dentist_id,
                $appointment_id,
                $diagnosis,
                $treatment_plan,
                $prescriptions,
                $recommendations
            );
            $insert_stmt->execute();

            $_SESSION['success'] = "Запись приема создана";
        }
        header("Location: medical-card.php?patient=$patient_id");
        exit;
    }
}

// Получаем историю приемов пациента (для таблицы внизу)
$appointments_query = "SELECT a.*, s.service_name 
                      FROM appointments a
                      LEFT JOIN services s ON a.service_id = s.service_id
                      WHERE a.patient_id = ? AND a.dentist_id = ?
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointments_stmt = $connection->prepare($appointments_query);
$appointments_stmt->bind_param('ii', $patient_id, $dentist_id);
$appointments_stmt->execute();
$appointments_history = $appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$title = "Медицинская карта - " . $patient['patient_full_name'];
include_once 'templates/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <h1>
                Медицинская карта
            </h1>
            <div class="header-actions">
                <a href="dentist-profile.php" class="btn btn-secondary">
                    Назад
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success"><?= $_SESSION['success'] ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="medical-card">
            <div class="card patient-info-card">
                <div class="medical-card-header">
                    <h2>
                        <img src="img/user-solid-full.svg" alt="Пациент" width="30px">
                        Информация о пациенте
                    </h2>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-row">
                            <span class="info-label">ФИО:</span>
                            <span class="info-value"><?= htmlspecialchars($patient['patient_full_name']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Телефон:</span>
                            <span class="info-value"><?= htmlspecialchars($patient['patient_phone_number']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Email:</span>
                            <span class="info-value"><?= htmlspecialchars($patient['patient_email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Пол:</span>
                            <span
                                class="info-value"><?= $patient['patient_gender'] == 'male' ? 'Мужской' : 'Женский' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Дата рождения:</span>
                            <span
                                class="info-value"><?= date('d.m.Y', strtotime($patient['patient_birth_date'])) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Полис:</span>
                            <span
                                class="info-value"><?= htmlspecialchars($patient['patient_insurance_number'] ?? '—') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($appointment_id): ?>
                <form method="POST" class="medical-card-form">
                    <div class="card">
                        <div class="medical-card-header">
                            <h2>
                                <img src="img/stethoscope-solid.svg" alt="Стетоскоп" width="30px">
                                <?= $current_entry ? 'Редактирование' : 'Заполнение' ?> записи приема от
                                <?= date('d.m.Y', strtotime($current_appointment_date)) ?>
                            </h2>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Дата приема:</label>
                                <div class="form-control-plaintext">
                                    <strong><?= date('d.m.Y', strtotime($current_appointment_date)) ?></strong>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="diagnosis">Диагноз <span class="text-danger">*</span></label>
                                <textarea name="diagnosis" id="diagnosis" rows="4" class="form-control"
                                    required><?= htmlspecialchars($current_entry['medical_card_diagnosis'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="treatment_plan">План лечения <span class="text-danger">*</span></label>
                                <textarea name="treatment_plan" id="treatment_plan" rows="4" class="form-control"
                                    required><?= htmlspecialchars($current_entry['medical_card_treatment_plan'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="prescriptions">Назначения</label>
                                <textarea name="prescriptions" id="prescriptions" rows="3"
                                    class="form-control"><?= htmlspecialchars($current_entry['medical_card_prescriptions'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="recommendations">Рекомендации</label>
                                <textarea name="recommendations" id="recommendations" rows="3"
                                    class="form-control"><?= htmlspecialchars($current_entry['medical_card_recommendations'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <?= $current_entry ? 'Сохранить изменения' : 'Сохранить и закрыть' ?>
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($medical_history)): ?>
                <div class="card history-card">
                    <div class="medical-card-header">
                        <h2>
                            <img src="img/history-solid.svg" alt="История" width="25px">
                            История посещений
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($medical_history as $entry): ?>
                            <div class="history-entry">
                                <div class="entry-header">
                                    <span class="entry-date">
                                        <?= date('d.m.Y', strtotime($entry['appointment_date'])) ?>
                                    </span>
                                    <span class="entry-doctor">
                                        <img src="img/user-md.svg" alt="Врач" width="15px">
                                        <?php
                                        if ($entry['dentist_id'] == $dentist_id) {
                                            echo 'Вы';
                                        } else {
                                            echo htmlspecialchars($entry['dentist_full_name'] ?? 'Врач');
                                        }
                                        ?>
                                    </span>
                                    <?php if ($entry['appointment_id'] == $appointment_id): ?>
                                        <span class="badge badge-current">Текущий прием</span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($entry['medical_card_diagnosis'])): ?>
                                    <div class="entry-diagnosis">
                                        <strong>Диагноз:</strong>
                                        <?= nl2br(htmlspecialchars($entry['medical_card_diagnosis'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($entry['medical_card_treatment_plan'])): ?>
                                    <div class="entry-treatment">
                                        <strong>План лечения:</strong>
                                        <?= nl2br(htmlspecialchars($entry['medical_card_treatment_plan'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($entry['prescriptions'])): ?>
                                    <div class="entry-prescriptions">
                                        <strong>Назначения:</strong>
                                        <?= nl2br(htmlspecialchars($entry['prescriptions'])) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($entry['recommendations'])): ?>
                                    <div class="entry-recommendations">
                                        <strong>Рекомендации:</strong>
                                        <?= nl2br(htmlspecialchars($entry['recommendations'])) ?>
                                    </div>
                                <?php endif; ?>

                                <div class="entry-footer">
                                    <small class="text-muted">
                                        Запись создана: <?= date('d.m.Y H:i', strtotime($entry['created_at'])) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($appointments_history)): ?>
                <div class="card appointments-card">
                    <div class="medical-card-header">
                        <h2>
                            <img src="img/calendar.svg" alt="Календарь" width="25px">
                            Приемы
                        </h2>
                    </div>
                    <div class="card-body">
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Время</th>
                                    <th>Услуга</th>
                                    <th>Статус</th>
                                    <th>Медкарта</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($appointments_history as $app): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($app['appointment_date'])) ?></td>
                                        <td><?= date('H:i', strtotime($app['appointment_time'])) ?></td>
                                        <td><?= htmlspecialchars($app['service_name'] ?? 'Консультация') ?></td>
                                        <td>
                                            <span class="badge badge-<?= $app['appointment_status'] ?>">
                                                <?php
                                                $statuses = [
                                                    'pending' => 'Ожидание',
                                                    'confirmed' => 'Подтверждено',
                                                    'completed' => 'Завершено',
                                                    'cancelled' => 'Отменено'
                                                ];
                                                echo $statuses[$app['appointment_status']] ?? $app['appointment_status'];
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            // Проверяем существование записи
                                            $entry_check_query = "SELECT medical_card_id FROM medical_cards 
                                            WHERE patient_id = ? AND appointment_id = ?";
                                            $entry_check_stmt = $connection->prepare($entry_check_query);
                                            $entry_check_stmt->bind_param('ii', $patient_id, $app['appointment_id']);
                                            $entry_check_stmt->execute();
                                            $entry_exists = $entry_check_stmt->get_result()->fetch_assoc();

                                            // Проверяем возможность редактирования
                                            $can_edit = false;
                                            if (in_array($app['appointment_status'], ['confirmed', 'completed'])) {
                                                $appointment_date = $app['appointment_date'];
                                                $current_date = date('Y-m-d');
                                                if ($appointment_date <= $current_date) {
                                                    $can_edit = true;
                                                }
                                            }
                                            ?>

                                            <?php if ($can_edit && $entry_exists): ?>
                                                <a href="medical-card.php?patient=<?= $patient_id ?>&appointment=<?= $app['appointment_id'] ?>"
                                                    class="btn btn-sm <?= $app['appointment_id'] == $appointment_id ? 'btn-primary' : 'btn-info' ?>">
                                                    <?= $app['appointment_id'] == $appointment_id ? 'Редактируется' : 'Редактировать' ?>
                                                </a>
                                            <?php elseif ($can_edit && !$entry_exists): ?>
                                                <a href="medical-card.php?patient=<?= $patient_id ?>&appointment=<?= $app['appointment_id'] ?>"
                                                    class="btn btn-sm <?= $app['appointment_id'] == $appointment_id ? 'btn-primary' : 'btn-secondary' ?>">
                                                    <?= $app['appointment_id'] == $appointment_id ? 'Текущий' : 'Заполнить' ?>
                                                </a>
                                            <?php elseif (!$can_edit && $entry_exists): ?>
                                                <span class="btn btn-sm btn-disabled" style="pointer-events: none; opacity: 0.6;">
                                                    Заполнено
                                                </span>
                                            <?php else: ?>
                                                <span class="btn btn-sm btn-disabled" style="pointer-events: none; opacity: 0.6;">
                                                    Недоступно
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include_once 'templates/footer.php'; ?>