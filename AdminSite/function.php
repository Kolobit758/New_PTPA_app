<?php
include "../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Add_accommodation'])) {
    echo "Hello World"; // ทดสอบดูว่าเข้ามาจริงไหม

    $name = $_POST['name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longtitude'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $rating = $_POST['rating'];
    $topics = $_POST['topics'] ?? [];

    // ตัวอย่าง insert
    $sql = "INSERT INTO accommodations (name, latitude, longitude, address, phone,rating)
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $latitude, $longitude, $address, $phone, $rating]);

    echo "Data added successfully!";

    header("location: Accomodation.php");
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Add_attraction'])) {
    $attraction_type  = $_POST['attraction_type'];
    $attraction_categories = $_POST['attraction_categories'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longtitude'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $rating = $_POST['rating'];
    $historical_info = $_POST['historical_info'];
    $Website = $_POST['website'];
    $opening_time = $_POST['opening_time'];
    $close_time = $_POST['close_time'];
    $entrance_fee = $_POST['entrance_fee'];

    $sql = "INSERT INTO attractions (name, latitude, longitude, address, phone,rating,description,historical_info,website,opening_time,close_time,entrance_fee)
            VALUES (?, ?, ?, ?, ?, ?,?,?,?,?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $name,
        $latitude,
        $longitude,
        $address,
        $phone,
        $rating,
        $description,
        $historical_info,
        $Website,
        $opening_time,
        $close_time,
        $entrance_fee
    ]);
}// 🟢 Update attraction
elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Update_attraction'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $address = $_POST['address'];
    $phone = $_POST['phone'];
    $rating = $_POST['rating'];
    $opening_time = $_POST['opening_time'];
    $close_time = $_POST['close_time'];
    $entrance_fee = $_POST['entrance_fee'];

    $sql = "UPDATE attractions 
            SET name = ?, description = ?, address = ?, phone = ?, rating = ?, 
                opening_time = ?, close_time = ?, entrance_fee = ?
            WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $address, $phone, $rating, $opening_time, $close_time, $entrance_fee, $id]);

    header("Location: Attraction.php");
    exit;
}

