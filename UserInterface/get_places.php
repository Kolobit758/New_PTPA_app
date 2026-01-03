<?php
require_once '../config/database.php';
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT id, name, latitude, longitude, historical_info FROM attractions");
$places = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $places[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'lat' => floatval($row['latitude']),
        'lng' => floatval($row['longitude']),
        'radius' => 100, // ปรับได้ตามต้องการ
        'message' => $row['historical_info']
        //ok ok
    ];
}

header('Content-Type: application/json');
echo json_encode($places);
