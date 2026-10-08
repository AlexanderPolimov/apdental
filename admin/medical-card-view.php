<?php
require_once '../includes/init.php';

if (!isset($_SESSION['administrator_id'])) {
    header('Location: login.php');
    exit;
}

$patient_id = $_GET['patient_id'] ?? 0;

if (!$patient_id) {
    $_SESSION['error'] = "Пациент не указан";
    header('Location: patients.php');
    exit;
}

// Получаем данные пациента
$patient_query = "SELECT * FROM patients WHERE patient_id = ?";
$patient_stmt = $connection->prepare($patient_query);
$patient_stmt->bind_param('i', $patient_id);
$patient_stmt->execute();
$patient = $patient_stmt->get_result()->fetch_assoc();

if (!$patient) {
    $_SESSION['error'] = "Пациент не найден";
    header('Location: patients.php');
    exit;
}

// Получаем ВСЕ записи медкарты для этого пациента
$history_query = "SELECT mc.*, a.appointment_date, a.appointment_time, s.service_name,
                         d.dentist_full_name, d.dentist_id
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

$title = "Медицинская карта - " . $patient['patient_full_name'];
include_once 'admin-header.php';
?>

<main class="admin-container">
    <div class="admin-page-header">
        <div class="admin-page-title">
            <h2>
                <img src="../img/user-solid-full.svg" alt="Пациент" width="30px">
                Информация о пациенте
            </h2>
            <div class="header-actions">
                <a href="patients.php" class="btn btn-secondary">← Назад к пациентам</a>
                <a href="appointment-edit.php?patient_id=<?= $patient_id ?>" class="btn btn-primary">
                    ➕ Новая запись
                </a>
            </div>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-header">
            <h3>Информация о пациенте</h3>
        </div>
        <div class="admin-card-body">
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
                    <span class="info-value"><?= $patient['patient_gender'] == 'male' ? 'Мужской' : 'Женский' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Дата рождения:</span>
                    <span class="info-value"><?= date('d.m.Y', strtotime($patient['patient_birth_date'])) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Полис:</span>
                    <span class="info-value"><?= htmlspecialchars($patient['patient_insurance_number'] ?? '—') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($medical_history)): ?>
        <?php foreach ($medical_history as $entry): ?>
            <div class="admin-card medical-card-view-mt-20">
                <div class="admin-card-header medical-card-view-card-header">
                    <h3>
                        <img src="../img/stethoscope-solid.svg" alt="Стетоскоп" width="30px">
                        Прием от <?= date('d.m.Y', strtotime($entry['appointment_date'])) ?>
                        <small class="medical-card-view-doctor-small">
                            Врач: <?= htmlspecialchars($entry['dentist_full_name'] ?? '—') ?>
                        </small>
                    </h3>

                    <?php if (!empty($entry['medical_card_treatment_plan'])): ?>
                        <a href="appointment-edit.php?from_medcard=1&patient_id=<?= $patient_id ?>&treatment_plan=<?= urlencode($entry['medical_card_treatment_plan']) ?>&diagnosis=<?= urlencode($entry['medical_card_diagnosis']) ?>"
                            class="btn btn-primary">
                            📅 Создать запись на основе плана лечения
                        </a>
                    <?php endif; ?>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($entry['medical_card_diagnosis'])): ?>
                        <div class="medical-card-view-mb-15">
                            <strong>🔍 Диагноз:</strong>
                            <div class="medical-card-view-diagnosis">
                                <?= nl2br(htmlspecialchars($entry['medical_card_diagnosis'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($entry['medical_card_treatment_plan'])): ?>
                        <div class="medical-card-view-mb-15">
                            <strong>📋 План лечения:</strong>
                            <div class="medical-card-view-treatment">
                                <?= nl2br(htmlspecialchars($entry['medical_card_treatment_plan'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($entry['medical_card_prescriptions'])): ?>
                        <div class="medical-card-view-mb-15">
                            <strong>💊 Назначения:</strong>
                            <div class="medical-card-view-prescriptions">
                                <?= nl2br(htmlspecialchars($entry['medical_card_prescriptions'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($entry['medical_card_recommendations'])): ?>
                        <div class="medical-card-view-mb-15">
                            <strong>📝 Рекомендации:</strong>
                            <div class="medical-card-view-recommendations">
                                <?= nl2br(htmlspecialchars($entry['medical_card_recommendations'])) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="medical-card-view-footer">
                        <small class="text-muted">
                            Запись создана: <?= date('d.m.Y H:i', strtotime($entry['created_at'])) ?>
                        </small>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="admin-card">
            <div class="admin-card-body medical-card-view-empty">
                <div class="medical-card-view-empty-icon">📭</div>
                <h3>Нет записей в медкарте</h3>
                <p>У этого пациента пока нет заполненных медицинских карт</p>
            </div>
        </div>
    <?php endif; ?>
</main>