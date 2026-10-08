<?php
require_once 'includes/init.php';

// Проверяем, авторизован ли пользователь
if (!is_auth() && !isset($_SESSION['administrator_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Получаем параметры
$dentist_id = isset($_GET['dentist_id']) ? (int) $_GET['dentist_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$service_id = isset($_GET['service_id']) ? (int) $_GET['service_id'] : 0;

// Валидация
if ($dentist_id <= 0 || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

// Проверяем формат даты
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format']);
    exit;
}

// Устанавливаем заголовок JSON
header('Content-Type: application/json');

// Массив с доступным временем (без секунд для отображения)
$all_times = [];
for ($hour = 9; $hour <= 20; $hour++) {
    $all_times[] = sprintf('%02d:00', $hour);
    $all_times[] = sprintf('%02d:30', $hour);
}

// Получаем длительность услуги
$duration_minutes = 60; // значение по умолчанию
if ($service_id > 0) {
    $duration_query = "SELECT service_duration FROM services WHERE service_id = ?";
    $stmt = $connection->prepare($duration_query);
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $duration_result = $stmt->get_result();
    $service = $duration_result->fetch_assoc();
    $duration_minutes = $service['service_duration'] ?? 60;
    $stmt->close();
}

// Получаем занятое время у конкретного врача с учетом длительности услуг
$busy_periods = [];
$busy_query = "
    SELECT a.appointment_time, s.service_duration 
    FROM appointments a
    LEFT JOIN services s ON a.service_id = s.service_id
    WHERE a.dentist_id = ? 
    AND a.appointment_date = ?
    AND a.appointment_status NOT IN ('cancelled', 'отменен')
";

$stmt = $connection->prepare($busy_query);
$stmt->bind_param('is', $dentist_id, $date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $start = strtotime($row['appointment_time']);
    $duration = $row['service_duration'] ?? 60;
    $end = strtotime("+{$duration} minutes", $start);
    $busy_periods[] = ['start' => $start, 'end' => $end];
}
$stmt->close();

// Получаем общее количество кабинетов
$total_cabinets_query = "SELECT COUNT(*) as total FROM cabinets";
$total_result = mysqli_query($connection, $total_cabinets_query);
$total_cabinets = mysqli_fetch_assoc($total_result)['total'];

// Получаем занятые кабинеты на это время (преобразуем в формат H:i)
$busy_cabinets_by_time = [];
$placeholders = implode(',', array_fill(0, count($all_times), '?'));
$cabinets_query = "
    SELECT TIME_FORMAT(appointment_time, '%H:%i') as appointment_time, COUNT(*) as busy_count
    FROM appointments 
    WHERE appointment_date = ? 
    AND appointment_time IN ($placeholders)
    AND appointment_status NOT IN ('cancelled', 'отменен')
    GROUP BY TIME_FORMAT(appointment_time, '%H:%i')
";

$types = str_repeat('s', count($all_times) + 1);
$params = array_merge([$date], $all_times);

$stmt = $connection->prepare($cabinets_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $busy_cabinets_by_time[$row['appointment_time']] = $row['busy_count'];
}
$stmt->close();

// Фильтруем свободное время
$free_times = [];
foreach ($all_times as $time) {
    $new_start = strtotime($time);
    $new_end = strtotime("+{$duration_minutes} minutes", $new_start);

    // Проверяем, не выходит ли за 21:00
    if ($new_end > strtotime('21:00:00')) {
        continue;
    }

    // Проверяем пересечение с занятыми периодами врача
    $is_busy = false;
    foreach ($busy_periods as $busy) {
        if (
            ($new_start >= $busy['start'] && $new_start < $busy['end']) ||
            ($new_end > $busy['start'] && $new_end <= $busy['end']) ||
            ($new_start <= $busy['start'] && $new_end >= $busy['end'])
        ) {
            $is_busy = true;
            break;
        }
    }

    // Проверяем, не занят ли врач
    if ($is_busy) {
        continue;
    }

    // Проверяем, есть ли свободные кабинеты
    $busy_count = isset($busy_cabinets_by_time[$time]) ? $busy_cabinets_by_time[$time] : 0;
    if ($busy_count >= $total_cabinets) {
        continue;
    }

    $free_times[] = $time;
}

echo json_encode(['times' => $free_times]);
?>