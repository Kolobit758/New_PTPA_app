<?php
session_start();
require_once '../config/database.php';

// ดึง Trip เฉพาะ public ล่าสุดก่อน
$sql = "SELECT tp.*, u.username, u.profile_image,
               GROUP_CONCAT(pti.image_path) AS images
        FROM trip_plans tp
        JOIN users u ON tp.user_id = u.id
        LEFT JOIN public_trip_images pti ON tp.id = pti.trip_id
        WHERE tp.is_public = 1
        GROUP BY tp.id
        ORDER BY tp.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);





?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Community Trips</title>

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

        .trip-gallery {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 5px;
            scrollbar-width: thin;
        }

        .trip-gallery::-webkit-scrollbar {
            height: 6px;
        }

        .trip-gallery::-webkit-scrollbar-thumb {
            background: #c2c2c2;
            border-radius: 10px;
        }

        .trip-gallery-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 10px;
            flex: 0 0 auto;
            /* บังคับให้จัดเรียงแนวนอน */
        }

        /* ภาพที่เหลือ (หลัง 3 ภาพแรก) ทำให้จางลงเล็กน้อย */
        .more-img {
            opacity: 0.85;
        }

        .more-img:hover {
            opacity: 1;
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

        .navbar {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
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

        .trip-card {
            background: #fff;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .15);
            transition: .3s;
        }

        /* รูปภาพในทริป */
        .trip-img {
            width: 50%;
            height: 200px;
            /* ปรับเป็น 180-300 ได้ตามความชอบ */
            object-fit: cover;
            /* ทำให้ภาพฟิตการ์ดและไม่เสียสัดส่วน */
            border-radius: 10px;
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

<body class="bg-light">
    <?php include "components/navbar.php" ?>

    <?php include "components/section.php"; ?>

    <div class="container py-5">
        <h2 class="mb-4 fw-bold text-primary">
            🗺️ Community Trip Plans
        </h2>

        <div class="row g-4">

            <?php foreach ($result as $item): ?>

                <?php

                if ($item['cover_image']) {
                    $img_string = "../" . $item['cover_image'];
                    
                    
                } else {
                    $img_string = "http://t3.gstatic.com/images?q=tbn:ANd9GcQ0CrIUp3NSpPmvoJMrdgey2TAtxipfRmg7YJO05Qj1yn-ubJZ-7sczDz_lVj2lnQ";
                }

                $images = [];
                if (!empty($item['images'])) {
                    $images = explode(",", $item['images']); // แปลง string เป็น array
                }
                ?>
                
                <div class="col-md-4 col-sm-6">
                    <div class="trip-card card p-3 h-100">

                        <!-- รูปปกทริป (responsive) -->
                        
                        <img src="<?= $img_string ?>"
                            class="w-100"
                            style="height:180px; object-fit:cover; border-radius:10px;">

                        <!-- User info -->
                        <div class="d-flex align-items-center mt-3">
                            <img class="rounded-circle me-3"
                                src="<?= $item['profile_image'] != null ? $item['profile_image'] : 'https://img.freepik.com/free-photo/portrait-white-man-isolated_53876-40306.jpg?semt=ais_hybrid&w=740&q=80' ?>"
                                style="width:45px;height:45px;object-fit:cover;">

                            <div>
                                <span class="fw-semibold"><?= htmlspecialchars($item['username']) ?></span><br>
                                <small class="text-muted"><?= date("d M Y", strtotime($item['created_at'])) ?></small>
                            </div>
                        </div>

                        <!-- Title -->
                        <div class="fw-bold mt-2" style="font-size:1.1rem;">
                            <?= htmlspecialchars($item['title']) ?>
                        </div>

                        <!-- Trip gallery -->
                        <!-- Trip gallery (Horizontal Scroll) -->
                        <div class="trip-gallery mt-3">

                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $index => $img): ?>
                                   
                                    <?php if ($index < 3): ?>
                                        <!-- 3 ภาพแรก แสดงปกติ -->
                                        <img src="../<?= $img ?>"
                                            class="trip-gallery-img">
                                    <?php else: ?>
                                        <!-- ที่เหลือยังแสดง แต่ต้อง scroll ไปดู -->
                                        <img src="../<?= $img ?>"
                                            class="trip-gallery-img more-img">
                                    <?php endif; ?>

                                <?php endforeach; ?>

                            <?php else: ?>
                                <div class="text-muted text-center">ไม่มีรูปสำหรับทริปนี้</div>
                            <?php endif; ?>

                        </div>


                        <a href="trip_public_view.php?id=<?= $item['id']; ?>"
                            class="btn btn-outline-primary w-100 mt-3">
                            ดูรายละเอียด
                        </a>

                    </div>
                </div>


            <?php endforeach; ?>

        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>