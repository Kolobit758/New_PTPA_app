<?php
session_start();
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['token'])) {
    echo json_encode(["status"=>"error","msg"=>"no token"]);
    exit;
}

$client_id="701551378352-4v2kgpgg25m81rrc24j93u8anqfkcsop.apps.googleusercontent.com";

$token = $data['token'];
$userInfo = json_decode(file_get_contents("https://oauth2.googleapis.com/tokeninfo?id_token=$token"),true);

if(!isset($userInfo['aud']) || $userInfo['aud'] !== $client_id){
    echo json_encode(["status"=>"error","msg"=>"invalid token"]);
    exit;
}

$gid=$userInfo['sub'];
$email=$userInfo['email'];
$name=$userInfo['name'];
$pic=$userInfo['picture'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE google_id=? OR email=?");
$stmt->execute([$gid,$email]);
$u=$stmt->fetch();

if($u){
    $_SESSION['user_id']=$u['id'];
    echo json_encode(["status"=>"trusted"]);
    exit;
}

$stmt=$pdo->prepare("INSERT INTO users(username,email,google_id,profile_image) VALUES (?,?,?,?)");
$stmt->execute([$name,$email,$gid,$pic]);

$_SESSION['user_id']=$pdo->lastInsertId();
echo json_encode(["status"=>"trusted"]);
exit;
