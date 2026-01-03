<?php

include "normal-login.php";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - พัทลุงทริป</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            /* background-image: url(https://media.publit.io/file/Philippines-night-G.png);
            background: linear-gradient(135deg, #393a3fff 0%, #2f1b42ff 100%); */
            background: linear-gradient(rgba(180, 19, 19, 0.23), rgba(253, 0, 0, 0.19)), url('https://media.publit.io/file/Philippines-night-G.png') center/cover;
            min-height: 100vh;
            display: flex;

        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(45deg, #2563eb, #10b981);
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(45deg, #2563eb, #10b981);
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 500;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 12px 15px;
        }

        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .google-scale {
            transform: scale(1.3);
            transform-origin: center;
        }
    </style>
</head>

<body>
    <div class="container-fluid  mt-5">
        <div class="row justify-content-center w-45">
            <div class="col-lg-5 col-md-7 col-sm-12">
                <div class="login-card">
                    <div class="login-header">
                        <h2 class="mb-0">
                            <i class="fas fa-sign-in-alt me-2"></i>
                            เข้าสู่ระบบ
                        </h2>
                        <p class="mb-0 mt-2 opacity-75">เข้าสู่ระบบเพื่อวางแผนการเดินทาง</p>
                    </div>

                    <div class="p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <?= $error ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">ชื่อผู้ใช่</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                                        required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">รหัสผ่าน</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 mb-3 d-flex align-contents-center">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                เข้าสู่ระบบ
                            </button>
                        </form>

                        <div class="text-center">
                            <p class="text-muted">ยังไม่มีบัญชี?
                                <a href="register.php" class="text-decoration-none">สมัครสมาชิก</a>
                            </p>
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-arrow-left me-1"></i>
                                กลับหน้าหลัก
                            </a>
                        </div>
                        <div class="row justify-content-center mt-3">
                            <div class="col-12 text-center">

                                <div id="g_id_onload"
                                    data-client_id="701551378352-4v2kgpgg25m81rrc24j93u8anqfkcsop.apps.googleusercontent.com"
                                    data-context="signin"
                                    data-ux_mode="popup"
                                    data-callback="handleCredentialResponse">
                                </div>

                                <div class="google-scale g_id_signin mt-3 d-inline-block"
                                    data-type="standard"
                                    data-size="large"
                                    data-theme="outline">
                                </div>

                            </div>
                        </div>

                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://accounts.google.com/gsi/client" async defer></script>



    <script>
        // function handleCredentialResponse(response) {
        //     fetch('login_google.php', {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json'
        //             },
        //             body: JSON.stringify({
        //                 token: response.credential
        //             })
        //         })
        //         .then(r => r.json())
        //         .then(data => {
        //             if (data.status === 'trusted') {
        //                 window.location.href = 'index.php';
        //                 return;
        //             }
        //             if (data.status === 'otp_required') {
        //                 // redirect to OTP page
        //                 window.location.href = 'otp.php?uid=' + encodeURIComponent(data.user_id);
        //                 return;
        //             }
        //             alert('Login failed');
        //         })
        //         .catch(err => {
        //             console.error(err);
        //             alert('Network error');
        //         });
        // }
        function handleCredentialResponse(response) {
            fetch('login_process.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        token: response.credential
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.status === "trusted") location.href = "index.php";
                    else alert("Google login failed");
                });
        }
    </script>

</body>

</html>