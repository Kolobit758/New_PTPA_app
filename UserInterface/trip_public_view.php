<?php
session_start();
require_once '../config/database.php';
$id = $_GET['id'];

$sql = "SELECT tp.id AS trip_id, tp.title, tp.created_at, tp.is_public, tp.actual_budget AS trip_budget, u.username,u.id as user_id,
        ti.day_number, ti.order_index, 

        a.id AS attraction_id, a.name AS attraction_name, a.description AS attraction_description, 
        a.address AS attraction_address, a.entrance_fee, a.estimated_duration,
        a.latitude AS attraction_lat, a.longitude AS attraction_lng,a.image_url AS attrac_img,

        acc.id AS accommodation_id, acc.name AS accommodation_name, acc.type AS accommodation_type,
        acc.description AS accommodation_description, acc.address AS accommodation_address, 
        acc.latitude AS accommodation_lat, acc.longitude AS accommodation_lng, acc.image_url As accom_img,

        rt.room_name AS room_type_name, rt.capacity AS room_capacity, rt.price_per_night

        

        FROM trip_plans tp
        JOIN users u ON tp.user_id = u.id
        LEFT JOIN trip_itinerary ti ON tp.id = ti.trip_plan_id
        LEFT JOIN attractions a ON ti.attraction_id = a.id
        LEFT JOIN accommodations acc ON ti.accommodation_id = acc.id
        LEFT JOIN room_types rt ON ti.room_type_id = rt.id
        WHERE tp.id = ?
        AND tp.is_public = 1
        ORDER BY ti.day_number, ti.order_index";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$data = $stmt->fetchAll();

