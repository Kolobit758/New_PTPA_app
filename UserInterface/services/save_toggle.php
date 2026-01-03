<?php
require "./config/database.php";

$user = $_SESSION['user_id'];
$id   = $_POST['trip_id'];

$q = $conn->prepare("SELECT id FROM public_saved_trips WHERE user_id=? AND trip_plan_id=?");
$q->execute([$user,$id]);

if($q->rowCount()>0){
    $del = $conn->prepare("DELETE FROM public_saved_trips WHERE user_id=? AND trip_plan_id=?");
    $del->execute([$user,$id]);
    $saved = false;
}else{
    $add = $conn->prepare("INSERT INTO public_saved_trips(user_id,trip_plan_id) VALUES(?,?)");
    $add->execute([$user,$id]);
    $saved = true;
}
echo json_encode(["saved"=>$saved]);
