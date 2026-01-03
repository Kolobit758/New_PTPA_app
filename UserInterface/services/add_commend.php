<?php
session_start();
require "../../config/database.php";

$trip_id = $_POST['trip_id'];
$comment = $_POST['comment'];
$user_id = $_SESSION['user_id'];
$parent_id = $_POST['parent_id'] ?? NULL;

$stmt = $pdo->prepare("INSERT INTO public_comments (trip_plan_id, user_id, comment_text, parent_id) VALUES (?, ?, ?, ?)");
$stmt->execute([$trip_id, $user_id, $comment, $parent_id]);


header("location: ../trip_public_view.php?id=" .$trip_id);

exit();