$stmt_comments = $pdo->prepare("
    SELECT c.id, c.comment_text, c.created_at, c.parent_id, u.username 
    FROM public_comments c
    JOIN users u ON c.user_id = u.id
    WHERE c.trip_plan_id = ? 
    ORDER BY c.created_at ASC
");
$stmt_comments->execute([$id]);
$comments = $stmt_comments->fetchAll();

function renderComments($comments, $parent_id = null, $level = 0)
{
    foreach ($comments as $c) {
        if ($c['parent_id'] == $parent_id) {

            echo '<div class="comment-item mb-2" style="margin-left:' . ($level * 20) . 'px">';

            echo "<div class='d-flex justify-content-between mb-2 flex-wrap gap-2'>
                    <div>
                        <strong>{$c['username']}</strong> 
                        <small class='text-muted ms-2'>{$c['created_at']}</small>
                    </div>";

            echo "<button class='toggle-thread' data-id='{$c['id']}'>
                    แสดง ▲
                  </button>
            </div>";

            echo "<p>" . nl2br(htmlspecialchars($c['comment_text'])) . "</p>";

            if (isset($_SESSION['user_id'])) {
                echo "<a href='#' class='reply-btn' data-id='{$c['id']}'>ตอบกลับ</a>";

                echo "
                <form action='services/add_commend.php' method='POST' class='reply-form' style='display:none'>
                    <input type='hidden' name='trip_id' value='{$_GET['id']}'>
                    <input type='hidden' name='parent_id' value='{$c['id']}'>
                    <textarea name='comment' class='form-control mb-2' rows='2' placeholder='ตอบกลับ...' required></textarea>
                    <button type='submit' class='btn btn-sm btn-primary'>ส่ง</button>
                </form>";
            }

            echo "</div>";

            echo "<div class='child-comments child-{$c['id']}' style='display:none;'>";
            renderComments($comments, $c['id'], $level + 1);
            echo "</div>";
        }
    }
}

$sql = "SELECT * FROM `public_trip_images` WHERE trip_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$uploadImgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data[0]['title'] ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        * {
            font-family: 'Kanit', sans-serif;
        }

        body {
            background-color: #f5f5f5;
            padding-bottom: 40px;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), 
                        url('assets/img/Phatthalung.png') center/cover;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-align: center;
        }

        .hero-section h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        @media (min-width: 768px) {
            .hero-section {
                min-height: 400px;
            }
            .hero-section h1 {
                font-size: 2.5rem;
            }
        }

        /* Main Container */
        .main-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            padding: 20px;
            margin-top: -40px;
            position: relative;
        }

        @media (min-width: 768px) {
            .main-container {
                padding: 40px;
            }
        }

        /* Budget */
        .budget-box {
            background: #4F46E5;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 30px;
        }

        .budget-box h2 {
            font-size: 1.2rem;
            margin: 0 0 10px 0;
            font-weight: 500;
        }

        .budget-box .amount {
            font-size: 2rem;
            font-weight: 600;
        }

        /* Section Headers */
        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #e5e7eb;
        }

        /* Cards */
        .item-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
            height: 100%;
        }

        .item-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .item-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .item-card .card-body {
            padding: 15px;
        }

        .item-card h5 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .item-card .text-muted {
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .item-card .badge {
            font-size: 0.85rem;
            padding: 5px 10px;
            font-weight: 400;
        }

        /* Image Gallery */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin: 30px 0;
        }

        @media (min-width: 576px) {
            .image-gallery {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 768px) {
            .image-gallery {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .gallery-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            aspect-ratio: 1;
            border: 1px solid #e5e7eb;
        }

        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gallery-item .delete-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            border: none;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .gallery-item .delete-btn:hover {
            background: #dc2626;
        }

        /* Upload Section */
        .upload-box {
            background: #f9fafb;
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
        }

        .upload-box h5 {
            font-size: 1rem;
            margin-bottom: 15px;
            font-weight: 500;
        }

        .upload-box input[type="file"] {
            margin-bottom: 10px;
        }

        /* Comments */
        .comments-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-top: 40px;
            border: 1px solid #e5e7eb;
        }

        .comment-item {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .comment-item strong {
            color: #1f2937;
        }

        .comment-item .text-muted {
            font-size: 0.85rem;
        }

        .child-comments {
            margin-top: 10px;
        }

        .child-comments .comment-item {
            background: white;
        }

        .toggle-thread {
            background: #4F46E5;
            border: none;
            color: white;
            padding: 5px 15px;
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .reply-btn {
            color: #4F46E5;
            font-weight: 500;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .reply-btn:hover {
            text-decoration: underline;
        }

        .reply-form {
            margin-top: 10px;
        }

        /* Buttons */
        .btn-primary {
            background: #4F46E5;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
        }

        .btn-primary:hover {
            background: #4338CA;
        }

        /* Pagination */
        .pagination-box {
            text-align: center;
            margin-top: 20px;
        }

        .pagination-box button {
            background: white;
            border: 1px solid #d1d5db;
            padding: 8px 20px;
            border-radius: 6px;
            margin: 0 5px;
        }

        .pagination-box button:hover:not(:disabled) {
            background: #f3f4f6;
        }

        .pagination-box button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #9ca3af;
        }
    </style>

</head>

<body>
    <?php include "components/navbar.php" ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div>
            <h1><?= $data[0]['title'] ?></h1>
            <p>📌 แชร์โดย <span class="fw-semibold"><?= $data[0]['username'] ?></span></p>
        </div>
    </div>

    <div class="container">
        <div class="main-container">
            
            <!-- Budget -->
            <div class="budget-box">
                <h2>💰 งบประมาณรวมทั้งทริป</h2>
                <div class="amount"><?= number_format($data[0]['trip_budget']) ?> บาท</div>
            </div>

            <!-- Accommodation Section -->
            <div class="mb-5">
                <h3 class="section-title">🏨 ที่พัก</h3>

                <div class="row">
                    <?php
                    $hasAccommodation = false;
                    foreach ($data as $item) :
                        if ($item['accommodation_name'] == null) continue;
                        $hasAccommodation = true;
                    ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="item-card">
                                <?php if ($item['accom_img']): ?>
                                    <img src="<?= $item['accom_img'] ?>" alt="<?= $item['accommodation_name'] ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5><?= $item['accommodation_name'] ?></h5>
                                    <p class="text-muted"><?= $item['accommodation_type'] ?></p>
                                    <?php if ($item['accommodation_address']) : ?>
                                        <small><i class="fas fa-map-marker-alt"></i> <?= $item['accommodation_address'] ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$hasAccommodation): ?>
                        <div class="col-12">
                            <p class="empty-state">ยังไม่มีการเพิ่มที่พัก</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Attraction Section -->
            <div class="mb-5">
                <h3 class="section-title">📍 สถานที่ท่องเที่ยว</h3>

                <div class="row">
                    <?php
                    $hasAttraction = false;
                    foreach ($data as $item) :
                        if ($item['attraction_name'] == null) continue;
                        $hasAttraction = true;
                    ?>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <div class="item-card">
                                <?php if ($item['attrac_img']): ?>
                                    <img src="<?= $item['attrac_img'] ?>" alt="<?= $item['attraction_name'] ?>">
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5><?= $item['attraction_name'] ?></h5>
                                    <p class="text-muted small"><i class="fas fa-map-marker-alt"></i> <?= $item['attraction_address'] ?></p>
                                    <?php if ($item['entrance_fee']): ?>
                                        <span class="badge bg-warning text-dark">💰 ค่าเข้า: <?= $item['entrance_fee'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($item['estimated_duration']): ?>
                                        <span class="badge bg-secondary ms-1">⏱️ <?= $item['estimated_duration'] ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if (!$hasAttraction): ?>
                        <div class="col-12">
                            <p class="empty-state">ยังไม่มีสถานที่ท่องเที่ยว</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Image Gallery -->
            <?php if (count($uploadImgs) > 0): ?>
            <div class="mb-5">
                <h3 class="section-title">📷 รูปภาพ</h3>
                
                <div class="image-gallery">
                    <?php foreach ($uploadImgs as $uploadImg) : ?>
                        <div class="gallery-item">
                            <img src="../<?= $uploadImg['image_path'] ?>" alt="Trip Photo">
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $data[0]['user_id']): ?>
                                <a href="./services/delete_image.php?id=<?=$uploadImg['id']?>&trip_id=<?= $id?>" class="delete-btn" onclick="return confirm('ลบรูปภาพนี้?')">×</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Upload Forms (Only for trip owner) -->
            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $data[0]['user_id']): ?>
                <div class="upload-box">
                    <h5>📤 อัปโหลดรูปภาพ</h5>
                    <form action="services/upload_images.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="trip_id" value="<?= $id ?>">
                        <input type="file" name="images[]" class="form-control" accept="image/*" multiple required>
                        <button type="submit" class="btn btn-primary mt-2">อัปโหลด</button>
                    </form>
                </div>

                <div class="upload-box">
                    <h5>🖼️ อัปโหลดภาพปก</h5>
                    <form action="services/upload_cover.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="trip_id" value="<?= $id ?>">
                        <input type="file" name="cover" class="form-control" required>
                        <button type="submit" class="btn btn-primary mt-2">อัปโหลด</button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Comments Section -->
            <div class="comments-section">
                <h4 class="mb-4">💬 ความคิดเห็น</h4>

                <!-- Form เพิ่ม comment -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="services/add_commend.php" method="POST" class="mb-4">
                        <input type="hidden" name="trip_id" value="<?= $id ?>">
                        <textarea name="comment" class="form-control mb-2" rows="3" placeholder="เพิ่มความคิดเห็น..." required></textarea>
                        <button type="submit" class="btn btn-primary">โพสต์</button>
                    </form>
                <?php else: ?>
                    <p class="text-muted">กรุณา <a href="login.php">เข้าสู่ระบบ</a> เพื่อแสดงความคิดเห็น</p>
                <?php endif; ?>

                <!-- แสดง comment -->
                <?php renderComments($comments); ?>

                <!-- Pagination -->
                <div class="pagination-box">
                    <button id="prev-btn">← ก่อนหน้า</button>
                    <span id="page-info" class="mx-2">หน้า 1 / 1</span>
                    <button id="next-btn">ถัดไป →</button>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // Toggle reply form
            document.querySelectorAll(".reply-btn").forEach(btn => {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    let form = this.parentElement.querySelector(".reply-form");
                    form.style.display = form.style.display === "block" ? "none" : "block";
                });
            });

            // Toggle หุบ/ขยาย reply thread
            document.querySelectorAll(".toggle-thread").forEach(btn => {
                btn.addEventListener("click", function() {
                    let id = this.dataset.id;
                    let block = document.querySelector(`.child-${id}`);

                    if (block.style.display === "none" || !block.style.display) {
                        block.style.display = "block";
                        this.innerHTML = "ซ่อน ▼";
                    } else {
                        block.style.display = "none";
                        this.innerHTML = "แสดง ▲";
                    }
                });
            });

            // Pagination
            const comments = document.querySelectorAll(".comments-section > .comment-item");
            const commentsPerPage = 4;
            const totalPages = Math.ceil(comments.length / commentsPerPage);

            let currentPage = 1;

            function showPage(page) {
                if (page < 1) page = 1;
                if (page > totalPages) page = totalPages;
                currentPage = page;

                const start = (currentPage - 1) * commentsPerPage;
                const end = currentPage * commentsPerPage;

                comments.forEach((item, index) => {
                    item.style.display = (index >= start && index < end) ? "block" : "none";
                });

                document.getElementById("page-info").innerText = `หน้า ${currentPage} / ${totalPages}`;
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
</body>

</html>