<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/init.php';

header('Content-Type: application/json');

if (!isset($_SESSION['administrator_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Просто показываем все услуги с категориями и кабинетами
$sql = "SELECT
            s.service_id as id,
            s.service_name as name,
            s.service_cost as cost,
            s.service_duration as duration,
            sc.service_category_id as category_id,
            sc.service_category_name as category_name,
            c.cabinet_id,
            c.cabinet_name
        FROM services s
        LEFT JOIN service_categories sc ON s.service_category_id = sc.service_category_id
        LEFT JOIN cabinets c ON sc.cabinet_id = c.cabinet_id
        ORDER BY sc.service_category_name, s.service_name";

$result = mysqli_query($connection, $sql);

if (!$result) {
    echo json_encode(['error' => mysqli_error($connection)]);
    exit;
}

$services = [];
while ($row = mysqli_fetch_assoc($result)) {
    $services[] = $row;
}

// Группируем по категориям
$categories = [];
foreach ($services as $service) {
    $cat_id = $service['category_id'] ?? 0;
    if (!isset($categories[$cat_id])) {
        $categories[$cat_id] = [
            'category_id' => $cat_id,
            'category_name' => $service['category_name'] ?? 'Без категории',
            'cabinet_id' => $service['cabinet_id'],
            'cabinet_name' => $service['cabinet_name'],
            'services' => []
        ];
    }
    $categories[$cat_id]['services'][] = [
        'id' => $service['id'],
        'name' => $service['name'],
        'cost' => $service['cost'],
        'duration' => $service['duration']
    ];
}

echo json_encode(['categories' => array_values($categories)]);
?>