<?php
// simple page to accept OTP (no auth required because it is part of login flow)
$user_id = intval($_GET['uid'] ?? 0);
if (!$user_id) {
    echo 'Invalid request';
    exit;
}
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Verify OTP</title>
</head>

<body>
    <h3>กรุณากรอกรหัส OTP ที่ส่งไปยังอีเมลของคุณ</h3>
    <form id="otpForm">
        <input id="otp" name="otp" maxlength="6" required />
        <button type="submit">ยืนยัน</button>
    </form>
    <script>
        document.getElementById('otpForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const otp = document.getElementById('otp').value;
            fetch('verify_otp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: <?php echo $user_id; ?>,
                    otp
                })
            }).then(r => r.json()).then(data => {
                if (data.status === 'success') {
                    window.location.href = 'index.php';
                } else {
                    alert(data.message || 'Invalid OTP');
                }
            });
        });
    </script>
</body>

</html>