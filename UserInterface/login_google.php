<?php

session_start();
require_once '../config/database.php';

if (!$google_id || !$email) {
echo json_encode(['status'=>'error','message'=>'missing user info']);
exit;
}


// 2) Find or create user
$stmt = $pdo->prepare('SELECT * FROM users WHERE google_id = ? OR email = ? LIMIT 1');
$stmt->execute([$google_id, $email]);
$user = $stmt->fetch();
if (!$user) {
$stmt = $pdo->prepare('INSERT INTO users (google_id, full_name, email, profile_image) VALUES (?,?,?,?)');
$stmt->execute([$google_id, $name, $email, $picture]);
$userId = $pdo->lastInsertId();
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
}
$user_id = (int)$user['id'];


// 3) Check device token cookie
$deviceToken = $_COOKIE['device_token'] ?? null;
if ($deviceToken) {
$stmt = $pdo->prepare('SELECT * FROM user_devices WHERE user_id = ? AND device_token = ? LIMIT 1');
$stmt->execute([$user_id, $deviceToken]);
$device = $stmt->fetch();
if ($device) {
// trusted -> create session
$_SESSION['user_id'] = $user_id;
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_name'] = $user['full_name'];
echo json_encode(['status'=>'trusted']);
exit;
}
}


// 4) Not trusted -> generate OTP, save and send
$otp = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expire = (new DateTime('+5 minutes'))->format('Y-m-d H:i:s');
// Delete old otps
$del = $pdo->prepare('DELETE FROM user_otp WHERE user_id = ?');
$del->execute([$user_id]);
// Insert new otp
$ins = $pdo->prepare('INSERT INTO user_otp (user_id, otp_code, expire_at) VALUES (?, ?, ?)');
$ins->execute([$user_id, $otp, $expire]);


// send via mail() for demo - replace with PHPMailer/SMTP in production
$subject = 'Your OTP Code';
$message = "รหัส OTP ของคุณ: $otp\nใช้ได้ 5 นาที";
$headers = 'From: no-reply@yourdomain.com\r\n' .
'Reply-To: no-reply@yourdomain.com\r\n' .
'X-Mailer: PHP/' . phpversion();
@mail($email, $subject, $message, $headers);


// respond that OTP was sent
echo json_encode(['status'=>'otp_required','user_id'=>$user_id]);
exit;