<?php
require "./config/database.php";

$user = $_SESSION['user_id'];
$id   = $_POST['trip_id'];

// Check existing like
$check = $conn->prepare("SELECT id FROM public_likes WHERE user_id=? AND trip_plan_id=?");
$check->execute([$user,$id]);

if($check->rowCount() > 0){
    $del = $conn->prepare("DELETE FROM public_likes WHERE user_id=? AND trip_plan_id=?");
    $del->execute([$user,$id]);
    $liked = false;
} else {
    $add = $conn->prepare("INSERT INTO public_likes(user_id,trip_plan_id) VALUES(?,?)");
    $add->execute([$user,$id]);
    $liked = true;
}

$count = $conn->prepare("SELECT COUNT(*) FROM public_likes WHERE trip_plan_id=?");
$count->execute([$id]);
$total = $count->fetchColumn();

echo json_encode(["liked"=>$liked,"count"=>$total]);
