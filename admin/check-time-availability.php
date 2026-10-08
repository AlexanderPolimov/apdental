<?php
require_once '../includes/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['administrator_id'])) {
    echo json_encode(['available' => false, 'message' => 'Не авторизован']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$appointment_id = $input['appointment_id'] ?? 0;
$date = $input['date'] ?? null;
$time = $input['time'] ?? null;

if (!$appointment_id || !$date || !$time) {
    echo json_encode(['available' => false, 'message' => 'Некорректные данные']);
    exit;
}

// Получаем текущую запись с категорией услуги и длительностью
$sql = "SELECT a.*, s.service_duration, s.service_category_id, d.dentist_id 
        FROM appointments a 
        LEFT JOIN services s ON a.service_id = s.service_id 
        LEFT JOIN dentists d ON a.dentist_id = d.dentist_id 
        WHERE a.appointment_id = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$appointment = mysqli_fetch_assoc($result);

if (!$appointment) {
    echo json_encode(['available' => false, 'message' => 'Запись не найдена']);
    exit;
}

$duration = $appointment['service_duration'] ?? 60;
$dentist_id = $appointment['dentist_id'];
$category_id = $appointment['service_category_id'];
$WORK_START = '09:00:00';
$WORK_END = '21:00:00';
$MIN_BREAK_MINUTES = 30;

$time_start = $time . ':00';
$hour = (int) substr($time, 0, 2);

if ($hour < 9 || $hour > 20) {
    echo json_encode(['available' => false, 'message' => 'Часы работы: с 09:00 до 21:00 (последняя запись в 20:00)', 'current_time' => date('H:i', strtotime($appointment['appointment_time']))]);
    exit;
}

$time_end = date('H:i:s', strtotime("+{$duration} minutes", strtotime($time_start)));

if (strtotime($time_end) > strtotime($WORK_END)) {
    echo json_encode(['available' => false, 'message' => "Время окончания услуги выходит за рабочие часы (до {$WORK_END})", 'current_time' => date('H:i', strtotime($appointment['appointment_time']))]);
    exit;
}

// ПРОВЕРКА ЗАНЯТОСТИ ВРАЧА
$check_sql = "SELECT TIME_FORMAT(a.appointment_time, '%H:%i') as appointment_time, s.service_duration 
              FROM appointments a 
              LEFT JOIN services s ON a.service_id = s.service_id 
              WHERE a.dentist_id = ? 
              AND a.appointment_date = ? 
              AND a.appointment_id != ?
              AND a.appointment_status NOT IN ('cancelled', 'отменен')";

$check_stmt = mysqli_prepare($connection, $check_sql);
mysqli_stmt_bind_param($check_stmt, "isi", $dentist_id, $date, $appointment_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);

$new_start = strtotime($time_start);
$new_end = strtotime($time_end);

while ($existing = mysqli_fetch_assoc($check_result)) {
    $existing_start = strtotime($existing['appointment_time'] . ':00');
    $existing_duration = $existing['service_duration'] ?? 60;
    $existing_end = strtotime("+{$existing_duration} minutes", $existing_start);

    // ПРОВЕРКА ПЕРЕСЕЧЕНИЯ ИНТЕРВАЛОВ
    $is_overlap = ($new_start >= $existing_start && $new_start < $existing_end) ||
        ($new_end > $existing_start && $new_end <= $existing_end) ||
        ($new_start <= $existing_start && $new_end >= $existing_end);

    if ($is_overlap) {
        echo json_encode([
            'available' => false,
            'message' => "Время пересекается с записью: " . date('H:i', $existing_start) . "-" . date('H:i', $existing_end),
            'current_time' => date('H:i', strtotime($appointment['appointment_time']))
        ]);
        exit;
    }

    // Проверяем перерыв между записями
    $time_diff = $new_start - $existing_end;
    if ($time_diff > 0 && $time_diff < ($MIN_BREAK_MINUTES * 60)) {
        echo json_encode([
            'available' => false,
            'message' => "Недостаточный перерыв после записи в " . date('H:i', $existing_start) . " (нужно минимум {$MIN_BREAK_MINUTES} минут)",
            'current_time' => date('H:i', strtotime($appointment['appointment_time']))
        ]);
        exit;
    }

    // Проверяем перерыв перед записью
    $time_diff_before = $existing_start - $new_end;
    if ($time_diff_before > 0 && $time_diff_before < ($MIN_BREAK_MINUTES * 60)) {
        echo json_encode([
            'available' => false,
            'message' => "Недостаточный перерыв перед записью в " . date('H:i', $existing_start) . " (нужно минимум {$MIN_BREAK_MINUTES} минут)",
            'current_time' => date('H:i', strtotime($appointment['appointment_time']))
        ]);
        exit;
    }
}

// ПРОВЕРКА ЗАНЯТОСТИ КАБИНЕТОВ
if ($category_id) {
    $cabinet_check_sql = "
        SELECT a.appointment_id, TIME_FORMAT(a.appointment_time, '%H:%i') as appointment_time, 
               s.service_duration, a.cabinet_id
        FROM appointments a
        LEFT JOIN services s ON a.service_id = s.service_id
        INNER JOIN cabinets c ON a.cabinet_id = c.cabinet_id
        INNER JOIN cabinets_service_categories csc ON c.cabinet_id = csc.cabinet_id
        WHERE a.appointment_date = ?
        AND a.appointment_id != ?
        AND a.appointment_status NOT IN ('cancelled', 'отменен')
        AND csc.service_category_id = ?
    ";

    $cabinet_stmt = mysqli_prepare($connection, $cabinet_check_sql);
    mysqli_stmt_bind_param($cabinet_stmt, "sii", $date, $appointment_id, $category_id);
    mysqli_stmt_execute($cabinet_stmt);
    $cabinet_result = mysqli_stmt_get_result($cabinet_stmt);

    // Получаем общее количество кабинетов для этой категории
    $total_cabinets_sql = "
        SELECT COUNT(*) as total 
        FROM cabinets c
        INNER JOIN cabinets_service_categories csc ON c.cabinet_id = csc.cabinet_id
        WHERE csc.service_category_id = ?
    ";
    $total_stmt = mysqli_prepare($connection, $total_cabinets_sql);
    mysqli_stmt_bind_param($total_stmt, "i", $category_id);
    mysqli_stmt_execute($total_stmt);
    $total_result = mysqli_stmt_get_result($total_stmt);
    $total_row = mysqli_fetch_assoc($total_result);
    $total_cabinets = $total_row['total'] ?? 1;
    mysqli_stmt_close($total_stmt);

    // Собираем все занятые кабинеты на это время с учетом длительности
    $busy_cabinets = [];
    while ($existing = mysqli_fetch_assoc($cabinet_result)) {
        $existing_start = strtotime($existing['appointment_time'] . ':00');
        $existing_duration = $existing['service_duration'] ?? 60;
        $existing_end = strtotime("+{$existing_duration} minutes", $existing_start);

        // Проверяем пересечение по времени
        if (
            ($new_start >= $existing_start && $new_start < $existing_end) ||
            ($new_end > $existing_start && $new_end <= $existing_end) ||
            ($new_start <= $existing_start && $new_end >= $existing_end)
        ) {
            $busy_cabinets[] = $existing['cabinet_id'];
        }
    }
    mysqli_stmt_close($cabinet_stmt);

    // Получаем все кабинеты для этой категории
    $cabinets_sql = "
        SELECT c.cabinet_id
        FROM cabinets c
        INNER JOIN cabinets_service_categories csc ON c.cabinet_id = csc.cabinet_id
        WHERE csc.service_category_id = ?
    ";
    $cabinets_stmt = mysqli_prepare($connection, $cabinets_sql);
    mysqli_stmt_bind_param($cabinets_stmt, "i", $category_id);
    mysqli_stmt_execute($cabinets_stmt);
    $cabinets_result = mysqli_stmt_get_result($cabinets_stmt);

    $available_cabinets = [];
    while ($cab = mysqli_fetch_assoc($cabinets_result)) {
        if (!in_array($cab['cabinet_id'], $busy_cabinets)) {
            $available_cabinets[] = $cab['cabinet_id'];
        }
    }
    mysqli_stmt_close($cabinets_stmt);

    if (count($available_cabinets) == 0) {
        echo json_encode([
            'available' => false,
            'message' => "Нет свободных кабинетов на это время",
            'current_time' => date('H:i', strtotime($appointment['appointment_time']))
        ]);
        exit;
    }
}

echo json_encode(['available' => true, 'message' => 'Время доступно']);
?>

<script src="../js/script.js"></script>