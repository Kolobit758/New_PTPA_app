<?php
require "./config/database.php";

$id = $_GET["trip_id"];

$stmt = $conn->prepare("
    SELECT c.comment, c.created_at, u.username 
    FROM public_comments c 
    JOIN users u ON c.user_id=u.id 
    WHERE c.trip_plan_id=? 
    ORDER BY c.id DESC
");
$stmt->execute([$id]);

while($r = $stmt->fetch(PDO::FETCH_ASSOC)){
    echo "<p><b>{$r['username']}:</b> {$r['comment']} <br><small>{$r['created_at']}</small></p>";
}
