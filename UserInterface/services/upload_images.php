<?php
require_once "../../config/database.php";

$trip_id = $_POST['trip_id'];

$upload_dir = "../../uploads/trips/";
if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);

foreach($_FILES['images']['tmp_name'] as $key=>$tmp){
    
    $name = time()."_".rand(1000,9999)."_".$_FILES['images']['name'][$key];
    $path = $upload_dir.$name;

    if(move_uploaded_file($tmp,$path)){
        $stmt = $pdo->prepare("INSERT INTO public_trip_images(trip_id,image_path) VALUES(?,?)");
        $stmt->execute([$trip_id,"uploads/trips/".$name]);
    }
}

header("Location: ../trip_public_view.php?id=".$trip_id); // กลับไปหน้าเดิม
exit;
