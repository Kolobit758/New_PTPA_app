<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');
$body = json_decode(file_get_contents('php://input'), true);
$user_id = intval($body['user_id'] ?? 0);
$otp = trim($body['otp'] ?? '');
if (!$user_id || !$otp) {
    echo json_encode(['status' => 'error', 'message' => 'missing']);
    exit;
}


// fetch OTP row
$stmt = $pdo->prepare('SELECT * FROM user_otp WHERE user_id = ? ORDER BY id DESC LIMIT 1');
$stmt->execute([$user_id]);
$row = $stmt->fetch();
if (!$row) {
    echo json_encode(['status' => 'error', 'message' => 'no otp']);
    exit;
}


// check expiry and match
$now = new DateTime();
$expire = new DateTime($row['expire_at']);
if ($now > $expire || $row['otp_code'] !== $otp) {
    echo json_encode(['status' => 'error', 'message' => 'invalid_or_expired']);
    exit;
}


// OTP ok -> remove otp rows for user
$pdo->prepare('DELETE FROM user_otp WHERE user_id = ?')->execute([$user_id]);


// create device_token
$deviceToken = bin2hex(random_bytes(32));
$stmt = $pdo->prepare('INSERT INTO user_devices (user_id, device_token, user_agent, ip_address) VALUES (?, ?, ?, ?)');
$stmt->execute([$user_id, $deviceToken, $_SERVER['HTTP_USER_AGENT'] ?? '', $_SERVER['REMOTE_ADDR'] ?? '']);


// set cookie (HttpOnly, Secure recommended)
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
setcookie('device_token', $deviceToken, time() + 60 * 60 * 24 * 30, '/', '', $secure, true);


// set session
$_SESSION['user_id'] = $user_id;
// get user info optional
$stmt = $pdo->prepare('SELECT email, full_name FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if ($user) {
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['full_name'];
}


echo json_encode(['status' => 'success']);
exit;
