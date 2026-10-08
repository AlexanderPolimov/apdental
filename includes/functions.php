<?php

/**
 * Отменяет записи, которые находятся в статусе ожидания дольше 24 часов
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int|null $patient_id - ID пациента
 * @return int - количество отмененных записей
 */
function cancel_expired_appointments($connection, $patient_id = null)
{
    $expire_hours = 24;

    $query = "
        UPDATE appointments 
        SET appointment_status = 'cancelled'
        WHERE appointment_status = 'pending'
        AND appointment_created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
    ";

    $params = [$expire_hours];
    $types = "i";

    if ($patient_id) {
        $query .= " AND patient_id = ?";
        $params[] = $patient_id;
        $types .= "i";
    }

    $stmt = mysqli_prepare($connection, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    }

    return 0;
}

/**
 * Автоматически завершает записи, которые уже прошли и имеют статус 'подтверждено'
 * 
 * @param mysqli $connection - объект подключения к БД
 * @return int - количество завершенных записей
 */
function complete_passed_appointments($connection)
{
    $query = "
        UPDATE appointments 
        SET appointment_status = 'completed'
        WHERE appointment_status = 'confirmed' 
        AND appointment_date < CURDATE()
    ";

    $stmt = mysqli_prepare($connection, $query);
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $affected = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        return $affected;
    }

    return 0;
}

// ============ ФУНКЦИИ ДЛЯ ВРАЧЕЙ ============

/**
 * Получает список всех стоматологов с их специализациями
 * 
 * @param mysqli $connection - объект подключения к БД
 * @return array - массив всех стоматологов, каждый элемент - ассоциативный массив с данными врача
 */
function get_all_dentists($connection)
{
    $query = "
        SELECT 
            d.dentist_id,
            d.dentist_full_name,
            d.dentist_experience_years,
            d.dentist_photo,
            d.dentist_description,
            s.specialization_name
        FROM dentists d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        ORDER BY d.dentist_id
    ";

    $result = mysqli_query($connection, $query);
    if (!$result)
        return [];

    $dentists = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $dentists[] = $row;
    }
    return $dentists;
}

/**
 * Получает данные конкретного стоматолога по его ID
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int $dentist_id - ID стоматолога
 * @return array|null - ассоциативный массив с данными врача или null, если не найден
 */
