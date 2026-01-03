<?php
session_start();
require_once '../config/database.php';

// ตรวจสอบว่าผู้ใช้ล็อกอินหรือไม่
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $trip_id = isset($_POST['trip_id']) ? (int)$_POST['trip_id'] : 0;

    if ($trip_id > 0) {
        // ตรวจสอบความเป็นเจ้าของทริป (security)
        $stmt = $pdo->prepare("SELECT id FROM trip_plans WHERE id = ? AND user_id = ?");
        $stmt->execute([$trip_id, $_SESSION['user_id']]);
        $trip = $stmt->fetch();

        if ($trip) {
            // ลบข้อมูล itinerary ก่อน (ลบแผนวัน-สถานที่-ที่พัก)
            $stmt = $pdo->prepare("DELETE FROM trip_itinerary WHERE trip_plan_id = ?");
            $stmt->execute([$trip_id]);

            // ลบข้อมูล trip plan
            $stmt = $pdo->prepare("DELETE FROM trip_plans WHERE id = ?");
            $stmt->execute([$trip_id]);

            $_SESSION['success_message'] = "ลบแผนทริปเรียบร้อยแล้ว";
            header('Location: index.php');  // เปลี่ยนเป็นหน้ารายการทริปของคุณ
            exit();
        } else {
            $_SESSION['error_message'] = "ไม่พบแผนทริปนี้ หรือคุณไม่มีสิทธิ์ลบ";
        }
    } else {
        $_SESSION['error_message'] = "ข้อมูลไม่ถูกต้อง";
    }
} else {
    $_SESSION['error_message'] = "Method ไม่ถูกต้อง";
}

header('Location: index.php'); // หรือหน้าเดิม
exit();
