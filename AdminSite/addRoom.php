<?php
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accommodation_id = $_POST['accommodation_id'];
    $room_name = $_POST['room_name'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price_per_night'];
    $description = $_POST['description'];
    $amenities = $_POST['amenities'] ?? 'ไม่มี';

    // ถ้าผู้ใช้ป้อนเป็น JSON เช่น {"wifi": true} ให้แปลงให้เป็นข้อความก่อนเก็บ
    if (is_array($amenities)) {
        $amenities = json_encode($amenities, JSON_UNESCAPED_UNICODE);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO room_types 
            (accommodation_id, room_name, capacity, price_per_night, description, amenities)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$accommodation_id, $room_name, $capacity, $price, $description, $amenities]);

        header("Location: Accommodation.php");
        exit;
    } catch (PDOException $e) {
        echo "❌ Database Error: " . $e->getMessage();
    }
}
?>
