<?php
include "../config/database.php";

// ดึงประเภทที่พัก
$sql = "SELECT * FROM accommodation_categories";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$accommodation_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ดึงข้อมูลที่พักและห้อง (JOIN)
$sql = "SELECT 
            a.id AS accommodation_id,
            a.name AS accommodation_name,
            r.id AS room_id,
            r.room_name,
            r.capacity,
            r.price_per_night,
            r.description,
            r.amenities
        FROM accommodations a
        LEFT JOIN room_types r ON a.id = r.accommodation_id
        ORDER BY a.id";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มข้อมูลให้แยกตามที่พัก
$accommodations = [];
foreach ($rows as $row) {
    $id = $row['accommodation_id'];
    if (!isset($accommodations[$id])) {
        $accommodations[$id] = [
            'id' => $id,
            'name' => $row['accommodation_name'],
            'rooms' => []
        ];
    }
    if (!empty($row['room_id'])) {
        $accommodations[$id]['rooms'][] = [
            'id' => $row['room_id'],
            'name' => $row['room_name'],
            'capacity' => $row['capacity'],
            'price' => $row['price_per_night'],
            'description' => $row['description'],
            'amenities' => $row['amenities']
        ];
    }
}

$sql = "SELECT * FROM attractions ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$attractions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdminSite</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

</head>


<body style="font-family: 'Kanit', sans-serif;">


    <!-- 🟦 Modal เพิ่มห้อง -->
    <div class="modal fade" id="addRoomModal" tabindex="-1" aria-labelledby="addRoomModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="addRoomForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoomModalLabel">เพิ่มห้องพัก</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="accommodation_id" id="accommodation_id">

                        <div class="mb-3">
                            <label class="form-label">ชื่อห้องพัก</label>
                            <input type="text" name="room_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">จำนวนคน</label>
                            <input type="number" name="capacity" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">ราคา (บาท)</label>
                            <input type="number" name="price_per_night" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">คำอธิบาย</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">สิ่งอำนวยความสะดวก</label>
                            <input type="text" name="amenities" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                        <button type="submit" class="btn btn-primary">บันทึก</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <div class="mt-5 ms-5">
        <h1>Admin Site - จัดการข้อมูลที่พัก</h1>
    </div>

    <!-- ฟอร์มเพิ่มที่พัก -->
    <div class="card m-4 shadow">
        <div class="card-body">
            <h2>เพิ่มที่พักใหม่</h2>
            <form id="addAccommodationForm" enctype="multipart/form-data" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="name" class="form-control" placeholder="ชื่อที่พัก" required>
                </div>

                <div class="col-md-12">
                    <label>ประเภทที่พัก:</label><br>
                    <?php foreach ($accommodation_categories as $row): ?>
                        <label class="me-3">
                            <input type="radio" name="topics" value="<?= htmlspecialchars($row['id']) ?>">
                            <?= htmlspecialchars($row['type_name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="col-md-3">
                    <input type="number" step="any" name="latitude" class="form-control" placeholder="Latitude">
                </div>
                <div class="col-md-3">
                    <input type="number" step="any" name="longitude" class="form-control" placeholder="Longitude">
                </div>
                <div class="col-md-6">
                    <input type="text" name="address" class="form-control" placeholder="ที่อยู่">
                </div>
                <div class="col-md-3">
                    <input type="number" name="phone" class="form-control" placeholder="เบอร์โทร">
                </div>
                <div class="col-md-3">
                    <input type="number" step="any" name="rating" class="form-control" placeholder="คะแนน (1-5)">
                </div>

                <div class="col-md-12">
                    <button type="submit" name="Add_accommodation" class="btn btn-success">บันทึกข้อมูล</button>
                </div>
                <div class="col-md-4">
                    <label class="form-label">อัปโหลดรูปภาพ</label>
                    <input type="file" name="image" accept="image/*" class="form-control">
                </div>

            </form>
        </div>
    </div>

    <!-- รายการที่พัก + ห้อง -->
    <div class="container mt-5 mb-5">
        <h2>รายการที่พักและห้องพักทั้งหมด</h2>

        <div class="container mt-5 mb-5">
            <h2>รายการที่พักและห้องพักทั้งหมด</h2>

            <div class="row">
                <?php foreach ($accommodations as $accom): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-primary text-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?= htmlspecialchars($accom['name']); ?></span>
                                    <button class="btn btn-light btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#addRoomModal"
                                        data-accom-id="<?= $accom['id']; ?>"
                                        data-accom-name="<?= htmlspecialchars($accom['name']); ?>">
                                        <i class="fa fa-plus"></i> เพิ่มห้อง
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($accom['rooms'])): ?>
                                    <?php foreach ($accom['rooms'] as $room): ?>
                                        <div class="border-bottom mb-2 pb-1">
                                            <strong><?= htmlspecialchars($room['name']); ?></strong><br>
                                            <small>คน: <?= htmlspecialchars($room['capacity']); ?> | ราคา: ฿<?= htmlspecialchars($room['price']); ?></small><br>
                                            <small><?= htmlspecialchars($room['description']); ?></small><br>
                                            <small class="text-muted"><?= htmlspecialchars($room['amenities']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted mb-0">ยังไม่มีห้องพัก</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 🟨 JS สำหรับตั้งค่า accommodation_id ตอนกดปุ่ม -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const addRoomModal = document.getElementById('addRoomModal');
                addRoomModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const accomId = button.getAttribute('data-accom-id');
                    const accomName = button.getAttribute('data-accom-name');

                    addRoomModal.querySelector('#accommodation_id').value = accomId;
                    addRoomModal.querySelector('.modal-title').textContent = 'เพิ่มห้องให้กับ: ' + accomName;
                });
            });
        </script>

        <!-- Bootstrap JS (ต้องมี Popper ด้วยสำหรับ Modal ทำงานได้) -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

