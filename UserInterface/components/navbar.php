<nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
    <?php
    $isLoggedIn = isset($_SESSION['user_id']);
    $user = null;

    if ($isLoggedIn) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }

    ?>
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand fw-bold text-primary" href="index.php">
            <i class="fas fa-map-marked-alt me-2"></i> พัทลุงทริป
        </a>

        <!-- Toggle Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <!-- Community -->

                <?php if ($isLoggedIn): ?>

                    <!-- If logged in -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle fw-semibold" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i>
                            <?= htmlspecialchars($user['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> โปรไฟล์</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> ออกจากระบบ</a></li>
                        </ul>
                    </li>

                <?php else: ?>

                    <!-- If not logged in -->
                    <li class="nav-item">
                        <a class="nav-link fw-semibold nav-hover" href="login.php">เข้าสู่ระบบ</a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-primary fw-semibold px-3 py-1" href="register.php">สมัครสมาชิก</a>
                    </li>

                <?php endif; ?>
                <li class="nav-item mx-lg-3">
                    <a class="nav-link fw-semibold nav-hover text-primary bg-warning px-3 py-2"
                        href="community.php" style="border-radius: 20px;">
                        <i class="fas fa-users me-1"></i> View Community
                    </a>
                </li>

            </ul>
        </div>

    </div>
</nav>