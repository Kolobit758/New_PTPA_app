<?php
require "../../config/database.php";

$id = $_POST['trip_id'];

$path = "../../uploads/trip_cover/" . time() . "_" . basename($_FILES['cover']['name']);
$realPath = "uploads/trip_cover/" . time() . "_" . basename($_FILES['cover']['name']);
move_uploaded_file($_FILES['cover']['tmp_name'], $path);

$up = $pdo->prepare("UPDATE trip_plans SET cover_image=? WHERE id=?");
$up->execute([$realPath,$id]);

header("Location: ../trip_public_view.php?id=".$id); // กลับไปหน้าเดิม
exit;