<script>
    document.getElementById("addAccommodationForm").addEventListener("submit", async function(e) {
        e.preventDefault(); // ❌ ห้ามส่งฟอร์มแบบปกติ

        const form = e.target;
        const formData = new FormData(form);
        formData.append("action", "add_accommodation"); // 🟢 บอก API ว่าทำอะไร

        try {
            const response = await fetch("api.php", {
                method: "POST",
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert(result.message);
                form.reset(); // ล้างฟอร์ม
            } else {
                alert("เกิดข้อผิดพลาด: " + result.message);
            }
        } catch (error) {
            alert("❌ ไม่สามารถเชื่อมต่อ API ได้");
            console.error(error);
        }
    });

    document.getElementById("addRoomForm").addEventListener("submit", async function(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        formData.append("action", "add_room");

        try {
            const response = await fetch("api.php", {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                alert(result.message);

                // ✅ แทรกห้องใหม่เข้า card ทันที โดยไม่โหลดหน้า
                const accomId = form.querySelector("#accommodation_id").value;
                const roomName = form.querySelector("[name='room_name']").value;
                const capacity = form.querySelector("[name='capacity']").value;
                const price = form.querySelector("[name='price_per_night']").value;
                const description = form.querySelector("[name='description']").value;
                const amenities = form.querySelector("[name='amenities']").value;

                const cardBody = document.querySelector(`[data-accom-id="${accomId}"]`)
                    ?.closest(".card")
                    ?.querySelector(".card-body");

                if (cardBody) {
                    const roomDiv = document.createElement("div");
                    roomDiv.className = "border-bottom mb-2 pb-1";
                    roomDiv.innerHTML = `
                    <strong>${roomName}</strong><br>
                    <small>คน: ${capacity} | ราคา: ฿${price}</small><br>
                    <small>${description}</small><br>
                    <small class="text-muted">${amenities}</small>
                `;
                    // ลบข้อความ “ยังไม่มีห้องพัก” ถ้ามี
                    const emptyMsg = cardBody.querySelector(".text-muted.mb-0");
                    if (emptyMsg) emptyMsg.remove();

                    cardBody.appendChild(roomDiv);
                }

                form.reset();
                bootstrap.Modal.getInstance(document.getElementById("addRoomModal")).hide();

            } else {
                alert("เกิดข้อผิดพลาด: " + result.message);
            }
        } catch (error) {
            alert("❌ ไม่สามารถเชื่อมต่อ API ได้");
            console.error(error);
        }
    });
</script>


</html>