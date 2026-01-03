<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$user = null;

if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
}

// Get user's trip plans
$trips = [];
if ($isLoggedIn) {
    $stmt = $pdo->prepare("
        SELECT tp.*, tt.name as tourism_type_name, tt.color as tourism_type_color 
        FROM trip_plans tp 
        LEFT JOIN tourism_types tt ON tp.tourism_type_id = tt.id 
        WHERE tp.user_id = ? 
        ORDER BY tp.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $trips = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>วางแผนท่องเที่ยวพัทลุง - Phatthalung Trip Planner</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #10b981;
            --accent-color: #f59e0b;
            --danger-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
        }

        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            /* background: linear-gradient(135deg,rgb(39, 17, 78) 0%,rgb(45, 28, 86) 100%); */
            background-color: rgb(37, 41, 76);
            min-height: 100vh;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.11)), url('assets/img/Phatthalung.png') center/cover;
            background-size: cover;
            background-attachment: fixed;
            min-height: 60vh;
            display: flex;
            align-items: center;
            color: white;
            text-align: center;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;

            padding: 12px 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
        }

        .trip-card {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 1rem;
            transition: transform 0.2s ease;
        }

        .trip-card.featured {
            transform: scale(1.05);
            border: 3px solid var(--accent-color);
        }

        .trip-card:hover {
            transform: translateY(-5px);
        }

        .trip-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        }

        #trip-pagination {

            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        #trip-pagination div {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 15px;

            background-color: rgb(223, 117, 37);
            width: 50%;
            height: 50px;
        }

        #trip-pagination button {
            background-color: rgb(255, 104, 3);
            border: none;
            color: white;
            padding: 10px 16px;
            font-size: 18px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            user-select: none;
        }

        #trip-pagination button:disabled {
            background-color: rgb(255, 0, 0);
            cursor: not-allowed;
        }

        #trip-pagination button:hover:not(:disabled) {
            background-color: rgb(179, 39, 0);
        }

        #page-info {
            font-weight: 600;
            font-size: 16px;
            min-width: 70px;
            text-align: center;
            user-select: none;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-planning {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-confirmed {
            background: #dcfce7;
            color: #166534;
        }

        .status-completed {
            background: #f3e8ff;
            color: #7c3aed;
        }

        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .nav-link-hover:hover {
            color: #0d6efd !important;
            /* Hover ให้เด่นขึ้น */
            transition: 0.3s;
        }

        .navbar-brand:hover {
            opacity: .8;
            transition: .3s;
        }

        .dropdown-menu a:hover {
            background: #f8f9fa;
        }


        @media (max-width: 768px) {
            .hero-section {
                min-height: 50vh;
                padding: 20px 0;
            }

            .trip-card.featured {
                transform: none;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include "components/navbar.php";?>


    <!-- Hero Section -->
    <?php include "components/section.php";?>




    <!-- Main Content -->
    <div class="container my-5">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error_message'] ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
            <!-- User's Trips Section -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="text-white">
                            <i class="fas fa-suitcase-rolling me-2"></i>
                            แผนการเดินทางของคุณ
                        </h2>
                        <a href="create-trip.php" class="btn btn-primary fs-md-5">
                            <span><i class="fas fa-plus me-2"></i></span>
                            <span>แผนการท่องเที่ยว</span>

                        </a>
                    </div>
                </div>
            </div>

            <?php if (empty($trips)): ?>
                <!-- No Trips Message -->
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card text-center p-5">
                            <div class="card-body">
                                <i class="fas fa-map-signs fa-3x text-muted mb-3"></i>
                                <h4>ยังไม่มีแผนการเดินทาง</h4>
                                <p class="text-muted mb-4">เริ่มต้นสร้างแผนการเดินทางแรกของคุณเพื่อสำรวจความงามของพัทลุง</p>

                                <a href="create-trip.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>
                                    สร้างแผนการเดินทาง
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Trips Grid -->
                <div class="trip-scroll-paginated">
                    <div class="row">
                        <?php foreach ($trips as $index => $trip): ?>
                            <div class="col-md-4 mt-5 mb-5 trip-page-item" data-page="<?= floor($index / 6) + 1 ?>">
                                <div class="trip-card">
                                    <div class="card-body">
                                        <?php if ($index === 0): ?>
                                            <div class="badge bg-warning text-dark mb-2">
                                                <i class="fas fa-star me-1"></i>
                                                ล่าสุด
                                            </div>
                                        <?php endif; ?>

                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <h5 class="card-title mb-0"><?= htmlspecialchars($trip['title']) ?></h5>
                                            <span class="status-badge status-<?= $trip['status'] ?>">
                                                <?php
                                                $statusText = [
                                                    'planning' => 'กำลังวางแผน',
                                                    'confirmed' => 'ยืนยันแล้ว',
                                                    'completed' => 'เสร็จสิ้น',
                                                    'cancelled' => 'ยกเลิก'
                                                ];
                                                echo $statusText[$trip['status']] ?? $trip['status'];
                                                ?>
                                            </span>
                                        </div>

                                        <?php if ($trip['tourism_type_name']): ?>
                                            <div class="mb-2">
                                                <span class="badge" style="background-color: <?= $trip['tourism_type_color'] ?>; color: white;">
                                                    <i class="fas fa-tag me-1"></i>
                                                    <?= htmlspecialchars($trip['tourism_type_name']) ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row text-muted small mb-3">
                                            <?php if ($trip['start_date'] && $trip['end_date']): ?>
                                                <div class="col-6">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d/m/Y', strtotime($trip['start_date'])) ?> - <?= date('d/m/Y', strtotime($trip['end_date'])) ?>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($trip['total_days']): ?>
                                                <div class="col-6">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= $trip['total_days'] ?> วัน
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($trip['budget']): ?>
                                                <div class="col-12 mt-1">
                                                    <i class="fas fa-wallet me-1"></i>
                                                    งบประมาณ: <?= number_format($trip['budget']) ?> บาท
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-2">
                                            <a href="view-trip.php?id=<?= $trip['id'] ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                                <i class="fas fa-eye me-1"></i>
                                                ดูแผน
                                            </a>
                                            <a href="map.php?trip_id=<?= $trip['id'] ?>" class="btn btn-outline-success btn-sm flex-fill">
                                                <i class="fas fa-map me-1"></i>
                                                แผนที่
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div> <!-- END .trip-page-item -->
                        <?php endforeach; ?>
                    </div>

                    <!-- ปุ่มเปลี่ยนหน้า -->
                    <div class="d-flex justify-content-center mt-3" id="trip-pagination">
                        <button id="prev-btn" aria-label="Previous page" title="หน้าก่อนหน้า">‹</button>
                        <div>
                            <span id="page-info" aria-live="polite" aria-atomic="true"></span>
                        </div>
                        <button id="next-btn" aria-label="Next page" title="หน้าถัดไป">›</button>
                    </div>

                </div>
            <?php endif ?>

        <?php else: ?>
            <!-- Guest Content -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card text-center p-5">
                        <div class="card-body">
                            <i class="fas fa-user-lock fa-3x text-muted mb-3"></i>
                            <h4>เข้าสู่ระบบเพื่อเริ่มวางแผน</h4>
                            <p class="text-muted mb-4">
                                สมัครสมาชิกหรือเข้าสู่ระบบเพื่อสร้างและจัดการแผนการเดินทางของคุณ
                            </p>
                            <div class="d-flex gap-1 justify-content-center">
                                <a href="login.php" class="rounded-5 btn btn-primary">

                                    เข้าสู่ระบบ
                                </a>
                                <a href="register.php" class="rounded-1 btn btn-outline-primary pt-2">

                                    สมัครสมาชิก
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Features Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="text-white text-center mb-5">
                    <i class="fas fa-star me-2"></i>
                    ฟีเจอร์เด่น
                </h3>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-leaf fa-3x text-success mb-3"></i>
                        <h5>ท่องเที่ยวธรรมชาติ</h5>
                        <p class="text-muted">สำรวจความงามของธรรมชาติ ป่าไผ่ ทะเลสาบ และภูเขา</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-primary mb-3"></i>
                        <h5>วัฒนธรรมท้องถิ่น</h5>
                        <p class="text-muted">เรียนรู้วัฒนธรรม ประเพณี และวิถีชีวิตชาวพัทลุง</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-book-open fa-3x text-warning mb-3"></i>
                        <h5>ตำนานและประวัติศาสตร์</h5>
                        <p class="text-muted">ค้นหาเรื่องราวตำนานและประวัติศาสตร์ที่น่าสนใจ</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        const items = document.querySelectorAll(".trip-page-item");
        const itemsPerPage = 6;
        const totalPages = Math.ceil(items.length / itemsPerPage);

        let currentPage = 1;

        function showPage(page) {
            if (page < 1) page = 1;
            if (page > totalPages) page = totalPages;
            currentPage = page;

            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = currentPage * itemsPerPage;

            items.forEach((item, index) => {
                item.style.display = (index >= startIndex && index < endIndex) ? "block" : "none";
            });

            document.getElementById("page-info").innerText = `หน้า ${currentPage} จากทั้งหมด ${totalPages}`;

            document.getElementById("prev-btn").disabled = (currentPage === 1);
            document.getElementById("next-btn").disabled = (currentPage === totalPages);
        }

        document.getElementById("prev-btn").addEventListener("click", () => {
            if (currentPage > 1) showPage(currentPage - 1);
        });

        document.getElementById("next-btn").addEventListener("click", () => {
            if (currentPage < totalPages) showPage(currentPage + 1);
        });

        showPage(1);
    });
</script>


</html>