<?php
require_once '../config/database.php';
session_start();

$id = $_POST['trip_id'];
$user = $_SESSION['user_id'];


$sql = "UPDATE trip_plans SET is_public = 1 WHERE id = :id AND user_id = :user";
$stmt = $pdo->prepare($sql);
$stmt->execute(['user' => $user,'id' => $id]);

header("Location: community.php");
exit;
