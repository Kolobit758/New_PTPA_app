<?php
session_start();
require_once '../config/database.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$trip_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$trip_id) {
    header('Location: index.php');
    exit();
}

// ดึงข้อมูลทริป
$stmt = $pdo->prepare("
    SELECT tp.*, 
           tt.name AS tourism_type_name, 
           tt.color AS tourism_type_color, 
           tt.icon AS tourism_type_icon
    FROM trip_plans tp
    LEFT JOIN tourism_types tt ON tp.tourism_type_id = tt.id
    WHERE tp.id = ? AND tp.user_id = ?
");
$stmt->execute([$trip_id, $_SESSION['user_id']]);
$trip = $stmt->fetch();

if (!$trip) {
    header('Location: index.php');
    exit();
}

// ดึงรายละเอียดกำหนดการ
$stmt = $pdo->prepare("
    SELECT ti.*, 
           a.name AS attraction_name, a.description AS attraction_description, 
           a.address AS attraction_address, a.entrance_fee, a.estimated_duration,
           a.latitude AS attraction_lat, a.longitude AS attraction_lng,

           acc.name AS accommodation_name, acc.type AS accommodation_type,
           acc.description AS accommodation_description, acc.address AS accommodation_address,
           acc.latitude AS accommodation_lat, acc.longitude AS accommodation_lng,

           rt.room_name AS room_type_name, rt.capacity AS room_capacity, rt.price_per_night

    FROM trip_itinerary ti
    LEFT JOIN attractions a ON ti.attraction_id = a.id
    LEFT JOIN accommodations acc ON ti.accommodation_id = acc.id
    LEFT JOIN room_types rt ON ti.room_type_id = rt.id
    WHERE ti.trip_plan_id = ?
    ORDER BY ti.day_number, ti.order_index
");
$stmt->execute([$trip_id]);
$itinerary_items = $stmt->fetchAll();

// จัดกลุ่มตามวัน
$itinerary = [];
$total_cost = 0;
foreach ($itinerary_items as $item) {
    $day = $item['day_number'];
    $itinerary[$day][] = $item;
    $total_cost += floatval($item['estimated_cost']);
}

// ตรวจสอบ Free Day
for ($day = 1; $day <= $trip['total_days']; $day++) {
    $has_attraction = false;
    if (isset($itinerary[$day])) {
        foreach ($itinerary[$day] as $item) {
            if (!empty($item['attraction_id'])) {
                $has_attraction = true;
                break;
            }
        }
    }
    if (!$has_attraction) {
        $itinerary[$day][] = [
            'is_free_day' => true,
            'activity_name' => 'Free Day',
            'activity_description' => 'วันนี้ไม่มีแผนท่องเที่ยว',
        ];
    }
}
?>


<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($trip['title']) ?> - พัทลุงทริป</title>

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
            /* background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); */
            background-color: rgb(37, 41, 76);
            min-height: 100vh;
        }

        .trip-header {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .day-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .day-header {
            /* background: linear-gradient(45deg,rgb(168, 124, 29),rgb(185, 55, 16)); */
            background: rgb(170, 40, 40);
            color: white;
            padding: 1rem;
            font-weight: 600;
        }

        .itinerary-item {
            border-left: 4px solid #e5e7eb;
            padding: 1rem;
            margin: 0.5rem 0;
            background: #f8fafc;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }

        .itinerary-item:hover {
            border-left-color: #2563eb;
            background: #eff6ff;
        }

        .itinerary-item.attraction {
            border-left-color: #10b981;
        }

        .itinerary-item.accommodation {
            border-left-color: #f59e0b;
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

        .btn-primary {
            background: linear-gradient(45deg, #2563eb, #10b981);
            border: none;
            border-radius: 50px;
            padding: 10px 25px;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light" style="background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">
                <i class="fas fa-map-marked-alt me-2"></i>
                พัทลุงทริป
            </a>

            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">
                    <i class="fas fa-home me-1"></i>
                    หน้าหลัก
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <!-- Trip Header -->
        <div class="trip-header p-4">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center mb-2">
                        <?php if ($trip['tourism_type_icon']): ?>
                            <div class="me-3" style="color: <?= $trip['tourism_type_color'] ?>">
                                <i class="<?= $trip['tourism_type_icon'] ?> fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h1 class="mb-0"><?= htmlspecialchars($trip['title']) ?></h1>
                            <?php if ($trip['tourism_type_name']): ?>
                                <span class="badge mt-2" style="background-color: <?= $trip['tourism_type_color'] ?>; color: white;">
                                    <?= htmlspecialchars($trip['tourism_type_name']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row text-muted mt-2">
                        <?php if ($trip['total_days']): ?>
                            <div class="col-md-3">
                                <i class="fas fa-clock me-1"></i>
                                <?= $trip['total_days'] ?> วัน
                            </div>
                        <?php endif; ?>
                        <?php if ($trip['budget']): ?>
                            <div class="col-md-4">
                                <i class="fas fa-wallet me-1"></i>
                                งบประมาณ: <?= number_format($trip['budget']) ?> บาท
                            </div>
                        <?php endif; ?>

                        <?php if (isset($total_cost)): ?>
                            <div class="col-md-4 ">
                                <h5><i class="fas fa-money-bill-wave me-1"></i>
                                    <font color="red">รวมค่าใช้จ่าย: <?= number_format($total_cost) ?> บาท</font>
                                </h5>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-5">
                            <i class="fas fa-calendar me-1"></i>
                            สร้างเมื่อ <?= date('d/m/Y', strtotime($trip['created_at'])) ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 text-lg-end">
                    <span class="status-badge status-<?= $trip['status'] ?> mb-3 d-inline-block">
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
                    <div>
                        <a href="map.php?trip_id=<?= $trip['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-map me-2"></i>
                            ดูแผนที่
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Itinerary -->
        <?php if (empty($itinerary)): ?>
            <div class="day-card">
                <div class="p-5 text-center">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h4>ยังไม่มีกำหนดการ</h4>
                    <p class="text-muted">แผนการเดินทางนี้ยังไม่มีกำหนดการรายละเอียด</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($itinerary as $day => $items): ?>
                <div class="day-card">
                    <div class="day-header">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>
                            วันที่ <?= $day ?>
                        </h4>
                    </div>

                    <div class="p-3">
                        <?php foreach ($items as $item): ?>
                            <?php if (!empty($item['is_free_day'])): ?>
                                <!-- Free Day Item -->
                                <div class="itinerary-item free-day text-center text-muted py-4">
                                    <i class="fas fa-coffee fa-2x mb-2"></i>
                                    <h6><?= htmlspecialchars($item['activity_name']) ?></h6>
                                    <p class="mb-0"><?= htmlspecialchars($item['activity_description']) ?></p>
                                </div>

                            <?php elseif ($item['attraction_id']): ?>
                                <!-- Attraction Item -->
                                <div class="itinerary-item attraction">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-2">
                                                <i class="fas fa-map-marker-alt text-success me-2"></i>
                                                <h6 class="mb-0"><?= htmlspecialchars($item['attraction_name']) ?></h6>
                                                <?php if ($item['start_time']): ?>
                                                    <span class="badge bg-light text-dark ms-2">
                                                        <?= date('H:i', strtotime($item['start_time'])) ?> น.
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($item['attraction_description']): ?>
                                                <p class="text-muted mb-2"><?= htmlspecialchars($item['attraction_description']) ?></p>
                                            <?php endif; ?>

                                            <?php if ($item['attraction_address']): ?>
                                                <div class="small text-muted mb-1">
                                                    <i class="fas fa-location-dot me-1"></i>
                                                    <?= htmlspecialchars($item['attraction_address']) ?>
                                                </div>
                                            <?php endif; ?>

                                            <div class="row small text-muted">
                                                <?php if ($item['entrance_fee']): ?>
                                                    <div class="col-auto">
                                                        <i class="fas fa-ticket-alt me-1"></i>
                                                        ค่าเข้าชม: <?= number_format($item['entrance_fee']) ?> บาท
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($item['estimated_duration']): ?>
                                                    <div class="col-auto">
                                                        <i class="fas fa-clock me-1"></i>
                                                        ใช้เวลา: <?= $item['estimated_duration'] ?> นาที
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <?php if ($item['estimated_cost']): ?>
                                            <div class="text-end">
                                                <span class="badge bg-success">
                                                    <?= number_format($item['estimated_cost']) ?> บาท
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                            <?php elseif ($item['accommodation_id']): ?>
                                <!-- Accommodation Item -->
                                <div class="itinerary-item accommodation">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap">
                                        <div class="flex-grow-1 me-2">
                                            <div class="d-flex align-items-center mb-2 flex-wrap">
                                                <i class="fas fa-bed text-warning me-2"></i>
                                                <h6 class="mb-0"><?= htmlspecialchars($item['accommodation_name']) ?></h6>
                                                <span class="badge bg-warning text-dark ms-2 mt-1 mt-sm-0">
                                                    <?= ucfirst($item['accommodation_type']) ?>
                                                </span>
                                            </div>

                                            <?php if ($item['accommodation_description']): ?>
                                                <p class="text-muted mb-2"><?= htmlspecialchars($item['accommodation_description']) ?></p>
                                            <?php endif; ?>

                                            <?php if ($item['accommodation_address']): ?>
                                                <div class="small text-muted">
                                                    <i class="fas fa-location-dot me-1"></i>
                                                    <?= htmlspecialchars($item['accommodation_address']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>



                                        <div class="text-end mt-2 mt-sm-0">
                                            <span class="badge bg-warning text-dark">
                                                <?= htmlspecialchars($item['room_type_name']) ?>
                                                (<?= $item['room_capacity'] ?> คน) -
                                                <?= number_format($item['price_per_night']) ?> บาท/คืน
                                                จำนวน <?= ceil($trip['guest_count'] / $item['room_capacity']); ?> ห้อง
                                            </span>
                                        </div>

                                    </div>

                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>
                    กลับหน้าหลัก
                </a>
                <a href="map.php?trip_id=<?= $trip['id'] ?>" class="btn btn-primary">
                    <i class="fas fa-map me-2"></i>
                    ดูแผนที่
                </a>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>
                    พิมพ์แผน
                </button>
                <!-- ตัวอย่างปุ่มลบในแต่ละทริป -->
                <form method="post" action="delete-trip.php" onsubmit="return confirm('แน่ใจหรือว่าต้องการลบแผนนี้?');">
                    <input type="hidden" name="trip_id" value="<?= $trip['id'] ?>">
                    <button type="submit" class="btn btn-danger">ลบแผนทริปนี้</button>
                </form>
                <form action="share_trip.php" method="POST">
                    <input type="hidden" name="trip_id" value="<?php echo $trip_id; ?>">
                    <button type="submit">📢 แชร์แผนนี้ให้สาธารณะ</button>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>