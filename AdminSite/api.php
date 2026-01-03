<?php
include "../config/database.php";

header("Content-Type: application/json");
error_reporting(0); // ป้องกัน warning แทรกใน JSON

$method = $_SERVER['REQUEST_METHOD'];

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// if ($method == 'GET') {
//     if ($id) {
//         $stmt = $pdo->prepare("SELECT * FROM attractions WHERE id = ?");
//         $stmt->execute([$id]);
//         $data = $stmt->fetch(PDO::FETCH_ASSOC); // ← เอาเฉพาะ 1 row

//         if ($data) {
//             echo json_encode(["success" => true, "Datas" => $data]);
//         } else {
//             echo json_encode(["success" => false, "message" => "ไม่พบที่เที่ยว"]);
//         }
//     } else {
//         $stmt = $pdo->query("SELECT * FROM attractions ORDER BY id DESC");
//         $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
//         echo json_encode(["success" => true, "data" => $data]);
//     }
//     exit;
// }

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if ($method == 'GET') {
    if ($id) {
        $stmt = $pdo->prepare('SELECT * FROM attractions WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $data = $stmt->fetch();

        if ($data) {
            echo json_encode(["success" => true, "data" => $data]);
        } else {
            echo json_encode(["success" => false, "message" => 'ไม่พบที่เที่ยว']);
        }
    } else {
        echo json_encode(["success" => false, "message" => 'ไม่พบไอดี']);
    }
    exit;
}



if ($method === "POST" && isset($_POST['action'])) {

    if ($_POST['action'] === 'add_accommodation') {
        $name = $_POST['name'];
        $latitude = $_POST['latitude'];
        $longitude = $_POST['longitude'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $rating = $_POST['rating'];
        $type = $_POST['topics'];

        $stmt = $pdo->prepare("INSERT INTO accommodations (name, latitude, longitude, address, phone, rating,type) VALUES (?, ?, ?, ?, ?, ?,?)");
        $stmt->execute([$name, $latitude, $longitude, $address, $phone, $rating, $type]);

        echo json_encode([
            "success" => true,
            "message" => "เพิ่มข้อมูลเรียบร้อย",
            "id" => $pdo->lastInsertId()
        ]);
        exit; // ✅ หยุดการทำงานที่นี่
    }

    if ($_POST['action'] === 'add_room') {
        $accommodation_id = $_POST['accommodation_id'];
        $room_name = $_POST['room_name'];
        $capacity = $_POST['capacity'];
        $price = $_POST['price_per_night'];
        $description = $_POST['description'];
        $amenities = $_POST['amenities'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO room_types (accommodation_id, room_name, capacity, price_per_night, description, amenities) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$accommodation_id, $room_name, $capacity, $price, $description, $amenities]);

        echo json_encode([
            "success" => true,
            "message" => "เพิ่มข้อมูลเรียบร้อย",
            "id" => $pdo->lastInsertId()
        ]);
        exit; // ✅
    }

    if ($_POST['action'] === 'add_attraction') {

        $upload_path = "../asset/uploads/";
        if (!file_exists($upload_path)) {
            mkdir($upload_path, 0777, true);
        }

        $image_url = null;

        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = "attraction_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $filename)) {
                $image_url = "../asset/uploads/" . $filename;
            }
        }

        if ($_POST['attraction_type'] != null) {
            $sql = "INSERT INTO attractions 
            (name, latitude, longitude, address, phone, rating, description, historical_info, website, opening_time, close_time, entrance_fee,tourism_type_id,category_id, image_url) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['name'],
                $_POST['latitude'],
                $_POST['longtitude'],
                $_POST['address'],
                $_POST['phone'],
                $_POST['rating'],
                $_POST['description'],
                $_POST['historical_info'],
                $_POST['website'],
                $_POST['opening_time'],
                $_POST['close_time'],
                $_POST['entrance_fee'],
                $_POST['attraction_type'],
                $_POST['attraction_categories'],
                $image_url
            ]);
        }
        else{
            $sql = "INSERT INTO places(name,lat,lng,rating,image_url) VALUES(?,?,?,?,?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['name'],
                $_POST['latitude'],
                $_POST['longtitude'],
                $_POST['rating'],
                $image_url
            ]);
        }


        echo json_encode([
            "success" => true,
            "message" => "เพิ่มข้อมูลเรียบร้อย",
            "id" => $pdo->lastInsertId()
        ]);
        exit;
    }


    if ($_POST['action'] === 'update_attraction') {

        $id = $_POST['id'];
        $upload_path = "../asset/uploads/";

        $image_url = $_POST['old_image_url']; // จาก hidden input

        // ถ้าอัปโหลดรูปใหม่
        if (!empty($_FILES['image']['name'])) {
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = "attraction_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $filename)) {
                $image_url = "../asset/uploads/" . $filename;
            }
        }

        $sql = "UPDATE attractions SET 
            name=?, description=?, address=?, phone=?, rating=?, 
            opening_time=?, close_time=?, entrance_fee=?, 
            latitude=?, longitude=?, image_url=?
            WHERE id=?";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['name'],
            $_POST['description'],
            $_POST['address'],
            $_POST['phone'],
            $_POST['rating'],
            $_POST['opening_time'],
            $_POST['close_time'],
            $_POST['entrance_fee'],
            $_POST['latitude'],
            $_POST['longtitude'],
            $image_url,
            $id
        ]);

        echo json_encode([
            "success" => true,
            "message" => "อัปเดตข้อมูลเรียบร้อย",
            "id" => $id
        ]);
        exit;
    }
}

// ❌ อย่าส่ง JSON ซ้ำถ้าเคสอื่นทำงานสำเร็จไปแล้ว
echo json_encode(["success" => false, "message" => "ไม่มีข้อมูลหรือ action ไม่ถูกต้อง"]);
exit;
