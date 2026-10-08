<?php
require_once '../includes/init.php';

if (!isset($_SESSION['administrator_id'])) {
    if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    } else {
        header('Location: login.php');
        exit;
    }
}

// Конфигурация рабочего времени
$WORK_START = '09:00:00';
$WORK_END = '21:00:00';
$MIN_BREAK_MINUTES = 30; // Минимальный перерыв

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    // JSON ЗАПРОС
    $input = json_decode(file_get_contents('php://input'), true);
    $appointment_id = $input['appointment_id'] ?? null;
    $changes = $input['changes'] ?? null;

    if (!$appointment_id || empty($changes)) {
        echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
        exit;
    }

    // Получаем текущую запись для проверок
    $current_sql = "SELECT a.*, s.service_duration, s.service_name, d.dentist_id 
                    FROM appointments a 
                    LEFT JOIN services s ON a.service_id = s.service_id
                    LEFT JOIN dentists d ON a.dentist_id = d.dentist_id
                    WHERE a.appointment_id = ?";
    $current_stmt = mysqli_prepare($connection, $current_sql);
    mysqli_stmt_bind_param($current_stmt, "i", $appointment_id);
    mysqli_stmt_execute($current_stmt);
    $current_result = mysqli_stmt_get_result($current_stmt);
    $current_appointment = mysqli_fetch_assoc($current_result);

    if (!$current_appointment) {
        echo json_encode(['success' => false, 'error' => 'Запись не найдена']);
        exit;
    }

    $response = ['success' => true, 'errors' => []];

    foreach ($changes as $field => $value) {
        $db_field = '';
        switch ($field) {
            case 'date':
                $db_field = 'appointment_date';
                $value = date('Y-m-d', strtotime($value));
                break;
            case 'time':
                $db_field = 'appointment_time';
                // Проверяем время с учетом длительности услуги
                $time_to_check = $value;
                $date_to_check = $changes['date'] ?? $current_appointment['appointment_date'];

                $validation_result = validateAppointmentTime(
                    $connection,
                    $current_appointment['dentist_id'],
                    $date_to_check,
                    $time_to_check,
                    $current_appointment['service_duration'] ?? 60,
                    $appointment_id
                );

                if ($validation_result !== true) {
                    echo json_encode(['success' => false, 'error' => $validation_result]);
                    exit;
                }
                break;
            case 'status':
                $db_field = 'appointment_status';
                if (!in_array($value, ['pending', 'confirmed', 'completed', 'cancelled'])) {
                    echo json_encode(['success' => false, 'error' => 'Неверный статус']);
                    exit;
                }
                break;
            default:
                continue 2;
        }

        $sql = "UPDATE appointments SET $db_field = ? WHERE appointment_id = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "si", $value, $appointment_id);

        if (!mysqli_stmt_execute($stmt)) {
            $response['success'] = false;
            $response['errors'][$field] = 'Ошибка обновления';
        }
    }

    echo json_encode($response);

} else {
    // FORM DATA (только статус из выпадающего списка)
    $appointment_id = $_POST['appointment_id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$appointment_id || !$status) {
        $_SESSION['error'] = 'Некорректные данные';
        header('Location: appointments.php');
        exit;
    }

    if (!in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
        $_SESSION['error'] = 'Неверный статус';
        header('Location: appointments.php');
        exit;
    }

    $sql = "UPDATE appointments SET appointment_status = ? WHERE appointment_id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $appointment_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success'] = 'Статус успешно обновлен';
    } else {
        $_SESSION['error'] = 'Ошибка при обновлении статуса';
    }

    header('Location: appointments.php');
    exit;
}

/**
 * Функция проверки времени записи
 */

function validateAppointmentTime($connection, $dentist_id, $date, $time, $duration_minutes, $exclude_appointment_id = 0)
{
    global $WORK_START, $WORK_END, $MIN_BREAK_MINUTES;

    // Проверяем формат времени
    if (!preg_match('/^([0-1][0-9]|2[0-3]):([0-5][0-9])$/', $time)) {
        return 'Неверный формат времени';
    }

    $time_start = $time . ':00';
    $hour = (int) substr($time, 0, 2);

    // Проверка рабочего времени начала
    if ($hour < 9 || $hour > 20) {
        return 'Часы работы: с 09:00 до 21:00 (последняя запись в 20:00)';
    }

    // Вычисляем время окончания
    $time_end = date('H:i:s', strtotime("+{$duration_minutes} minutes", strtotime($time_start)));

    // Проверяем, что запись не выходит за рабочее время
    if (strtotime($time_end) > strtotime($WORK_END)) {
        return "Время окончания услуги выходит за рабочие часы (до {$WORK_END})";
    }

    // Проверяем пересечения с другими записями
    $check_sql = "SELECT a.*, s.service_duration 
                  FROM appointments a 
                  LEFT JOIN services s ON a.service_id = s.service_id 
                  WHERE a.dentist_id = ? 
                  AND a.appointment_date = ? 
                  AND a.appointment_id != ?
                  AND a.appointment_status NOT IN ('cancelled')
                  ORDER BY a.appointment_time";

    $check_stmt = mysqli_prepare($connection, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "isi", $dentist_id, $date, $exclude_appointment_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    $new_start = strtotime($time_start);
    $new_end = strtotime($time_end);

    $conflicts = [];

    while ($existing = mysqli_fetch_assoc($check_result)) {
        $existing_start = strtotime($existing['appointment_time']);
        $existing_duration = $existing['service_duration'] ?? 60;
        $existing_end = strtotime("+{$existing_duration} minutes", $existing_start);

        // Проверяем пересечение интервалов
        if (
            ($new_start >= $existing_start && $new_start < $existing_end) ||
            ($new_end > $existing_start && $new_end <= $existing_end) ||
            ($new_start <= $existing_start && $new_end >= $existing_end)
        ) {

            $conflicts[] = date('H:i', $existing_start) . ' - ' . date('H:i', $existing_end);
        }

        // Проверяем перерыв между записями
        $time_diff_prev = $new_start - $existing_end;
        if ($time_diff_prev > 0 && $time_diff_prev < ($MIN_BREAK_MINUTES * 60)) {
            $conflicts[] = "недостаточный перерыв после записи в " . date('H:i', $existing_start) .
                " (нужно минимум {$MIN_BREAK_MINUTES} минут)";
        }

        // Проверяем перерыв перед следующей записью
        if (isset($next_start)) {
            $time_diff_next = $existing_start - $new_end;
            if ($time_diff_next > 0 && $time_diff_next < ($MIN_BREAK_MINUTES * 60)) {
                $conflicts[] = "недостаточный перерыв перед записью в " . date('H:i', $existing_start) .
                    " (нужно минимум {$MIN_BREAK_MINUTES} минут)";
            }
        }
    }

    if (!empty($conflicts)) {
        return "Выбранное время конфликтует с другими записями: " . implode(', ', $conflicts);
    }

    return true;
}
?>