function get_dentist_by_id($connection, $dentist_id)
{
    $stmt = mysqli_prepare($connection, "
        SELECT 
            d.dentist_id,
            d.dentist_full_name,
            d.dentist_phone_number,
            d.dentist_email,
            d.dentist_experience_years,
            d.dentist_description,
            d.dentist_photo,
            d.specialization_id,
            s.specialization_name
        FROM dentists d
        LEFT JOIN specializations s ON d.specialization_id = s.specialization_id
        WHERE d.dentist_id = ?
    ");
    if (!$stmt)
        return null;

    mysqli_stmt_bind_param($stmt, 'i', $dentist_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row;
}

// ============ ФУНКЦИИ ДЛЯ УСЛУГ ============

/**
 * Получает все категории услуг с минимальной ценой услуги в каждой категории
 * 
 * @param mysqli $connection - объект подключения к БД
 * @return array - массив категорий с их минимальными ценами
 */
function get_service_categories_with_min_price($connection)
{
    $query = "
        SELECT 
            sc.service_category_id,
            sc.service_category_name,
            MIN(s.service_cost) as min_price
        FROM service_categories sc
        LEFT JOIN services s ON sc.service_category_id = s.service_category_id
        WHERE s.service_cost > 0
        GROUP BY sc.service_category_id, sc.service_category_name
        ORDER BY sc.service_category_id
    ";

    $result = mysqli_query($connection, $query);
    if (!$result)
        return [];

    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

/**
 * Получает все услуги, принадлежащие определенной категории
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int $category_id - ID категории услуг
 * @return array - массив услуг в указанной категории
 */
function get_services_by_category($connection, $category_id)
{
    $stmt = mysqli_prepare($connection, "
        SELECT 
            service_id,
            service_name,
            service_description,
            service_cost,
            service_duration
        FROM services
        WHERE service_category_id = ? AND service_cost > 0
        ORDER BY service_cost
    ");
    if (!$stmt)
        return [];

    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $services = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $services;
}

/**
 * Получает информацию о категории услуг по её ID
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int $category_id - ID категории
 * @return array|null - ассоциативный массив с данными категории или null
 */
function get_service_category_by_id($connection, $category_id)
{
    $stmt = mysqli_prepare($connection, "
        SELECT 
            service_category_id,
            service_category_name
        FROM service_categories
        WHERE service_category_id = ?
    ");
    if (!$stmt)
        return null;

    mysqli_stmt_bind_param($stmt, 'i', $category_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row;
}

// ============ ФУНКЦИИ ДЛЯ ОТЗЫВОВ ============

/**
 * Получает все отзывы клиентов, отсортированные по дате добавления (сначала новые)
 * 
 * @param mysqli $connection - объект подключения к БД
 * @return array - массив всех отзывов
 */
function get_all_reviews($connection)
{
    $query = "
        SELECT 
            review_id,
            review_client_name,
            review_text,
            review_rating,
            review_date_added
        FROM reviews
        ORDER BY review_date_added DESC
    ";

    $result = mysqli_query($connection, $query);
    if (!$result)
        return [];

    $reviews = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $reviews[] = $row;
    }
    return $reviews;
}

// ============ ФУНКЦИИ ДЛЯ ОБОРУДОВАНИЯ ============

/**
 * Получает список всего медицинского оборудования
 * 
 * @param mysqli $connection - объект подключения к БД
 * @return array - массив с данными об оборудовании
 */
function get_all_equipment($connection)
{
    $query = "
        SELECT 
            equipment_id,
            equipment_name,
            equipment_description,
            equipment_specifications,
            equipment_photo
        FROM equipment
        ORDER BY equipment_id
    ";

    $result = mysqli_query($connection, $query);
    if (!$result)
        return [];

    $equipment = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $equipment[] = $row;
    }
    return $equipment;
}

/**
 * Получает данные конкретного оборудования по его ID
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int $equipment_id - ID оборудования
 * @return array|null - ассоциативный массив с данными оборудования или null
 */
function get_equipment_by_id($connection, $equipment_id)
{
    $stmt = mysqli_prepare($connection, "
        SELECT 
            equipment_id,
            equipment_name,
            equipment_description,
            equipment_specifications,
            equipment_photo
        FROM equipment
        WHERE equipment_id = ?
    ");
    if (!$stmt)
        return null;

    mysqli_stmt_bind_param($stmt, 'i', $equipment_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row;
}

// ============ ФУНКЦИИ ДЛЯ ПАЦИЕНТОВ ============

/**
 * Регистрирует нового пациента в системе
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param string $full_name - полное имя пациента
 * @param string $phone - номер телефона
 * @param string $email - Email
 * @param string $password - пароль (будет захеширован)
 * @param string $gender - пол ('male' или 'female')
 * @param string|null $birth_date - дата рождения (YYYY-MM-DD)
 * @param string|null $insurance_number - номер страхового полиса
 * @return int|false - ID нового пациента или false при ошибке
 */
function register_patient($connection, $full_name, $phone, $email, $password, $gender, $birth_date = null, $insurance_number = null)
{
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = mysqli_prepare(
        $connection,
        'INSERT INTO patients (patient_full_name, patient_phone_number, patient_email,
         patient_password, patient_gender, patient_birth_date, patient_insurance_number)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    if (!$stmt)
        return false;

    mysqli_stmt_bind_param(
        $stmt,
        'sssssss',
        $full_name,
        $phone,
        $email,
        $hashed_password,
        $gender,
        $birth_date,
        $insurance_number
    );

    $ok = mysqli_stmt_execute($stmt);
    $id = $ok ? mysqli_insert_id($connection) : false;
    mysqli_stmt_close($stmt);

    return $id;
}

/**
 * Аутентифицирует пациента по email и паролю
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param string $email - Email пациента
 * @param string $password - пароль для проверки
 * @return array|false - ассоциативный массив с данными пациента (без пароля) или false
 */
function login_patient($connection, $email, $password)
{
    $stmt = mysqli_prepare(
        $connection,
        "SELECT patient_id, patient_full_name, patient_email, patient_password
    FROM patients WHERE patient_email = ?"
    );
    if (!$stmt)
        return false;
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $patient = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$patient)
        return false;
    if (password_verify($password, $patient['patient_password'])) {
        unset($patient['patient_password']);
        return $patient;
    }
    return false;
}

/**
 * Получает данные пациента по его ID
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int $patient_id - ID пациента
 * @return array|null - ассоциативный массив с данными пациента или null
 */
function get_patient_by_id($connection, $patient_id)
{
    $stmt = mysqli_prepare($connection, "
        SELECT 
            patient_id,
            patient_full_name,
            patient_phone_number,
            patient_email,
            patient_gender,
            patient_birth_date,
            patient_insurance_number
        FROM patients
        WHERE patient_id = ?
    ");
    if (!$stmt)
        return null;

    mysqli_stmt_bind_param($stmt, 'i', $patient_id);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    return $row;
}

// ============ ФУНКЦИИ ДЛЯ ЗАПИСЕЙ ============

/**
 * Создает новую запись на прием
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param string $date - дата приема (YYYY-MM-DD)
 * @param string $time - время приема (HH:MM:SS)
 * @param int $patient_id - ID пациента
 * @param int $dentist_id - ID стоматолога
 * @param int $service_id - ID услуги
 * @param int $cabinet_id - ID кабинета
 * @return int|false - ID созданной записи или false при ошибке
 */
function create_appointment($connection, $date, $time, $patient_id, $dentist_id, $service_id, $cabinet_id)
{
    $stmt = mysqli_prepare(
        $connection,
        "INSERT INTO appointments (appointment_date, appointment_time, appointment_status,
         patient_id, dentist_id, service_id, cabinet_id)
         VALUES (?, ?, 'pending', ?, ?, ?, ?)"
    );
    if (!$stmt)
        return false;

    mysqli_stmt_bind_param($stmt, 'ssiiii', $date, $time, $patient_id, $dentist_id, $service_id, $cabinet_id);

    $ok = mysqli_stmt_execute($stmt);
    $id = $ok ? mysqli_insert_id($connection) : false;
    mysqli_stmt_close($stmt);

    return $id;
}

/**
 * Получает все записи конкретного пациента с детальной информацией
 * 
 * @param mysqli $connection - объект подключения к БД
 * @param int $patient_id - ID пациента
 * @return array - массив записей пациента
 */
function get_patient_appointments($connection, $patient_id)
{
    $stmt = mysqli_prepare($connection, "
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.appointment_status,
            a.appointment_cost,
            d.dentist_full_name,
            s.service_name,
            c.cabinet_name
        FROM appointments a
        LEFT JOIN dentists d  ON a.dentist_id  = d.dentist_id
        LEFT JOIN services s  ON a.service_id  = s.service_id
        LEFT JOIN cabinets c  ON a.cabinet_id   = c.cabinet_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    if (!$stmt)
        return [];

    mysqli_stmt_bind_param($stmt, 'i', $patient_id);
    mysqli_stmt_execute($stmt);
    $appointments = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    return $appointments;
}

// ============ ФУНКЦИИ ДЛЯ КАБИНЕТОВ ============

/**
 * Получает список всех медицинских кабинетов
 * 
 * @param mysqli $connection - объект подключения к БД
 * @return array - массив кабинетов (каждый содержит id и название)
 */
function get_all_cabinets($connection)
{
    $query = "
        SELECT 
            cabinet_id,
            cabinet_name
        FROM cabinets
        ORDER BY cabinet_id
    ";

    $result = mysqli_query($connection, $query);
    if (!$result)
        return [];

    $cabinets = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $cabinets[] = $row;
    }
    return $cabinets;
}

// ============ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ ============

/**
 * Проверяет, авторизован ли пациент в системе
 * 
 * @return bool true, если пациент авторизован (есть patient_id в сессии), иначе false
 */
function is_auth()
{
    return isset($_SESSION['patient_id']);
}

/**
 * Форматирует цену в удобный для чтения вид с пробелами и знаком рубля
 * 
 * @param float|int $price - цена для форматирования
 * @return string - отформатированная цена (например: "1 500 ₽")
 */
function format_price($price)
{
    return number_format($price, 0, ',', ' ') . ' ₽';
}

/**
 * Преобразует дату в формате YYYY-MM-DD в русский формат "день месяц год"
 * 
 * @param string $date_string - дата в формате YYYY-MM-DD
 * @return string - дата в русском формате (например: "15 января 2024")
 */
function format_russian_date($date_string)
{
    $timestamp = strtotime($date_string);
    $day = date('d', $timestamp);
    $month_num = date('n', $timestamp);
    $year = date('Y', $timestamp);

    $months = [
        1 => 'января',
        2 => 'февраля',
        3 => 'марта',
        4 => 'апреля',
        5 => 'мая',
        6 => 'июня',
        7 => 'июля',
        8 => 'августа',
        9 => 'сентября',
        10 => 'октября',
        11 => 'ноября',
        12 => 'декабря'
    ];

    return $day . ' ' . $months[$month_num] . ' ' . $year;
}

/**
 * Возвращает сокращенное название месяца на русском языке по его номеру
 * 
 * @param int $month_num - номер месяца
 * @return string - сокращенное название месяца
 */
function get_month_name($month_num)
{
    $months = [
        1 => 'Янв',
        2 => 'Фев',
        3 => 'Мар',
        4 => 'Апр',
        5 => 'Мая',
        6 => 'Июн',
        7 => 'Июл',
        8 => 'Авг',
        9 => 'Сен',
        10 => 'Окт',
        11 => 'Ноя',
        12 => 'Дек'
    ];
    return $months[$month_num];
}

/**
 * Экранирует HTML-спецсимволы для безопасного вывода данных
 */
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
?>