
<?php

require "../../config/database.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    exit('Invalid ID');
}

$image_id = $_GET['id'];
$trip_id = $_GET['trip_id'];

$sql = 'DELETE FROM public_trip_images WHERE id = :id';
$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $image_id]);

header("location: ../trip_public_view.php?id=".$trip_id);

?>
