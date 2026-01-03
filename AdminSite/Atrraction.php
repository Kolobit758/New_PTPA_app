<?php

include "../config/database.php";

$sql = "SELECT * FROM tourism_types";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$attraction_type = $stmt->fetchAll();

$sql = "SELECT * FROM attraction_categories";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$attraction_categories = $stmt->fetchAll();

$sql = "SELECT * FROM places";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$places = $stmt->fetchAll(); // Cafe Resternet

$sql = "SELECT attractions.id, attractions.name, attractions.description, attractions.tourism_type_id,attractions.latitude,attractions.longitude,attractions.address,attractions.phone,attractions.website,attractions.opening_time,attractions.entrance_fee,attractions.estimated_duration,attractions.rating,attractions.image_url,attractions.historical_info,attractions.category_id,attractions.close_time,tourism_types.icon,tourism_types.color  FROM attractions INNER JOIN tourism_types ON attractions.tourism_type_id = tourism_types.id ORDER BY attractions.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tourism Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body style="font-family: 'Kanit', sans-serif; background-color:#f8f9fa;">




    <div class="container py-5">
        <h1 class="mb-4 text-center fw-bold text-success">จัดการข้อมูลสถานที่ท่องเที่ยว</h1>

        <div class="container card mt-5">
            <form id="search-form">
                <input type="number" name="id" placeholder="attraction_id">
                <button type="submit" class="btn btn-success">Submit</button>

            </form>

            <div id="result"></div>
        </div>

        <!-- 🟦 ฟอร์มเพิ่มสถานที่ -->
        <div class="card shadow-sm mb-5 mt-5">
            <div class="card-body">
                <h4 class="card-title mb-4">เพิ่มสถานที่ท่องเที่ยวใหม่</h4>
                <form id="add_attraction" enctype="multipart/form-data" class="row g-3">

                    <div class="col-12">
                        <label class="form-label fw-semibold">ประเภทสถานที่ท่องเที่ยว:</label><br>
                        <?php foreach ($attraction_type as $row): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="attraction_type" value="<?= htmlspecialchars($row['id']) ?>">
                                <label class="form-check-label"><?= htmlspecialchars($row['name_en']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">หมวดหมู่สถานที่:</label><br>
                        <?php foreach ($attraction_categories as $row): ?>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" name="attraction_categories" value="<?= htmlspecialchars($row['id']) ?>">
                                <label class="form-check-label"><?= htmlspecialchars($row['name']); ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="ชื่อสถานที่" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="description" class="form-control" placeholder="คำอธิบาย">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="address" class="form-control" placeholder="ที่อยู่">
                    </div>

                    <div class="col-md-3">
                        <input type="text" name="phone" class="form-control" placeholder="เบอร์โทร">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="any" name="rating" class="form-control" placeholder="คะแนน (1-5)" min="1" max="5">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="any" name="entrance_fee" class="form-control" placeholder="ค่าเข้า (บาท)">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="website" class="form-control" placeholder="เว็บไซต์ (ถ้ามี)">
                    </div>

                    <div class="col-md-3">
                        <input type="time" name="opening_time" class="form-control" placeholder="เวลาเปิด">
                    </div>
                    <div class="col-md-3">
                        <input type="time" name="close_time" class="form-control" placeholder="เวลาปิด">
                    </div>

                    <div class="col-md-3">
                        <input type="number" step="any" name="latitude" class="form-control" placeholder="Latitude">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="any" name="longtitude" class="form-control" placeholder="Longitude">
                    </div>

                    <div class="col-md-6">
                        <input type="text" name="historical_info" class="form-control" placeholder="ข้อมูลประวัติ (ถ้ามี)">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">อัปโหลดรูปภาพ</label>
                        <input type="file" name="image" accept="image/*" class="form-control">
                    </div>


                    <div class="col-12 mt-3">
                        <button class="btn btn-success w-100" type="submit" name="Add_attraction">
                            <i class="fa fa-plus"></i> เพิ่มสถานที่ท่องเที่ยว
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 🟩 รายการสถานที่ -->
        <div class="mb-4">
            <h3 class="fw-semibold mb-3">รายการสถานที่ท่องเที่ยว</h3>
            <div id="attractionList" class="row">
                <?php foreach ($attractions as $item): ?>
                    <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                        <div class="card h-100 shadow-sm">
                            <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: <?= $item['color'] ?>;">
                                <span class="fw-semibold"><?= htmlspecialchars($item['name']); ?></span>
                                <button class="btn btn-light btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editModal"
                                    data-id="<?= $item['id']; ?>"
                                    data-name="<?= htmlspecialchars($item['name']); ?>"
                                    data-description="<?= htmlspecialchars($item['description']); ?>"
                                    data-address="<?= htmlspecialchars($item['address']); ?>"
                                    data-phone="<?= htmlspecialchars($item['phone']); ?>"
                                    data-rating="<?= htmlspecialchars($item['rating']); ?>"
                                    data-opening="<?= htmlspecialchars($item['opening_time']); ?>"
                                    data-close="<?= htmlspecialchars($item['close_time']); ?>"
                                    data-fee="<?= htmlspecialchars($item['entrance_fee']); ?>"
                                    data-latitude="<?= htmlspecialchars($item['latitude']); ?>"
                                    data-longtitude="<?= htmlspecialchars($item['longitude']); ?>"">
                                    
                                
                                    
                                    <i class=" fa fa-edit"></i>
                                </button>
                            </div>
                            <div class="card-body">
                                <p><strong>คำอธิบาย:</strong> <?= htmlspecialchars($item['description']); ?></p>
                                <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($item['address']); ?></p>
                                <p><strong>โทร:</strong> <?= htmlspecialchars($item['phone']); ?></p>
                                <p><strong>เวลาเปิด:</strong> <?= htmlspecialchars($item['opening_time']); ?> - <?= htmlspecialchars($item['close_time']); ?></p>
                                <p><strong>ค่าเข้า:</strong> <?= htmlspecialchars($item['entrance_fee']); ?> บาท</p>
                                <p><strong>Rating:</strong> <?= htmlspecialchars($item['rating']); ?></p>
                                <p><strong>latitude:</strong> <?= htmlspecialchars($item['latitude']); ?></p>
                                <p><strong>longtitude:</strong> <?= htmlspecialchars($item['longitude']); ?></p>
                                <img src="<?= $item['image_url'] ?>" width="100px" height="100px" alt="">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="attractionList"></div>
            </div>
        </div>
        <div class="card p-4">
            <h4 class="card-body d-flex justify-content-center">Normal PLace</h4>
            <?php foreach ($places as $item): ?>
                <div class="col-lg-4 col-md-6 col-sm-12 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background-color: #601c90ff;">
                            <span class="fw-semibold"><?= htmlspecialchars($item['name']); ?></span>
                            <button class="btn btn-light btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $item['id']; ?>"
                                data-name="<?= htmlspecialchars($item['name']); ?>"
                                data-address="<?= htmlspecialchars($item['address']); ?>"
                                data-rating="<?= htmlspecialchars($item['rating']); ?>"
                                data-latitude="<?= htmlspecialchars($item['lat']); ?>"
                                data-longtitude="<?= htmlspecialchars($item['lng']); ?>"">
                                    
                                
                                    
                                    <i class=" fa fa-edit"></i>
                            </button>
                        </div>
                        <div class="card-body">
                            <p><strong>ที่อยู่:</strong> <?= htmlspecialchars($item['address']); ?></p>
                            <p><strong>Rating:</strong> <?= htmlspecialchars($item['rating']); ?></p>
                            <p><strong>latitude:</strong> <?= htmlspecialchars($item['lat']); ?></p>
                            <p><strong>longtitude:</strong> <?= htmlspecialchars($item['lng']); ?></p>
                            <img src="<?= $item['image_url'] ?>" width="100px" height="100px" alt="">
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 🟨 Modal แก้ไข -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="update_attraction" enctype="multipart/form-data">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">แก้ไขข้อมูลสถานที่ท่องเที่ยว</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" id="edit_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label>ชื่อสถานที่</label>
                                <input type="text" name="name" id="edit_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>เบอร์โทร</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label>คำอธิบาย</label>
                                <textarea name="description" id="edit_description" class="form-control"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label>ที่อยู่</label>
                                <input type="text" name="address" id="edit_address" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>เวลาเปิด</label>
                                <input type="time" name="opening_time" id="edit_opening" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>เวลาปิด</label>
                                <input type="time" name="close_time" id="edit_close" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>ค่าเข้า</label>
                                <input type="number" name="entrance_fee" id="edit_fee" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>เรตติ้ง</label>
                                <input type="number" step="any" name="rating" id="edit_rating" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label>ละติจูด</label>
                                <input type="number" step="any" name="latitude" id="edit_latitude" class="form-control" ">
                            </div>
                            <div class=" col-md-3">
                                <label>ลองติจูด</label>
                                <input type="number" step="any" name="longtitude" id="edit_longtitude" class="form-control">
                            </div>
                            <input type="file" name="image" accept="image/*" class="form-control">

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" name="Update_attraction" class="btn btn-success">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- 🟩 Script เติมข้อมูลลงใน Modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editModal');
            editModal.addEventListener('show.bs.modal', event => {
                const button = event.relatedTarget;
                document.getElementById('edit_id').value = button.getAttribute('data-id');
                document.getElementById('edit_name').value = button.getAttribute('data-name');
                document.getElementById('edit_description').value = button.getAttribute('data-description');
                document.getElementById('edit_address').value = button.getAttribute('data-address');
                document.getElementById('edit_phone').value = button.getAttribute('data-phone');
                document.getElementById('edit_rating').value = button.getAttribute('data-rating');
                document.getElementById('edit_opening').value = button.getAttribute('data-opening');
                document.getElementById('edit_close').value = button.getAttribute('data-close');
                document.getElementById('edit_fee').value = button.getAttribute('data-fee');
                document.getElementById('edit_latitude').value = button.getAttribute('data-latitude');
                document.getElementById('edit_longtitude').value = button.getAttribute('data-longtitude');

            });
        });
    </script>

</body>
<script src="Attraction.js"></script>


</html>